<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Admin'),
            self::Editor => __('Editör'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => __('Tüm içerikler ve kullanıcı yönetimi'),
            self::Editor => __('Aktiviteler, bloglar, şehirler vb.'),
        };
    }

    /**
     * Rolün erişebildiği alanlar (Roller sayfasında gösterilir).
     *
     * @return list<string>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::Admin => array_map('__', ['Analitik', 'Tüm içerik modülleri', 'Kullanıcı ekleme, düzenleme, silme', 'Rol atama']),
            self::Editor => array_map('__', ['Analitik', 'Tüm içerik modülleri']),
        };
    }
}
