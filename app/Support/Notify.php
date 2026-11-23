<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\PanelNotice;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Panel bildirimleri gönderir. Kime: herkes (yeni e-posta, yardım talebi, yorum, abone), sadece adminler (sistem
 * hataları) ya da işi başlatan kişi (çeviri). Okundu bilgisi kişiye özeldir (Laravel notifications tablosu).
 */
class Notify
{
    /** tür => [Türkçe ad, renk, simge (svg path)] */
    public const KINDS = [
        'mail' => ['E-posta', 'sky', 'M4 6h16v12H4zM4 7l8 6 8-6'],
        'help' => ['Yardım talebi', 'amber', 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01'],
        'review' => ['Yorum', 'yellow', 'm12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z'],
        'subscriber' => ['Abone', 'emerald', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM19 8v6M22 11h-6'],
        'translation' => ['Çeviri', 'purple', 'm5 8 6 6M4 14l6-6 2-3M2 5h12M22 22l-5-10-5 10M14 18h6'],
        'translation_failed' => ['Çeviri hatası', 'red', 'm5 8 6 6M4 14l6-6 2-3M2 5h12M22 22l-5-10-5 10M14 18h6'],
        'system' => ['Sistem', 'slate', 'M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z'],
        'seo' => ['SEO', 'blue', 'M21 21l-4.3-4.3M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14z'],
    ];

    /** Renk → simge kutusunun sınıfları (Tailwind tam sınıf adlarını görsün diye burada yazılı) */
    public const TONES = [
        'sky' => 'bg-sky-100 text-sky-600',
        'amber' => 'bg-amber-100 text-amber-600',
        'yellow' => 'bg-yellow-100 text-yellow-600',
        'emerald' => 'bg-emerald-100 text-emerald-600',
        'purple' => 'bg-purple-100 text-purple-600',
        'red' => 'bg-red-100 text-red-600',
        'slate' => 'bg-slate-100 text-slate-600',
        'blue' => 'bg-blue-100 text-blue-600',
    ];

    public static function everyone(string $kind, string $title, ?string $body = null, ?string $url = null): void
    {
        self::send(User::all(), $kind, $title, $body, $url);
    }

    public static function admins(string $kind, string $title, ?string $body = null, ?string $url = null): void
    {
        self::send(User::where('role', Role::Admin)->get(), $kind, $title, $body, $url);
    }

    /** İşi başlatan kişiye; kim olduğu bilinmiyorsa (ör. zamanlayıcı) herkese */
    public static function user(?int $userId, string $kind, string $title, ?string $body = null, ?string $url = null): void
    {
        $user = $userId ? User::find($userId) : null;
        $user ? self::send(collect([$user]), $kind, $title, $body, $url) : self::everyone($kind, $title, $body, $url);
    }

    /** Zil ve sayfa için tek bildirimin görünen hali */
    public static function present(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        [$label, $color, $icon] = self::KINDS[$data['kind'] ?? 'system'] ?? self::KINDS['system'];

        return [
            'id' => $notification->id,
            'kind' => $data['kind'] ?? 'system',
            'label' => __($label),
            'tone' => self::TONES[$color] ?? self::TONES['slate'],
            'icon' => $icon,
            'title' => (string) ($data['title'] ?? ''),
            'body' => $data['body'] ?? null,
            'url' => $data['url'] ?? null,
            'open' => route('notifications.open', $notification->id),
            'read' => $notification->read_at !== null,
            'time' => $notification->created_at?->diffForHumans(),
            'date' => $notification->created_at?->format('d.m.Y H:i'),
        ];
    }

    /** @param  Collection<int, User>  $users */
    private static function send(Collection $users, string $kind, string $title, ?string $body, ?string $url): void
    {
        if ($users->isEmpty()) {
            return;
        }
        rescue(fn () => Notification::send($users, new PanelNotice($kind, Str::limit($title, 180), $body !== null ? Str::limit($body, 400) : null, $url)));
    }
}
