<?php

namespace Modules\Membership\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Billing\Interfaces\Contracts\UsageCounterServiceInterface;
use Modules\Membership\Classes\DTOs\AcceptInvitationData;
use Modules\Membership\Classes\DTOs\CreateInvitationData;
use Modules\Membership\Interfaces\Contracts\InvitationRepositoryInterface;
use Modules\Membership\Interfaces\Contracts\InvitationServiceInterface;
use Modules\Membership\Models\Invitation;
use Modules\Membership\Models\Membership;
use Modules\Membership\Notifications\TenantInvitationNotification;
use Modules\User\Models\User;

class InvitationService implements InvitationServiceInterface
{
    public function __construct(
        private readonly InvitationRepositoryInterface $invitationRepository,
        private readonly UsageCounterServiceInterface $usageCounterService,
    ) {}

    public function listActiveForTenant(int $tenantId): Collection
    {
        return $this->invitationRepository->listActiveForTenant($tenantId);
    }

    public function create(CreateInvitationData $data): Invitation
    {
        $existingInvitation = $this->invitationRepository->findActiveByTenantAndEmail($data->tenantId, $data->email);

        if ($existingInvitation) {
            throw ValidationException::withMessages([
                'email' => ['An active invitation for this email already exists in this tenant.'],
            ]);
        }

        $invitation = $this->invitationRepository->create($data, Str::random(64));

        Notification::route('mail', $invitation->email)
            ->notify(new TenantInvitationNotification($invitation));

        return $invitation;
    }

    public function previewByToken(string $token): Invitation
    {
        $invitation = $this->invitationRepository->findActiveByToken($token);

        if (! $invitation) {
            throw ValidationException::withMessages([
                'token' => ['Invitation token is invalid or expired.'],
            ]);
        }

        return $invitation->load('tenant:id,name,slug', 'inviter:id,name,email');
    }

    public function acceptByToken(string $token, AcceptInvitationData $data): Membership
    {
        $invitation = $this->previewByToken($token);
        $user = $this->resolveUserForAcceptance($invitation, $data);

        $membership = $this->invitationRepository->upsertMembershipFromInvitation($invitation, $user);
        $this->usageCounterService->syncTenantUsage((int) $invitation->tenant_id);
        $this->invitationRepository->markAccepted($invitation);

        return $membership;
    }

    public function revoke(Invitation $invitation): Invitation
    {
        return $this->invitationRepository->revoke($invitation);
    }

    private function resolveUserForAcceptance(Invitation $invitation, AcceptInvitationData $data): User
    {
        $invitationEmail = mb_strtolower((string) $invitation->email);

        if ($data->user) {
            if (mb_strtolower((string) $data->user->email) !== $invitationEmail) {
                throw ValidationException::withMessages([
                    'email' => ['Authenticated user email does not match invitation email.'],
                ]);
            }

            return $data->user;
        }

        $existingUser = User::query()->where('email', $invitationEmail)->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => ['Account already exists for this invitation. Please login and accept again.'],
            ]);
        }

        if (! $data->name || ! $data->password) {
            throw ValidationException::withMessages([
                'name' => ['Name is required when creating a new account from invitation.'],
                'password' => ['Password is required when creating a new account from invitation.'],
            ]);
        }

        return User::query()->create([
            'name' => $data->name,
            'email' => $invitationEmail,
            'password' => Hash::make($data->password),
        ]);
    }
}
