<?php

namespace Modules\Project\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\Classes\DTOs\CreateProjectData;
use Modules\Project\Classes\DTOs\UpdateProjectData;
use Modules\Project\Events\ProjectCreated;
use Modules\Project\Events\ProjectDeleted;
use Modules\Project\Events\ProjectUpdated;
use Modules\Project\Interfaces\Contracts\ProjectRepositoryInterface;
use Modules\Project\Interfaces\Contracts\ProjectServiceInterface;
use Modules\Project\Models\Project;
use Modules\User\Models\User;

class ProjectService implements ProjectServiceInterface
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    public function listForTenant(int $tenantId): Collection
    {
        return $this->projectRepository->listForTenant($tenantId);
    }

    public function create(CreateProjectData $data, User $actor): Project
    {
        $project = $this->projectRepository->create($data);

        ProjectCreated::dispatch($project, $actor);

        return $project;
    }

    public function update(Project $project, UpdateProjectData $data, User $actor): Project
    {
        $project = $this->projectRepository->update($project, $data);

        ProjectUpdated::dispatch($project, $actor);

        return $project;
    }

    public function delete(Project $project, User $actor): void
    {
        $projectId = $project->id;
        $projectName = $project->name;
        $tenantId = (int) $project->tenant_id;

        $this->projectRepository->delete($project);

        ProjectDeleted::dispatch($projectId, $projectName, $tenantId, $actor);
    }
}
