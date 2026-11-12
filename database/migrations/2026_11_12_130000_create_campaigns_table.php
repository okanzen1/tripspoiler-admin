<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kampanyalar / indirim kodları (ör. Megapass %10 kodu). Sitede /campaigns sayfası, menüde ve footer'da bağlantı;
     * yayında kampanya yoksa hepsi gizlenir, /campaigns anasayfaya yönlenir.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->json('title');              // 12 dil
            $table->json('description')->nullable();
            $table->json('badge')->nullable();  // kısa etiket: "%10 indirim"
            $table->string('code', 60)->nullable();
            $table->string('url', 1000)->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        if (!DB::table('pages')->where('key', 'campaigns')->exists()) {
            DB::table('pages')->insert([
                'key' => 'campaigns',
                'name' => 'Kampanyalar',
                'path' => '/campaigns',
                'sort_order' => (int) DB::table('pages')->max('sort_order') + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('pages')->where('key', 'campaigns')->delete();
        Schema::dropIfExists('campaigns');
    }
};
