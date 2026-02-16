<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedBigInteger('source_product_id')
                  ->nullable()
                  ->after('affiliate_id');
        });

        DB::table('activities')->update([
            'source_product_id' => null
        ]);

        Schema::table('activities', function (Blueprint $table) {
            $table->unique(
                ['affiliate_id', 'source_product_id'],
                'affiliate_source_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropUnique('affiliate_source_unique');
            $table->dropColumn('source_product_id');
        });
    }
};