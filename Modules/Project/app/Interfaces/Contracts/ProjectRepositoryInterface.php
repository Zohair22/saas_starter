<?php

namespace Modules\Project\Interfaces\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\Classes\DTOs\CreateProjectData;
use Modules\Project\Classes\DTOs\UpdateProjectData;
use Modules\Project\Models\Project;

interface ProjectRepositoryInterface
{
    /**
     * @param  array{q?:string,sort?:string,per_page?:int,page?:int}  $filters
     */
    public function listForTenant(int $tenantId, array $filters = []): Collection|LengthAwarePaginator;

    public function create(CreateProjectData $data): Project;

    public function update(Project $project, UpdateProjectData $data): Project;

    public function delete(Project $project): void;
}
