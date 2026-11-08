<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

/**
 * Yönetim > İşlem kaydı: paneldeki her ekleme, güncelleme, silme, çeviri ve giriş (App\Support\ActivityLogger yazar).
 */
class ActivityLogController extends Controller
{
    /** İşlem → [panelde görünen fiil, renk] */
    public const ACTIONS = [
        'created' => ['ekledi', 'emerald'],
        'updated' => ['güncelledi', 'sky'],
        'deleted' => ['sildi', 'rose'],
        'translated' => ['çevirdi', 'purple'],
        'login' => ['giriş yaptı', 'slate'],
        'logout' => ['çıkış yaptı', 'slate'],
        'login_failed' => ['başarısız giriş denemesi', 'amber'],
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'user' => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'in:' . implode(',', array_keys(self::ACTIONS))],
            'type' => ['nullable', 'string', 'in:' . implode(',', array_keys(ActivityLogger::TYPES))],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $logs = ActivityLog::query()->with('user')
            ->when($filters['user'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('subject_type', $type))
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', \Carbon\Carbon::parse($date)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->where('created_at', '<=', \Carbon\Carbon::parse($date)->endOfDay()))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('subject_label', 'like', "%{$term}%")
                ->orWhere('user_name', 'like', "%{$term}%")
                ->orWhere('ip', 'like', "%{$term}%")
                ->orWhere('changes', 'like', "%{$term}%")))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => User::orderBy('first_name')->get(),
            'todayCount' => ActivityLog::where('created_at', '>=', today())->count(),
            'failedCount' => ActivityLog::where('action', 'login_failed')->where('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    /** Alan adlarının okunur hali ("meta_title (en)" → "SEO başlığı (en)") */
    public static function field(string $field): string
    {
        $names = [
            'title' => 'Başlık', 'name' => 'Ad', 'description' => 'Açıklama', 'content' => 'İçerik', 'excerpt' => 'Özet', 'slug' => 'Adres',
            'active' => 'Yayında', 'sort_order' => 'Sıra', 'meta_title' => 'SEO başlığı', 'meta_description' => 'SEO açıklaması',
            'eyebrow' => 'Üst etiket', 'title_highlight' => 'Kırmızı kısım', 'subtitle' => 'Alt başlık', 'text' => 'Metin', 'question' => 'Soru',
            'answer' => 'Cevap', 'price' => 'Fiyat', 'affiliate_url' => 'Partner linki', 'city_id' => 'Şehir', 'partner_id' => 'Partner',
            'cover_path' => 'Kapak görseli', 'image' => 'Görsel', 'image_mobile' => 'Telefon görseli', 'focus' => 'Kadraj', 'role' => 'Rol',
            'email' => 'E-posta', 'first_name' => 'Ad', 'last_name' => 'Soyad', 'status' => 'Durum', 'rating' => 'Puan', 'published_at' => 'Yayın tarihi',
            'hero_image' => 'Hero görseli', 'hero_image_mobile' => 'Hero telefon görseli', 'hero_focus' => 'Hero kadrajı', 'hero_focus_mobile' => 'Hero telefon kadrajı',
            'hero_overlay' => 'Hero karartması', 'hero_featured' => 'Popüler kartı', 'hero_pass_id' => 'Şehir kartı', 'hero_guide_id' => 'Blog kartı',
            'sections_disabled' => 'Gizli bölümler', 'title_1' => 'Başlık 1. satır', 'title_2' => 'Başlık 2. satır', 'title_3' => 'Başlık 3. satır',
        ];
        $base = preg_replace('/\s*\(.*\)$/', '', $field);
        $suffix = substr($field, strlen($base));

        return ($names[$base] ?? \Illuminate\Support\Str::headline($base)) . $suffix;
    }
}
