<?php

namespace Modules\Membership\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Membership\Classes\DTOs\CreateMembershipData;
use Modules\Membership\Classes\DTOs\UpdateMembershipData;
use Modules\Membership\Http\Requests\StoreMembershipRequest;
use Modules\Membership\Http\Requests\UpdateMembershipRequest;
use Modules\Membership\Interfaces\Contracts\MembershipServiceInterface;
use Modules\Membership\Models\Membership;
use Modules\Membership\Services\CurrentMembershipService;
use Modules\Membership\Transformers\MembershipResource;
use Symfony\Component\HttpFoundation\Response;

class MembershipController extends Controller
{
    public function __construct(
        private readonly MembershipServiceInterface $membershipService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Membership::class);

        $tenantId = (int) data_get(request()->attributes->get('tenant'), 'id');
        $memberships = $this->membershipService->listForTenant($tenantId);

        return MembershipResource::collection($memberships)
            ->additional(['meta' => [
                'current_membership' => MembershipResource::make(CurrentMembershipService::get()),
                'capabilities' => CurrentMembershipService::capabilitiesFor(request()->user()),
            ]]);
    }

    public function store(StoreMembershipRequest $request): JsonResponse
    {
        $this->authorize('create', Membership::class);

        $membership = $this->membershipService->create(CreateMembershipData::fromRequest($request));

        return MembershipResource::make($membership)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Membership $membership): MembershipResource
    {
        $this->authorize('view', $membership);

        return MembershipResource::make($membership->load('user:id,name,email'));
    }

    public function update(UpdateMembershipRequest $request, Membership $membership): MembershipResource
    {
        $this->authorize('update', $membership);

        $membership = $this->membershipService->update($membership, UpdateMembershipData::fromRequest($request));

        return MembershipResource::make($membership);
    }

    public function destroy(Membership $membership): JsonResponse
    {
        $this->authorize('delete', $membership);

        $this->membershipService->delete($membership);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
