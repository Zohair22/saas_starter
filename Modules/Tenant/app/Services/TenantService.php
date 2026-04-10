<?php

namespace Modules\Tenant\Services;

use Modules\Tenant\Classes\DTOs\CreateTenantData;
use Modules\Tenant\Interfaces\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Interfaces\Contracts\TenantServiceInterface;
use Modules\Tenant\Models\Tenants;

class TenantService implements TenantServiceInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    public function create(CreateTenantData $data): Tenants
    {
        return $this->tenantRepository->create($data);
    }
}
