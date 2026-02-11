<?php

namespace Laraditz\Whatsapp\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Laraditz\Whatsapp\DTOs\Account;
use Laraditz\Whatsapp\Responses\MessageResponse;
use Laraditz\Whatsapp\Services\MessageService;
use Laraditz\Whatsapp\Services\WhatsappClient;
use Laraditz\Whatsapp\Tests\TestCase;

class MessageServiceTest extends TestCase
{
    protected MessageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('whatsapp.logging.messages', false);
        config()->set('whatsapp.logging.api_requests', false);

        $account = new Account(
            name: 'default',
            accessToken: 'test-token',
            phoneNumberId: 'test-phone-id',
            businessAccountId: 'test-business-id',
        );

        $client = new WhatsappClient(
            account: $account,
            baseUrl: 'https://graph.facebook.com',
            apiVersion: 'v24.0',
        );

        $this->service = new MessageService(client: $client);
    }

    public function test_send_text_message(): void
    {
        Http::fake([
            '*/test-phone-id/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '601234', 'wa_id' => '601234']],
                'messages' => [['id' => 'wamid.test123']],
            ]),
        ]);

        $response = $this->service->to('601234')->text('Hello!')->send();

        $this->assertInstanceOf(MessageResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('wamid.test123', $response->messageId());

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['messaging_product'] === 'whatsapp'
                && $body['to'] === '601234'
                && $body['type'] === 'text'
                && $body['text']['body'] === 'Hello!';
        });
    }

    public function test_send_image_message(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.img']]])]);

        $this->service
            ->to('601234')
            ->image(link: 'https://example.com/photo.jpg', caption: 'My photo')
            ->send();

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['type'] === 'image'
                && $body['image']['link'] === 'https://example.com/photo.jpg'
                && $body['image']['caption'] === 'My photo';
        });
    }

    public function test_send_template_message_with_components(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.tpl']]])]);

        $this->service
            ->to('601234')
            ->template(name: 'order_update', language: 'en')
            ->component(type: 'body', parameters: [
                ['type' => 'text', 'text' => 'ORDER-123'],
            ])
            ->send();

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['type'] === 'template'
                && $body['template']['name'] === 'order_update'
                && $body['template']['language']['code'] === 'en'
                && $body['template']['components'][0]['type'] === 'body';
        });
    }

    public function test_payload_merges_with_chain(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.merge']]])]);

        $this->service
            ->to('601234')
            ->template(name: 'hello', language: 'en')
            ->payload(['template' => ['components' => [
                ['type' => 'header', 'parameters' => [['type' => 'image', 'image' => ['link' => 'https://example.com/img.jpg']]]],
            ]]])
            ->send();

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['template']['name'] === 'hello'
                && $body['template']['language']['code'] === 'en'
                && $body['template']['components'][0]['type'] === 'header';
        });
    }

    public function test_type_resets_when_switching(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.switch']]])]);

        $this->service
            ->to('601234')
            ->text('first')
            ->image(link: 'https://example.com/img.jpg')
            ->send();

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['type'] === 'image'
                && ! isset($body['text']);
        });
    }

    public function test_service_resets_after_send(): void
    {
        Http::fakeSequence('*')
            ->push(['messages' => [['id' => 'wamid.1']]])
            ->push(['messages' => [['id' => 'wamid.2']]]);

        $this->service->to('601234')->text('First')->send();

        $response = $this->service->to('609999')->text('Second')->send();

        $this->assertSame('wamid.2', $response->messageId());
    }
}
