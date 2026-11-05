<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Blog sırası (panelde sürükle-bırak, şehir başına): küçük olan önce; 0 = sıralanmamış (yeni yazılar en üstte)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', fn (Blueprint $table) => $table->unsignedSmallInteger('sort_order')->default(0)->after('active'));
    }

    public function down(): void
    {
        Schema::table('blogs', fn (Blueprint $table) => $table->dropColumn('sort_order'));
    }
};
