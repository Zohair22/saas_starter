<?php

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Membership\Models\Membership;
use Modules\Tenant\Models\Tenants;
use Modules\User\Database\Factories\UserFactory;

#[Fillable(['name', 'email', 'password', 'is_super_admin', 'mfa_enabled', 'mfa_secret', 'mfa_recovery_codes'])]
#[Hidden(['password', 'remember_token', 'mfa_secret', 'mfa_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'bool',
            'mfa_enabled' => 'bool',
            'mfa_secret' => 'encrypted',
            'mfa_recovery_codes' => 'array',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenants::class, 'memberships', 'user_id', 'tenant_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedTenants(): HasMany
    {
        return $this->hasMany(Tenants::class, 'owner_id');
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
