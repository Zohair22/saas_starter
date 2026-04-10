<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_event_id',
        'type',
        'payload_hash',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
