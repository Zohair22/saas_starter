<?php

namespace Modules\Project\Policies;

use Modules\Membership\Enums\TenantPermission;
use Modules\Membership\Services\CurrentMembershipService;
use Modules\Project\Models\Project;
use Modules\User\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return CurrentMembershipService::for($user) !== null;
    }

    public function view(User $user, Project $project): bool
    {
        $tenantId = (int) data_get(request()->attributes->get('tenant'), 'id');

        if ((int) $project->tenant_id !== $tenantId) {
            return false;
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->view($user, $project) && $this->canManageProjects($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->view($user, $project) && $this->canManageProjects($user);
    }

    private function canManageProjects(User $user): bool
    {
        return CurrentMembershipService::hasPermission($user, TenantPermission::ManageProjects);
    }
}
