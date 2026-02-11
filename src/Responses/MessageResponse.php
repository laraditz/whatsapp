<?php

namespace Laraditz\Whatsapp\Responses;

class MessageResponse extends BaseResponse
{
    public function messageId(): ?string
    {
        return $this->get('messages.0.id');
    }

    public function contacts(): array
    {
        return $this->get('contacts', []);
    }
}
