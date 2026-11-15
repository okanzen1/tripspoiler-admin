<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner bağlantıları: hangi sekmede çıkacağı (boş = hepsi; pass = şehir kartları, product = deneyimler, package = paketler)
 * ve isteğe bağlı kart resmi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_links', function (Blueprint $table) {
            $table->string('type', 20)->nullable()->after('partner_id');
            $table->string('image_path')->nullable()->after('count_label');
        });
    }

    public function down(): void
    {
        Schema::table('partner_links', function (Blueprint $table) {
            $table->dropColumn(['type', 'image_path']);
        });
    }
};
