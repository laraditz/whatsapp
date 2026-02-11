<?php

namespace Laraditz\Whatsapp\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Laraditz\Whatsapp\Contracts\AccountRepository;
use Laraditz\Whatsapp\Facades\Whatsapp;
use Laraditz\Whatsapp\Repositories\ConfigAccountRepository;
use Laraditz\Whatsapp\Responses\MessageResponse;
use Laraditz\Whatsapp\Responses\TemplateResponse;
use Laraditz\Whatsapp\Tests\TestCase;

class WhatsappIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('whatsapp.logging.api_requests', false);
        config()->set('whatsapp.logging.messages', false);
        config()->set('whatsapp.logging.templates', false);
    }

    public function test_service_provider_registers_account_repository(): void
    {
        $repo = $this->app->make(AccountRepository::class);

        $this->assertInstanceOf(ConfigAccountRepository::class, $repo);
    }

    public function test_facade_sends_text_message(): void
    {
        Http::fake([
            '*/test-phone-id/messages' => Http::response([
                'messages' => [['id' => 'wamid.integration']],
                'contacts' => [['input' => '601234', 'wa_id' => '601234']],
            ]),
        ]);

        $response = Whatsapp::message()
            ->to('601234')
            ->text('Integration test')
            ->send();

        $this->assertInstanceOf(MessageResponse::class, $response);
        $this->assertSame('wamid.integration', $response->messageId());
    }

    public function test_facade_lists_templates(): void
    {
        Http::fake([
            '*/test-business-id/message_templates*' => Http::response([
                'data' => [
                    ['id' => '1', 'name' => 'hello', 'status' => 'APPROVED'],
                ],
            ]),
        ]);

        $response = Whatsapp::template()->list();

        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertCount(1, $response->templates());
    }

    public function test_account_switching(): void
    {
        config()->set('whatsapp.accounts.support', [
            'access_token' => 'support-token',
            'phone_number_id' => 'support-phone-id',
            'business_account_id' => 'support-business-id',
        ]);

        Http::fake([
            '*/support-phone-id/messages' => Http::response([
                'messages' => [['id' => 'wamid.support']],
            ]),
        ]);

        $response = Whatsapp::account('support')
            ->message()
            ->to('601234')
            ->text('From support')
            ->send();

        $this->assertSame('wamid.support', $response->messageId());

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'support-phone-id')
                && $request->hasHeader('Authorization', 'Bearer support-token');
        });
    }
}
