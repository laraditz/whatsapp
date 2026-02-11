<?php

namespace Laraditz\Whatsapp\Services;

use Laraditz\Whatsapp\Models\WhatsappTemplate;
use Laraditz\Whatsapp\Responses\TemplateResponse;

class TemplateService
{
    protected array $mergePayload = [];

    public function __construct(
        protected WhatsappClient $client,
    ) {}

    public function payload(array $payload): static
    {
        $this->mergePayload = array_replace_recursive($this->mergePayload, $payload);

        return $this;
    }

    public function list(array $fields = [], ?int $limit = null, ?string $after = null): TemplateResponse
    {
        $businessAccountId = $this->client->getAccount()->businessAccountId;

        $query = [];

        if (! empty($fields)) {
            $query['fields'] = implode(',', $fields);
        }

        if ($limit !== null) {
            $query['limit'] = $limit;
        }

        if ($after !== null) {
            $query['after'] = $after;
        }

        $response = $this->client->get(
            endpoint: "{$businessAccountId}/message_templates",
            data: $query,
        );

        $templateResponse = new TemplateResponse(data: $response->json() ?? []);

        $templateResponse->setNextPageResolver(
            fn (string $cursor) => $this->list(fields: $fields, limit: $limit, after: $cursor)
        );

        $this->syncTemplates(response: $templateResponse);

        return $templateResponse;
    }

    public function get(string $id): TemplateResponse
    {
        $businessAccountId = $this->client->getAccount()->businessAccountId;

        $response = $this->client->get(
            endpoint: "{$businessAccountId}/message_templates/{$id}",
        );

        return new TemplateResponse(data: $response->json() ?? []);
    }

    public function create(string $name, string $language, string $category, array $components = []): TemplateResponse
    {
        $businessAccountId = $this->client->getAccount()->businessAccountId;

        $data = array_replace_recursive([
            'name' => $name,
            'language' => $language,
            'category' => $category,
            'components' => $components,
        ], $this->mergePayload);

        $response = $this->client->post(
            endpoint: "{$businessAccountId}/message_templates",
            data: $data,
        );

        $this->resetPayload();

        return new TemplateResponse(data: $response->json() ?? []);
    }

    public function update(string $id, array $components = []): TemplateResponse
    {
        $businessAccountId = $this->client->getAccount()->businessAccountId;

        $data = array_replace_recursive([
            'components' => $components,
        ], $this->mergePayload);

        $response = $this->client->post(
            endpoint: "{$businessAccountId}/message_templates/{$id}",
            data: $data,
        );

        $this->resetPayload();

        return new TemplateResponse(data: $response->json() ?? []);
    }

    public function delete(string $name): TemplateResponse
    {
        $businessAccountId = $this->client->getAccount()->businessAccountId;

        $response = $this->client->delete(
            endpoint: "{$businessAccountId}/message_templates",
            data: ['name' => $name],
        );

        if (config('whatsapp.logging.templates') ?? false) {
            WhatsappTemplate::where('account_name', $this->client->getAccount()->name)
                ->where('name', $name)
                ->delete();
        }

        return new TemplateResponse(data: $response->json() ?? []);
    }

    protected function syncTemplates(TemplateResponse $response): void
    {
        if (! (config('whatsapp.logging.templates') ?? false)) {
            return;
        }

        $accountName = $this->client->getAccount()->name;

        foreach ($response->templates() as $template) {
            WhatsappTemplate::updateOrCreate(
                [
                    'account_name' => $accountName,
                    'wa_template_id' => $template['id'],
                ],
                [
                    'name' => $template['name'] ?? '',
                    'language' => $template['language'] ?? '',
                    'category' => $template['category'] ?? '',
                    'status' => $template['status'] ?? '',
                    'components' => $template['components'] ?? [],
                ],
            );
        }
    }

    protected function resetPayload(): void
    {
        $this->mergePayload = [];
    }
}
