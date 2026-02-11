<?php

namespace Laraditz\Whatsapp\Responses;

use Illuminate\Support\Arr;

abstract class BaseResponse
{
    public function __construct(protected array $data) {}

    public function isSuccessful(): bool
    {
        return ! isset($this->data['error']);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }
}
