<?php

namespace Modules\Membership\Classes\DTOs;

use Modules\Membership\Http\Requests\UpdateMembershipRequest;

class UpdateMembershipData
{
    public function __construct(
        public readonly ?string $role,
    ) {}

    public static function fromRequest(UpdateMembershipRequest $request): self
    {
        return new self(
            role: $request->validated('role'),
        );
    }
}
