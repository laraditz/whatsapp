<?php

namespace Laraditz\Whatsapp\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Laraditz\Whatsapp\Events\MessageDelivered;
use Laraditz\Whatsapp\Events\MessageRead;
use Laraditz\Whatsapp\Events\MessageReceived;
use Laraditz\Whatsapp\Tests\TestCase;

class WebhookTest extends TestCase
{
    public function test_verify_returns_challenge_on_valid_token(): void
    {
        $response = $this->get('/whatsapp/webhook?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test-verify-token',
            'hub_challenge' => 'challenge-string',
        ]));

        $response->assertStatus(200);
        $response->assertSee('challenge-string');
    }

    public function test_verify_returns_403_on_invalid_token(): void
    {
        $response = $this->get('/whatsapp/webhook?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong-token',
            'hub_challenge' => 'challenge-string',
        ]));

        $response->assertStatus(403);
    }

    public function test_handle_dispatches_message_received_event(): void
    {
        Event::fake();
        config()->set('whatsapp.logging.messages', false);
        config()->set('whatsapp.logging.webhooks', false);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'phone_number_id' => 'test-phone-id',
                            'display_phone_number' => '601234',
                        ],
                        'messages' => [[
                            'id' => 'wamid.incoming',
                            'from' => '609999',
                            'type' => 'text',
                            'text' => ['body' => 'Hello from user'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $secret = 'test-secret';
        $signature = 'sha256='.hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/whatsapp/webhook', $payload, [
            'X-Hub-Signature-256' => $signature,
        ]);

        $response->assertOk();

        Event::assertDispatched(MessageReceived::class, function ($event) {
            return $event->from === '609999'
                && $event->message === 'Hello from user'
                && $event->type === 'text';
        });
    }

    public function test_handle_dispatches_status_events(): void
    {
        Event::fake();
        config()->set('whatsapp.logging.messages', false);
        config()->set('whatsapp.logging.webhooks', false);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'phone_number_id' => 'test-phone-id',
                            'display_phone_number' => '601234',
                        ],
                        'statuses' => [
                            ['id' => 'wamid.1', 'status' => 'delivered'],
                            ['id' => 'wamid.2', 'status' => 'read'],
                        ],
                    ],
                ]],
            ]],
        ];

        $secret = 'test-secret';
        $signature = 'sha256='.hash_hmac('sha256', json_encode($payload), $secret);

        $response = $this->postJson('/whatsapp/webhook', $payload, [
            'X-Hub-Signature-256' => $signature,
        ]);

        $response->assertOk();

        Event::assertDispatched(MessageDelivered::class);
        Event::assertDispatched(MessageRead::class);
    }

    public function test_handle_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/whatsapp/webhook', ['entry' => []], [
            'X-Hub-Signature-256' => 'sha256=invalidsignature',
        ]);

        $response->assertStatus(403);
    }
}
