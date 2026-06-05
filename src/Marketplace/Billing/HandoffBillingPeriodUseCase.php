<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\Billing;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
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
use NeneServe\Tenant\AuthContext;

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
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DatabaseTransactionManagerInterface $transactions,
        private InvoiceClientInterface $invoice,
    ) {
    }

    public function execute(AuthContext $actor, string $periodId): InvoiceHandoff
    {
        $period = (new PdoBillingPeriodRepository($this->query))->findByIdInOrganization($periodId, $actor->organizationId);

        if ($period === null) {
            throw new BillingPeriodNotFoundException();
        }

        if ($period->isOpen()) {
            throw new InvalidPeriodTransitionException('Close the billing period before handoff.');
        }

        $snapshot = (new PdoSpendSnapshotRepository($this->query))->latestForPeriod($actor->organizationId, $period->id);

        if ($snapshot === null) {
            throw new InvalidPeriodTransitionException('No spend snapshot to hand off; close the period first.');
        }

        $externalReference = sprintf('ho:%s:%s:v%d', $actor->organizationId, $period->id, $snapshot->version);

        // Idempotency: a completed handoff is returned as-is — no second charge.
        $existing = (new PdoInvoiceHandoffRepository($this->query))->findByExternalReference($actor->organizationId, $externalReference);

        if ($existing !== null && $existing->status === 'handed_off') {
            return $existing;
        }

        $this->reconcile($actor, $period->id, $snapshot, $externalReference);

        $invoiceClientId = $this->resolveInvoiceClientId($actor, $period->campaignId);

        $pending = new InvoiceHandoff(
            'ho-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            $period->id,
            $externalReference,
            $snapshot->billableImpressions,
            $snapshot->billableClicks,
            $snapshot->pricingRuleVersion,
            $snapshot->spentCents,
            'reconciled',
            'pending',
            null,
            gmdate('c'),
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
                static function (DatabaseQueryExecutorInterface $tx) use ($failed, $actor): void {
                    (new PdoInvoiceHandoffRepository($tx))->save($failed);
                    (new PdoAuditLog($tx))->record(
                        $actor->organizationId,
                        $actor->userId,
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

        return $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($completed, $handedOffPeriod, $actor, $snapshot): InvoiceHandoff {
                (new PdoInvoiceHandoffRepository($tx))->save($completed);
                (new PdoBillingPeriodRepository($tx))->save($handedOffPeriod);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
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
                    $actor->organizationId,
                    $actor->userId,
                    'invoice.handed_off',
                    'billing_period',
                    $completed->billingPeriodId,
                    ['external_reference' => $completed->externalReference, 'invoice_payment_id' => $completed->invoicePaymentId, 'amount_cents' => $completed->amountCents],
                );

                return $completed;
            },
        );
    }

    /**
     * Snapshot hash must verify and amount must reproduce as units × versioned
     * rate; otherwise record + audit the discrepancy and refuse the handoff.
     */
    private function reconcile(AuthContext $actor, string $periodId, SpendSnapshot $snapshot, string $externalReference): void
    {
        $rule = (new PdoPricingRuleRepository($this->query))->findByIdInOrganization($snapshot->pricingRuleId, $actor->organizationId);
        $recomputed = $rule === null
            ? null
            : SpendCalculator::compute($rule->model, $rule->rateCents, $snapshot->billableImpressions, $snapshot->billableClicks);

        if (SpendSnapshotHasher::verify($snapshot) && $recomputed !== null && $recomputed === $snapshot->spentCents) {
            return;
        }

        $discrepancy = new InvoiceHandoff(
            'ho-' . bin2hex(random_bytes(8)),
            $actor->organizationId,
            $periodId,
            $externalReference,
            $snapshot->billableImpressions,
            $snapshot->billableClicks,
            $snapshot->pricingRuleVersion,
            $snapshot->spentCents,
            'discrepancy',
            'failed',
            null,
            gmdate('c'),
        );
        $this->transactions->transactional(
            static function (DatabaseQueryExecutorInterface $tx) use ($discrepancy, $actor, $recomputed): void {
                (new PdoInvoiceHandoffRepository($tx))->save($discrepancy);
                (new PdoAuditLog($tx))->record(
                    $actor->organizationId,
                    $actor->userId,
                    'invoice.reconciliation_discrepancy',
                    'billing_period',
                    $discrepancy->billingPeriodId,
                    ['external_reference' => $discrepancy->externalReference, 'snapshot_spent_cents' => $discrepancy->amountCents, 'recomputed_cents' => $recomputed],
                );
            },
        );

        throw new ReconciliationFailedException('Snapshot did not reconcile; handoff refused.');
    }

    private function resolveInvoiceClientId(AuthContext $actor, string $campaignId): ?string
    {
        $campaign = (new PdoCampaignRepository($this->query))->findByIdInOrganization($campaignId, $actor->organizationId);

        if ($campaign === null) {
            return null;
        }

        $advertiser = (new PdoAdvertiserRepository($this->query))->findByIdInOrganization($campaign->advertiserId, $actor->organizationId);

        return $advertiser?->invoiceClientId;
    }
}
