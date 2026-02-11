<?php

namespace Laraditz\Whatsapp\Exceptions;

use Exception;

class WhatsappException extends Exception
{
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
