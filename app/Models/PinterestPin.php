<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bir blogun Pinterest'e bir gönderimi (App\Services\Pinterest). pin_id boşsa gönderilemedi (error'da sebebi).
 */
class PinterestPin extends Model
{
    protected $fillable = ['blog_id', 'pin_id', 'board_id', 'manual', 'error'];

    protected function casts(): array
    {
        return ['manual' => 'boolean'];
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function scopeSent(Builder $query): void
    {
        $query->whereNotNull('pin_id');
    }

    public function scopeFailed(Builder $query): void
    {
        $query->whereNull('pin_id');
    }

    public function url(): ?string
    {
        return $this->pin_id ? 'https://www.pinterest.com/pin/' . $this->pin_id . '/' : null;
    }
}
