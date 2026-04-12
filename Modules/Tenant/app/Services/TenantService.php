<?php

namespace Modules\Tenant\Services;

use Illuminate\Support\Facades\DB;
use Modules\Membership\Classes\DTOs\CreateMembershipData;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Interfaces\Contracts\MembershipServiceInterface;
use Modules\Tenant\Classes\DTOs\CreateTenantData;
use Modules\Tenant\Interfaces\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Interfaces\Contracts\TenantServiceInterface;
use Modules\Tenant\Models\Tenants;

class TenantService implements TenantServiceInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly MembershipServiceInterface $membershipService,
    ) {}

    public function create(CreateTenantData $data): Tenants
    {
        return DB::transaction(function () use ($data): Tenants {
            $tenant = $this->tenantRepository->create($data);
            $previousTenant = app()->bound('tenant') ? app('tenant') : null;

            app()->instance('tenant', $tenant);

            try {
                $this->membershipService->create(new CreateMembershipData(
                    tenantId: (int) $tenant->id,
                    userId: $data->ownerId,
                    role: MembershipRole::Owner->value,
                ));
            } finally {
                if ($previousTenant !== null) {
                    app()->instance('tenant', $previousTenant);
                } else {
                    app()->forgetInstance('tenant');
                }
            }

            return $tenant;
        });
    }
}
