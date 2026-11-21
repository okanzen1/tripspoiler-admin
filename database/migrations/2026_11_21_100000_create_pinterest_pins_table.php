<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pinterest'e gönderilen bloglar (Yönetim › Pinterest). Her gönderim bir satır: otomatik sıra bir blogu bir kez
 * gönderir; elle "Tekrar gönder" yeni satır ekler. Hata olursa pin_id boş, error dolu kalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pinterest_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->constrained()->cascadeOnDelete();
            $table->string('pin_id', 64)->nullable();
            $table->string('board_id', 64)->nullable();
            $table->boolean('manual')->default(false); // elle gönderildi (otomatik sıra değil)
            $table->string('error', 500)->nullable();
            $table->timestamps();
            $table->index(['blog_id', 'pin_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pinterest_pins');
    }
};
