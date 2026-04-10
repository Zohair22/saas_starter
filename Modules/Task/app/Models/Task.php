<?php

namespace Modules\Task\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Project\Models\Project;
use Modules\Task\Database\Factories\TaskFactory;
use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;
use Modules\Tenant\Models\Scopes\TenantScope;
use Modules\Tenant\Models\Tenants;
use Modules\Tenant\Traits\BelongsToTenant;
use Modules\User\Models\User;

class Task extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'due_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenants::class, 'tenant_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }
}
