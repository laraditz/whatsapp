<?php

namespace Laraditz\Whatsapp\Tests\Unit;

use Laraditz\Whatsapp\DTOs\Account;
use Laraditz\Whatsapp\Exceptions\AccountNotFoundException;
use Laraditz\Whatsapp\Repositories\ConfigAccountRepository;
use Laraditz\Whatsapp\Tests\TestCase;

class ConfigAccountRepositoryTest extends TestCase
{
    public function test_find_returns_account_dto(): void
    {
        $repo = new ConfigAccountRepository();

        $account = $repo->find('default');

        $this->assertInstanceOf(Account::class, $account);
        $this->assertSame('default', $account->name);
        $this->assertSame('test-token', $account->accessToken);
        $this->assertSame('test-phone-id', $account->phoneNumberId);
        $this->assertSame('test-business-id', $account->businessAccountId);
    }

    public function test_find_throws_when_account_not_found(): void
    {
        $repo = new ConfigAccountRepository();

        $this->expectException(AccountNotFoundException::class);

        $repo->find('nonexistent');
    }

    public function test_all_returns_collection_of_account_dtos(): void
    {
        $repo = new ConfigAccountRepository();

        $accounts = $repo->all();

        $this->assertCount(1, $accounts);
        $this->assertInstanceOf(Account::class, $accounts->first());
    }
}
