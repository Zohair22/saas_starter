<?php

namespace Modules\Project\Interfaces\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\Classes\DTOs\CreateProjectData;
use Modules\Project\Classes\DTOs\UpdateProjectData;
use Modules\Project\Models\Project;
use Modules\User\Models\User;

interface ProjectServiceInterface
{
    /**
     * @param  array{q?:string,sort?:string,per_page?:int,page?:int}  $filters
     */
    public function listForTenant(int $tenantId, array $filters = []): Collection|LengthAwarePaginator;

    public function create(CreateProjectData $data, User $actor): Project;

    public function update(Project $project, UpdateProjectData $data, User $actor): Project;

    public function delete(Project $project, User $actor): void;
}
