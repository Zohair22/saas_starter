<?php

namespace Modules\AuditLog\Enums;

enum AuditAction: string
{
    case ProjectCreated = 'project.created';
    case ProjectUpdated = 'project.updated';
    case ProjectDeleted = 'project.deleted';
    case TaskCreated = 'task.created';
    case TaskUpdated = 'task.updated';
    case TaskCompleted = 'task.completed';
    case BillingSubscriptionChanged = 'billing.subscription_changed';
    case BillingSubscriptionCanceled = 'billing.subscription_canceled';
    case BillingPaymentFailed = 'billing.payment_failed';
    case BillingInvoicePaid = 'billing.invoice_paid';
    case ApiTokenCreated = 'api_token.created';
    case ApiTokenRevoked = 'api_token.revoked';
}
