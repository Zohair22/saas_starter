<?php

namespace Modules\Membership\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Membership\Classes\DTOs\AcceptInvitationData;
use Modules\Membership\Classes\DTOs\CreateInvitationData;
use Modules\Membership\Http\Requests\AcceptInvitationRequest;
use Modules\Membership\Http\Requests\StoreInvitationRequest;
use Modules\Membership\Interfaces\Contracts\InvitationServiceInterface;
use Modules\Membership\Models\Invitation;
use Modules\Membership\Transformers\InvitationResource;
use Modules\Membership\Transformers\MembershipResource;
use Symfony\Component\HttpFoundation\Response;

class InvitationController extends Controller
{
    public function __construct(
        private readonly InvitationServiceInterface $invitationService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invitation::class);

        $tenantId = (int) data_get(request()->attributes->get('tenant'), 'id');
        $invitations = $this->invitationService->listActiveForTenant($tenantId);

        return InvitationResource::collection($invitations);
    }

    public function store(StoreInvitationRequest $request): JsonResponse
    {
        $this->authorize('create', Invitation::class);

        $invitation = $this->invitationService->create(CreateInvitationData::fromRequest($request));

        return InvitationResource::make($invitation)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function showByToken(string $token): InvitationResource
    {
        return InvitationResource::make($this->invitationService->previewByToken($token));
    }

    public function accept(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        $membership = $this->invitationService->acceptByToken($token, AcceptInvitationData::fromRequest($request));

        return response()->json([
            'message' => 'Invitation accepted successfully.',
            'membership' => MembershipResource::make($membership),
        ]);
    }

    public function destroy(Invitation $invitation): JsonResponse
    {
        $this->authorize('delete', $invitation);

        $this->invitationService->revoke($invitation);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
