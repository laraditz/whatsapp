<?php

namespace Laraditz\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Laraditz\Whatsapp\Enums\MessageDirection;
use Laraditz\Whatsapp\Enums\MessageStatus;

class WhatsappMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'account_name',
        'wa_message_id',
        'direction',
        'to',
        'from',
        'type',
        'content',
        'status',
        'status_at',
    ];

    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'status' => MessageStatus::class,
            'content' => 'array',
            'status_at' => 'datetime',
        ];
    }
}
