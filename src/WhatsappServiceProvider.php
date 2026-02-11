<?php

namespace Laraditz\Whatsapp;

use Illuminate\Support\ServiceProvider;
use Laraditz\Whatsapp\Contracts\AccountRepository;
use Laraditz\Whatsapp\Repositories\ConfigAccountRepository;
use Laraditz\Whatsapp\Repositories\DatabaseAccountRepository;

class WhatsappServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('whatsapp.php'),
            ], 'whatsapp-config');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'whatsapp-migrations');

            $this->commands([
                Console\SyncTemplatesCommand::class,
                Console\SyncMessagesCommand::class,
            ]);
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/whatsapp.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'whatsapp');

        $this->app->singleton(AccountRepository::class, function () {
            return match (config('whatsapp.account_driver')) {
                'database' => new DatabaseAccountRepository(),
                default => new ConfigAccountRepository(),
            };
        });

        $this->app->singleton('whatsapp', function ($app) {
            return new Whatsapp(
                accountRepository: $app->make(AccountRepository::class),
                config: config('whatsapp'),
            );
        });
    }
}
