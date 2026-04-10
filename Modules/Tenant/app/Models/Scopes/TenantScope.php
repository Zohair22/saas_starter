<?php

namespace Modules\Tenant\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = request()->attributes->get('tenant');
        $tenantId = data_get($tenant, 'id');

        if ($tenantId === null) {
            if (app()->runningInConsole()) {
                return;
            }

            // Fail closed in HTTP contexts if tenant resolution is missing.
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where('tenant_id', $tenantId);
    }
}
