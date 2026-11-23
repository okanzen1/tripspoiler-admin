<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Bir kerelik: toplu çeviri sırasındaki tekrarlı işleri temizler, çevirisi eksik her kaydı bir kez sıraya alır
 * (php artisan translations:requeue). Migrate ile kendiliğinden çalışsın diye burada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('translations:requeue');
    }

    public function down(): void {}
};
