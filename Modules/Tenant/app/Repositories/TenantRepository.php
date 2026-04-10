<?php

namespace Modules\Tenant\Repositories;

use Modules\Tenant\Classes\DTOs\CreateTenantData;
use Modules\Tenant\Interfaces\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Models\Tenants;

class TenantRepository implements TenantRepositoryInterface
{
    public function create(CreateTenantData $data): Tenants
    {
        return Tenants::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'owner_id' => $data->ownerId,
        ]);
    }
}
