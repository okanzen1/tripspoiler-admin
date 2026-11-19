<?php

namespace App\Models;

use App\Support\Privacy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * Sitedeki "Plan your trip" formundan gelen yardım talebi. Ad, e-posta ve cevapların hepsi şifreli
 * (site ve panel aynı APP_KEY); email_hash ile aynı kişinin talepleri eşleşir. 12 ay sonra silinir.
 * İki projede aynı tablo: site yazar (tripspoiler/app/Models/HelpRequest.php), panel okur ve yanıtlar.
 */
class HelpRequest extends Model
{
    use MassPrunable;

    public const STATUSES = ['new' => 'Yeni', 'answered' => 'Cevaplandı', 'closed' => 'Kapandı'];

    /** Cevapların paneldeki adları (sitedeki seçeneklerin anahtarları) */
    public const LABELS = [
        'group' => ['solo' => 'Tek başına', 'couple' => 'Çift', 'family' => 'Aile', 'friends' => 'Arkadaşlar', 'business' => 'İş gezisi'],
        'first_time' => ['yes' => 'İlk kez gidiyor', 'no' => 'Daha önce gitti'],
        'access' => ['wheelchair' => 'Tekerlekli sandalye', 'stroller' => 'Bebek arabası', 'limited_walking' => 'Az yürüme', 'senior' => 'Yaşlı yolcu'],
        'needs' => ['activities' => 'Aktivite ve bilet', 'sightseeing' => 'Gezip görme', 'stay' => 'Konaklama (semt)', 'transport' => 'Ulaşım', 'food' => 'Yemek', 'daytrips' => 'Günübirlik gezi', 'practical' => 'Pratik bilgi', 'occasion' => 'Özel gün'],
        'stay_vibe' => ['quiet' => 'Sakin semt', 'lively' => 'Hareketli semt', 'any' => 'Fark etmez'],
        'stay_budget' => ['budget' => 'Ekonomik', 'mid' => 'Orta', 'luxury' => 'Lüks'],
        'stay_extras' => ['central' => 'Merkeze yakın', 'transit' => 'Ulaşıma yakın', 'family' => 'Aileye uygun', 'sea' => 'Deniz manzarası'],
        'food' => ['local' => 'Yerel lezzetler', 'vegetarian' => 'Vejetaryen', 'vegan' => 'Vegan', 'halal' => 'Helal', 'kids' => 'Çocuk dostu', 'street' => 'Sokak lezzetleri'],
        'occasion' => ['honeymoon' => 'Balayı', 'birthday' => 'Doğum günü', 'anniversary' => 'Yıl dönümü', 'proposal' => 'Evlilik teklifi', 'other' => 'Başka'],
        'pace' => ['relaxed' => 'Sakin', 'balanced' => 'Dengeli', 'packed' => 'Dolu dolu'],
        'interests' => ['history' => 'Tarih ve müze', 'views' => 'Manzara ve fotoğraf', 'food' => 'Yemek', 'shopping' => 'Alışveriş', 'nightlife' => 'Gece hayatı', 'nature' => 'Doğa ve deniz', 'art' => 'Sanat ve mimari', 'kids' => 'Çocuklu aktiviteler'],
        'budget' => ['economy' => 'Ekonomik', 'mid' => 'Orta', 'comfort' => 'Rahat'],
    ];

    protected $fillable = ['name', 'email', 'answers', 'locale', 'status', 'newsletter', 'answered_at'];

    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'email' => 'encrypted',
            'answers' => 'encrypted:array',
            'newsletter' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $request) {
            $request->email_hash = Privacy::hash($request->email);
        });
    }

    /** Kişisel veri gereğinden uzun tutulmaz: 12 ayı geçen talepler silinir (model:prune) */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subMonths(12));
    }

    public function scopeUnanswered(Builder $query): void
    {
        $query->where('status', 'new');
    }

    /** Seçenek anahtarlarını panel adlarına çevirir */
    public static function label(string $group, mixed $value): array|string|null
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(fn ($v) => self::LABELS[$group][$v] ?? null, $value)));
        }

        return $value === null ? null : (self::LABELS[$group][$value] ?? (string) $value);
    }

    /** Aynı kişinin (aynı e-posta) diğer talepleri */
    public function others()
    {
        return static::where('email_hash', $this->email_hash)->whereKeyNot($this->id)->latest()->get();
    }
}
