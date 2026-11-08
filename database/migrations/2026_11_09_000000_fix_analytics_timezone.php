<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Site ziyaret ve tıklamaları UTC yazıyordu, panel İstanbul saatiyle okuyor (3 saat geride görünüyordu).
     * Site de artık İstanbul saatinde (tripspoiler/config/app.php); şimdiye kadarki kayıtlar +3 saat düzeltilir.
     * Demo veriler panelde üretildiği için zaten doğru, dokunulmaz.
     */
    public function up(): void
    {
        foreach (['visits', 'affiliate_clicks'] as $table) {
            DB::table($table)->where('visitor_id', 'not like', 'demo_%')
                ->update(['created_at' => DB::raw('DATE_ADD(created_at, INTERVAL 3 HOUR)')]);
        }
    }

    public function down(): void
    {
        foreach (['visits', 'affiliate_clicks'] as $table) {
            DB::table($table)->where('visitor_id', 'not like', 'demo_%')
                ->update(['created_at' => DB::raw('DATE_SUB(created_at, INTERVAL 3 HOUR)')]);
        }
    }
};
