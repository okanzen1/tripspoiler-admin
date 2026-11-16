<?php

use App\Models\Activity;
use App\Models\Blog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dillere göre adresler (App\Models\Concerns\HasLocalizedSlugs). Var olan çevrilmiş yazı ve aktivitelerin adresleri
 * hemen üretilir; eski (İngilizce) adresler sitede çalışmaya devam eder ve yeni adrese yönlenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['blogs', 'activities'] as $table) {
            if (!Schema::hasColumn($table, 'slugs')) {
                Schema::table($table, fn (Blueprint $t) => $t->json('slugs')->nullable()->after('slug'));
            }
        }

        foreach ([Blog::class, Activity::class] as $class) {
            $class::query()->orderBy('id')->each(function ($model) {
                if ($model->fillLocalizedSlugs()) {
                    $model->saveQuietly();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['blogs', 'activities'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('slugs'));
        }
    }
};
