<?php

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Models\Tenants;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var Tenants $tenant */
        $tenant = request()->attributes->get('tenant');
        $this->authorize('viewPlans', $tenant);

        if (! $tenant->stripe_id) {
            return response()->json(['data' => []]);
        }

        $invoices = $tenant->invoices()->map(fn ($invoice) => [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'status' => $invoice->status,
            'total' => $invoice->total(),
            'amount_paid' => $invoice->amountPaid(),
            'amount_due' => $invoice->amountDue(),
            'currency' => $invoice->invoice->currency ?? 'usd',
            'period_start' => $invoice->invoice->period_start
                ? date('Y-m-d', $invoice->invoice->period_start)
                : null,
            'period_end' => $invoice->invoice->period_end
                ? date('Y-m-d', $invoice->invoice->period_end)
                : null,
            'created' => date('Y-m-d', $invoice->invoice->created),
            'hosted_invoice_url' => $invoice->invoice->hosted_invoice_url ?? null,
            'pdf_url' => $invoice->invoice->invoice_pdf ?? null,
        ]);

        return response()->json(['data' => $invoices]);
    }
}
