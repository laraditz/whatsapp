<?php

namespace Laraditz\Whatsapp\Tests;

use Laraditz\Whatsapp\WhatsappServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            WhatsappServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Whatsapp' => \Laraditz\Whatsapp\Facades\Whatsapp::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('whatsapp.accounts.default', [
            'access_token' => 'test-token',
            'phone_number_id' => 'test-phone-id',
            'business_account_id' => 'test-business-id',
            'webhook_verify_token' => 'test-verify-token',
            'webhook_secret' => 'test-secret',
        ]);
    }
}
