<?php

namespace App\Models;

use App\Services\ImageUploader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = ['name', 'slug', 'website', 'logo_path', 'active', 'show_on_site'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'show_on_site' => 'boolean'];
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class, 'partner', 'slug');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 2));
    }

    public function logoUrl(): ?string
    {
        return ImageUploader::url($this->logo_path);
    }

    public function websiteHost(): ?string
    {
        return $this->website ? preg_replace('/^www\./', '', parse_url($this->website, PHP_URL_HOST) ?? '') : null;
    }
}
