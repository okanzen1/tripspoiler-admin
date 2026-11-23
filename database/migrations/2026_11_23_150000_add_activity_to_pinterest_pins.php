<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pinterest'e aktiviteler de gider: bir gönderim ya bir bloga ya bir aktiviteye aittir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pinterest_pins', function (Blueprint $table) {
            $table->foreignId('blog_id')->nullable()->change();
            $table->foreignId('activity_id')->nullable()->after('blog_id')->constrained()->cascadeOnDelete();
            $table->index(['activity_id', 'pin_id']);
        });
    }

    public function down(): void
    {
        Schema::table('pinterest_pins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_id');
        });
    }
};
