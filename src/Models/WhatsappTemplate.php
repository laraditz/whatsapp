<?php

namespace Laraditz\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Laraditz\Whatsapp\Enums\TemplateStatus;

class WhatsappTemplate extends Model
{
    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'account_name',
        'wa_template_id',
        'name',
        'language',
        'category',
        'status',
        'components',
    ];

    protected function casts(): array
    {
        return [
            'status' => TemplateStatus::class,
            'components' => 'array',
        ];
    }
}
