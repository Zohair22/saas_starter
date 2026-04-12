<?php

namespace Modules\User\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Modules\User\Classes\DTOs\CreateUserData;
use Modules\User\Http\Requests\LoginUserRequest;
use Modules\User\Http\Requests\RegisterUserRequest;
use Modules\User\Interfaces\Contracts\UserServiceInterface;
use Modules\User\Models\User;
use Modules\User\Services\MfaService;
use Modules\User\Transformers\UserResource;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly MfaService $mfaService,
    ) {}

    /**
     * Register a new user.
     */
    public function store(RegisterUserRequest $request): JsonResponse
    {
        $user = $this->userService->create(CreateUserData::fromRequest($request));

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Login and issue API token.
     */
    public function login(LoginUserRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->validated('email'))
            ->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->mfa_enabled) {
            $mfaCode = $request->validated('mfa_code');
            $recoveryCode = $request->validated('mfa_recovery_code');

            if ($mfaCode === null && $recoveryCode === null) {
                return response()->json([
                    'message' => 'MFA code is required.',
                    'mfa_required' => true,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (! $this->mfaService->challengePassed($user, $mfaCode, $recoveryCode)) {
                return response()->json([
                    'message' => 'Invalid MFA credentials.',
                    'mfa_required' => true,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user' => UserResource::make($user),
            'token' => $token,
        ]);
    }

    /**
     * Get current authenticated user.
     */
    public function me(): UserResource
    {
        return UserResource::make(request()->user());
    }

    /**
     * Show a specific user.
     */
    public function show(User $user): UserResource
    {
        return UserResource::make($user);
    }

    /**
     * Logout and revoke current token.
     */
    public function logout(): JsonResponse
    {
        request()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Delete a user account.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
