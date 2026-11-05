<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    /**
     * Eski sitedeki partner (megapass) taşınır; varsa dokunmaz.
     */
    public function run(): void
    {
        Partner::firstOrCreate(['slug' => 'megapass'], ['name' => 'Megapass', 'active' => true]);
    }
}
