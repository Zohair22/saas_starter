<?php

namespace Tests\Unit\Services;

use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Membership\Classes\DTOs\CreateMembershipData;
use Modules\Membership\Classes\DTOs\UpdateMembershipData;
use Modules\Membership\Interfaces\Contracts\MembershipRepositoryInterface;
use Modules\Membership\Models\Membership;
use Modules\Membership\Services\MembershipService;
use PHPUnit\Framework\TestCase;

class MembershipServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MembershipRepositoryInterface $repository;

    private MembershipService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(MembershipRepositoryInterface::class);
        $this->service = new MembershipService($this->repository);
    }

    public function test_list_for_tenant_delegates_to_repository(): void
    {
        $collection = new Collection;
        $this->repository->shouldReceive('listForTenant')->with(3)->andReturn($collection);

        $result = $this->service->listForTenant(3);

        $this->assertSame($collection, $result);
    }

    public function test_create_delegates_to_repository(): void
    {
        $membership = Mockery::mock(Membership::class);
        $data = new CreateMembershipData(tenantId: 1, userId: 2, role: 'member');

        $this->repository->shouldReceive('create')->with($data)->andReturn($membership);

        $result = $this->service->create($data);

        $this->assertSame($membership, $result);
    }

    public function test_update_delegates_to_repository(): void
    {
        $membership = Mockery::mock(Membership::class);
        $updated = Mockery::mock(Membership::class);
        $data = new UpdateMembershipData(role: 'admin');

        $this->repository->shouldReceive('update')->with($membership, $data)->andReturn($updated);

        $result = $this->service->update($membership, $data);

        $this->assertSame($updated, $result);
    }

    public function test_delete_delegates_to_repository(): void
    {
        $membership = Mockery::mock(Membership::class);
        $this->repository->shouldReceive('delete')->with($membership)->once();

        $this->service->delete($membership);
    }
}
