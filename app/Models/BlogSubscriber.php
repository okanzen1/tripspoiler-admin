<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * Blog abonesi. E-posta şifreli tutulur (front ile aynı APP_KEY); aynı adres email_hash ile tekrar eklenmez.
 */
class BlogSubscriber extends Model
{
    protected $fillable = ['email', 'source_path', 'unsubscribed_at'];

    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (self $subscriber) => $subscriber->email_hash = self::hash($subscriber->email));
    }

    public static function hash(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }

    public static function findByEmail(string $email): ?self
    {
        return self::where('email_hash', self::hash($email))->first();
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('unsubscribed_at');
    }

    public function isActive(): bool
    {
        return $this->unsubscribed_at === null;
    }

    /**
     * Toplu e-postanın altındaki "abonelikten çık" bağlantısı (imzalı, tahmin edilemez).
     */
    public function unsubscribeUrl(): string
    {
        return URL::signedRoute('subscribers.unsubscribe', ['subscriber' => $this->id]);
    }
}
