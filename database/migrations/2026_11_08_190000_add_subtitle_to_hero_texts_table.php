<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Anasayfa hero'su: başlığın altındaki kalın kısa satır ("Explore smarter. Travel better.") */
    public function up(): void
    {
        Schema::table('hero_texts', function (Blueprint $table) {
            $table->json('subtitle')->nullable()->after('title_highlight');
        });
    }

    public function down(): void
    {
        Schema::table('hero_texts', function (Blueprint $table) {
            $table->dropColumn('subtitle');
        });
    }
};
