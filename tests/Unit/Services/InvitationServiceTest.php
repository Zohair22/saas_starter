<?php

namespace Tests\Unit\Services;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Modules\Billing\Interfaces\Contracts\UsageCounterServiceInterface;
use Modules\Membership\Classes\DTOs\AcceptInvitationData;
use Modules\Membership\Classes\DTOs\CreateInvitationData;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Interfaces\Contracts\InvitationRepositoryInterface;
use Modules\Membership\Models\Invitation;
use Modules\Membership\Models\Membership;
use Modules\Membership\Notifications\TenantInvitationNotification;
use Modules\Membership\Services\InvitationService;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class InvitationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_throws_when_active_invitation_already_exists(): void
    {
        /** @var InvitationRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new InvitationService($repository, $usageCounterService);

        $existingInvitation = new Invitation;
        $data = new CreateInvitationData(
            tenantId: 10,
            email: 'member@example.com',
            role: MembershipRole::Member->value,
            invitedBy: null,
            expiresAt: CarbonImmutable::now()->addDay(),
        );

        $repository
            ->shouldReceive('findActiveByTenantAndEmail')
            ->once()
            ->with($data->tenantId, $data->email)
            ->andReturn($existingInvitation);

        $this->expectException(ValidationException::class);

        $service->create($data);
    }

    public function test_create_creates_invitation_and_sends_notification(): void
    {
        /** @var InvitationRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new InvitationService($repository, $usageCounterService);

        Notification::fake();

        $invitation = new Invitation([
            'tenant_id' => 10,
            'email' => 'member@example.com',
            'role' => MembershipRole::Member->value,
            'token' => 'abc123',
            'expires_at' => CarbonImmutable::now()->addDay(),
        ]);
        $data = new CreateInvitationData(
            tenantId: 10,
            email: 'member@example.com',
            role: MembershipRole::Member->value,
            invitedBy: null,
            expiresAt: CarbonImmutable::now()->addDay(),
        );

        $repository
            ->shouldReceive('findActiveByTenantAndEmail')
            ->once()
            ->with($data->tenantId, $data->email)
            ->andReturnNull();

        $repository
            ->shouldReceive('create')
            ->once()
            ->with($data, Mockery::type('string'))
            ->andReturn($invitation);

        $result = $service->create($data);

        $this->assertSame($invitation, $result);
        Notification::assertSentOnDemand(TenantInvitationNotification::class);
    }

    public function test_preview_by_token_throws_when_token_not_found(): void
    {
        /** @var InvitationRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new InvitationService($repository, $usageCounterService);

        $repository
            ->shouldReceive('findActiveByToken')
            ->once()
            ->with('missing-token')
            ->andReturnNull();

        $this->expectException(ValidationException::class);

        $service->previewByToken('missing-token');
    }

    public function test_preview_by_token_returns_loaded_invitation_when_found(): void
    {
        /** @var InvitationRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new InvitationService($repository, $usageCounterService);

        $invitation = Mockery::mock(Invitation::class);
        $invitation->shouldReceive('load')->once()->with('tenant:id,name,slug', 'inviter:id,name,email')->andReturnSelf();

        $repository
            ->shouldReceive('findActiveByToken')
            ->once()
            ->with('valid-token')
            ->andReturn($invitation);

        $result = $service->previewByToken('valid-token');

        $this->assertSame($invitation, $result);
    }

    public function test_accept_by_token_throws_when_authenticated_email_does_not_match_invitation(): void
    {
        /** @var InvitationRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new InvitationService($repository, $usageCounterService);

        $invitation = new Invitation([
            'tenant_id' => 10,
            'email' => 'invitee@example.com',
            'role' => MembershipRole::Member->value,
        ]);
        $authenticatedUser = User::factory()->create(['email' => 'other@example.com']);
        $data = new AcceptInvitationData($authenticatedUser, null, null);

        $repository->shouldReceive('findActiveByToken')->once()->with('token-1')->andReturn($invitation);

        $this->expectException(ValidationException::class);

        $service->acceptByToken('token-1', $data);
    }

    public function test_accept_by_token_throws_when_account_already_exists_for_invitation_email(): void
    {
        /** @var InvitationRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new InvitationService($repository, $usageCounterService);

        $invitation = new Invitation([
            'tenant_id' => 10,
            'email' => 'existing@example.com',
            'role' => MembershipRole::Member->value,
        ]);
        User::factory()->create(['email' => 'existing@example.com']);
        $data = new AcceptInvitationData(null, null, null);

        $repository->shouldReceive('findActiveByToken')->once()->with('token-2')->andReturn($invitation);

        $this->expectException(ValidationException::class);

        $service->acceptByToken('token-2', $data);
    }

    public function test_accept_by_token_throws_when_name_or_password_missing_for_new_user(): void
    {
        /** @var InvitationRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new InvitationService($repository, $usageCounterService);

        $invitation = new Invitation([
            'tenant_id' => 10,
            'email' => 'new-user@example.com',
            'role' => MembershipRole::Member->value,
        ]);
        $data = new AcceptInvitationData(null, null, null);

        $repository->shouldReceive('findActiveByToken')->once()->with('token-3')->andReturn($invitation);

        $this->expectException(ValidationException::class);

        $service->acceptByToken('token-3', $data);
    }

    public function test_accept_by_token_creates_new_user_and_returns_membership(): void
    {
        /** @var InvitationRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new InvitationService($repository, $usageCounterService);

        $tenantOwner = User::factory()->create();
        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-invitation-service',
            'owner_id' => $tenantOwner->id,
        ]);
        $invitation = new Invitation([
            'tenant_id' => $tenant->id,
            'email' => 'accepted@example.com',
            'role' => MembershipRole::Member->value,
            'token' => 'token-accept',
            'expires_at' => CarbonImmutable::now()->addDay(),
        ]);
        $membership = new Membership;
        $data = new AcceptInvitationData(null, 'Invited User', 'password123');

        $repository->shouldReceive('findActiveByToken')->once()->with('token-4')->andReturn($invitation);

        $repository
            ->shouldReceive('upsertMembershipFromInvitation')
            ->once()
            ->with($invitation, Mockery::on(function (User $user): bool {
                return $user->email === 'accepted@example.com' && $user->name === 'Invited User';
            }))
            ->andReturn($membership);

        $usageCounterService
            ->shouldReceive('syncTenantUsage')
            ->once()
            ->with($tenant->id);

        $repository
            ->shouldReceive('markAccepted')
            ->once()
            ->with($invitation)
            ->andReturn($invitation);

        $result = $service->acceptByToken('token-4', $data);

        $this->assertSame($membership, $result);
        $this->assertDatabaseHas('users', ['email' => 'accepted@example.com', 'name' => 'Invited User']);
    }

    public function test_revoke_delegates_to_repository(): void
    {
        /** @var InvitationRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        /** @var UsageCounterServiceInterface&MockInterface $usageCounterService */
        $usageCounterService = Mockery::mock(UsageCounterServiceInterface::class);
        $service = new InvitationService($repository, $usageCounterService);

        $invitation = new Invitation;

        $repository
            ->shouldReceive('revoke')
            ->once()
            ->with($invitation)
            ->andReturn($invitation);

        $result = $service->revoke($invitation);

        $this->assertSame($invitation, $result);
    }
}
