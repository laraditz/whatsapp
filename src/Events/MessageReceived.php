<?php

namespace Laraditz\Whatsapp\Events;

use Illuminate\Foundation\Events\Dispatchable;

class MessageReceived
{
    use Dispatchable;

    public function __construct(
        public string $from,
        public string $message,
        public string $type,
        public string $accountName,
        public array $raw,
    ) {}
}
