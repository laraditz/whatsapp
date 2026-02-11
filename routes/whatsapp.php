<?php

use Illuminate\Support\Facades\Route;
use Laraditz\Whatsapp\Http\Controllers\WebhookController;

Route::prefix(config('whatsapp.webhook_path', 'whatsapp/webhook'))
    ->group(function () {
        Route::get('/', [WebhookController::class, 'verify']);
        Route::post('/', [WebhookController::class, 'handle']);
    });
