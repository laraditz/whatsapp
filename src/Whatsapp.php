<?php

namespace Laraditz\Whatsapp;

use Laraditz\Whatsapp\Contracts\AccountRepository;
use Laraditz\Whatsapp\DTOs\Account;
use Laraditz\Whatsapp\Services\MessageService;
use Laraditz\Whatsapp\Services\TemplateService;
use Laraditz\Whatsapp\Services\WhatsappClient;

class Whatsapp
{
    protected ?string $accountName = null;

    public function __construct(
        protected AccountRepository $accountRepository,
        protected array $config,
    ) {}

    public function account(string $name): static
    {
        $clone = clone $this;
        $clone->accountName = $name;

        return $clone;
    }

    public function message(): MessageService
    {
        return new MessageService(client: $this->makeClient());
    }

    public function template(): TemplateService
    {
        return new TemplateService(client: $this->makeClient());
    }

    protected function resolveAccount(): Account
    {
        $name = $this->accountName ?? $this->config['default'] ?? 'default';

        return $this->accountRepository->find(name: $name);
    }

    protected function makeClient(): WhatsappClient
    {
        return new WhatsappClient(
            account: $this->resolveAccount(),
            baseUrl: $this->config['base_url'] ?? 'https://graph.facebook.com',
            apiVersion: $this->config['api_version'] ?? 'v24.0',
        );
    }
}
