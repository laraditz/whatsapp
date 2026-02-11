<?php

namespace Laraditz\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappWebhookLog extends Model
{
    protected $table = 'whatsapp_webhook_logs';

    protected $fillable = [
        'account_name',
        'event_type',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
