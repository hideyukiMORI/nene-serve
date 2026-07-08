<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;
use NeneServe\Audit\PdoAuditLog;
use NeneServe\Marketplace\Invoice\InvoiceClientException;
use NeneServe\Marketplace\Invoice\InvoiceClientInterface;
use NeneServe\Marketplace\InvoiceHandoff;
use NeneServe\Marketplace\PdoAdvertiserRepository;
use NeneServe\Marketplace\PdoBillingPeriodRepository;
use NeneServe\Marketplace\PdoCampaignRepository;
use NeneServe\Marketplace\PdoInvoiceHandoffRepository;
use NeneServe\Marketplace\PdoPricingRuleRepository;
use NeneServe\Marketplace\PdoSpendSnapshotRepository;
use NeneServe\Marketplace\SpendCalculator;
use NeneServe\Marketplace\SpendSnapshot;
use NeneServe\Marketplace\SpendSnapshotHasher;
use NeneServe\Marketplace\UseCase\BillingPeriodNotFoundException;
use NeneServe\Marketplace\UseCase\HandoffFailedException;
use NeneServe\Marketplace\UseCase\InvalidPeriodTransitionException;
use NeneServe\Marketplace\UseCase\ReconciliationFailedException;
use NeneServe\Support\Id;
use NeneServe\Support\SqlDialect;

/**
 * Reconciles a closed billing period's snapshot and hands the **net** amount to
 * NeNe Invoice (handoff contract, billing §3.4/§3.5; ADR 0014).
 *
 * - **Reconcile first:** verify the snapshot hash and that amount = units ×
 *   versioned rate. Any discrepancy is recorded + audited and the handoff is
 *   refused — never absorbed.
 * - **Idempotent on external_reference:** a re-run returns the existing handoff
 *   and never posts a second charge.
 * - **Failure-isolated:** an Invoice transport error records a `failed` handoff
 *   and throws; serving is not paused and the call can be retried.
 */
