<?php

namespace App\Support;

/**
 * Özellik kartlarında seçilebilen ikonlar (24x24, çizgi). Sitede de aynısı var: tripspoiler/app/Support/FeatureIcons.php
 * Yeni ikon eklenirse iki dosyaya da eklenmeli.
 */
class FeatureIcons
{
    public const ICONS = [
        'ticket' => ['Bilet', '<path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v1.5a2.5 2.5 0 0 0 0 5V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1.5a2.5 2.5 0 0 0 0-5z"/><path d="M14 6v12" stroke-dasharray="2 2"/>'],
        'percent' => ['İndirim', '<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>'],
        'wallet' => ['Cüzdan', '<path d="M4 7a2 2 0 0 1 2-2h11v4"/><path d="M4 7v10a2 2 0 0 0 2 2h14V9H6a2 2 0 0 1-2-2z"/><circle cx="16" cy="14" r="1.2"/>'],
        'headphones' => ['Sesli rehber', '<path d="M4 15v-3a8 8 0 0 1 16 0v3"/><path d="M4 15a2 2 0 0 1 2-2h1v7H6a2 2 0 0 1-2-2zM20 15a2 2 0 0 0-2-2h-1v7h1a2 2 0 0 0 2-2z"/>'],
        'globe' => ['Dünya / dil', '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/>'],
        'map-pin' => ['Konum', '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>'],
        'landmark' => ['Müze / yapı', '<path d="M3 21h18M5 21V10M19 21V10M9 21V10M15 21V10"/><path d="M12 3 3 8h18z"/>'],
        'compass' => ['Pusula', '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>'],
        'book' => ['Rehber / kitap', '<path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2z"/><path d="M8 7h6M8 11h4"/>'],
        'zap' => ['Hızlı', '<path d="M13 3 5 14h6l-1 7 8-11h-6z"/>'],
        'smartphone' => ['Telefon', '<rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18.5h2"/>'],
        'clock' => ['Saat', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
        'calendar' => ['Takvim', '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>'],
        'shield' => ['Güven', '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z"/><path d="m9 12 2 2 4-4"/>'],
        'star' => ['Yıldız', '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>'],
        'heart' => ['Kalp', '<path d="M12 20s-7-4.4-9-9a4.8 4.8 0 0 1 9-3 4.8 4.8 0 0 1 9 3c-2 4.6-9 9-9 9z"/>'],
        'thumbs-up' => ['Beğeni', '<path d="M7 10v10H4V10zM7 10l4-7a2 2 0 0 1 3 2l-1 5h6a2 2 0 0 1 2 2.3l-1.3 6A2 2 0 0 1 17.7 20H7"/>'],
        'users' => ['İnsanlar', '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.4 3.4-5.5 6.5-5.5s5.7 2.1 6.5 5.5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c1.9.8 3.1 2.6 3.5 5.2"/>'],
        'message' => ['Destek', '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>'],
        'camera' => ['Fotoğraf', '<path d="M4 8a2 2 0 0 1 2-2h2l1.5-2h5L16 6h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="12" cy="13" r="3.5"/>'],
        'ship' => ['Tekne / tur', '<path d="M3 17c2 2 4 2 6 0s4-2 6 0 4 2 6 0"/><path d="M5 14 4 10h16l-1 4M8 10V6h8v4M12 3v3"/>'],
        'gift' => ['Hediye', '<rect x="3.5" y="8" width="17" height="4" rx="1"/><path d="M5 12v8h14v-8M12 8v12M12 8S10.5 4 8 4.5 7.5 8 12 8zM12 8s1.5-4 4-3.5S16.5 8 12 8z"/>'],
        'sparkles' => ['Öne çıkan', '<path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>'],
        'check' => ['Onay', '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>'],
        'utensils' => ['Yemek', '<path d="M7 3v8a2 2 0 0 0 2 2v8M11 3v8a2 2 0 0 1-2 2M7 3v5M11 3v5"/><path d="M17 21V3c-2 1.5-3 4-3 7v4h3"/>'],
        'bed' => ['Konaklama', '<path d="M3 19V6M3 15h18v4M21 15v-3a3 3 0 0 0-3-3h-7v6"/><circle cx="7" cy="11" r="2"/>'],
        'bus' => ['Ulaşım', '<rect x="4" y="3.5" width="16" height="14" rx="3"/><path d="M4 11h16M8 17.5V20M16 17.5V20"/><circle cx="8" cy="14.5" r="1"/><circle cx="16" cy="14.5" r="1"/>'],
        'plane' => ['Uçak', '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>'],
        'sun' => ['Güneş / mevsim', '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/>'],
        'route' => ['Rota', '<circle cx="6" cy="19" r="2.5"/><circle cx="18" cy="5" r="2.5"/><path d="M8.5 19H16a3.5 3.5 0 0 0 0-7H8a3.5 3.5 0 0 1 0-7h7.5"/>'],
        'bag' => ['Alışveriş', '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/>'],
        'info' => ['Bilgi', '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>'],
        'moon' => ['Gece hayatı', '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>'],
    ];

    public static function svg(?string $key, string $class = 'size-6'): string
    {
        $paths = self::ICONS[$key][1] ?? self::ICONS['sparkles'][1];

        return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
    }
}
