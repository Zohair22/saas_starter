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

class Invitation extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'email',
        'role',
        'token',
        'invited_by',
        'expires_at',
        'accepted_at',
        'revoked_at',
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
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenants::class, 'tenant_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
