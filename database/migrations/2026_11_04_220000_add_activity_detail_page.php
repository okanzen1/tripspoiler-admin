<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // "Aktivite detayı": tüm aktivite sayfalarının ortak düzeni (Bölümler). SEO her aktivitenin kendi formunda.
    public function up(): void
    {
        if (DB::table('pages')->where('key', 'activity')->exists()) {
            return;
        }

        DB::table('pages')->where('sort_order', '>=', 2)->increment('sort_order');
        DB::table('pages')->insert([
            'key' => 'activity',
            'name' => 'Aktivite detayı',
            'path' => '/activities/…',
            'sort_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('pages')->where('key', 'activity')->delete();
        DB::table('pages')->where('sort_order', '>', 2)->decrement('sort_order');
    }
};
