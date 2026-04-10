<?php

namespace Modules\Membership\Classes\DTOs;

use Carbon\CarbonImmutable;
use Modules\Membership\Http\Requests\StoreInvitationRequest;

class CreateInvitationData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $email,
        public readonly string $role,
        public readonly ?int $invitedBy,
        public readonly CarbonImmutable $expiresAt,
    ) {}

    public static function fromRequest(StoreInvitationRequest $request): self
    {
        $expiresAt = $request->validated('expires_at');

        return new self(
            tenantId: (int) data_get($request->attributes->get('tenant'), 'id'),
            email: mb_strtolower((string) $request->validated('email')),
            role: (string) $request->validated('role'),
            invitedBy: $request->user()?->id,
            expiresAt: $expiresAt ? CarbonImmutable::parse($expiresAt) : now()->addDays(7)->toImmutable(),
        );
    }
}
