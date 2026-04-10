<?php

namespace Modules\Project\Interfaces\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\Classes\DTOs\CreateProjectData;
use Modules\Project\Classes\DTOs\UpdateProjectData;
use Modules\Project\Models\Project;

interface ProjectRepositoryInterface
{
    public function listForTenant(int $tenantId): Collection;

    public function create(CreateProjectData $data): Project;

    public function update(Project $project, UpdateProjectData $data): Project;

    public function delete(Project $project): void;
}
