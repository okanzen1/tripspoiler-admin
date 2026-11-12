<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hava durumu:
     *  - activities.setting: indoor (kapalı mekan) | outdoor (açık hava) | both; hava bölümündeki "Bu havada ne yapılır" için
     *  - "Hava durumu" sayfası (sitede /weather): tüm şehirlerin havası
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('setting', 10)->nullable()->after('audio_guide');
        });

        if (!DB::table('pages')->where('key', 'weather')->exists()) {
            DB::table('pages')->insert([
                'key' => 'weather',
                'name' => 'Hava durumu',
                'path' => '/weather',
                'sort_order' => (int) DB::table('pages')->max('sort_order') + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('pages')->where('key', 'weather')->delete();
        Schema::table('activities', fn (Blueprint $table) => $table->dropColumn('setting'));
    }
};
