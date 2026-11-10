<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Panel işlem kaydı (App\Support\ActivityLogger yazar). 1 yıldan eski kayıtlar her gün silinir (model:prune).
 */
class ActivityLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /** Kişi adı, IP, tarayıcı, kayıt adı ve değişiklikler şifreli (App\Support\Privacy) */
    protected $casts = [
        'user_name' => 'encrypted', 'ip' => 'encrypted', 'user_agent' => 'encrypted', 'subject_label' => 'encrypted',
        'changes' => 'encrypted:array', 'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Şifreli alanlarda arama (veritabanı LIKE yapamaz): son kayıtlar çözülüp bakılır.
     *
     * @return list<int>
     */
    public static function idsMatching(string $term, int $window = 5000): array
    {
        $needle = mb_strtolower(trim($term));
        $ids = [];
        static::query()->latest('id')->limit($window)->get(['id', 'user_name', 'ip', 'subject_label', 'changes', 'url'])
            ->each(function (self $log) use ($needle, &$ids) {
                $haystack = mb_strtolower(implode(' ', [$log->user_name, $log->ip, $log->subject_label, $log->url, json_encode($log->changes, JSON_UNESCAPED_UNICODE)]));
                if (str_contains($haystack, $needle)) {
                    $ids[] = $log->id;
                }
            });

        return $ids;
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subYear());
    }
}
