<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google Search Console verisi (son 28 gün, App\Services\SearchConsole): her güncellemede silinip yeniden yazılır.
 * query boş satırlar sayfa toplamıdır (Google bazı aramaları gizlilik için göstermez; sayfa toplamı tamdır).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_console_rows', function (Blueprint $table) {
            $table->id();
            $table->string('query', 500)->nullable();
            $table->text('page');
            $table->char('page_hash', 40)->index();
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->float('ctr')->default(0);
            $table->float('position')->default(0);
            $table->index(['query', 'impressions']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_console_rows');
    }
};
