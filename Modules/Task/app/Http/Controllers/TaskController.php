<?php

namespace Modules\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Project\Models\Project;
use Modules\Task\Classes\DTOs\CreateTaskData;
use Modules\Task\Classes\DTOs\UpdateTaskData;
use Modules\Task\Http\Requests\StoreTaskRequest;
use Modules\Task\Http\Requests\UpdateTaskRequest;
use Modules\Task\Interfaces\Contracts\TaskServiceInterface;
use Modules\Task\Models\Task;
use Modules\Task\Transformers\TaskResource;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskServiceInterface $taskService,
    ) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $this->ensureProjectInTenant($project);
        $this->authorize('viewAny', [Task::class, $project]);

        $validated = request()->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:open,in_progress,done'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'sort' => ['nullable', 'in:updated_desc,updated_asc,due_asc,due_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $tasks = $this->taskService->listForProject($project->id, $validated);

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->ensureProjectInTenant($project);
        $this->authorize('create', [Task::class, $project]);

        $task = $this->taskService->create(
            CreateTaskData::fromRequest($request),
            $request->user(),
        );

        return TaskResource::make($task)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Project $project, Task $task): TaskResource
    {
        $this->ensureProjectInTenant($project);
        abort_if((int) $task->project_id !== (int) $project->id, Response::HTTP_NOT_FOUND);
        $this->authorize('view', $task);

        return TaskResource::make($task->load(['creator:id,name,email', 'assignee:id,name,email']));
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task): TaskResource
    {
        $this->ensureProjectInTenant($project);
        abort_if((int) $task->project_id !== (int) $project->id, Response::HTTP_NOT_FOUND);
        $this->authorize('update', $task);

        $task = $this->taskService->update(
            $task,
            UpdateTaskData::fromRequest($request),
            $request->user(),
        );

        return TaskResource::make($task);
    }

    public function destroy(Project $project, Task $task): JsonResponse
    {
        $this->ensureProjectInTenant($project);
        abort_if((int) $task->project_id !== (int) $project->id, Response::HTTP_NOT_FOUND);
        $this->authorize('delete', $task);

        $this->taskService->delete($task);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    private function ensureProjectInTenant(Project $project): void
    {
        $tenantId = (int) data_get(request()->attributes->get('tenant'), 'id');

        abort_if((int) $project->tenant_id !== $tenantId, Response::HTTP_NOT_FOUND);
    }
}
