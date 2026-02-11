<?php

namespace Laraditz\Whatsapp\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Laraditz\Whatsapp\DTOs\Account;
use Laraditz\Whatsapp\Exceptions\WhatsappApiException;
use Laraditz\Whatsapp\Exceptions\WhatsappAuthException;
use Laraditz\Whatsapp\Exceptions\WhatsappRateLimitException;
use Laraditz\Whatsapp\Services\WhatsappClient;
use Laraditz\Whatsapp\Tests\TestCase;

class WhatsappClientTest extends TestCase
{
    protected Account $account;
    protected WhatsappClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('whatsapp.logging.api_requests', false);

        $this->account = new Account(
            name: 'default',
            accessToken: 'test-token',
            phoneNumberId: 'test-phone-id',
            businessAccountId: 'test-business-id',
        );

        $this->client = new WhatsappClient(
            account: $this->account,
            baseUrl: 'https://graph.facebook.com',
            apiVersion: 'v24.0',
        );
    }

    public function test_get_request_sends_correct_url_and_auth(): void
    {
        Http::fake([
            'graph.facebook.com/v24.0/test-endpoint*' => Http::response(['data' => 'ok']),
        ]);

        $response = $this->client->get(endpoint: 'test-endpoint', data: ['field' => 'value']);

        $this->assertSame(200, $response->status());
        $this->assertSame('ok', $response->json('data'));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com/v24.0/test-endpoint')
                && $request->hasHeader('Authorization', 'Bearer test-token');
        });
    }

    public function test_post_request_sends_json_body(): void
    {
        Http::fake([
            'graph.facebook.com/v24.0/test-endpoint' => Http::response(['success' => true]),
        ]);

        $response = $this->client->post(endpoint: 'test-endpoint', data: ['key' => 'value']);

        $this->assertSame(200, $response->status());

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request['key'] === 'value';
        });
    }

    public function test_throws_auth_exception_on_invalid_token(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token',
                    'type' => 'OAuthException',
                    'code' => 190,
                    'fbtrace_id' => 'trace-123',
                ],
            ], 401),
        ]);

        $this->expectException(WhatsappAuthException::class);

        $this->client->get(endpoint: 'test');
    }

    public function test_throws_rate_limit_exception_on_throttle(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Too many calls',
                    'type' => 'OAuthException',
                    'code' => 4,
                    'fbtrace_id' => 'trace-456',
                ],
            ], 429),
        ]);

        $this->expectException(WhatsappRateLimitException::class);

        $this->client->get(endpoint: 'test');
    }

    public function test_throws_api_exception_on_generic_error(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Something went wrong',
                    'type' => 'APIException',
                    'code' => 100,
                ],
            ], 400),
        ]);

        $this->expectException(WhatsappApiException::class);

        $this->client->get(endpoint: 'test');
    }
}
