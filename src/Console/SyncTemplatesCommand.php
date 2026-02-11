<?php

namespace Laraditz\Whatsapp\Console;

use Illuminate\Console\Command;
use Laraditz\Whatsapp\Contracts\AccountRepository;
use Laraditz\Whatsapp\Facades\Whatsapp;
use Laraditz\Whatsapp\Models\WhatsappTemplate;

class SyncTemplatesCommand extends Command
{
    protected $signature = 'whatsapp:sync-templates {--account= : Specific account name to sync}';

    protected $description = 'Sync WhatsApp message templates from the API to the local database';

    public function handle(AccountRepository $accountRepository): int
    {
        $accountName = $this->option('account');

        $accounts = $accountName
            ? collect([$accountRepository->find(name: $accountName)])
            : $accountRepository->all();

        $originalLogging = config('whatsapp.logging.templates');
        config()->set('whatsapp.logging.templates', true);

        foreach ($accounts as $account) {
            $this->info("Syncing templates for account: {$account->name}");

            $templateService = Whatsapp::account($account->name)->template();
            $response = $templateService->list();

            $syncedIds = [];

            while (true) {
                foreach ($response->templates() as $template) {
                    $syncedIds[] = $template['id'];
                }

                $this->line("  Synced {$response->templates()->count()} templates...");

                if (! $response->hasNextPage()) {
                    break;
                }

                $response = $response->nextPage();
            }

            $deleted = WhatsappTemplate::where('account_name', $account->name)
                ->whereNotIn('wa_template_id', $syncedIds)
                ->delete();

            if ($deleted > 0) {
                $this->line("  Removed {$deleted} stale templates.");
            }

            $this->info("  Done. Total: ".count($syncedIds).' templates.');
        }

        config()->set('whatsapp.logging.templates', $originalLogging);

        return self::SUCCESS;
    }
}
