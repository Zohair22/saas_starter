<?php

namespace Modules\Membership\Services;

use Modules\Membership\Enums\MembershipRole;

class RoleService
{
    public static function isOwner(): bool
    {
        return CurrentMembershipService::get()->role === MembershipRole::Owner;
    }

    public static function isAdmin(): bool
    {
        return in_array(
            CurrentMembershipService::get()->role,
            [MembershipRole::Owner, MembershipRole::Admin],
            strict: true,
        );
    }

    public static function isMember(): bool
    {
        return CurrentMembershipService::get()->role === MembershipRole::Member;
    }
}
