<?php

namespace App\Enums;

enum ActivityType: string
{
    case Product = 'product';
    case Pass = 'pass';
    case Package = 'package';

    public function label(): string
    {
        return match ($this) {
            self::Product => __('Ürün'),
            self::Pass => __('Şehir kartı (Pass)'),
            self::Package => __('Paket'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Product => __('Tek bir tur, bilet ya da deneyim'),
            self::Pass => __('Birçok yere giriş içeren şehir kartı'),
            self::Package => __('Birden fazla aktiviteyi birleştiren paket'),
        };
    }
}
