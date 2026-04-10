<?php

namespace Modules\Project\Interfaces\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\Classes\DTOs\CreateProjectData;
use Modules\Project\Classes\DTOs\UpdateProjectData;
use Modules\Project\Models\Project;
use Modules\User\Models\User;

interface ProjectServiceInterface
{
    public function listForTenant(int $tenantId): Collection;

    public function create(CreateProjectData $data, User $actor): Project;

    public function update(Project $project, UpdateProjectData $data, User $actor): Project;

    public function delete(Project $project, User $actor): void;
}
