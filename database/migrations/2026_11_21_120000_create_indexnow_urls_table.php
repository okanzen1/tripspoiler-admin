<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IndexNow'a bildirilen site adresleri (App\Services\IndexNow): site haritasındaki her adres ve son değişiklik
 * tarihi. Haritada yeni çıkan ya da tarihi değişen adresler bir sonraki çalışmada bildirilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indexnow_urls', function (Blueprint $table) {
            $table->id();
            $table->char('url_hash', 40)->unique();
            $table->text('url');
            $table->string('lastmod', 40)->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indexnow_urls');
    }
};
