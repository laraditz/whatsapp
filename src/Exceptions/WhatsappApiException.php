<?php

namespace Laraditz\Whatsapp\Exceptions;

class WhatsappApiException extends WhatsappException
{
    protected ?int $subCode;
    protected ?string $fbTraceId;
    protected ?string $errorType;
    protected array $errorData;

    public function __construct(
        string $message,
        int $code = 0,
        ?int $subCode = null,
        ?string $fbTraceId = null,
        ?string $errorType = null,
        array $errorData = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->subCode = $subCode;
        $this->fbTraceId = $fbTraceId;
        $this->errorType = $errorType;
        $this->errorData = $errorData;
    }

    public function getSubCode(): ?int
    {
        return $this->subCode;
    }

    public function getFbTraceId(): ?string
    {
        return $this->fbTraceId;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'sub_code' => $this->subCode,
            'fbtrace_id' => $this->fbTraceId,
            'type' => $this->errorType,
            'data' => $this->errorData,
        ];
    }

    public static function fromResponse(array $error): static
    {
        return new static(
            message: $error['message'] ?? 'Unknown WhatsApp API error',
            code: $error['code'] ?? 0,
            subCode: $error['error_subcode'] ?? null,
            fbTraceId: $error['fbtrace_id'] ?? null,
            errorType: $error['type'] ?? null,
            errorData: $error,
        );
    }
}
