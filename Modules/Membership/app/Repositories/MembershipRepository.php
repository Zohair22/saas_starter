<?php

namespace Modules\Membership\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Billing\Interfaces\Contracts\UsageCounterServiceInterface;
use Modules\Membership\Classes\DTOs\CreateMembershipData;
use Modules\Membership\Classes\DTOs\UpdateMembershipData;
use Modules\Membership\Interfaces\Contracts\MembershipRepositoryInterface;
use Modules\Membership\Models\Membership;

class MembershipRepository implements MembershipRepositoryInterface
{
    public function __construct(
        private readonly UsageCounterServiceInterface $usageCounterService,
    ) {}

    public function listForTenant(int $tenantId): Collection
    {
        return Membership::query()
            ->with('user:id,name,email')
            ->get();
    }

    public function create(CreateMembershipData $data): Membership
    {
        $membership = Membership::create([
            'user_id' => $data->userId,
            'role' => $data->role,
        ])->load('user:id,name,email');

        $this->usageCounterService->incrementUsers($data->tenantId);

        return $membership;
    }

    public function update(Membership $membership, UpdateMembershipData $data): Membership
    {
        $membership->update([
            'role' => $data->role ?? $membership->role,
        ]);

        return $membership->refresh()->load('user:id,name,email');
    }

    public function delete(Membership $membership): void
    {
        $tenantId = (int) $membership->tenant_id;
        $membership->delete();
        $this->usageCounterService->decrementUsers($tenantId);
    }
}
