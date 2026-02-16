<?php

namespace Laraditz\Whatsapp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Laraditz\Whatsapp\Enums\MessageDirection;
use Laraditz\Whatsapp\Enums\MessageStatus;
use Laraditz\Whatsapp\Events\MessageDelivered;
use Laraditz\Whatsapp\Events\MessageRead;
use Laraditz\Whatsapp\Events\MessageReceived;
use Laraditz\Whatsapp\Events\WebhookReceived;
use Laraditz\Whatsapp\Models\WhatsappMessage;
use Laraditz\Whatsapp\Models\WhatsappWebhookLog;

class WebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $accounts = config('whatsapp.accounts', []);
        $validToken = false;

        foreach ($accounts as $account) {
            if (($account['webhook_verify_token'] ?? null) === $token) {
                $validToken = true;
                break;
            }
        }

        if ($mode === 'subscribe' && $validToken) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->verifySignature(request: $request)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        WebhookReceived::dispatch($payload);

        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $accountName = $this->resolveAccountName(phoneNumberId: $value['metadata']['phone_number_id'] ?? '');

                $this->logWebhook(accountName: $accountName, value: $value);

                $this->processMessages(accountName: $accountName, value: $value);
                $this->processStatuses(accountName: $accountName, value: $value);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $accounts = config('whatsapp.accounts', []);

        foreach ($accounts as $account) {
            $secret = $account['webhook_secret'] ?? null;

            if (! $secret) {
                continue;
            }

            $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        return false;
    }

    protected function resolveAccountName(string $phoneNumberId): string
    {
        $accounts = config('whatsapp.accounts', []);

        foreach ($accounts as $name => $account) {
            if (($account['phone_number_id'] ?? null) === $phoneNumberId) {
                return $name;
            }
        }

        return 'unknown';
    }

    protected function processMessages(string $accountName, array $value): void
    {
        $messages = $value['messages'] ?? [];

        foreach ($messages as $messageData) {
            $from = $messageData['from'] ?? '';
            $type = $messageData['type'] ?? 'text';
            $messageId = $messageData['id'] ?? '';
            $body = $this->extractMessageBody(type: $type, data: $messageData);

            if (config('whatsapp.logging.messages') ?? false) {
                WhatsappMessage::create([
                    'account_name' => $accountName,
                    'wa_message_id' => $messageId,
                    'direction' => MessageDirection::Inbound,
                    'to' => $value['metadata']['display_phone_number'] ?? null,
                    'from' => $from,
                    'type' => $type,
                    'content' => $messageData,
                    'status' => MessageStatus::Delivered,
                    'status_at' => now(),
                ]);
            }

            MessageReceived::dispatch($from, $body, $type, $accountName, $messageData);
        }
    }

    protected function processStatuses(string $accountName, array $value): void
    {
        $statuses = $value['statuses'] ?? [];

        foreach ($statuses as $statusData) {
            $messageId = $statusData['id'] ?? '';
            $status = $statusData['status'] ?? '';

            if (config('whatsapp.logging.messages') ?? false) {
                $messageStatus = MessageStatus::tryFrom($status);

                if ($messageStatus) {
                    WhatsappMessage::where('wa_message_id', $messageId)
                        ->update([
                            'status' => $messageStatus,
                            'status_at' => now(),
                        ]);
                }
            }

            match ($status) {
                'delivered' => MessageDelivered::dispatch($messageId, $accountName, $statusData),
                'read' => MessageRead::dispatch($messageId, $accountName, $statusData),
                default => null,
            };
        }
    }

    protected function extractMessageBody(string $type, array $data): string
    {
        return match ($type) {
            'text' => $data['text']['body'] ?? '',
            'image' => $data['image']['caption'] ?? '[image]',
            'video' => $data['video']['caption'] ?? '[video]',
            'document' => $data['document']['caption'] ?? '[document]',
            'audio' => '[audio]',
            'sticker' => '[sticker]',
            'location' => ($data['location']['name'] ?? '') ?: '[location]',
            'contacts' => '[contacts]',
            'interactive' => $data['interactive']['button_reply']['title']
                ?? $data['interactive']['list_reply']['title']
                ?? '[interactive]',
            default => "[{$type}]",
        };
    }

    protected function logWebhook(string $accountName, array $value): void
    {
        if (! (config('whatsapp.logging.webhooks') ?? false)) {
            return;
        }

        $eventType = 'unknown';

        if (! empty($value['messages'])) {
            $eventType = 'message';
        } elseif (! empty($value['statuses'])) {
            $eventType = 'status';
        }

        WhatsappWebhookLog::create([
            'account_name' => $accountName,
            'event_type' => $eventType,
            'payload' => $value,
            'processed_at' => now(),
        ]);
    }
}
