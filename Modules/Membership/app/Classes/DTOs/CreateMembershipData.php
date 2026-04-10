<?php

namespace Modules\Membership\Classes\DTOs;

use Modules\Membership\Http\Requests\StoreMembershipRequest;

class CreateMembershipData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly string $role,
    ) {}

    public static function fromRequest(StoreMembershipRequest $request): self
    {
        return new self(
            tenantId: (int) data_get($request->attributes->get('tenant'), 'id'),
            userId: (int) $request->validated('user_id'),
            role: $request->validated('role'),
        );
    }
}
