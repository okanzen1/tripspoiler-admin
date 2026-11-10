<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ziyaretçi yorumu. activity_id boşsa sitenin geneli hakkında. Ad ve e-posta şifreli tutulur (App\Support\Privacy).
 */
class Review extends Model
{
    protected $fillable = ['activity_id', 'name', 'email', 'rating', 'comment', 'approved'];

    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'email' => 'encrypted',
            'rating' => 'integer',
            'approved' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $review) {
            $review->email_hash = \App\Support\Privacy::hash($review->email);
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('approved', true);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('approved', false);
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr(trim($this->name), 0, 1));
    }
}