final readonly class HandoffBillingPeriodUseCase implements HandoffBillingPeriodUseCaseInterface
{
    /**
     * @param RequestScopedHolder<string> $organizationId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
        private InvoiceClientInterface $invoice,
        private RequestScopedHolder $organizationId,
        private ClockInterface $clock,
        private SqlDialect $dialect = SqlDialect::Mysql,
    ) {
    }

    public function execute(HandoffBillingPeriodInput $input): HandoffBillingPeriodOutput
    {
        $organizationId = $this->organizationId->get();
        $actorUserId = $input->actorUserId;
        $dialect = $this->dialect;

        $period = (new PdoBillingPeriodRepository($this->query))->findByIdInOrganization($input->periodId, $organizationId);

        if ($period === null) {
            throw new BillingPeriodNotFoundException();
        }

        if ($period->isOpen()) {
            throw new InvalidPeriodTransitionException('Close the billing period before handoff.');
        }

        $snapshot = (new PdoSpendSnapshotRepository($this->query))->latestForPeriod($organizationId, $period->id);

        if ($snapshot === null) {
            throw new InvalidPeriodTransitionException('No spend snapshot to hand off; close the period first.');
        }

        $externalReference = sprintf('ho:%s:%s:v%d', $organizationId, $period->id, $snapshot->version);

        // Idempotency: a completed handoff is returned as-is — no second charge.
        $existing = (new PdoInvoiceHandoffRepository($this->query))->findByExternalReference($organizationId, $externalReference);

        if ($existing !== null && $existing->status === 'handed_off') {
            return new HandoffBillingPeriodOutput($existing);
        }

        $this->reconcile($organizationId, $actorUserId, $period->id, $snapshot, $externalReference);

        $invoiceClientId = $this->resolveInvoiceClientId($organizationId, $period->campaignId);

        $pending = new InvoiceHandoff(
            Id::generate('ho'),
            $organizationId,
            $period->id,
            $externalReference,
            $snapshot->billableImpressions,
            $snapshot->billableClicks,
            $snapshot->pricingRuleVersion,
            $snapshot->spentCents,
            'reconciled',
            'pending',
            null,
            $this->clock->now()->format('c'),
        );

        try {
            $result = $this->invoice->postCharge(
                $externalReference,
                $invoiceClientId,
                $snapshot->spentCents, // net, no tax
                $period->periodStart,
                $period->periodEnd,
            );
        } catch (InvoiceClientException $e) {
            // Failure isolation: record failed, do not pause serving, allow retry.
            $failed = $pending->withResult('failed', null);
            $this->transactions->transactional(
                static function (DatabaseQueryExecutorInterface $tx) use ($failed, $organizationId, $actorUserId, $dialect): void {
                    (new PdoInvoiceHandoffRepository($tx, $dialect))->save($failed);
                    (new PdoAuditLog($tx))->record(
                        $organizationId,
                        $actorUserId,
                        'invoice.handoff_failed',
                        'billing_period',
                        $failed->billingPeriodId,
                        ['external_reference' => $failed->externalReference, 'amount_cents' => $failed->amountCents],
                    );
                },
            );

            throw new HandoffFailedException('Invoice handoff failed: ' . $e->getMessage(), 0, $e);
        }

        $completed = $pending->withResult('handed_off', $result->invoicePaymentId);
        $handedOffPeriod = $period->withStatus('handed_off');

        $stored = $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($completed, $handedOffPeriod, $organizationId, $actorUserId, $snapshot, $dialect): InvoiceHandoff {
                (new PdoInvoiceHandoffRepository($tx, $dialect))->save($completed);
                (new PdoBillingPeriodRepository($tx, $dialect))->save($handedOffPeriod);
                (new PdoAuditLog($tx))->record(
                    $organizationId,
                    $actorUserId,
                    'invoice.reconciled',
                    'billing_period',
                    $completed->billingPeriodId,
                    [
                        'external_reference' => $completed->externalReference,
                        'billable_impressions' => $snapshot->billableImpressions,
                        'billable_clicks' => $snapshot->billableClicks,
                        'amount_cents' => $completed->amountCents,
                    ],
                );
                (new PdoAuditLog($tx))->record(
                    $organizationId,
                    $actorUserId,
                    'invoice.handed_off',
                    'billing_period',
                    $completed->billingPeriodId,
                    ['external_reference' => $completed->externalReference, 'invoice_payment_id' => $completed->invoicePaymentId, 'amount_cents' => $completed->amountCents],
                );

                return $completed;
            },
        );

        return new HandoffBillingPeriodOutput($stored);
    }

    /**
     * Snapshot hash must verify and amount must reproduce as units × versioned
     * rate; otherwise record + audit the discrepancy and refuse the handoff.
     */
    private function reconcile(string $organizationId, string $actorUserId, string $periodId, SpendSnapshot $snapshot, string $externalReference): void
    {
        $rule = (new PdoPricingRuleRepository($this->query))->findByIdInOrganization($snapshot->pricingRuleId, $organizationId);
        $recomputed = $rule === null
            ? null
            : SpendCalculator::compute($rule->model, $rule->rateCents, $snapshot->billableImpressions, $snapshot->billableClicks);

        if (SpendSnapshotHasher::verify($snapshot) && $recomputed !== null && $recomputed === $snapshot->spentCents) {
            return;
        }

        $discrepancy = new InvoiceHandoff(
            Id::generate('ho'),
            $organizationId,
            $periodId,
            $externalReference,
            $snapshot->billableImpressions,
            $snapshot->billableClicks,
            $snapshot->pricingRuleVersion,
            $snapshot->spentCents,
            'discrepancy',
            'failed',
            null,
            $this->clock->now()->format('c'),
        );
        $dialect = $this->dialect;
        $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($discrepancy, $organizationId, $actorUserId, $recomputed, $dialect): void {
                (new PdoInvoiceHandoffRepository($tx, $dialect))->save($discrepancy);
                (new PdoAuditLog($tx))->record(
                    $organizationId,
                    $actorUserId,
                    'invoice.reconciliation_discrepancy',
                    'billing_period',
                    $discrepancy->billingPeriodId,
                    ['external_reference' => $discrepancy->externalReference, 'snapshot_spent_cents' => $discrepancy->amountCents, 'recomputed_cents' => $recomputed],
                );
            },
        );

        throw new ReconciliationFailedException('Snapshot did not reconcile; handoff refused.');
    }

    private function resolveInvoiceClientId(string $organizationId, string $campaignId): ?string
    {
        $campaign = (new PdoCampaignRepository($this->query))->findByIdInOrganization($campaignId, $organizationId);

        if ($campaign === null) {
            return null;
        }

        $advertiser = (new PdoAdvertiserRepository($this->query))->findByIdInOrganization($campaign->advertiserId, $organizationId);

        return $advertiser?->invoiceClientId;
    }
}
