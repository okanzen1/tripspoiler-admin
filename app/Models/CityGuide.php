<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Şehir sayfasının üstündeki genel başlık ve açıklama (şehir başına bir kayıt).
 */
class CityGuide extends Model
{
    use HasTranslations;

    /**
     * "Kısa bilgiler" tablosu (sitede şehir sayfasında): anahtar → [paneldeki ad, örnek]. Sütun adı fact_{anahtar}.
     * Sıra sitedeki sıradır. Yeni bilgi eklemek: buraya ekle + migration + front'taki etiket (lang/*\/cities-page.php).
     */
    public const FACTS = [
        'best_time' => ['En iyi zaman', 'April–June and September–October: warm, fewer crowds'],
        'days' => ['Kaç gün yeter', '3–4 days for the main sights'],
        'budget' => ['Günlük bütçe (yaklaşık)', 'About €80–150 per person, excluding hotel'],
        'airport' => ['Havalimanından merkeze', 'Metro line 3, about 40 minutes'],
        'transport' => ['Şehir içi ulaşım', 'Metro, tram and buses; the centre is walkable'],
        'currency' => ['Para birimi', 'Euro (€); cards widely accepted'],
        'language' => ['Dil', 'Greek; English widely spoken in tourist areas'],
        'tipping' => ['Bahşiş', '5–10% in restaurants is appreciated'],
        'power' => ['Priz ve voltaj', 'Type C/F, 230 V'],
        'emergency' => ['Acil numara', '112'],
    ];

    protected $fillable = ['city_id', 'title', 'description', 'hero_cards', 'facts_checked_at',
        'fact_best_time', 'fact_days', 'fact_budget', 'fact_airport', 'fact_transport', 'fact_currency', 'fact_language', 'fact_tipping', 'fact_power', 'fact_emergency'];

    public array $translatable = ['title', 'description',
        'fact_best_time', 'fact_days', 'fact_budget', 'fact_airport', 'fact_transport', 'fact_currency', 'fact_language', 'fact_tipping', 'fact_power', 'fact_emergency'];

    protected function casts(): array
    {
        return ['facts_checked_at' => 'date'];
    }

    public function getLocale(): string
    {
        return 'en';
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
