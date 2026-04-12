<?php

namespace Modules\User\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\User\Services\MfaService;
use Symfony\Component\HttpFoundation\Response;

class MfaController extends Controller
{
    public function __construct(
        private readonly MfaService $mfaService,
    ) {}

    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->mfaService->setup($user);

        return response()->json([
            'secret' => $result['secret'],
            'recovery_codes' => $result['recovery_codes'],
            'otpauth_url' => $this->mfaService->otpauthUrl($user, $result['secret']),
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (! $this->mfaService->enable($request->user(), (string) $validated['code'])) {
            return response()->json([
                'message' => 'The provided MFA code is invalid.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'MFA has been enabled.',
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        if (! Hash::check((string) $validated['password'], (string) $request->user()->password)) {
            return response()->json([
                'message' => 'The provided password is invalid.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->mfaService->disable($request->user());

        return response()->json([
            'message' => 'MFA has been disabled.',
        ]);
    }
}
