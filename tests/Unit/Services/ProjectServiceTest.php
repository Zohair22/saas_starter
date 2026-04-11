<?php

namespace Tests\Unit\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Project\Classes\DTOs\CreateProjectData;
use Modules\Project\Classes\DTOs\UpdateProjectData;
use Modules\Project\Events\ProjectCreated;
use Modules\Project\Events\ProjectDeleted;
use Modules\Project\Events\ProjectUpdated;
use Modules\Project\Interfaces\Contracts\ProjectRepositoryInterface;
use Modules\Project\Models\Project;
use Modules\Project\Services\ProjectService;
use Modules\User\Models\User;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    private ProjectRepositoryInterface $repository;

    private ProjectService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(ProjectRepositoryInterface::class);
        $this->service = new ProjectService($this->repository);
    }

    public function test_list_for_tenant_delegates_to_repository(): void
    {
        $collection = new Collection;
        $this->repository->shouldReceive('listForTenant')->with(5)->andReturn($collection);

        $result = $this->service->listForTenant(5);

        $this->assertSame($collection, $result);
    }

    public function test_create_calls_repository_and_dispatches_project_created_event(): void
    {
        Event::fake([ProjectCreated::class]);

        $project = Mockery::mock(Project::class);
        $actor = Mockery::mock(User::class);
        $data = new CreateProjectData(tenantId: 1, createdBy: 2, name: 'Alpha', description: null);

        $this->repository->shouldReceive('create')->with($data)->andReturn($project);

        $result = $this->service->create($data, $actor);

        $this->assertSame($project, $result);
        Event::assertDispatched(ProjectCreated::class, function (ProjectCreated $event) use ($project, $actor): bool {
            return $event->project === $project && $event->actor === $actor;
        });
    }

    public function test_update_calls_repository_and_dispatches_project_updated_event(): void
    {
        Event::fake([ProjectUpdated::class]);

        $project = Mockery::mock(Project::class);
        $updated = Mockery::mock(Project::class);
        $actor = Mockery::mock(User::class);
        $data = new UpdateProjectData(name: 'Beta', description: null);

        $this->repository->shouldReceive('update')->with($project, $data)->andReturn($updated);

        $result = $this->service->update($project, $data, $actor);

        $this->assertSame($updated, $result);
        Event::assertDispatched(ProjectUpdated::class, function (ProjectUpdated $event) use ($updated, $actor): bool {
            return $event->project === $updated && $event->actor === $actor;
        });
    }

    public function test_delete_calls_repository_and_dispatches_project_deleted_event(): void
    {
        Event::fake([ProjectDeleted::class]);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(99);
        $project->shouldReceive('getAttribute')->with('name')->andReturn('Old Project');
        $project->shouldReceive('getAttribute')->with('tenant_id')->andReturn(7);

        $actor = Mockery::mock(User::class);

        $this->repository->shouldReceive('delete')->with($project)->once();

        $this->service->delete($project, $actor);

        Event::assertDispatched(ProjectDeleted::class, function (ProjectDeleted $event) use ($actor): bool {
            return $event->projectId === 99
                && $event->projectName === 'Old Project'
                && $event->tenantId === 7
                && $event->actor === $actor;
        });
    }
}
