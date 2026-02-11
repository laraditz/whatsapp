<?php

namespace Laraditz\Whatsapp\Repositories;

use Illuminate\Support\Collection;
use Laraditz\Whatsapp\Contracts\AccountRepository;
use Laraditz\Whatsapp\DTOs\Account;
use Laraditz\Whatsapp\Exceptions\AccountNotFoundException;
use Laraditz\Whatsapp\Models\WhatsappAccount;

class DatabaseAccountRepository implements AccountRepository
{
    public function find(string $name): Account
    {
        $record = WhatsappAccount::where('name', $name)->first();

        if (! $record) {
            throw new AccountNotFoundException("WhatsApp account [{$name}] not found in database.");
        }

        return $this->mapToAccount(record: $record);
    }

    public function all(): Collection
    {
        return WhatsappAccount::all()->map(
            fn (WhatsappAccount $record) => $this->mapToAccount(record: $record)
        );
    }

    protected function mapToAccount(WhatsappAccount $record): Account
    {
        return new Account(
            name: $record->name,
            accessToken: $record->access_token,
            phoneNumberId: $record->phone_number_id,
            businessAccountId: $record->business_account_id,
            webhookVerifyToken: $record->webhook_verify_token,
            webhookSecret: $record->webhook_secret,
        );
    }
}
