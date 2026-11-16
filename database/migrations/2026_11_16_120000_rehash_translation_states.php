<?php

use App\Models\TranslationState;
use App\Services\Translation\ModelTranslator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Migrations\Migration;

/**
 * Çeviri parmak izi artık biçimi (boşluk, HTML etiketi) yok sayıyor. Şu an "Güncel" olan çevirilerin kaydını yeni
 * hesaba taşı ki hiçbiri boşuna "eski" görünmesin. Zaten eski olanlara dokunulmaz.
 */
return new class extends Migration
{
    public function up(): void
    {
        $translator = app(ModelTranslator::class);

        TranslationState::query()->get()->groupBy(fn ($s) => $s->translatable_type . '#' . $s->translatable_id)
            ->each(function ($states) use ($translator) {
                $first = $states->first();
                $class = Relation::getMorphedModel($first->translatable_type) ?? $first->translatable_type;
                if (!class_exists($class) || !($model = $class::find($first->translatable_id))) {
                    return;
                }
                $legacy = $translator->legacyHash($model);
                $new = $translator->hash($model);
                $states->where('source_hash', $legacy)->each(fn ($s) => $s->update(['source_hash' => $new]));
            });
    }

    public function down(): void {}
};
