<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;
use Modules\Billing\Models\Plan;
use Modules\Membership\Models\Membership;
use Modules\Project\Models\Project;
use Modules\Tenant\Database\Factories\TenantsFactory;
use Modules\User\Models\User;

class Tenants extends Model
{
    use Billable, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'plan_id',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'billing_status',
        'grace_period_ends_at',
        'delinquent_since',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'grace_period_ends_at' => 'datetime',
            'delinquent_since' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'tenant_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'tenant_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships', 'tenant_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    protected static function newFactory(): TenantsFactory
    {
        return TenantsFactory::new();
    }
}
