<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            // Eğer foreign key varsa önce drop et
            if (Schema::hasColumn('blogs', 'source_venue_id')) {
                $table->dropColumn('source_venue_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->unsignedBigInteger('source_venue_id')->nullable();

            // Eğer geri almak istersen FK ekleyebilirsin
            // $table->foreign('source_venue_id')->references('id')->on('venues')->nullOnDelete();
        });
    }
};
