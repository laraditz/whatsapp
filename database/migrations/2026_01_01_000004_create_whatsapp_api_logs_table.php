<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('method', 10);
            $table->string('endpoint');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration_ms');
            $table->timestamps();

            $table->index('account_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_api_logs');
    }
};
