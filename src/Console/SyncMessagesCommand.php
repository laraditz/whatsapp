<?php

namespace Laraditz\Whatsapp\Console;

use Illuminate\Console\Command;
use Laraditz\Whatsapp\Contracts\AccountRepository;
use Laraditz\Whatsapp\Enums\MessageStatus;
use Laraditz\Whatsapp\Models\WhatsappMessage;
use Laraditz\Whatsapp\Services\WhatsappClient;

class SyncMessagesCommand extends Command
{
    protected $signature = 'whatsapp:sync-messages
        {--account= : Specific account name to sync}
        {--since= : Only sync messages created after this date (Y-m-d)}';

    protected $description = 'Sync message statuses from the WhatsApp API for messages not yet delivered or read';

    public function handle(AccountRepository $accountRepository): int
    {
        $accountName = $this->option('account');
        $since = $this->option('since');

        $accounts = $accountName
            ? collect([$accountRepository->find(name: $accountName)])
            : $accountRepository->all();

        foreach ($accounts as $account) {
            $this->info("Syncing messages for account: {$account->name}");

            $query = WhatsappMessage::where('account_name', $account->name)
                ->whereIn('status', [MessageStatus::Sent]);

            if ($since) {
                $query->where('created_at', '>=', $since);
            }

            $pending = $query->count();
            $this->line("  Found {$pending} messages with pending status.");

            $client = new WhatsappClient(
                account: $account,
                baseUrl: config('whatsapp.base_url', 'https://graph.facebook.com'),
                apiVersion: config('whatsapp.api_version', 'v24.0'),
            );

            $updated = 0;

            $query->chunkById(100, function ($messages) use ($client, &$updated) {
                foreach ($messages as $message) {
                    try {
                        $response = $client->get(
                            endpoint: $message->wa_message_id,
                        );

                        $status = $response->json('status');
                        $messageStatus = MessageStatus::tryFrom($status);

                        if ($messageStatus && $messageStatus !== $message->status) {
                            $message->update([
                                'status' => $messageStatus,
                                'status_at' => now(),
                            ]);
                            $updated++;
                        }
                    } catch (\Throwable $e) {
                        $this->warn("  Failed to sync message {$message->wa_message_id}: {$e->getMessage()}");
                    }
                }
            });

            $this->info("  Done. Updated {$updated} messages.");
        }

        return self::SUCCESS;
    }
}
