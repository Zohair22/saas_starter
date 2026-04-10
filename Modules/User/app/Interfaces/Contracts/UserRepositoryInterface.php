<?php

namespace Modules\User\Interfaces\Contracts;

use Modules\User\Classes\DTOs\CreateUserData;
use Modules\User\Models\User;

interface UserRepositoryInterface
{
    public function create(CreateUserData $data): User;
}
