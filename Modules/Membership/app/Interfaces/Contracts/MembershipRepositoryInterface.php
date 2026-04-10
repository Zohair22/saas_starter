<?php

namespace Modules\Membership\Interfaces\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Membership\Classes\DTOs\CreateMembershipData;
use Modules\Membership\Classes\DTOs\UpdateMembershipData;
use Modules\Membership\Models\Membership;

interface MembershipRepositoryInterface
{
    public function listForTenant(int $tenantId): Collection;

    public function create(CreateMembershipData $data): Membership;

    public function update(Membership $membership, UpdateMembershipData $data): Membership;

    public function delete(Membership $membership): void;
}
