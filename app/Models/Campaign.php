<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Kampanya / indirim kodu (sitede /campaigns). Kod, bağlantı ve tarih aralığı; partner seçilirse logosu görünür.
 */
class Campaign extends Model
{
    use HasTranslations;

    protected $fillable = ['partner_id', 'code', 'url', 'starts_at', 'ends_at', 'active', 'sort_order'];

    /** Çok dilli alanlar; panel İngilizce (en) düzenler, diğer diller Çeviriler'den */
    public array $translatable = ['title', 'description', 'badge', 'note'];

    public function getLocale(): string
    {
        return 'en';
    }

    protected function casts(): array
    {
        return ['active' => 'boolean', 'starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /** Sitede görünenler: açık ve tarih aralığında (bitiş günü dahil) */
    public function scopeVisible(Builder $query): void
    {
        $today = now()->toDateString();
        $query->where('active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today));
    }

    /** Paneldeki durum: live | scheduled | expired | off */
    public function status(): string
    {
        if (!$this->active) {
            return 'off';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }
        if ($this->ends_at && $this->ends_at->endOfDay()->isPast()) {
            return 'expired';
        }

        return 'live';
    }
}
