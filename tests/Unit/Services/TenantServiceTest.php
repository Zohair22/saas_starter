<?php

namespace Tests\Unit\Services;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Tenant\Classes\DTOs\CreateTenantData;
use Modules\Tenant\Interfaces\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Models\Tenants;
use Modules\Tenant\Services\TenantService;
use PHPUnit\Framework\TestCase;

class TenantServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_create_delegates_to_repository_and_returns_tenant(): void
    {
        $tenant = Mockery::mock(Tenants::class);
        $data = new CreateTenantData(name: 'Acme', slug: 'acme', ownerId: 1);

        $repository = Mockery::mock(TenantRepositoryInterface::class);
        $repository->shouldReceive('create')->with($data)->andReturn($tenant);

        $service = new TenantService($repository);
        $result = $service->create($data);

        $this->assertSame($tenant, $result);
    }
}
