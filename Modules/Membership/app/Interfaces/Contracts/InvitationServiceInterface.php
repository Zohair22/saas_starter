<?php

namespace Modules\Membership\Interfaces\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Membership\Classes\DTOs\AcceptInvitationData;
use Modules\Membership\Classes\DTOs\CreateInvitationData;
use Modules\Membership\Models\Invitation;
use Modules\Membership\Models\Membership;

interface InvitationServiceInterface
{
    public function listActiveForTenant(int $tenantId): Collection;

    public function create(CreateInvitationData $data): Invitation;

    public function previewByToken(string $token): Invitation;

    public function acceptByToken(string $token, AcceptInvitationData $data): Membership;

    public function revoke(Invitation $invitation): Invitation;
}
