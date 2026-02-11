<?php

namespace Laraditz\Whatsapp\Facades;

use Illuminate\Support\Facades\Facade;
use Laraditz\Whatsapp\Services\MessageService;
use Laraditz\Whatsapp\Services\TemplateService;

/**
 * @method static \Laraditz\Whatsapp\Whatsapp account(string $name)
 * @method static MessageService message()
 * @method static TemplateService template()
 *
 * @see \Laraditz\Whatsapp\Whatsapp
 */
class Whatsapp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'whatsapp';
    }
}
