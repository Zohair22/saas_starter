<?php

namespace Modules\Tenant\Interfaces\Contracts;

use Modules\Tenant\Classes\DTOs\CreateTenantData;
use Modules\Tenant\Models\Tenants;

interface TenantServiceInterface
{
    public function create(CreateTenantData $data): Tenants;
}
