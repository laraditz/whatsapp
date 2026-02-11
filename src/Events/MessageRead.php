<?php

namespace Laraditz\Whatsapp\Events;

use Illuminate\Foundation\Events\Dispatchable;

class MessageRead
{
    use Dispatchable;

    public function __construct(
        public string $messageId,
        public string $accountName,
        public array $raw,
    ) {}
}
