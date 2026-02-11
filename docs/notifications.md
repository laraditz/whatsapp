# Laravel Notifications

Send WhatsApp messages using Laravel's built-in notification system.

## Setup

Add the `routeNotificationForWhatsapp` method to your notifiable model:

```php
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public function routeNotificationForWhatsapp($notification): ?string
    {
        return $this->phone_number;
    }
}
```

## Creating a Notification

```php
use Illuminate\Notifications\Notification;
use Laraditz\Whatsapp\Channels\WhatsappChannel;
use Laraditz\Whatsapp\Messages\WhatsappMessage;

class OrderShipped extends Notification
{
    public function __construct(
        protected Order $order,
    ) {}

    public function via($notifiable): array
    {
        return [WhatsappChannel::class];
    }

    public function toWhatsapp($notifiable): WhatsappMessage
    {
        return WhatsappMessage::create()
            ->template(name: 'order_update', language: 'en')
            ->component(type: 'body', parameters: [
                ['type' => 'text', 'text' => $this->order->id],
            ]);
    }
}
```

## Sending

```php
$user->notify(new OrderShipped($order));
```

## Message Types

All message types from the [Sending Messages](sending-messages.md) documentation are supported:

### Text

```php
public function toWhatsapp($notifiable): WhatsappMessage
{
    return WhatsappMessage::create()
        ->text('Your order has shipped!');
}
```

### Image

```php
public function toWhatsapp($notifiable): WhatsappMessage
{
    return WhatsappMessage::create()
        ->image(link: 'https://example.com/receipt.jpg', caption: 'Your receipt');
}
```

### Document

```php
public function toWhatsapp($notifiable): WhatsappMessage
{
    return WhatsappMessage::create()
        ->document(
            link: 'https://example.com/invoice.pdf',
            filename: 'invoice.pdf',
            caption: 'Your invoice',
        );
}
```

### Template with Components

```php
public function toWhatsapp($notifiable): WhatsappMessage
{
    return WhatsappMessage::create()
        ->template(name: 'promo_offer', language: 'en')
        ->component(type: 'header', parameters: [
            ['type' => 'image', 'image' => ['link' => 'https://example.com/promo.jpg']],
        ])
        ->component(type: 'body', parameters: [
            ['type' => 'text', 'text' => '50% OFF'],
        ]);
}
```

### Interactive

```php
public function toWhatsapp($notifiable): WhatsappMessage
{
    return WhatsappMessage::create()
        ->interactive([
            'type' => 'button',
            'body' => ['text' => 'Rate your experience:'],
            'action' => [
                'buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'good', 'title' => 'Good']],
                    ['type' => 'reply', 'reply' => ['id' => 'bad', 'title' => 'Bad']],
                ],
            ],
        ]);
}
```

## Using `payload()` for Advanced Scenarios

```php
public function toWhatsapp($notifiable): WhatsappMessage
{
    return WhatsappMessage::create()
        ->template(name: 'order_update', language: 'en')
        ->payload([
            'template' => [
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'image', 'image' => ['link' => 'https://example.com/img.jpg']],
                        ],
                    ],
                ],
            ],
        ]);
}
```

## Multi-Account

Specify which WhatsApp account to send from:

```php
public function toWhatsapp($notifiable): WhatsappMessage
{
    return WhatsappMessage::create()
        ->account('support')
        ->text('Your ticket has been resolved.');
}
```

If no account is specified, the default account from config is used.

## Response Handling

The `WhatsappChannel::send()` method returns a `MessageResponse` (or `null` if the notifiable has no phone number). When using Laravel's notification system directly, the response is not typically accessed. If you need the response, use the `MessageService` directly instead:

```php
$response = Whatsapp::message()
    ->to($user->phone_number)
    ->text('Hello!')
    ->send();

$response->messageId(); // 'wamid.xxx'
```
