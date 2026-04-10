<?php

namespace Modules\ActivityLog\Interfaces\Contracts;

use Modules\ActivityLog\Enums\ActivityAction;
use Modules\User\Models\User;

interface ActivityLogServiceInterface
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        int $tenantId,
        User $actor,
        ActivityAction $action,
        ?object $subject = null,
        array $metadata = [],
    ): void;
}
