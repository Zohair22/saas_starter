<?php

namespace Modules\Task\Policies;

use Modules\Membership\Enums\TenantPermission;
use Modules\Membership\Services\CurrentMembershipService;
use Modules\Project\Models\Project;
use Modules\Task\Models\Task;
use Modules\User\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return CurrentMembershipService::for($user) !== null;
    }

    public function view(User $user, Task $task): bool
    {
        return CurrentMembershipService::for($user) !== null;
    }

    public function create(User $user, Project $project): bool
    {
        return CurrentMembershipService::hasPermission($user, TenantPermission::ManageProjects);
    }

    public function update(User $user, Task $task): bool
    {
        return CurrentMembershipService::hasPermission($user, TenantPermission::ManageProjects);
    }

    public function delete(User $user, Task $task): bool
    {
        return CurrentMembershipService::hasPermission($user, TenantPermission::ManageProjects);
    }
}
