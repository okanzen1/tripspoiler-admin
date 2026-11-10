<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Partnerler listesinde sürükleyerek verilen sıra (sitedeki partner şeridi de bu sırayla)
        Schema::table('partners', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('show_on_site');
        });

        // Mevcut partnerler bugünkü sırasıyla (aktifler önce, ada göre) numaralansın
        DB::table('partners')->orderByDesc('active')->orderBy('name')->pluck('id')
            ->each(fn ($id, $i) => DB::table('partners')->where('id', $id)->update(['sort_order' => $i + 1]));
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
