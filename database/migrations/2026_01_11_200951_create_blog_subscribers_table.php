<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_subscribers', function (Blueprint $table) {
            $table->id();

            /**
             * Email
             * - DB'de şifreli tutulacak (Crypt)
             * - Bu yüzden text
             */
            $table->text('email');

            /**
             * Unsubscribe için unique token
             * Mail içinden iptal linki buraya bakacak
             */
            $table->string('unsubscribe_token')->unique();

            /**
             * Abonelik durumu
             * 1 = aktif
             * 0 = pasif / unsubscribed
             */
            $table->boolean('status')->default(1);

            $table->timestamps();

            // performans için
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_subscribers');
    }
};
