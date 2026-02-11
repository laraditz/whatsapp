<?php

namespace Laraditz\Whatsapp\DTOs;

class Account
{
    public function __construct(
        public string $name,
        public string $accessToken,
        public string $phoneNumberId,
        public string $businessAccountId,
        public ?string $webhookVerifyToken = null,
        public ?string $webhookSecret = null,
    ) {}
}
