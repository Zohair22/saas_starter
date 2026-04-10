<?php

namespace Modules\User\Interfaces\Contracts;

use Modules\User\Classes\DTOs\CreateUserData;
use Modules\User\Models\User;

interface UserServiceInterface
{
    public function create(CreateUserData $data): User;
}
