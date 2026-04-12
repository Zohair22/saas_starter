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

        $invoices = $tenant->invoices()->map(function ($invoice): array {
            $stripeInvoice = $invoice->invoice;
            $periodStart = data_get($stripeInvoice, 'period_start');
            $periodEnd = data_get($stripeInvoice, 'period_end');
            $created = data_get($stripeInvoice, 'created');

            return [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'total' => $invoice->total(),
                'amount_paid' => $invoice->amountPaid(),
                'amount_due' => $invoice->amountDue(),
                'currency' => data_get($stripeInvoice, 'currency', 'usd'),
                'period_start' => $periodStart ? date('Y-m-d', (int) $periodStart) : null,
                'period_end' => $periodEnd ? date('Y-m-d', (int) $periodEnd) : null,
                'created' => $created ? date('Y-m-d', (int) $created) : null,
                'hosted_invoice_url' => data_get($stripeInvoice, 'hosted_invoice_url'),
                'pdf_url' => data_get($stripeInvoice, 'invoice_pdf'),
            ];
        });

        return response()->json(['data' => $invoices]);
    }
}
