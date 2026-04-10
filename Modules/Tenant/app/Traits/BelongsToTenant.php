<?php

namespace Modules\Tenant\Traits;

use LogicException;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model): void {
            $currentTenantId = app()->has('tenant') ? (int) app('tenant')->id : null;

            if ($currentTenantId !== null) {
                $incomingTenantId = $model->getAttribute('tenant_id');

                if ($incomingTenantId !== null && (int) $incomingTenantId !== $currentTenantId) {
                    throw new LogicException('Cross-tenant assignment is not allowed.');
                }

                $model->setAttribute('tenant_id', $currentTenantId);

                return;
            }

            if (! app()->runningInConsole() && ! app()->runningUnitTests()) {
                throw new LogicException('Tenant context is required for tenant-scoped model creation.');
            }
        });

        static::saving(function ($model): void {
            if (! $model->exists || ! $model->isDirty('tenant_id')) {
                return;
            }

            throw new LogicException('tenant_id is immutable.');
        });
    }
}
