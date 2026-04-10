<?php

namespace Modules\User\Classes\DTOs;

use Modules\User\Http\Requests\RegisterUserRequest;

class CreateUserData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function fromRequest(RegisterUserRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }
}
