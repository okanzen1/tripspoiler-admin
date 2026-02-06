<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'meta_title')) {
                $table->dropColumn('meta_title');
            }

            if (Schema::hasColumn('activities', 'meta_description')) {
                $table->dropColumn('meta_description');
            }
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->json('meta_title')->nullable()->after('slug');
            $table->json('meta_description')->nullable()->after('meta_title');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'meta_title')) {
                $table->dropColumn('meta_title');
            }

            if (Schema::hasColumn('activities', 'meta_description')) {
                $table->dropColumn('meta_description');
            }
        });
    }
};