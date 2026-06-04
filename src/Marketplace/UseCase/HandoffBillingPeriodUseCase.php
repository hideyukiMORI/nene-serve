<?php

declare(strict_types=1);

namespace NeneServe\Marketplace\UseCase;

use NeneServe\Audit\AuditLogInterface;
use NeneServe\Marketplace\AdvertiserRepositoryInterface;
use NeneServe\Marketplace\BillingPeriodRepositoryInterface;
use NeneServe\Marketplace\CampaignRepositoryInterface;
use NeneServe\Marketplace\Invoice\InvoiceClientException;
use NeneServe\Marketplace\Invoice\InvoiceClientInterface;
use NeneServe\Marketplace\InvoiceHandoff;
use NeneServe\Marketplace\InvoiceHandoffRepositoryInterface;
use NeneServe\Marketplace\PricingRuleRepositoryInterface;
use NeneServe\Marketplace\SpendCalculator;
use NeneServe\Marketplace\SpendSnapshot;
use NeneServe\Marketplace\SpendSnapshotHasher;
use NeneServe\Marketplace\SpendSnapshotRepositoryInterface;
use NeneServe\Support\TransactionManagerInterface;
use NeneServe\Tenant\AuthContext;

/**
 * Reconciles a closed billing period's snapshot and hands the **net** amount to
 * NeNe Invoice (handoff contract, billing §3.4/§3.5).
 *
 * - **Reconcile first:** verify the snapshot hash and that amount = units × rate
 *   (versioned). Any discrepancy is recorded + audited and the handoff is refused
 *   — never absorbed.
 * - **Idempotent on external_reference:** a re-run returns the existing handoff
 *   and never posts a second charge.
 * - **Failure-isolated:** an Invoice transport error records a `failed` handoff
 *   and throws; serving is not paused and the call can be retried.
 */
final class HandoffBillingPeriodUseCase
{
    public function __construct(
        private readonly BillingPeriodRepositoryInterface $periods,
        private readonly CampaignRepositoryInterface $campaigns,
        private readonly AdvertiserRepositoryInterface $advertisers,
        private readonly SpendSnapshotRepositoryInterface $snapshots,
        private readonly PricingRuleRepositoryInterface $pricingRules,
        private readonly InvoiceHandoffRepositoryInterface $handoffs,
        private readonly InvoiceClientInterface $invoice,
        private readonly AuditLogInterface $audit,
        private readonly TransactionManagerInterface $tx,
    ) {
    }

    public function execute(AuthContext $actor, string $periodId): InvoiceHandoff
    {
        $period = $this->periods->findByIdInOrganization($periodId, $actor->organizationId);
        if ($period === null) {
            throw new BillingPeriodNotFoundException();
        }
        if ($period->isOpen()) {
            throw new InvalidPeriodTransitionException('Close the billing period before handoff.');
        }

        $snapshot = $this->snapshots->latestForPeriod($actor->organizationId, $period->id);
        if ($snapshot === null) {
            throw new InvalidPeriodTransitionException('No spend snapshot to hand off; close the period first.');
        }

        $externalReference = sprintf('ho:%s:%s:v%d', $actor->organizationId, $period->id, $snapshot->version);

        // Idempotency: a completed handoff is returned as-is — no second charge.
        $existing = $this->handoffs->findByExternalReference($actor->organizationId, $externalReference);
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
            $this->tx->transactional(function () use ($failed, $actor): void {
                $this->handoffs->save($failed);
                $this->audit->record(
                    $actor->organizationId,
                    $actor->userId,
                    'invoice.handoff_failed',
                    'billing_period',
                    $failed->billingPeriodId,
                    ['external_reference' => $failed->externalReference, 'amount_cents' => $failed->amountCents],
                );
            });

            throw new HandoffFailedException('Invoice handoff failed: ' . $e->getMessage(), 0, $e);
        }

        $completed = $pending->withResult('handed_off', $result->invoicePaymentId);
        $handedOffPeriod = $period->withStatus('handed_off');

        return $this->tx->transactional(function () use ($completed, $handedOffPeriod, $actor, $snapshot): InvoiceHandoff {
            $this->handoffs->save($completed);
            $this->periods->save($handedOffPeriod);
            $this->audit->record(
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
            $this->audit->record(
                $actor->organizationId,
                $actor->userId,
                'invoice.handed_off',
                'billing_period',
                $completed->billingPeriodId,
                ['external_reference' => $completed->externalReference, 'invoice_payment_id' => $completed->invoicePaymentId, 'amount_cents' => $completed->amountCents],
            );

            return $completed;
        });
    }

    /**
     * Snapshot hash must verify and amount must reproduce as units × versioned
     * rate; otherwise record + audit the discrepancy and refuse the handoff.
     */
    private function reconcile(AuthContext $actor, string $periodId, SpendSnapshot $snapshot, string $externalReference): void
    {
        $rule = $this->pricingRules->findByIdInOrganization($snapshot->pricingRuleId, $actor->organizationId);
        $recomputed = $rule === null
            ? null
            : SpendCalculator::compute($rule->model, $rule->rateCents, $snapshot->billableImpressions, $snapshot->billableClicks);

        if (!SpendSnapshotHasher::verify($snapshot) || $recomputed === null || $recomputed !== $snapshot->spentCents) {
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
            $this->tx->transactional(function () use ($discrepancy, $actor, $recomputed): void {
                $this->handoffs->save($discrepancy);
                $this->audit->record(
                    $actor->organizationId,
                    $actor->userId,
                    'invoice.reconciliation_discrepancy',
                    'billing_period',
                    $discrepancy->billingPeriodId,
                    ['external_reference' => $discrepancy->externalReference, 'snapshot_spent_cents' => $discrepancy->amountCents, 'recomputed_cents' => $recomputed],
                );
            });

            throw new ReconciliationFailedException('Snapshot did not reconcile; handoff refused.');
        }
    }

    private function resolveInvoiceClientId(AuthContext $actor, string $campaignId): ?string
    {
        $campaign = $this->campaigns->findByIdInOrganization($campaignId, $actor->organizationId);
        if ($campaign === null) {
            return null;
        }
        $advertiser = $this->advertisers->findByIdInOrganization($campaign->advertiserId, $actor->organizationId);

        return $advertiser?->invoiceClientId;
    }
}
