<?php

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Membership\Enums\MembershipRole;
use Modules\Membership\Models\Membership;
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

    public function test_webhook_lifecycle_transitions_toggle_write_lock_and_recovery(): void
    {
        config(['cashier.webhook.secret' => null]);

        $owner = User::factory()->create();
        $tenant = Tenants::query()->create([
            'name' => 'Acme Lifecycle',
            'slug' => 'acme-lifecycle',
            'owner_id' => $owner->id,
            'stripe_id' => 'cus_lifecycle_123',
        ]);

        Membership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'role' => MembershipRole::Owner->value,
        ]);

        Sanctum::actingAs($owner);

        $paymentFailedPayload = [
            'id' => 'evt_lifecycle_failed_001',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_lifecycle_failed_001',
                    'customer' => 'cus_lifecycle_123',
                ],
            ],
        ];

        $graceCanceledPayload = [
            'id' => 'evt_lifecycle_canceled_grace_001',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'customer' => 'cus_lifecycle_123',
                    'cancel_at_period_end' => true,
                    'current_period_end' => now()->addDay()->timestamp,
                ],
            ],
        ];

        $lockedCanceledPayload = [
            'id' => 'evt_lifecycle_canceled_locked_001',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'customer' => 'cus_lifecycle_123',
                    'cancel_at_period_end' => true,
                    'current_period_end' => now()->subDay()->timestamp,
                ],
            ],
        ];

        $paidPayload = [
            'id' => 'evt_lifecycle_paid_001',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_lifecycle_paid_001',
                    'customer' => 'cus_lifecycle_123',
                ],
            ],
        ];

        $this->postJson('/api/v1/billing/webhook', $paymentFailedPayload)->assertOk();
        $tenant->refresh();
        $this->assertSame('past_due', $tenant->billing_status);

        $this->postJson('/api/v1/billing/webhook', $graceCanceledPayload)->assertOk();
        $tenant->refresh();
        $this->assertSame('canceled', $tenant->billing_status);
        $this->assertNotNull($tenant->grace_period_ends_at);
        $this->assertTrue($tenant->grace_period_ends_at->isFuture());

        $duringGraceWrite = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/projects', [
                'name' => 'Grace Allowed Project',
            ]);
        $duringGraceWrite->assertCreated();

        $this->postJson('/api/v1/billing/webhook', $lockedCanceledPayload)->assertOk();
        $tenant->refresh();
        $this->assertSame('canceled', $tenant->billing_status);
        $this->assertNotNull($tenant->grace_period_ends_at);
        $this->assertTrue($tenant->grace_period_ends_at->isPast());

        $lockedWrite = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/projects', [
                'name' => 'Locked Project',
            ]);
        $lockedWrite->assertStatus(423);

        $this->postJson('/api/v1/billing/webhook', $paidPayload)->assertOk();
        $tenant->refresh();
        $this->assertSame('active', $tenant->billing_status);

        $recoveredWrite = $this
            ->withHeader('X-Tenant-ID', (string) $tenant->id)
            ->postJson('/api/v1/projects', [
                'name' => 'Recovered Project',
            ]);
        $recoveredWrite->assertCreated();
    }
}
