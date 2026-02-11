<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('wa_message_id')->nullable()->index();
            $table->string('direction');
            $table->string('to')->nullable();
            $table->string('from')->nullable();
            $table->string('type');
            $table->json('content')->nullable();
            $table->string('status');
            $table->timestamp('status_at')->nullable();
            $table->timestamps();

            $table->index(['account_name', 'direction']);
            $table->index(['account_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
