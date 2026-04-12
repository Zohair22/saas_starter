<?php

namespace Modules\User\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Enums\TenantPermission;
use Modules\Membership\Models\Membership;
use Modules\Membership\Support\TenantRolePermissions;
use Modules\Tenant\Models\Tenants;
use Modules\Tenant\Transformers\TenantResource;
use Modules\User\Transformers\UserResource;

class SessionBootstrapController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenants = $user->tenants()->orderBy('name')->get();

        $activeTenant = $this->resolveActiveTenant($request, $tenants);
        $currentMembership = null;
        $capabilities = $this->defaultCapabilities();

        if ($activeTenant) {
            $currentMembership = Membership::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $activeTenant->id)
                ->where('user_id', $user->id)
                ->first();

            $capabilities = $this->capabilitiesForMembership($currentMembership);
        }

        $payload = [
            'user' => UserResource::make($user)->resolve($request),
            'tenants' => TenantResource::collection($tenants)->resolve($request),
            'active_tenant_id' => $activeTenant?->id,
            'context' => [
                'current_membership' => $this->membershipPayload($currentMembership),
                'capabilities' => $capabilities,
            ],
        ];

        $response = response()->json($payload);
        $response->setEtag(hash('sha256', (string) json_encode($payload)));
        $response->headers->set('Cache-Control', 'private, max-age=15, must-revalidate');
        $response->isNotModified($request);

        return $response;
    }

    private function resolveActiveTenant(Request $request, $tenants): ?Tenants
    {
        if ($tenants->isEmpty()) {
            return null;
        }

        $requestedTenant = $request->header('X-Tenant-ID');

        if (! $requestedTenant) {
            return $tenants->first();
        }

        $tenant = ctype_digit((string) $requestedTenant)
            ? $tenants->firstWhere('id', (int) $requestedTenant)
            : $tenants->firstWhere('slug', strtolower((string) $requestedTenant));

        return $tenant ?? $tenants->first();
    }

    private function membershipPayload(?Membership $membership): ?array
    {
        if (! $membership) {
            return null;
        }

        $role = $membership->role instanceof MembershipRole
            ? $membership->role->value
            : (string) $membership->role;

        return [
            'id' => $membership->id,
            'tenant_id' => $membership->tenant_id,
            'user_id' => $membership->user_id,
            'role' => $role,
            'role_flags' => [
                'is_owner' => $role === MembershipRole::Owner->value,
                'is_admin' => $role === MembershipRole::Admin->value,
                'is_member' => $role === MembershipRole::Member->value,
            ],
            'is_current_user' => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function capabilitiesForMembership(?Membership $membership): array
    {
        if (! $membership) {
            return $this->defaultCapabilities();
        }

        $permissions = TenantRolePermissions::permissionsForRole($membership->role);

        return [
            'is_tenant_member' => true,
            'can_view_memberships' => in_array(TenantPermission::ViewMemberships->value, $permissions, true),
            'can_view_billing' => true,
            'can_manage_projects' => in_array(TenantPermission::ManageProjects->value, $permissions, true),
            'can_manage_memberships' => in_array(TenantPermission::ManageMemberships->value, $permissions, true),
            'can_manage_invitations' => in_array(TenantPermission::ManageInvitations->value, $permissions, true),
            'can_manage_billing' => in_array(TenantPermission::ManageBilling->value, $permissions, true),
            'can_manage_tenant_settings' => in_array(TenantPermission::ManageTenantSettings->value, $permissions, true),
            'is_tenant_owner' => $membership?->role instanceof MembershipRole
                ? $membership->role === MembershipRole::Owner
                : (string) ($membership?->role ?? '') === MembershipRole::Owner->value,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function defaultCapabilities(): array
    {
        return [
            'is_tenant_member' => false,
            'can_view_memberships' => false,
            'can_view_billing' => false,
            'can_manage_projects' => false,
            'can_manage_memberships' => false,
            'can_manage_invitations' => false,
            'can_manage_billing' => false,
            'can_manage_tenant_settings' => false,
            'is_tenant_owner' => false,
        ];
    }
}
