<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Panel işlem kaydı: kim, ne zaman, neyi, nasıl değiştirdi (Yönetim > İşlem kaydı) */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 120)->nullable();       // kullanıcı sonradan silinse de adı kalsın
            $table->string('action', 30);                        // created, updated, deleted, login, logout, login_failed, translated
            $table->string('subject_type', 60)->nullable();      // activity, blog, setting…
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label', 255)->nullable();    // kaydın o anki adı ("Ayasofya bileti")
            $table->json('changes')->nullable();                 // alan => [eski, yeni]
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
