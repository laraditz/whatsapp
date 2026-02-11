<?php

namespace Laraditz\Whatsapp\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Laraditz\Whatsapp\DTOs\Account;
use Laraditz\Whatsapp\Responses\TemplateResponse;
use Laraditz\Whatsapp\Services\TemplateService;
use Laraditz\Whatsapp\Services\WhatsappClient;
use Laraditz\Whatsapp\Tests\TestCase;

class TemplateServiceTest extends TestCase
{
    protected TemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('whatsapp.logging.templates', false);
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

        $this->service = new TemplateService(client: $client);
    }

    public function test_list_templates(): void
    {
        Http::fake([
            '*/test-business-id/message_templates*' => Http::response([
                'data' => [
                    ['id' => '123', 'name' => 'hello', 'status' => 'APPROVED'],
                    ['id' => '456', 'name' => 'order', 'status' => 'PENDING'],
                ],
                'paging' => ['cursors' => ['after' => 'cursor123'], 'next' => 'https://...'],
            ]),
        ]);

        $response = $this->service->list();

        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertCount(2, $response->templates());
        $this->assertTrue($response->hasNextPage());
    }

    public function test_list_with_fields_and_limit(): void
    {
        Http::fake(['*' => Http::response(['data' => []])]);

        $this->service->list(fields: ['name', 'status'], limit: 10);

        Http::assertSent(function ($request) {
            $url = urldecode($request->url());

            return str_contains($url, 'fields=name,status')
                && str_contains($url, 'limit=10');
        });
    }

    public function test_get_single_template(): void
    {
        Http::fake([
            '*/test-business-id/message_templates/123' => Http::response([
                'id' => '123',
                'name' => 'hello',
                'status' => 'APPROVED',
                'category' => 'UTILITY',
                'components' => [['type' => 'BODY', 'text' => 'Hi {{1}}']],
            ]),
        ]);

        $response = $this->service->get(id: '123');

        $this->assertSame('123', $response->id());
        $this->assertSame('hello', $response->name());
        $this->assertSame('UTILITY', $response->category());
    }

    public function test_create_template(): void
    {
        Http::fake(['*' => Http::response(['id' => '789', 'status' => 'PENDING'])]);

        $response = $this->service->create(
            name: 'new_template',
            language: 'en',
            category: 'UTILITY',
            components: [['type' => 'BODY', 'text' => 'Hello {{1}}']],
        );

        $this->assertSame('789', $response->id());

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['name'] === 'new_template'
                && $body['language'] === 'en'
                && $body['category'] === 'UTILITY';
        });
    }

    public function test_create_with_payload_merge(): void
    {
        Http::fake(['*' => Http::response(['id' => '789'])]);

        $this->service
            ->payload(['components' => [['type' => 'HEADER', 'format' => 'IMAGE']]])
            ->create(
                name: 'new_template',
                language: 'en',
                category: 'MARKETING',
                components: [['type' => 'BODY', 'text' => 'Hello']],
            );

        Http::assertSent(function ($request) {
            $body = $request->data();

            return isset($body['components']);
        });
    }

    public function test_update_template(): void
    {
        Http::fake(['*' => Http::response(['success' => true])]);

        $this->service->update(
            id: '123',
            components: [['type' => 'BODY', 'text' => 'Updated {{1}}']],
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'message_templates/123')
                && $request->method() === 'POST';
        });
    }

    public function test_delete_template(): void
    {
        Http::fake(['*' => Http::response(['success' => true])]);

        $this->service->delete(name: 'old_template');

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), 'message_templates');
        });
    }
}
