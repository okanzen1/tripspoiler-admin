<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Yüklenen özel ikon (media diskinde SVG ya da WebP); doluysa "icon" yerine bu gösterilir
        Schema::table('features', function (Blueprint $table) {
            $table->string('icon_path')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('icon_path');
        });
    }
};
