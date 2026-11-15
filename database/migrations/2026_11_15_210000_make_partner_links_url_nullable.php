<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Partner bağlantısında adres isteğe bağlı: boşsa kart partnerin kendi sitesine (partners.website) gider */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_links', function (Blueprint $table) {
            $table->string('url', 1000)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('partner_links', function (Blueprint $table) {
            $table->string('url', 1000)->nullable(false)->change();
        });
    }
};
