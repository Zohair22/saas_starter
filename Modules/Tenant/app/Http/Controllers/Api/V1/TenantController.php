<?php

namespace Modules\Tenant\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Tenant\Classes\DTOs\CreateTenantData;
use Modules\Tenant\Http\Requests\StoreTenantRequest;
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
        $tenants = Tenants::where('owner_id', request()->user()->id)->get();

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
    public function update(Tenants $tenant): JsonResponse
    {
        //

        return response()->json([]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenants $tenant): JsonResponse
    {
        //

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
