<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Models\Scopes\TenantScope;
use Modules\Tenant\Traits\BelongsToTenant;

class TenantUsageCounter extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'period_start',
        'users_count',
        'projects_count',
        'api_requests_count',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected function casts(): array
    {
        return [
            'users_count' => 'integer',
            'projects_count' => 'integer',
            'api_requests_count' => 'integer',
        ];
    }
}
