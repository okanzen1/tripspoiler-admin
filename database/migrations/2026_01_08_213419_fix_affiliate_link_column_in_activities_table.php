<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // affiliate_url varsa SİL
            if (Schema::hasColumn('activities', 'affiliate_url')) {
                $table->dropColumn('affiliate_url');
            }

            // affiliate_link yoksa EKLE
            if (!Schema::hasColumn('activities', 'affiliate_link')) {
                $table->text('affiliate_link')->nullable()->after('affiliate_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'affiliate_link')) {
                $table->dropColumn('affiliate_link');
            }

            if (!Schema::hasColumn('activities', 'affiliate_url')) {
                $table->string('affiliate_url')->nullable();
            }
        });
    }
};
