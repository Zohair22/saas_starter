<?php

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Tenants;
use Symfony\Component\HttpFoundation\Response;

class PaymentMethodController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var Tenants $tenant */
        $tenant = request()->attributes->get('tenant');
        $this->authorize('viewPlans', $tenant);

        if (! $tenant->stripe_id) {
            return response()->json([
                'data' => [],
                'default_payment_method' => null,
            ]);
        }

        $default = $tenant->defaultPaymentMethod();
        $methods = $tenant->paymentMethods()->map(fn ($pm) => $this->formatPaymentMethod($pm, $default?->id));

        return response()->json([
            'data' => $methods,
            'default_payment_method' => $default ? $this->formatPaymentMethod($default, $default->id) : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Tenants $tenant */
        $tenant = $request->attributes->get('tenant');
        $this->authorize('manageSubscription', $tenant);

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'starts_with:pm_'],
        ]);

        $tenant->addPaymentMethod($validated['payment_method']);

        return response()->json(['message' => 'Payment method added.'], Response::HTTP_CREATED);
    }

    public function setDefault(Request $request): JsonResponse
    {
        /** @var Tenants $tenant */
        $tenant = $request->attributes->get('tenant');
        $this->authorize('manageSubscription', $tenant);

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'starts_with:pm_'],
        ]);

        $tenant->updateDefaultPaymentMethod($validated['payment_method']);

        return response()->json(['message' => 'Default payment method updated.']);
    }

    public function destroy(string $paymentMethodId): JsonResponse
    {
        /** @var Tenants $tenant */
        $tenant = request()->attributes->get('tenant');
        $this->authorize('manageSubscription', $tenant);

        $paymentMethod = $tenant->findPaymentMethod($paymentMethodId);

        if (! $paymentMethod) {
            return response()->json(['message' => 'Payment method not found.'], Response::HTTP_NOT_FOUND);
        }

        $paymentMethod->delete();

        return response()->json(['message' => 'Payment method removed.']);
    }

    private function formatPaymentMethod(mixed $pm, ?string $defaultId): array
    {
        $card = $pm->card ?? null;
        $sepa = $pm->sepaDebit ?? $pm->sepa_debit ?? null;
        $type = $pm->type ?? 'unknown';

        return [
            'id' => $pm->id,
            'type' => $type,
            'is_default' => $pm->id === $defaultId,
            'brand' => $card?->brand ?? null,
            'last4' => $card?->last4 ?? $sepa?->last4 ?? null,
            'exp_month' => $card?->exp_month ?? null,
            'exp_year' => $card?->exp_year ?? null,
            'name' => data_get($pm, 'billing_details.name') ?? data_get($pm, 'billingDetails.name'),
        ];
    }
}
