<?php

namespace Modules\User\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Models\User;
use Modules\User\Services\MfaService;
use Symfony\Component\HttpFoundation\Response;

class RequireMfaStepUp
{
    public function __construct(
        private readonly MfaService $mfaService,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user || ! $user->mfa_enabled) {
            return $next($request);
        }

        $mfaCode = $this->extractString($request, 'mfa_code');
        $mfaRecoveryCode = $this->extractString($request, 'mfa_recovery_code');

        if ($mfaCode === null && $mfaRecoveryCode === null) {
            return $this->stepUpRequiredResponse('MFA step-up is required for this action.', 428);
        }

        if (! $this->mfaService->challengePassed($user, $mfaCode, $mfaRecoveryCode)) {
            return $this->stepUpRequiredResponse('Invalid MFA credentials.', 422);
        }

        return $next($request);
    }

    private function extractString(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    private function stepUpRequiredResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'step_up_required' => true,
        ], $status);
    }
}
