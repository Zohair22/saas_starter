<?php

namespace Modules\Project\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function listForTenant(int $tenantId, array $filters = []): Collection|LengthAwarePaginator
    {
        $query = Project::query()
            ->with('creator:id,name,email')
            ->where('tenant_id', $tenantId);

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        match ((string) ($filters['sort'] ?? 'updated_desc')) {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'updated_asc' => $query->orderBy('updated_at'),
            default => $query->orderByDesc('updated_at'),
        };

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : null;

        if ($perPage !== null && $perPage > 0) {
            $page = max((int) ($filters['page'] ?? 1), 1);

            return $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();
        }

        return $query->get();
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
