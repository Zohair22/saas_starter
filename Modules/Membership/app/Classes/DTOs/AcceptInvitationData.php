<?php

namespace Modules\Membership\Classes\DTOs;

use Modules\Membership\Http\Requests\AcceptInvitationRequest;
use Modules\User\Models\User;

class AcceptInvitationData
{
    public function __construct(
        public readonly ?User $user,
        public readonly ?string $name,
        public readonly ?string $password,
    ) {}

    public static function fromRequest(AcceptInvitationRequest $request): self
    {
        return new self(
            user: $request->user(),
            name: $request->validated('name'),
            password: $request->validated('password'),
        );
    }
}
