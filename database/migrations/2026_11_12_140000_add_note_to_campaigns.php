<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Kampanya notu (12 dil): "Tıkladığınızda kod otomatik uygulanır" gibi, sitede vurgulu satır
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('note')->nullable()->after('badge');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', fn (Blueprint $table) => $table->dropColumn('note'));
    }
};
