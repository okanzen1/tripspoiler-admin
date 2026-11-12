<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // "Yerel saat" sayfası (sitede /time): şehrin canlı saati, saat farkı hesaplayıcı, ortak saat planlayıcı
    public function up(): void
    {
        if (DB::table('pages')->where('key', 'time')->exists()) {
            return;
        }

        DB::table('pages')->insert([
            'key' => 'time',
            'name' => 'Yerel saat',
            'path' => '/time',
            'sort_order' => (int) DB::table('pages')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('pages')->where('key', 'time')->delete();
    }
};
