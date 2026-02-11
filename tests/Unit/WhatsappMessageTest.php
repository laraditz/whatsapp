<?php

namespace Laraditz\Whatsapp\Tests\Unit;

use Laraditz\Whatsapp\Messages\WhatsappMessage;
use PHPUnit\Framework\TestCase;

class WhatsappMessageTest extends TestCase
{
    public function test_create_returns_instance(): void
    {
        $message = WhatsappMessage::create();

        $this->assertInstanceOf(WhatsappMessage::class, $message);
        $this->assertNull($message->account);
        $this->assertNull($message->type);
        $this->assertSame([], $message->data);
    }

    public function test_account_sets_account_name(): void
    {
        $message = WhatsappMessage::create()->account('support');

        $this->assertSame('support', $message->account);
    }

    public function test_text_sets_type_and_data(): void
    {
        $message = WhatsappMessage::create()->text('Hello!');

        $this->assertSame('text', $message->type);
        $this->assertSame('Hello!', $message->data['body']);
        $this->assertFalse($message->data['previewUrl']);
    }

    public function test_text_with_preview_url(): void
    {
        $message = WhatsappMessage::create()->text('Check https://example.com', previewUrl: true);

        $this->assertTrue($message->data['previewUrl']);
    }

    public function test_image_sets_type_and_data(): void
    {
        $message = WhatsappMessage::create()->image(link: 'https://example.com/img.jpg', caption: 'Photo');

        $this->assertSame('image', $message->type);
        $this->assertSame('https://example.com/img.jpg', $message->data['link']);
        $this->assertSame('Photo', $message->data['caption']);
    }

    public function test_video_sets_type_and_data(): void
    {
        $message = WhatsappMessage::create()->video(link: 'https://example.com/vid.mp4', caption: 'Video');

        $this->assertSame('video', $message->type);
        $this->assertSame('https://example.com/vid.mp4', $message->data['link']);
        $this->assertSame('Video', $message->data['caption']);
    }

    public function test_audio_sets_type_and_data(): void
    {
        $message = WhatsappMessage::create()->audio(link: 'https://example.com/audio.mp3');

        $this->assertSame('audio', $message->type);
        $this->assertSame('https://example.com/audio.mp3', $message->data['link']);
    }

    public function test_document_sets_type_and_data(): void
    {
        $message = WhatsappMessage::create()->document(
            link: 'https://example.com/doc.pdf',
            filename: 'invoice.pdf',
            caption: 'Invoice',
        );

        $this->assertSame('document', $message->type);
        $this->assertSame('https://example.com/doc.pdf', $message->data['link']);
        $this->assertSame('invoice.pdf', $message->data['filename']);
        $this->assertSame('Invoice', $message->data['caption']);
    }

    public function test_sticker_sets_type_and_data(): void
    {
        $message = WhatsappMessage::create()->sticker(link: 'https://example.com/sticker.webp');

        $this->assertSame('sticker', $message->type);
        $this->assertSame('https://example.com/sticker.webp', $message->data['link']);
    }

    public function test_location_sets_type_and_data(): void
    {
        $message = WhatsappMessage::create()->location(
            latitude: 3.139,
            longitude: 101.687,
            name: 'KL',
            address: 'Kuala Lumpur',
        );

        $this->assertSame('location', $message->type);
        $this->assertSame(3.139, $message->data['latitude']);
        $this->assertSame(101.687, $message->data['longitude']);
        $this->assertSame('KL', $message->data['name']);
    }

    public function test_contact_sets_type_and_data(): void
    {
        $contacts = [['name' => ['formatted_name' => 'John']]];
        $message = WhatsappMessage::create()->contact($contacts);

        $this->assertSame('contact', $message->type);
        $this->assertSame($contacts, $message->data['contacts']);
    }

    public function test_interactive_sets_type_and_data(): void
    {
        $interactive = ['type' => 'button', 'body' => ['text' => 'Choose']];
        $message = WhatsappMessage::create()->interactive($interactive);

        $this->assertSame('interactive', $message->type);
        $this->assertSame($interactive, $message->data['interactive']);
    }

    public function test_template_sets_type_and_data(): void
    {
        $message = WhatsappMessage::create()->template(name: 'hello', language: 'en');

        $this->assertSame('template', $message->type);
        $this->assertSame('hello', $message->data['name']);
        $this->assertSame('en', $message->data['language']);
        $this->assertSame([], $message->components);
    }

    public function test_component_appends_to_components(): void
    {
        $message = WhatsappMessage::create()
            ->template(name: 'order', language: 'en')
            ->component(type: 'header', parameters: [['type' => 'image', 'image' => ['link' => 'https://example.com/img.jpg']]])
            ->component(type: 'body', parameters: [['type' => 'text', 'text' => 'ORDER-123']]);

        $this->assertCount(2, $message->components);
        $this->assertSame('header', $message->components[0]['type']);
        $this->assertSame('body', $message->components[1]['type']);
    }

    public function test_template_resets_components(): void
    {
        $message = WhatsappMessage::create()
            ->template(name: 'first', language: 'en')
            ->component(type: 'body', parameters: [['type' => 'text', 'text' => 'test']])
            ->template(name: 'second', language: 'en');

        $this->assertSame('second', $message->data['name']);
        $this->assertSame([], $message->components);
    }

    public function test_reaction_sets_type_and_data(): void
    {
        $message = WhatsappMessage::create()->reaction(messageId: 'wamid.xxx', emoji: "\u{1F44D}");

        $this->assertSame('reaction', $message->type);
        $this->assertSame('wamid.xxx', $message->data['messageId']);
    }

    public function test_payload_sets_custom_payload(): void
    {
        $payload = ['template' => ['components' => [['type' => 'header']]]];
        $message = WhatsappMessage::create()->payload($payload);

        $this->assertSame($payload, $message->customPayload);
    }

    public function test_switching_type_replaces_previous(): void
    {
        $message = WhatsappMessage::create()
            ->text('Hello')
            ->image(link: 'https://example.com/img.jpg');

        $this->assertSame('image', $message->type);
        $this->assertArrayNotHasKey('body', $message->data);
    }
}
