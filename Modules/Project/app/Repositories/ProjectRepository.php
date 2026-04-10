<?php

namespace Modules\Project\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Billing\Interfaces\Contracts\UsageCounterServiceInterface;
use Modules\Project\Classes\DTOs\CreateProjectData;
use Modules\Project\Classes\DTOs\UpdateProjectData;
use Modules\Project\Interfaces\Contracts\ProjectRepositoryInterface;
use Modules\Project\Models\Project;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(
        private readonly UsageCounterServiceInterface $usageCounterService,
    ) {}

    public function listForTenant(int $tenantId): Collection
    {
        return Project::query()
            ->with('creator:id,name,email')
            ->latest()
            ->get();
    }

    public function create(CreateProjectData $data): Project
    {
        $project = Project::create([
            'created_by' => $data->createdBy,
            'name' => $data->name,
            'description' => $data->description,
        ])->load('creator:id,name,email');

        $this->usageCounterService->incrementProjects((int) $project->tenant_id);

        return $project;
    }

    public function update(Project $project, UpdateProjectData $data): Project
    {
        $project->update([
            'name' => $data->name ?? $project->name,
            'description' => $data->description,
        ]);

        return $project->refresh()->load('creator:id,name,email');
    }

    public function delete(Project $project): void
    {
        $tenantId = (int) $project->tenant_id;
        $project->delete();
        $this->usageCounterService->decrementProjects($tenantId);
    }
}
