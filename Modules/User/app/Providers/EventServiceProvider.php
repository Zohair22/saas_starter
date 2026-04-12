<?php

namespace Modules\User\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Billing\Events\BillingInvoicePaid;
use Modules\Billing\Events\BillingPaymentFailed;
use Modules\Billing\Events\BillingSubscriptionCanceled;
use Modules\Billing\Events\BillingSubscriptionChanged;
use Modules\Project\Events\ProjectCreated;
use Modules\Project\Events\ProjectUpdated;
use Modules\Task\Events\TaskCompleted;
use Modules\Task\Events\TaskCreated;
use Modules\Task\Events\TaskUpdated;
use Modules\User\Listeners\NotifyBillingActivity;
use Modules\User\Listeners\NotifyProjectActivity;
use Modules\User\Listeners\NotifyTaskActivity;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        ProjectCreated::class => [NotifyProjectActivity::class],
        ProjectUpdated::class => [NotifyProjectActivity::class],
        TaskCreated::class => [NotifyTaskActivity::class],
        TaskUpdated::class => [NotifyTaskActivity::class],
        TaskCompleted::class => [NotifyTaskActivity::class],
        BillingPaymentFailed::class => [NotifyBillingActivity::class],
        BillingInvoicePaid::class => [NotifyBillingActivity::class],
        BillingSubscriptionChanged::class => [NotifyBillingActivity::class],
        BillingSubscriptionCanceled::class => [NotifyBillingActivity::class],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
