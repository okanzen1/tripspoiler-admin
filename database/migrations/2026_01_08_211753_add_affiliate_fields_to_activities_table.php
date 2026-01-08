<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedBigInteger('affiliate_id')->nullable()->after('museum_id');
            $table->text('affiliate_link')->nullable()->after('affiliate_id');

            $table->foreign('affiliate_id')
                ->references('id')
                ->on('affiliate_partners')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['affiliate_id']);
            $table->dropColumn(['affiliate_id', 'affiliate_link']);
        });
    }
};
