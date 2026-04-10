<?php

namespace Modules\ActivityLog\Policies;

use Modules\Membership\Services\CurrentMembershipService;
use Modules\User\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return CurrentMembershipService::for($user) !== null;
    }
}
