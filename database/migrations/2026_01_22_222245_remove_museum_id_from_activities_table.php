<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Foreign key’i düşür
            $table->dropForeign(['museum_id']);

            // Kolonu sil
            $table->dropColumn('museum_id');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedBigInteger('museum_id')->nullable();

            $table->foreign('museum_id')
                ->references('id')
                ->on('museums')
                ->nullOnDelete();
        });
    }
};
