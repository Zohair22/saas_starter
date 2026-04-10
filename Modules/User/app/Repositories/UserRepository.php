<?php

namespace Modules\User\Repositories;

use Modules\User\Classes\DTOs\CreateUserData;
use Modules\User\Interfaces\Contracts\UserRepositoryInterface;
use Modules\User\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function create(CreateUserData $data): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]);
    }
}
