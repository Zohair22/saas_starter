<?php

namespace Modules\Tenant\Interfaces\Contracts;

use Modules\Tenant\Classes\DTOs\CreateTenantData;
use Modules\Tenant\Models\Tenants;

interface TenantRepositoryInterface
{
    public function create(CreateTenantData $data): Tenants;
}
