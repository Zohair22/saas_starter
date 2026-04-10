<?php

namespace Modules\Membership\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Membership\Enums\MembershipRole;
use Modules\Tenant\Models\Scopes\TenantScope;
use Modules\Tenant\Models\Tenants;
use Modules\Tenant\Traits\BelongsToTenant;
use Modules\User\Models\User;

class Membership extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'tenant_id',
        'role',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => MembershipRole::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenants::class, 'tenant_id');
    }
}
