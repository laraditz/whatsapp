# Changelog

All notable changes to `laraditz/whatsapp` will be documented in this file

## 1.0.3 - 2026-02-16

### Added

- `WebhookReceived` event dispatched with the full raw payload before webhook processing

## 1.0.2 - 2026-02-12

### Changed

- Extended `component()` method to support `subType`, `index`, `format`, and `text` parameters for header, footer, and button template components

## 1.0.1 - 2026-02-11

### Added

- WhatsApp notification channel (`WhatsappChannel`) for Laravel's built-in notification system
- `WhatsappMessage` fluent builder for composing notifications with full message type support
- Container alias for `Whatsapp` class to support dependency injection by class name

## 1.0.0 - 2026-02-11

### Added

- Multi-account support with config and database drivers
- Fluent message API for all WhatsApp message types (text, image, video, audio, document, sticker, location, contacts, interactive, template, reaction)
- `payload()` method for deep merging custom data with fluent-built payloads
- Template management (list, get, create, update, delete) with cursor-based pagination
- Webhook controller with Meta verification challenge and `X-Hub-Signature-256` validation
- Laravel events: `MessageReceived`, `MessageDelivered`, `MessageRead`
- Database logging for API requests, messages, webhooks, and templates
- Eloquent models with PHP backed enums for message direction, message status, and template status
- Artisan commands: `whatsapp:sync-templates` and `whatsapp:sync-messages`
- Publishable config and migrations using `publishesMigrations()` (Laravel 11+)
- Typed exceptions: `WhatsappApiException`, `WhatsappAuthException`, `WhatsappRateLimitException`, `AccountNotFoundException`
- Response objects (`MessageResponse`, `TemplateResponse`) with dot notation access
