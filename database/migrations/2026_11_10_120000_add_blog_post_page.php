<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // "Blog yazısı": tüm blog yazılarının ortak düzeni (Bölümler). SEO her yazının kendi formunda.
    public function up(): void
    {
        if (DB::table('pages')->where('key', 'post')->exists()) {
            return;
        }

        $after = (int) DB::table('pages')->where('key', 'blog')->value('sort_order');
        DB::table('pages')->where('sort_order', '>', $after)->increment('sort_order');
        DB::table('pages')->insert([
            'key' => 'post',
            'name' => 'Blog yazısı',
            'path' => '/blog/…',
            'sort_order' => $after + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $order = DB::table('pages')->where('key', 'post')->value('sort_order');
        DB::table('pages')->where('key', 'post')->delete();
        if ($order !== null) {
            DB::table('pages')->where('sort_order', '>', $order)->decrement('sort_order');
        }
    }
};
