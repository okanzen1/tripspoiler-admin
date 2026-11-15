<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Partner bağlantısı kart resminin yatay odağı (0 sol – 100 sağ); kart dar olduğu için resim yanlardan kırpılır */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_links', function (Blueprint $table) {
            $table->unsignedTinyInteger('image_focus')->default(50)->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('partner_links', function (Blueprint $table) {
            $table->dropColumn('image_focus');
        });
    }
};
