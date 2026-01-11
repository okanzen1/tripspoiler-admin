<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            // varsa indexleri düş
            try {
                $table->dropIndex(['place', 'place_id']);
            } catch (\Throwable $e) {
            }

            // kolonları yeniden adlandır
            if (Schema::hasColumn('blogs', 'place')) {
                $table->renameColumn('place', 'source');
            }

            if (Schema::hasColumn('blogs', 'place_id')) {
                $table->renameColumn('place_id', 'source_id');
            }
        });

        // yeni index
        Schema::table('blogs', function (Blueprint $table) {
            $table->index(['source', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            try {
                $table->dropIndex(['source', 'source_id']);
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('blogs', 'source')) {
                $table->renameColumn('source', 'place');
            }

            if (Schema::hasColumn('blogs', 'source_id')) {
                $table->renameColumn('source_id', 'place_id');
            }
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->index(['place', 'place_id']);
        });
    }
};
