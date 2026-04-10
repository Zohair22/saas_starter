<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Project\Classes\DTOs\CreateProjectData;
use Modules\Project\Classes\DTOs\UpdateProjectData;
use Modules\Project\Http\Requests\StoreProjectRequest;
use Modules\Project\Http\Requests\UpdateProjectRequest;
use Modules\Project\Interfaces\Contracts\ProjectServiceInterface;
use Modules\Project\Models\Project;
use Modules\Project\Transformers\ProjectResource;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectServiceInterface $projectService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $tenantId = (int) data_get(request()->attributes->get('tenant'), 'id');
        $projects = $this->projectService->listForTenant($tenantId);

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projectService->create(CreateProjectData::fromRequest($request), $request->user());

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return ProjectResource::make($project->load('creator:id,name,email'));
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project = $this->projectService->update($project, UpdateProjectData::fromRequest($request), $request->user());

        return ProjectResource::make($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->delete($project, request()->user());

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
