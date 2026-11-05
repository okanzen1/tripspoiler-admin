<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // SSS'nin yeri: şehir (o şehir seçiliyken SSS bölümlerinde) ya da sayfa (ör. "activities": o sayfanın SSS bölümünde)
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('blog_id')->constrained()->cascadeOnDelete();
            $table->string('page_key', 50)->nullable()->after('city_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn('page_key');
        });
    }
};
