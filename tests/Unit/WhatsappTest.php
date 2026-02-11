<?php

namespace Laraditz\Whatsapp\Tests\Unit;

use Laraditz\Whatsapp\Facades\Whatsapp;
use Laraditz\Whatsapp\Services\MessageService;
use Laraditz\Whatsapp\Services\TemplateService;
use Laraditz\Whatsapp\Tests\TestCase;

class WhatsappTest extends TestCase
{
    public function test_facade_resolves_whatsapp_instance(): void
    {
        $this->assertInstanceOf(\Laraditz\Whatsapp\Whatsapp::class, Whatsapp::getFacadeRoot());
    }

    public function test_message_returns_message_service(): void
    {
        $service = Whatsapp::message();

        $this->assertInstanceOf(MessageService::class, $service);
    }

    public function test_template_returns_template_service(): void
    {
        $service = Whatsapp::template();

        $this->assertInstanceOf(TemplateService::class, $service);
    }

    public function test_account_returns_new_instance(): void
    {
        $default = Whatsapp::getFacadeRoot();
        $switched = Whatsapp::account('default');

        $this->assertNotSame($default, $switched);
    }
}
