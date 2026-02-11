<?php

namespace Laraditz\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappApiLog extends Model
{
    protected $table = 'whatsapp_api_logs';

    protected $fillable = [
        'account_name',
        'method',
        'endpoint',
        'request_payload',
        'response_payload',
        'status_code',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }
}
