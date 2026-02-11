<?php

namespace Laraditz\Whatsapp\Repositories;

use Illuminate\Support\Collection;
use Laraditz\Whatsapp\Contracts\AccountRepository;
use Laraditz\Whatsapp\DTOs\Account;
use Laraditz\Whatsapp\Exceptions\AccountNotFoundException;

class ConfigAccountRepository implements AccountRepository
{
    public function find(string $name): Account
    {
        $accounts = config('whatsapp.accounts', []);

        if (! isset($accounts[$name])) {
            throw new AccountNotFoundException("WhatsApp account [{$name}] not found in config.");
        }

        return $this->mapToAccount(name: $name, data: $accounts[$name]);
    }

    public function all(): Collection
    {
        $accounts = config('whatsapp.accounts', []);

        return collect($accounts)->map(
            fn (array $data, string $name) => $this->mapToAccount(name: $name, data: $data)
        )->values();
    }

    protected function mapToAccount(string $name, array $data): Account
    {
        return new Account(
            name: $name,
            accessToken: $data['access_token'],
            phoneNumberId: $data['phone_number_id'],
            businessAccountId: $data['business_account_id'],
            webhookVerifyToken: $data['webhook_verify_token'] ?? null,
            webhookSecret: $data['webhook_secret'] ?? null,
        );
    }
}
