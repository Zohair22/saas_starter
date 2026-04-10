<?php

namespace Modules\AuditLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\AuditLog\Enums\AuditAction;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            $tenantId = (int) data_get(app()->has('tenant') ? app('tenant') : null, 'id');

            if ($tenantId > 0) {
                $query->where('tenant_id', $tenantId);
            }
        });
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
