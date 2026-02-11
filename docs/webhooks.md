# Webhooks & Events

## Webhook Setup

The package automatically registers webhook routes at the configured path (default: `/whatsapp/webhook`).

### Meta App Configuration

In your [Meta App Dashboard](https://developers.facebook.com/apps/):

1. Go to **WhatsApp > Configuration**
2. Set **Callback URL** to: `https://yourdomain.com/whatsapp/webhook`
3. Set **Verify Token** to the value of your `WHATSAPP_WEBHOOK_VERIFY_TOKEN` env variable
4. Subscribe to the webhook fields you need (e.g., `messages`)

### Custom Webhook Path

```env
WHATSAPP_WEBHOOK_PATH=api/whatsapp/webhook
```

This changes the route to `/api/whatsapp/webhook`.

## Webhook Verification

The package handles the GET verification challenge automatically. When Meta sends the verification request, the controller:

1. Checks `hub.verify_token` against all configured account `webhook_verify_token` values
2. Returns the `hub.challenge` value on match
3. Returns 403 on mismatch

## Incoming Webhooks

POST requests are validated using the `X-Hub-Signature-256` header against the `webhook_secret` of each configured account. Invalid signatures are rejected with 403.

## Laravel Events

The package dispatches three events based on the webhook payload:

### MessageReceived

Dispatched when a user sends a message to your WhatsApp number.

```php
use Laraditz\Whatsapp\Events\MessageReceived;

Event::listen(MessageReceived::class, function (MessageReceived $event) {
    $event->from;        // Sender's phone number
    $event->message;     // Message body (text content or type placeholder)
    $event->type;        // Message type: 'text', 'image', 'video', etc.
    $event->accountName; // Which WhatsApp account received it
    $event->raw;         // Full raw message data from the API
});
```

### MessageDelivered

Dispatched when a sent message is delivered to the recipient.

```php
use Laraditz\Whatsapp\Events\MessageDelivered;

Event::listen(MessageDelivered::class, function (MessageDelivered $event) {
    $event->messageId;   // WhatsApp message ID (wamid.xxx)
    $event->accountName; // Account name
    $event->raw;         // Full raw status data
});
```

### MessageRead

Dispatched when a sent message is read by the recipient.

```php
use Laraditz\Whatsapp\Events\MessageRead;

Event::listen(MessageRead::class, function (MessageRead $event) {
    $event->messageId;   // WhatsApp message ID
    $event->accountName; // Account name
    $event->raw;         // Full raw status data
});
```

## Using Event Listeners

### In a Service Provider

```php
use Illuminate\Support\Facades\Event;
use Laraditz\Whatsapp\Events\MessageReceived;

// In boot() of a service provider
Event::listen(MessageReceived::class, function (MessageReceived $event) {
    Log::info("Message from {$event->from}: {$event->message}");
});
```

### With a Listener Class

```php
// app/Listeners/HandleWhatsappMessage.php
namespace App\Listeners;

use Laraditz\Whatsapp\Events\MessageReceived;
use Laraditz\Whatsapp\Facades\Whatsapp;

class HandleWhatsappMessage
{
    public function handle(MessageReceived $event): void
    {
        // Auto-reply
        Whatsapp::account($event->accountName)
            ->message()
            ->to($event->from)
            ->text("Thanks for your message! We'll get back to you soon.")
            ->send();
    }
}
```

Register in `EventServiceProvider` or use event discovery.

## Message Status Tracking

When `logging.messages` is enabled, the package automatically:

- Creates a record in `whatsapp_messages` for inbound messages
- Updates the `status` field when delivery/read receipts arrive (`sent` -> `delivered` -> `read`)

For messages that may have missed status updates:

```bash
# Sync message statuses from the API
php artisan whatsapp:sync-messages

# For a specific account and date range
php artisan whatsapp:sync-messages --account=default --since=2024-01-01
```

## Webhook Logging

When `logging.webhooks` is enabled, every webhook payload is logged to the `whatsapp_webhook_logs` table with the event type (`message`, `status`) and full payload for debugging.
