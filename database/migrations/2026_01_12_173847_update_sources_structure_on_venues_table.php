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
        Schema::table('venues', function (Blueprint $table) {
            // eski tekil source varsa kaldır
            if (Schema::hasColumn('venues', 'source')) {
                $table->dropColumn('source');
            }

            // yeni yapı
            $table->json('sources')->nullable()->after('museum_id');
            $table->json('source_ids')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('source')->nullable()->after('museum_id');
            $table->dropColumn('sources');
        });
    }
};
