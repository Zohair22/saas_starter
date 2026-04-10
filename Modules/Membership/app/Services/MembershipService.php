<?php

namespace Modules\Membership\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Membership\Classes\DTOs\CreateMembershipData;
use Modules\Membership\Classes\DTOs\UpdateMembershipData;
use Modules\Membership\Interfaces\Contracts\MembershipRepositoryInterface;
use Modules\Membership\Interfaces\Contracts\MembershipServiceInterface;
use Modules\Membership\Models\Membership;

class MembershipService implements MembershipServiceInterface
{
    public function __construct(
        private readonly MembershipRepositoryInterface $membershipRepository,
    ) {}

    public function listForTenant(int $tenantId): Collection
    {
        return $this->membershipRepository->listForTenant($tenantId);
    }

    public function create(CreateMembershipData $data): Membership
    {
        return $this->membershipRepository->create($data);
    }

    public function update(Membership $membership, UpdateMembershipData $data): Membership
    {
        return $this->membershipRepository->update($membership, $data);
    }

    public function delete(Membership $membership): void
    {
        $this->membershipRepository->delete($membership);
    }
}
