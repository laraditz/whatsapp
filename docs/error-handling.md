# Error Handling

The package throws typed exceptions for different error scenarios, allowing you to handle each case specifically.

## Exception Hierarchy

```
WhatsappException (base)
├── WhatsappApiException (API errors with error details)
│   ├── WhatsappAuthException (authentication/token errors)
│   └── WhatsappRateLimitException (rate limiting/throttling)
└── AccountNotFoundException (account not found in repository)
```

## Catching Exceptions

```php
use Laraditz\Whatsapp\Exceptions\AccountNotFoundException;
use Laraditz\Whatsapp\Exceptions\WhatsappApiException;
use Laraditz\Whatsapp\Exceptions\WhatsappAuthException;
use Laraditz\Whatsapp\Exceptions\WhatsappRateLimitException;
use Laraditz\Whatsapp\Facades\Whatsapp;

try {
    Whatsapp::message()->to('60123456789')->text('Hello')->send();
} catch (WhatsappAuthException $e) {
    // Invalid or expired access token
    // Error code 190, or sub-codes 463, 460
    Log::error('Auth failed', $e->toArray());

} catch (WhatsappRateLimitException $e) {
    // Too many API calls
    // Error codes 4, 80007
    Log::warning('Rate limited, retry later', $e->toArray());

} catch (WhatsappApiException $e) {
    // Any other API error (validation, permissions, etc.)
    Log::error('API error', $e->toArray());

} catch (AccountNotFoundException $e) {
    // Specified account doesn't exist in config or database
    Log::error($e->getMessage());
}
```

## WhatsappApiException Details

All API exceptions carry detailed error information from the WhatsApp API:

```php
try {
    Whatsapp::message()->to('invalid')->text('Hi')->send();
} catch (WhatsappApiException $e) {
    $e->getMessage();     // 'Invalid parameter'
    $e->getCode();        // 100
    $e->getSubCode();     // null or specific sub-code
    $e->getFbTraceId();   // 'AbCdEfG123' (useful for Meta support)
    $e->getErrorType();   // 'OAuthException'
    $e->toArray();        // Full error details as array
}
```

## Error Codes Reference

| Code | Sub-code | Exception | Meaning |
|------|----------|-----------|---------|
| 190 | - | `WhatsappAuthException` | Invalid access token |
| - | 463 | `WhatsappAuthException` | Token expired |
| - | 460 | `WhatsappAuthException` | Password changed |
| 4 | - | `WhatsappRateLimitException` | Too many API calls |
| 80007 | - | `WhatsappRateLimitException` | Rate limit reached |
| 100 | - | `WhatsappApiException` | Invalid parameter |
| 131000-131099 | - | `WhatsappApiException` | Message-specific errors |

## Account Not Found

```php
try {
    Whatsapp::account('nonexistent')->message()->to('601234')->text('Hi')->send();
} catch (AccountNotFoundException $e) {
    // "WhatsApp account [nonexistent] not found in config."
    // or "WhatsApp account [nonexistent] not found in database."
}
```

## Catching All Package Exceptions

Use the base `WhatsappException` to catch everything:

```php
use Laraditz\Whatsapp\Exceptions\WhatsappException;

try {
    Whatsapp::message()->to('60123456789')->text('Hello')->send();
} catch (WhatsappException $e) {
    // Catches all: API errors, auth, rate limit, account not found
    report($e);
}
```
