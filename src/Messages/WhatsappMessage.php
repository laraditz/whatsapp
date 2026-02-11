<?php

namespace Laraditz\Whatsapp\Messages;

class WhatsappMessage
{
    public ?string $account = null;
    public ?string $type = null;
    public array $data = [];
    public array $components = [];
    public ?array $customPayload = null;

    public static function create(): static
    {
        return new static();
    }

    public function account(string $name): static
    {
        $this->account = $name;

        return $this;
    }

    public function text(string $body, bool $previewUrl = false): static
    {
        $this->type = 'text';
        $this->data = ['body' => $body, 'previewUrl' => $previewUrl];

        return $this;
    }

    public function image(string $link, ?string $caption = null): static
    {
        $this->type = 'image';
        $this->data = ['link' => $link, 'caption' => $caption];

        return $this;
    }

    public function video(string $link, ?string $caption = null): static
    {
        $this->type = 'video';
        $this->data = ['link' => $link, 'caption' => $caption];

        return $this;
    }

    public function audio(string $link): static
    {
        $this->type = 'audio';
        $this->data = ['link' => $link];

        return $this;
    }

    public function document(string $link, ?string $filename = null, ?string $caption = null): static
    {
        $this->type = 'document';
        $this->data = ['link' => $link, 'filename' => $filename, 'caption' => $caption];

        return $this;
    }

    public function sticker(string $link): static
    {
        $this->type = 'sticker';
        $this->data = ['link' => $link];

        return $this;
    }

    public function location(float $latitude, float $longitude, ?string $name = null, ?string $address = null): static
    {
        $this->type = 'location';
        $this->data = ['latitude' => $latitude, 'longitude' => $longitude, 'name' => $name, 'address' => $address];

        return $this;
    }

    public function contact(array $contacts): static
    {
        $this->type = 'contact';
        $this->data = ['contacts' => $contacts];

        return $this;
    }

    public function interactive(array $interactive): static
    {
        $this->type = 'interactive';
        $this->data = ['interactive' => $interactive];

        return $this;
    }

    public function template(string $name, string $language): static
    {
        $this->type = 'template';
        $this->data = ['name' => $name, 'language' => $language];
        $this->components = [];

        return $this;
    }

    public function component(string $type, array $parameters): static
    {
        $this->components[] = ['type' => $type, 'parameters' => $parameters];

        return $this;
    }

    public function reaction(string $messageId, string $emoji): static
    {
        $this->type = 'reaction';
        $this->data = ['messageId' => $messageId, 'emoji' => $emoji];

        return $this;
    }

    public function payload(array $payload): static
    {
        $this->customPayload = $payload;

        return $this;
    }
}
