<?php

namespace Modules\Tenant\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Classes\DTOs\CreateTenantData;
use Modules\Tenant\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Http\Requests\TransferOwnershipRequest;
use Modules\Tenant\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Interfaces\Contracts\TenantServiceInterface;
use Modules\Tenant\Models\Tenants;
use Modules\Tenant\Transformers\TenantResource;
use Symfony\Component\HttpFoundation\Response;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantServiceInterface $tenantService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $tenants = request()->user()
            ->tenants()
            ->orderBy('name')
            ->get();

        return TenantResource::collection($tenants);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->create(CreateTenantData::fromRequest($request));

        return TenantResource::make($tenant)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show(Tenants $tenant): TenantResource
    {
        return TenantResource::make($tenant);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTenantRequest $request, Tenants $tenant): JsonResponse
    {
        $this->authorize('manageTenantSettings', $tenant);

        $tenant->update($request->validated());

        return TenantResource::make($tenant->fresh())
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenants $tenant): JsonResponse
    {
        $this->authorize('deleteTenant', $tenant);

        request()->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        // Cancel active Stripe subscription before deleting
        if ($tenant->subscribed()) {
            $tenant->subscription()->cancelNow();
        }

        $tenant->delete();

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Transfer tenant ownership to another member.
     */
    public function transferOwnership(TransferOwnershipRequest $request, Tenants $tenant): JsonResponse
    {
        $this->authorize('transferOwnership', $tenant);

        $newOwnerId = $request->validated('new_owner_id');
        $previousOwnerId = (int) $tenant->owner_id;

        // New owner must already be a member
        $isMember = Membership::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $newOwnerId)
            ->exists();

        if (! $isMember) {
            return response()->json(
                ['message' => 'The new owner must be an existing member of this tenant.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $tenant->update(['owner_id' => $newOwnerId]);

        // Demote previous owner to admin if still a member.
        Membership::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $previousOwnerId)
            ->update(['role' => 'admin']);

        // Elevate new owner's membership role
        Membership::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $newOwnerId)
            ->update(['role' => 'owner']);

        return TenantResource::make($tenant->fresh())
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
