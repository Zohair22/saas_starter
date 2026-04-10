<?php

namespace Modules\AuditLog\Listeners;

use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Interfaces\Contracts\AuditLogServiceInterface;
use Modules\Billing\Events\BillingSubscriptionCanceled;
use Modules\Tenant\Models\Tenants;

class LogBillingSubscriptionCanceledAudit
{
    public function __construct(
        private readonly AuditLogServiceInterface $auditLogService,
    ) {}

    public function handle(BillingSubscriptionCanceled $event): void
    {
        $tenant = $this->resolveTenant($event->payload);

        if (! $tenant) {
            return;
        }

        $this->auditLogService->record(
            action: AuditAction::BillingSubscriptionCanceled,
            tenantId: (int) $tenant->id,
            newValues: [
                'event_id' => (string) data_get($event->payload, 'id'),
                'type' => (string) data_get($event->payload, 'type'),
            ],
        );
    }

    private function resolveTenant(array $payload): ?Tenants
    {
        $customerId = (string) data_get($payload, 'data.object.customer');

        if ($customerId === '') {
            return null;
        }

        return Tenants::query()->where('stripe_id', $customerId)->first();
    }
}
