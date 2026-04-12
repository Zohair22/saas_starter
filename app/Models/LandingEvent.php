<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'page',
        'cta_id',
        'ab_variant',
        'path',
        'referrer',
        'ip_address',
        'user_agent',
    ];
}
