<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Modules\Membership\Interfaces\Contracts\MembershipServiceInterface;
use Modules\Tenant\Classes\DTOs\CreateTenantData;
use Modules\Tenant\Interfaces\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Models\Tenants;
use Modules\Tenant\Services\TenantService;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    public function test_create_creates_owner_membership_and_returns_tenant(): void
    {
        $tenant = new Tenants;
        $tenant->id = 99;
        $data = new CreateTenantData(name: 'Acme', slug: 'acme', ownerId: 1);

        /** @var TenantRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(TenantRepositoryInterface::class);
        $repository->shouldReceive('create')->once()->with($data)->andReturn($tenant);

        /** @var MembershipServiceInterface&MockInterface $membershipService */
        $membershipService = Mockery::mock(MembershipServiceInterface::class);
        $membershipService
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($membershipData) use ($data, $tenant): bool {
                return $membershipData->tenantId === $tenant->id
                    && $membershipData->userId === $data->ownerId
                    && $membershipData->role === 'owner';
            }));

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $service = new TenantService($repository, $membershipService);
        $result = $service->create($data);

        $this->assertSame($tenant, $result);
    }
}
