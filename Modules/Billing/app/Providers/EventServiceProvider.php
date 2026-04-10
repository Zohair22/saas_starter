<?php

namespace Modules\Billing\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Billing\Events\BillingInvoicePaid;
use Modules\Billing\Events\BillingPaymentFailed;
use Modules\Billing\Events\BillingSubscriptionCanceled;
use Modules\Billing\Events\BillingSubscriptionChanged;
use Modules\Billing\Listeners\DispatchInvoicePaidJob;
use Modules\Billing\Listeners\DispatchPaymentFailedJob;
use Modules\Billing\Listeners\DispatchSubscriptionCanceledJob;
use Modules\Billing\Listeners\DispatchSubscriptionChangedJob;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        BillingPaymentFailed::class => [
            DispatchPaymentFailedJob::class,
        ],
        BillingSubscriptionCanceled::class => [
            DispatchSubscriptionCanceledJob::class,
        ],
        BillingSubscriptionChanged::class => [
            DispatchSubscriptionChangedJob::class,
        ],
        BillingInvoicePaid::class => [
            DispatchInvoicePaidJob::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;
}
