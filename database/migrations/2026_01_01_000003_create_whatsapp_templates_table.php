<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('wa_template_id')->index();
            $table->string('name');
            $table->string('language');
            $table->string('category');
            $table->string('status');
            $table->json('components')->nullable();
            $table->timestamps();

            $table->unique(['account_name', 'wa_template_id']);
            $table->index(['account_name', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
