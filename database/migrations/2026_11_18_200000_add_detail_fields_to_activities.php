<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aktivite sayfasındaki ayrı bloklar (çok dilli, her satır bir madde): öne çıkanlar, dahil, dahil değil,
 * buluşma noktası, önemli bilgiler, iptal koşulları. Google ve yapay zekâ "ne dahil / nerede buluşuluyor" sorularını buradan okur.
 */
return new class extends Migration
{
    public const FIELDS = ['highlights', 'included', 'excluded', 'meeting_point', 'important_info', 'cancellation'];

    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            foreach (self::FIELDS as $field) {
                $table->text($field)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('activities', fn (Blueprint $table) => $table->dropColumn(self::FIELDS));
    }
};
