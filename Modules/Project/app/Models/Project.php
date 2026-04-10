<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Project\Database\Factories\ProjectFactory;
use Modules\Task\Models\Task;
use Modules\Tenant\Models\Scopes\TenantScope;
use Modules\Tenant\Models\Tenants;
use Modules\Tenant\Traits\BelongsToTenant;
use Modules\User\Models\User;

class Project extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'description',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenants::class, 'tenant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
