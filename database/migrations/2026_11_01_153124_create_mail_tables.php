<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Basit anahtar/değer ayarları (ör. e-posta imzası)
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // E-posta yazarken tek tıkla eklenen hazır yanıtlar
        Schema::create('mail_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('body');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Front'taki blog abonelik formu bu tabloya yazar. E-posta şifreli, aramak/tekrarı engellemek için hash.
        Schema::create('blog_subscribers', function (Blueprint $table) {
            $table->id();
            $table->text('email');
            $table->string('email_hash', 64)->unique();
            $table->string('source_path', 500)->nullable();
            $table->timestamp('unsubscribed_at')->nullable()->index();
            $table->timestamps();
        });

        // Toplu e-posta geçmişi (adresler değil, sadece sayılar tutulur)
        Schema::create('bulk_emails', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->unsignedInteger('recipient_count');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_emails');
        Schema::dropIfExists('blog_subscribers');
        Schema::dropIfExists('mail_templates');
        Schema::dropIfExists('settings');
    }
};
