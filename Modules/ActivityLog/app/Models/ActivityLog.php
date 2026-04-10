<?php

namespace Modules\ActivityLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\ActivityLog\Enums\ActivityAction;
use Modules\Tenant\Models\Scopes\TenantScope;
use Modules\Tenant\Models\Tenants;
use Modules\Tenant\Traits\BelongsToTenant;
use Modules\User\Models\User;

class ActivityLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'action' => ActivityAction::class,
            'metadata' => 'array',
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
