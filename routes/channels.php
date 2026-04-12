<?php

use Illuminate\Support\Facades\Broadcast;
use Modules\Membership\Models\Membership;
use Modules\Project\Models\Project;

Broadcast::channel('App.Models.User.{id}', function ($user, int $id): bool {
    return (int) $user->id === $id;
});

Broadcast::channel('tenant.{tenantId}.project.{projectId}', function ($user, int $tenantId, int $projectId): array|bool {
    $isTenantMember = Membership::query()
        ->withoutGlobalScopes()
        ->where('tenant_id', $tenantId)
        ->where('user_id', $user->id)
        ->exists();

    if (! $isTenantMember) {
        return false;
    }

    $projectExistsInTenant = Project::query()
        ->withoutGlobalScopes()
        ->whereKey($projectId)
        ->where('tenant_id', $tenantId)
        ->exists();

    if (! $projectExistsInTenant) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
