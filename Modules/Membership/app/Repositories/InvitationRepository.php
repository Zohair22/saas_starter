<?php

namespace Modules\Membership\Repositories;

use Modules\Membership\Classes\DTOs\CreateInvitationData;
use Modules\Membership\Interfaces\Contracts\InvitationRepositoryInterface;
use Modules\Membership\Models\Invitation;
use Modules\Membership\Models\Membership;
use Modules\User\Models\User;

class InvitationRepository implements InvitationRepositoryInterface
{
    public function findActiveByTenantAndEmail(int $tenantId, string $email): ?Invitation
    {
        return Invitation::query()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function create(CreateInvitationData $data, string $token): Invitation
    {
        return Invitation::query()->create([
            'email' => $data->email,
            'role' => $data->role,
            'token' => $token,
            'invited_by' => $data->invitedBy,
            'expires_at' => $data->expiresAt,
        ])->load('tenant:id,name,slug', 'inviter:id,name,email');
    }

    public function findActiveByToken(string $token): ?Invitation
    {
        return Invitation::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function markAccepted(Invitation $invitation): Invitation
    {
        $invitation->update(['accepted_at' => now()]);

        return $invitation->refresh();
    }

    public function revoke(Invitation $invitation): Invitation
    {
        $invitation->update(['revoked_at' => now()]);

        return $invitation->refresh();
    }

    public function upsertMembershipFromInvitation(Invitation $invitation, User $user): Membership
    {
        return Membership::query()->updateOrCreate(
            [
                'tenant_id' => $invitation->tenant_id,
                'user_id' => $user->id,
            ],
            [
                'role' => data_get($invitation->role, 'value', $invitation->role),
            ]
        )->load('user:id,name,email');
    }
}
