# Sending Messages

All messages are sent using the fluent `MessageService` via the `Whatsapp` facade.

## Basic Pattern

```php
use Laraditz\Whatsapp\Facades\Whatsapp;

$response = Whatsapp::message()
    ->to('60123456789')
    ->text('Hello!')
    ->send();

$response->isSuccessful(); // true
$response->messageId();    // 'wamid.xxx'
$response->contacts();     // [['input' => '60123456789', 'wa_id' => '60123456789']]
```

## Message Types

### Text

```php
Whatsapp::message()
    ->to('60123456789')
    ->text('Hello, world!')
    ->send();

// With URL preview
Whatsapp::message()
    ->to('60123456789')
    ->text('Check out https://example.com', previewUrl: true)
    ->send();
```

### Image

```php
Whatsapp::message()
    ->to('60123456789')
    ->image(link: 'https://example.com/photo.jpg', caption: 'A beautiful photo')
    ->send();
```

### Video

```php
Whatsapp::message()
    ->to('60123456789')
    ->video(link: 'https://example.com/video.mp4', caption: 'Watch this')
    ->send();
```

### Audio

```php
Whatsapp::message()
    ->to('60123456789')
    ->audio(link: 'https://example.com/audio.mp3')
    ->send();
```

### Document

```php
Whatsapp::message()
    ->to('60123456789')
    ->document(
        link: 'https://example.com/invoice.pdf',
        filename: 'invoice-2024.pdf',
        caption: 'Your invoice',
    )
    ->send();
```

### Sticker

```php
Whatsapp::message()
    ->to('60123456789')
    ->sticker(link: 'https://example.com/sticker.webp')
    ->send();
```

### Location

```php
Whatsapp::message()
    ->to('60123456789')
    ->location(
        latitude: 3.1390,
        longitude: 101.6869,
        name: 'Kuala Lumpur',
        address: 'Kuala Lumpur, Malaysia',
    )
    ->send();
```

### Contacts

```php
Whatsapp::message()
    ->to('60123456789')
    ->contact([
        [
            'name' => ['formatted_name' => 'John Doe', 'first_name' => 'John', 'last_name' => 'Doe'],
            'phones' => [['phone' => '+60198765432', 'type' => 'CELL']],
        ],
    ])
    ->send();
```

### Interactive (Buttons)

```php
Whatsapp::message()
    ->to('60123456789')
    ->interactive([
        'type' => 'button',
        'body' => ['text' => 'Choose an option:'],
        'action' => [
            'buttons' => [
                ['type' => 'reply', 'reply' => ['id' => 'btn_yes', 'title' => 'Yes']],
                ['type' => 'reply', 'reply' => ['id' => 'btn_no', 'title' => 'No']],
            ],
        ],
    ])
    ->send();
```

### Interactive (List)

```php
Whatsapp::message()
    ->to('60123456789')
    ->interactive([
        'type' => 'list',
        'body' => ['text' => 'Select a product:'],
        'action' => [
            'button' => 'View Products',
            'sections' => [
                [
                    'title' => 'Electronics',
                    'rows' => [
                        ['id' => 'phone', 'title' => 'Smartphone', 'description' => 'Latest model'],
                        ['id' => 'laptop', 'title' => 'Laptop', 'description' => 'High performance'],
                    ],
                ],
            ],
        ],
    ])
    ->send();
```

### Template

All methods support both positional and named arguments:

```php
// Using named arguments
Whatsapp::message()
    ->to('60123456789')
    ->template(name: 'hello_world', language: 'en')
    ->send();

// Using positional arguments
Whatsapp::message()
    ->to('60123456789')
    ->template('hello_world', 'en')
    ->send();
```

```php
// Template with body parameters (named)
Whatsapp::message()
    ->to('60123456789')
    ->template(name: 'order_update', language: 'en')
    ->component(type: 'body', parameters: [
        ['type' => 'text', 'text' => 'ORDER-123'],
        ['type' => 'text', 'text' => 'shipped'],
    ])
    ->send();

// Same example using positional arguments
Whatsapp::message()
    ->to('60123456789')
    ->template('order_update', 'en')
    ->component('body', [
        ['type' => 'text', 'text' => 'ORDER-123'],
        ['type' => 'text', 'text' => 'shipped'],
    ])
    ->send();
```

```php
// Template with header image and body parameters
Whatsapp::message()
    ->to('60123456789')
    ->template(name: 'promo_offer', language: 'en')
    ->component(type: 'header', parameters: [
        ['type' => 'image', 'image' => ['link' => 'https://example.com/promo.jpg']],
    ])
    ->component(type: 'body', parameters: [
        ['type' => 'text', 'text' => '50% OFF'],
    ])
    ->send();
```

### Reaction

```php
Whatsapp::message()
    ->to('60123456789')
    ->reaction(messageId: 'wamid.xxx', emoji: "\u{1F44D}")
    ->send();
```

## Using `payload()` for Advanced Scenarios

The `payload()` method deep-merges with whatever the fluent chain has built. This is useful for fields not covered by the fluent API or for overriding specific values:

```php
// Merge additional template components
Whatsapp::message()
    ->to('60123456789')
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
    ])
    ->send();

// Pass the entire payload directly
Whatsapp::message()
    ->to('60123456789')
    ->payload([
        'type' => 'text',
        'text' => ['body' => 'Built entirely from payload'],
    ])
    ->send();
```

Values in `payload()` take precedence over values set by fluent methods.

## Multi-Account

```php
// Uses default account
Whatsapp::message()->to('60123456789')->text('Hello')->send();

// Uses specific account
Whatsapp::account('support')->message()->to('60123456789')->text('Hello from support')->send();
Whatsapp::account('marketing')->message()->to('60123456789')->text('New promo!')->send();
```

## Response Object

The `send()` method returns a `MessageResponse`:

```php
$response = Whatsapp::message()->to('60123456789')->text('Hello')->send();

$response->isSuccessful(); // bool
$response->messageId();    // string|null - WhatsApp message ID
$response->contacts();     // array - recipient info
$response->toArray();      // array - raw API response
$response->get('messages.0.id'); // mixed - dot notation access
```
