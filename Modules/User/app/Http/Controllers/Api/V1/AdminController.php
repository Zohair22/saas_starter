<?php

namespace Modules\User\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;

class AdminController extends Controller
{
    /**
     * Return global admin dashboard metrics and recent tenant/user snapshots.
     */
    public function dashboard(Request $request): JsonResponse
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);

        $tenantCount = Tenants::query()->count();
        $userCount = User::query()->count();
        $recentTenants = Tenants::query()
            ->latest()
            ->limit(10)
            ->get(['id', 'name', 'slug', 'owner_id', 'billing_status', 'created_at']);

        $tenantsByStatus = Tenants::query()
            ->selectRaw('COALESCE(billing_status, ?) as status, COUNT(*) as total', ['none'])
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'metrics' => [
                'tenants' => $tenantCount,
                'users' => $userCount,
            ],
            'tenants_by_status' => $tenantsByStatus,
            'recent_tenants' => $recentTenants,
        ]);
    }

    /**
     * Impersonate another user by issuing a dedicated token.
     */
    public function impersonate(Request $request, User $user): JsonResponse
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);

        $user->tokens()->where('name', 'impersonation')->delete();
        $token = $user->createToken('impersonation')->plainTextToken;
        $firstTenantId = $user->tenants()->orderBy('name')->value('tenants.id');

        return response()->json([
            'message' => 'Impersonation session prepared.',
            'auth' => [
                'token' => $token,
                'tenant_id' => $firstTenantId,
            ],
            'impersonated_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
