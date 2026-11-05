<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Sitenin sabit sayfaları. Var olanların SEO bilgisine dokunmaz.
     */
    public function run(): void
    {
        $pages = [
            ['home', 'Anasayfa', '/'],
            ['activities', 'Aktiviteler', '/activities'],
            ['cities', 'Şehirler', '/cities'],
            ['blog', 'Blog', '/blog'],
            ['reviews', 'Yorumlar', '/reviews'],
            ['about', 'Hakkımızda', '/about'],
            ['contact', 'İletişim', '/contact'],
            ['privacy', 'Gizlilik politikası', '/privacy'],
        ];

        foreach ($pages as $order => [$key, $name, $path]) {
            Page::firstOrCreate(['key' => $key], ['name' => $name, 'path' => $path, 'sort_order' => $order]);
        }
    }
}
