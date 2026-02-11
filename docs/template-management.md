# Template Management

Manage WhatsApp message templates via the `TemplateService`.

## Listing Templates

```php
use Laraditz\Whatsapp\Facades\Whatsapp;

// List all templates
$response = Whatsapp::template()->list();

foreach ($response->templates() as $template) {
    echo $template['name'] . ' - ' . $template['status'];
}

// With specific fields and limit
$response = Whatsapp::template()->list(
    fields: ['name', 'status', 'category'],
    limit: 20,
);

// Pagination
$response = Whatsapp::template()->list(limit: 10);

while ($response) {
    foreach ($response->templates() as $template) {
        // Process template
    }

    $response = $response->hasNextPage() ? $response->nextPage() : null;
}
```

## Getting a Single Template

```php
$response = Whatsapp::template()->get(id: '123456789');

$response->id();         // '123456789'
$response->name();       // 'order_update'
$response->status();     // TemplateStatus::Approved
$response->category();   // 'UTILITY'
$response->components(); // [['type' => 'BODY', 'text' => '...']]
```

## Creating Templates

```php
$response = Whatsapp::template()->create(
    name: 'order_confirmation',
    language: 'en',
    category: 'UTILITY',
    components: [
        [
            'type' => 'BODY',
            'text' => 'Your order {{1}} has been confirmed. Estimated delivery: {{2}}.',
        ],
    ],
);

$response->id();     // New template ID
$response->status(); // TemplateStatus::Pending (awaiting Meta approval)
```

### With `payload()` Merge

```php
Whatsapp::template()
    ->payload([
        'components' => [
            [
                'type' => 'HEADER',
                'format' => 'IMAGE',
                'example' => [
                    'header_handle' => ['https://example.com/header.jpg'],
                ],
            ],
        ],
    ])
    ->create(
        name: 'promo_with_image',
        language: 'en',
        category: 'MARKETING',
        components: [
            ['type' => 'BODY', 'text' => 'Check out our latest offer: {{1}}'],
        ],
    );
```

## Updating Templates

```php
Whatsapp::template()->update(
    id: '123456789',
    components: [
        ['type' => 'BODY', 'text' => 'Updated: Your order {{1}} is on the way.'],
    ],
);
```

## Deleting Templates

```php
Whatsapp::template()->delete(name: 'old_template');
```

Note: Deleting a template removes all language versions of that template.

## Multi-Account

```php
// List templates for a specific account
Whatsapp::account('support')->template()->list();

// Create template under a specific account
Whatsapp::account('marketing')->template()->create(
    name: 'promo',
    language: 'en',
    category: 'MARKETING',
    components: [...],
);
```

## Syncing Templates

Templates are automatically synced to the local `whatsapp_templates` table when `logging.templates` is enabled in config.

For manual sync (e.g., after creating templates directly in Meta Business Manager):

```bash
# Sync all accounts
php artisan whatsapp:sync-templates

# Sync specific account
php artisan whatsapp:sync-templates --account=default
```

The sync command:
1. Fetches all templates from the API (handles pagination)
2. Upserts each template into the local table
3. Removes stale templates that no longer exist in the API

## Response Object

The `TemplateResponse` provides typed access to template data:

```php
$response = Whatsapp::template()->list();

// List response
$response->isSuccessful();    // bool
$response->templates();       // Collection
$response->hasNextPage();     // bool
$response->nextPage();        // TemplateResponse|null

// Single template response
$response = Whatsapp::template()->get(id: '123');
$response->id();              // string
$response->name();            // string
$response->status();          // TemplateStatus enum
$response->category();        // string
$response->components();      // array
$response->toArray();         // array - raw API response
```
