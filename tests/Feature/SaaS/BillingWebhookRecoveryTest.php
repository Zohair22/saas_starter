<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;
use Tests\TestCase;

class BillingWebhookRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_failed_then_invoice_paid_recovers_tenant_billing_status(): void
    {
        config(['cashier.webhook.secret' => null]);

        $owner = User::factory()->create();
        $tenant = Tenants::query()->create([
            'name' => 'Acme',
            'slug' => 'acme-recovery',
            'owner_id' => $owner->id,
            'stripe_id' => 'cus_recovery_123',
        ]);

        $paymentFailedPayload = [
            'id' => 'evt_recovery_failed_001',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_recovery_failed_001',
                    'customer' => 'cus_recovery_123',
                ],
            ],
        ];

        $paidPayload = [
            'id' => 'evt_recovery_paid_001',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_recovery_paid_001',
                    'customer' => 'cus_recovery_123',
                ],
            ],
        ];

        $failedResponse = $this->postJson('/api/v1/billing/webhook', $paymentFailedPayload);
        $failedResponse->assertOk();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'billing_status' => 'past_due',
        ]);

        $this->assertDatabaseHas('webhook_receipts', [
            'stripe_event_id' => 'evt_recovery_failed_001',
            'type' => 'invoice.payment_failed',
        ]);

        $paidResponse = $this->postJson('/api/v1/billing/webhook', $paidPayload);
        $paidResponse->assertOk();

        $tenant->refresh();

        $this->assertSame('active', $tenant->billing_status);
        $this->assertNull($tenant->delinquent_since);
    }
}
