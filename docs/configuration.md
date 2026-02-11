# Configuration Guide

## Config File

After publishing, the config is available at `config/whatsapp.php`.

```php
return [
    'default' => env('WHATSAPP_ACCOUNT', 'default'),
    'api_version' => env('WHATSAPP_API_VERSION', 'v24.0'),
    'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),
    'account_driver' => env('WHATSAPP_ACCOUNT_DRIVER', 'config'),
    'webhook_path' => env('WHATSAPP_WEBHOOK_PATH', 'whatsapp/webhook'),

    'logging' => [
        'api_requests' => true,
        'messages' => true,
        'webhooks' => true,
        'templates' => true,
    ],

    'accounts' => [
        'default' => [
            'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
            'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
            'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
        ],
    ],
];
```

## Environment Variables

| Variable | Description |
|----------|-------------|
| `WHATSAPP_ACCESS_TOKEN` | Permanent or temporary access token from Meta |
| `WHATSAPP_PHONE_NUMBER_ID` | Phone number ID from WhatsApp Business settings |
| `WHATSAPP_BUSINESS_ACCOUNT_ID` | WhatsApp Business Account ID |
| `WHATSAPP_WEBHOOK_VERIFY_TOKEN` | Token you define for webhook verification |
| `WHATSAPP_WEBHOOK_SECRET` | App secret from Meta App Dashboard (for signature validation) |
| `WHATSAPP_ACCOUNT_DRIVER` | `config` (default) or `database` |
| `WHATSAPP_WEBHOOK_PATH` | Webhook URL path (default: `whatsapp/webhook`) |
| `WHATSAPP_API_VERSION` | Graph API version (default: `v24.0`) |

## Account Drivers

### Config Driver (default)

Accounts are defined in `config/whatsapp.php`. Add multiple accounts by adding keys under `accounts`:

```php
'accounts' => [
    'default' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
    ],
    'support' => [
        'access_token' => env('WHATSAPP_SUPPORT_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_SUPPORT_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_SUPPORT_BUSINESS_ACCOUNT_ID'),
        'webhook_verify_token' => env('WHATSAPP_SUPPORT_WEBHOOK_VERIFY_TOKEN'),
        'webhook_secret' => env('WHATSAPP_SUPPORT_WEBHOOK_SECRET'),
    ],
],
```

### Database Driver

For dynamic account management (e.g., multi-tenant SaaS), switch to the database driver:

```env
WHATSAPP_ACCOUNT_DRIVER=database
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag=whatsapp-migrations
php artisan migrate
```

This creates the `whatsapp_accounts` table. The `access_token` and `webhook_secret` columns are automatically encrypted at rest using Laravel's `encrypted` cast.

Manage accounts via the `WhatsappAccount` model:

```php
use Laraditz\Whatsapp\Models\WhatsappAccount;

WhatsappAccount::create([
    'name' => 'marketing',
    'access_token' => 'your-token',
    'phone_number_id' => '123456',
    'business_account_id' => '789012',
    'webhook_verify_token' => 'verify-token',
    'webhook_secret' => 'app-secret',
]);
```

## Logging

All logging writes to database tables. Toggle each log type independently:

```php
'logging' => [
    'api_requests' => true,   // Logs every API call to whatsapp_api_logs
    'messages' => true,        // Logs sent/received messages to whatsapp_messages
    'webhooks' => true,        // Logs webhook payloads to whatsapp_webhook_logs
    'templates' => true,       // Syncs templates to whatsapp_templates
],
```

Migrations must be published and run for logging to work. Set any option to `false` to disable that log type.

## Database Tables

| Table | Purpose |
|-------|---------|
| `whatsapp_accounts` | Account storage (database driver only) |
| `whatsapp_messages` | Sent and received messages with status tracking |
| `whatsapp_templates` | Local mirror of WhatsApp templates |
| `whatsapp_api_logs` | Every API request and response |
| `whatsapp_webhook_logs` | Every webhook payload received |
