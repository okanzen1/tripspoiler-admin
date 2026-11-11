<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // "Döviz kurları" sayfası: tüm kurlar + para çevirici (sitede /exchange-rates)
    public function up(): void
    {
        if (DB::table('pages')->where('key', 'rates')->exists()) {
            return;
        }

        DB::table('pages')->insert([
            'key' => 'rates',
            'name' => 'Döviz kurları',
            'path' => '/exchange-rates',
            'sort_order' => (int) DB::table('pages')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('pages')->where('key', 'rates')->delete();
    }
};
