<?php

namespace Laraditz\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappAccount extends Model
{
    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'name',
        'access_token',
        'phone_number_id',
        'business_account_id',
        'webhook_verify_token',
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'webhook_secret' => 'encrypted',
        ];
    }
}
