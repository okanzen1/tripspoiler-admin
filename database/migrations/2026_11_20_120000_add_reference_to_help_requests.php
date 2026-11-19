<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Talep numarası (ör. TS-48217): misafire teşekkür ekranında ve e-postada verilir; sitede numara + e-postayla
 * durum sorgulanır, panelde numarayla aranır. Kişisel veri değil, şifresiz ve tekil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_requests', function (Blueprint $table) {
            $table->string('reference', 20)->nullable()->unique()->after('id');
        });

        // Önceden gelen talepler de numara alır
        foreach (DB::table('help_requests')->whereNull('reference')->pluck('id') as $id) {
            do {
                $ref = 'TS-' . random_int(10000, 99999);
            } while (DB::table('help_requests')->where('reference', $ref)->exists());
            DB::table('help_requests')->where('id', $id)->update(['reference' => $ref]);
        }
    }

    public function down(): void
    {
        Schema::table('help_requests', fn (Blueprint $table) => $table->dropColumn('reference'));
    }
};
