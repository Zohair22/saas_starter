<?php

namespace Modules\User\Services;

use Modules\User\Classes\DTOs\CreateUserData;
use Modules\User\Interfaces\Contracts\UserRepositoryInterface;
use Modules\User\Interfaces\Contracts\UserServiceInterface;
use Modules\User\Models\User;

class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function create(CreateUserData $data): User
    {
        return $this->userRepository->create($data);
    }
}
