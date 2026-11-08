<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ziyaret ve tıklamalara IP, şehir, tarayıcı, işletim sistemi (site yazar: tripspoiler/app/Support/Tracker.php) */
    public function up(): void
    {
        foreach (['visits', 'affiliate_clicks'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('ip', 45)->nullable()->after('session_id')->index();
                $table->string('city', 80)->nullable()->after('country');
                $table->string('browser', 30)->nullable()->after('device');
                $table->string('os', 30)->nullable()->after('browser');
            });
        }
    }

    public function down(): void
    {
        foreach (['visits', 'affiliate_clicks'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex([ 'ip' ]);
                $table->dropColumn(['ip', 'city', 'browser', 'os']);
            });
        }
    }
};
