<?php

namespace Modules\Project\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\User\Models\User;

class ProjectDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $projectId,
        public readonly string $projectName,
        public readonly int $tenantId,
        public readonly User $actor,
    ) {}
}
