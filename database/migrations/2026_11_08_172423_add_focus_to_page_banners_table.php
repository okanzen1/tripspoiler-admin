<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_banners', function (Blueprint $table) {
            $table->unsignedTinyInteger('focus')->default(50)->after('image_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('page_banners', function (Blueprint $table) {
            $table->dropColumn('focus');
        });
    }
};