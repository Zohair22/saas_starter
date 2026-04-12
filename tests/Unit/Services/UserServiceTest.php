<?php

namespace Tests\Unit\Services;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\User\Classes\DTOs\CreateUserData;
use Modules\User\Interfaces\Contracts\UserRepositoryInterface;
use Modules\User\Models\User;
use Modules\User\Services\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_create_delegates_to_repository_and_returns_user(): void
    {
        $user = Mockery::mock(User::class);
        $data = new CreateUserData(name: 'Jane', email: 'jane@example.com', password: 'hashed');

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('create')->with($data)->andReturn($user);

        $service = new UserService($repository);
        $result = $service->create($data);

        $this->assertSame($user, $result);
    }
}
