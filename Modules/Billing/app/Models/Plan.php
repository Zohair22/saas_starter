<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'stripe_price_id',
        'max_users',
        'max_projects',
        'api_rate_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_users' => 'integer',
            'max_projects' => 'integer',
            'api_rate_limit' => 'integer',
        ];
    }
}
