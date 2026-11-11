<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ülkenin para birimi (ISO 4217, ör. EUR): sitede döviz kuru şeridi için. Ülke kodundan otomatik doldurulur.
        Schema::table('countries', function (Blueprint $table) {
            $table->char('currency', 3)->nullable()->after('slug');
        });

        DB::table('countries')->select('id', 'code')->orderBy('id')->each(function ($country) {
            DB::table('countries')->where('id', $country->id)->update(['currency' => \App\Models\Country::defaultCurrency($country->code)]);
        });
    }

    public function down(): void
    {
        Schema::table('countries', fn (Blueprint $table) => $table->dropColumn('currency'));
    }
};
