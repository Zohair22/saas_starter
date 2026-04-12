<?php

namespace Modules\Membership\Interfaces\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Membership\Classes\DTOs\CreateInvitationData;
use Modules\Membership\Models\Invitation;
use Modules\Membership\Models\Membership;
use Modules\User\Models\User;

interface InvitationRepositoryInterface
{
    public function listActiveForTenant(int $tenantId): Collection;

    public function findActiveByTenantAndEmail(int $tenantId, string $email): ?Invitation;

    public function create(CreateInvitationData $data, string $token): Invitation;

    public function findActiveByToken(string $token): ?Invitation;

    public function markAccepted(Invitation $invitation): Invitation;

    public function revoke(Invitation $invitation): Invitation;

    public function upsertMembershipFromInvitation(Invitation $invitation, User $user): Membership;
}
