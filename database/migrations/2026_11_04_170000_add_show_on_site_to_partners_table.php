<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Footer'ın üstündeki "Destekçilerimiz ve partnerlerimiz" şeridinde görünsün mü
        Schema::table('partners', function (Blueprint $table) {
            $table->boolean('show_on_site')->default(false)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('show_on_site');
        });
    }
};
