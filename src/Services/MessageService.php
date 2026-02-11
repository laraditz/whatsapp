<?php

namespace Laraditz\Whatsapp\Services;

use Laraditz\Whatsapp\Enums\MessageDirection;
use Laraditz\Whatsapp\Enums\MessageStatus;
use Laraditz\Whatsapp\Models\WhatsappMessage;
use Laraditz\Whatsapp\Responses\MessageResponse;

class MessageService
{
    protected array $data = [];
    protected array $mergePayload = [];

    public function __construct(
        protected WhatsappClient $client,
    ) {
        $this->data['messaging_product'] = 'whatsapp';
    }

    public function to(string $phoneNumber): static
    {
        $this->data['to'] = $phoneNumber;

        return $this;
    }

    public function text(string $body, bool $previewUrl = false): static
    {
        $this->resetType();
        $this->data['type'] = 'text';
        $this->data['text'] = [
            'preview_url' => $previewUrl,
            'body' => $body,
        ];

        return $this;
    }

    public function image(string $link, ?string $caption = null): static
    {
        $this->resetType();
        $this->data['type'] = 'image';
        $this->data['image'] = array_filter([
            'link' => $link,
            'caption' => $caption,
        ]);

        return $this;
    }

    public function video(string $link, ?string $caption = null): static
    {
        $this->resetType();
        $this->data['type'] = 'video';
        $this->data['video'] = array_filter([
            'link' => $link,
            'caption' => $caption,
        ]);

        return $this;
    }

    public function audio(string $link): static
    {
        $this->resetType();
        $this->data['type'] = 'audio';
        $this->data['audio'] = [
            'link' => $link,
        ];

        return $this;
    }

    public function document(string $link, ?string $filename = null, ?string $caption = null): static
    {
        $this->resetType();
        $this->data['type'] = 'document';
        $this->data['document'] = array_filter([
            'link' => $link,
            'filename' => $filename,
            'caption' => $caption,
        ]);

        return $this;
    }

    public function sticker(string $link): static
    {
        $this->resetType();
        $this->data['type'] = 'sticker';
        $this->data['sticker'] = [
            'link' => $link,
        ];

        return $this;
    }

    public function location(float $latitude, float $longitude, ?string $name = null, ?string $address = null): static
    {
        $this->resetType();
        $this->data['type'] = 'location';
        $this->data['location'] = array_filter([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'name' => $name,
            'address' => $address,
        ]);

        return $this;
    }

    public function contact(array $contacts): static
    {
        $this->resetType();
        $this->data['type'] = 'contacts';
        $this->data['contacts'] = $contacts;

        return $this;
    }

    public function interactive(array $interactive): static
    {
        $this->resetType();
        $this->data['type'] = 'interactive';
        $this->data['interactive'] = $interactive;

        return $this;
    }

    public function template(string $name, string $language): static
    {
        $this->resetType();
        $this->data['type'] = 'template';
        $this->data['template'] = [
            'name' => $name,
            'language' => [
                'code' => $language,
            ],
        ];

        return $this;
    }

    public function component(string $type, array $parameters): static
    {
        $this->data['template']['components'][] = [
            'type' => $type,
            'parameters' => $parameters,
        ];

        return $this;
    }

    public function reaction(string $messageId, string $emoji): static
    {
        $this->resetType();
        $this->data['type'] = 'reaction';
        $this->data['reaction'] = [
            'message_id' => $messageId,
            'emoji' => $emoji,
        ];

        return $this;
    }

    public function payload(array $payload): static
    {
        $this->mergePayload = array_replace_recursive($this->mergePayload, $payload);

        return $this;
    }

    public function send(): MessageResponse
    {
        $data = array_replace_recursive($this->data, $this->mergePayload);

        $phoneNumberId = $this->client->getAccount()->phoneNumberId;

        $response = $this->client->post(
            endpoint: "{$phoneNumberId}/messages",
            data: $data,
        );

        $messageResponse = new MessageResponse(data: $response->json() ?? []);

        $this->logMessage(data: $data, response: $messageResponse);

        $this->reset();

        return $messageResponse;
    }

    protected function logMessage(array $data, MessageResponse $response): void
    {
        if (! (config('whatsapp.logging.messages') ?? false)) {
            return;
        }

        WhatsappMessage::create([
            'account_name' => $this->client->getAccount()->name,
            'wa_message_id' => $response->messageId(),
            'direction' => MessageDirection::Outbound,
            'to' => $data['to'] ?? null,
            'from' => $this->client->getAccount()->phoneNumberId,
            'type' => $data['type'] ?? 'unknown',
            'content' => $data,
            'status' => $response->isSuccessful() ? MessageStatus::Sent : MessageStatus::Failed,
            'status_at' => now(),
        ]);
    }

    protected function resetType(): void
    {
        $currentType = $this->data['type'] ?? null;

        if ($currentType) {
            unset($this->data[$currentType], $this->data['type']);
        }
    }

    protected function reset(): void
    {
        $this->data = ['messaging_product' => 'whatsapp'];
        $this->mergePayload = [];
    }
}
