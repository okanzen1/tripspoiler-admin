<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * Site yazar (tripspoiler/app/Support/Tracker.php). IP şifreli; gruplama ve arama ip_hash ile (App\Support\Privacy).
 * 6 aydan eski kayıtlar her gece silinir (model:prune, routes/console.php).
 */
class AffiliateClick extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    /** Kayıtlar bu kadar ay saklanır (gizlilik sayfasında da yazar) */
    public const KEEP_MONTHS = 6;

    protected $guarded = ['id'];

    protected $casts = ['ip' => 'encrypted', 'created_at' => 'datetime'];

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subMonths(self::KEEP_MONTHS));
    }
}
