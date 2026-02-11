<?php

namespace Laraditz\Whatsapp;

use Illuminate\Support\Str;
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
                __DIR__ . '/../config/config.php' => config_path('whatsapp.php'),
            ], 'whatsapp-config');

            $this->publishMigrations();

            $this->commands([
                Console\SyncTemplatesCommand::class,
                Console\SyncMessagesCommand::class,
            ]);
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/whatsapp.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'whatsapp');

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

        $this->app->alias('whatsapp', Whatsapp::class);
    }

    protected function publishMigrations()
    {
        $databasePath = __DIR__ . '/../database/migrations/';
        $migrationPath = database_path('migrations/');

        $files = array_diff(scandir($databasePath), array('.', '..'));
        $date = date('Y_m_d');
        $time = date('His');

        $migrationFiles = collect($files)
            ->mapWithKeys(function (string $file) use ($databasePath, $migrationPath, $date, &$time) {
                $filename = Str::replace(Str::substr($file, 0, 17), '', $file);

                $found = glob($migrationPath . '*' . $filename);
                $time = date("His", strtotime($time) + 1); // ensure in order
    
                return !!count($found) === true ? []
                    : [
                        $databasePath . $file => $migrationPath . $date . '_' . $time . $filename,
                    ];
            });

        if ($migrationFiles->isNotEmpty()) {
            $this->publishes($migrationFiles->toArray(), 'whatsapp-migrations');
        }
    }
}
