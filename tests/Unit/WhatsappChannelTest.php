<?php

namespace Laraditz\Whatsapp\Tests\Unit;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Laraditz\Whatsapp\Channels\WhatsappChannel;
use Laraditz\Whatsapp\Facades\Whatsapp;
use Laraditz\Whatsapp\Messages\WhatsappMessage;
use Laraditz\Whatsapp\Responses\MessageResponse;
use Laraditz\Whatsapp\Tests\TestCase;

class WhatsappChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('whatsapp.logging.messages', false);
        config()->set('whatsapp.logging.api_requests', false);
    }

    public function test_sends_text_notification(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.test']]])]);

        $channel = app(WhatsappChannel::class);
        $response = $channel->send(new TestNotifiable(), new TextNotification());

        $this->assertInstanceOf(MessageResponse::class, $response);
        $this->assertSame('wamid.test', $response->messageId());

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['to'] === '60123456789'
                && $body['type'] === 'text'
                && $body['text']['body'] === 'Hello from notification!';
        });
    }

    public function test_sends_template_notification_with_components(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.tpl']]])]);

        $channel = app(WhatsappChannel::class);
        $channel->send(new TestNotifiable(), new TemplateNotification());

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['type'] === 'template'
                && $body['template']['name'] === 'order_update'
                && $body['template']['language']['code'] === 'en'
                && $body['template']['components'][0]['type'] === 'body';
        });
    }

    public function test_returns_null_when_no_phone_number(): void
    {
        $channel = app(WhatsappChannel::class);
        $response = $channel->send(new NoPhoneNotifiable(), new TextNotification());

        $this->assertNull($response);

        Http::assertNothingSent();
    }

    public function test_sends_with_custom_account(): void
    {
        config()->set('whatsapp.accounts.support', [
            'access_token' => 'support-token',
            'phone_number_id' => 'support-phone-id',
            'business_account_id' => 'support-business-id',
        ]);

        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.acc']]])]);

        $channel = app(WhatsappChannel::class);
        $channel->send(new TestNotifiable(), new AccountNotification());

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'support-phone-id/messages')
                && $request->hasHeader('Authorization', 'Bearer support-token');
        });
    }

    public function test_sends_with_payload_merge(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.merge']]])]);

        $channel = app(WhatsappChannel::class);
        $channel->send(new TestNotifiable(), new PayloadNotification());

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['template']['name'] === 'promo'
                && $body['template']['components'][0]['type'] === 'header';
        });
    }

    public function test_sends_image_notification(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.img']]])]);

        $channel = app(WhatsappChannel::class);
        $channel->send(new TestNotifiable(), new ImageNotification());

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['type'] === 'image'
                && $body['image']['link'] === 'https://example.com/photo.jpg'
                && $body['image']['caption'] === 'Your receipt';
        });
    }
}

class TestNotifiable
{
    public function routeNotificationFor(string $driver, $notification = null): ?string
    {
        return '60123456789';
    }
}

class NoPhoneNotifiable
{
    public function routeNotificationFor(string $driver, $notification = null): ?string
    {
        return null;
    }
}

class TextNotification extends Notification
{
    public function toWhatsapp($notifiable): WhatsappMessage
    {
        return WhatsappMessage::create()->text('Hello from notification!');
    }
}

class TemplateNotification extends Notification
{
    public function toWhatsapp($notifiable): WhatsappMessage
    {
        return WhatsappMessage::create()
            ->template(name: 'order_update', language: 'en')
            ->component(type: 'body', parameters: [
                ['type' => 'text', 'text' => 'ORDER-123'],
            ]);
    }
}

class AccountNotification extends Notification
{
    public function toWhatsapp($notifiable): WhatsappMessage
    {
        return WhatsappMessage::create()
            ->account('support')
            ->text('Hello from support!');
    }
}

class PayloadNotification extends Notification
{
    public function toWhatsapp($notifiable): WhatsappMessage
    {
        return WhatsappMessage::create()
            ->template(name: 'promo', language: 'en')
            ->payload(['template' => ['components' => [
                ['type' => 'header', 'parameters' => [['type' => 'image', 'image' => ['link' => 'https://example.com/img.jpg']]]],
            ]]]);
    }
}

class ImageNotification extends Notification
{
    public function toWhatsapp($notifiable): WhatsappMessage
    {
        return WhatsappMessage::create()
            ->image(link: 'https://example.com/photo.jpg', caption: 'Your receipt');
    }
}
