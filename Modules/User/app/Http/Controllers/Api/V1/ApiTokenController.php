<?php

namespace Modules\User\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Interfaces\Contracts\AuditLogServiceInterface;
use Modules\User\Http\Requests\CreateApiTokenRequest;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenController extends Controller
{
    public function __construct(
        private readonly AuditLogServiceInterface $auditLogService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()
            ->latest('id')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'created_at'])
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
            ]);

        return response()->json(['data' => $tokens]);
    }

    public function store(CreateApiTokenRequest $request): JsonResponse
    {
        $abilities = $request->validated('abilities', ['*']);
        $tokenResult = $request->user()->createToken($request->validated('name'), $abilities);

        $this->auditLogService->record(
            action: AuditAction::ApiTokenCreated,
            actor: $request->user(),
            newValues: [
                'token_name' => $request->validated('name'),
                'abilities' => $abilities,
            ],
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent(),
        );

        return response()->json([
            'message' => 'API token created.',
            'token' => $tokenResult->plainTextToken,
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $token = PersonalAccessToken::query()
            ->whereKey($tokenId)
            ->where('tokenable_type', $request->user()::class)
            ->where('tokenable_id', $request->user()->id)
            ->first();

        if (! $token) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $this->auditLogService->record(
            action: AuditAction::ApiTokenRevoked,
            actor: $request->user(),
            oldValues: [
                'token_id' => $token->id,
                'token_name' => $token->name,
            ],
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent(),
        );

        $token->delete();

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
