<?php

namespace Laraditz\Whatsapp\Contracts;

use Illuminate\Support\Collection;
use Laraditz\Whatsapp\DTOs\Account;

interface AccountRepository
{
    public function find(string $name): Account;

    public function all(): Collection;
}
