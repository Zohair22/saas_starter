<?php

namespace Modules\AuditLog\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\AuditLog\Listeners\LogBillingInvoicePaidAudit;
use Modules\AuditLog\Listeners\LogBillingPaymentFailedAudit;
use Modules\AuditLog\Listeners\LogBillingSubscriptionCanceledAudit;
use Modules\AuditLog\Listeners\LogBillingSubscriptionChangedAudit;
use Modules\AuditLog\Listeners\LogProjectCreatedAudit;
use Modules\AuditLog\Listeners\LogProjectDeletedAudit;
use Modules\AuditLog\Listeners\LogProjectUpdatedAudit;
use Modules\AuditLog\Listeners\LogTaskCompletedAudit;
use Modules\AuditLog\Listeners\LogTaskCreatedAudit;
use Modules\AuditLog\Listeners\LogTaskUpdatedAudit;
use Modules\Billing\Events\BillingInvoicePaid;
use Modules\Billing\Events\BillingPaymentFailed;
use Modules\Billing\Events\BillingSubscriptionCanceled;
use Modules\Billing\Events\BillingSubscriptionChanged;
use Modules\Project\Events\ProjectCreated;
use Modules\Project\Events\ProjectDeleted;
use Modules\Project\Events\ProjectUpdated;
use Modules\Task\Events\TaskCompleted;
use Modules\Task\Events\TaskCreated;
use Modules\Task\Events\TaskUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ProjectCreated::class => [LogProjectCreatedAudit::class],
        ProjectUpdated::class => [LogProjectUpdatedAudit::class],
        ProjectDeleted::class => [LogProjectDeletedAudit::class],
        TaskCreated::class => [LogTaskCreatedAudit::class],
        TaskUpdated::class => [LogTaskUpdatedAudit::class],
        TaskCompleted::class => [LogTaskCompletedAudit::class],
        BillingSubscriptionChanged::class => [LogBillingSubscriptionChangedAudit::class],
        BillingSubscriptionCanceled::class => [LogBillingSubscriptionCanceledAudit::class],
        BillingPaymentFailed::class => [LogBillingPaymentFailedAudit::class],
        BillingInvoicePaid::class => [LogBillingInvoicePaidAudit::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
