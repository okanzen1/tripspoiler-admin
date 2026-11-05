<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * İlk admin hesabı .env'deki ADMIN_* değerlerinden oluşturulur.
     */
    public function run(): void
    {
        $this->call([LanguageSeeder::class, CountrySeeder::class, CitySeeder::class, PartnerSeeder::class, PageSeeder::class]);

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL')],
            [
                'first_name' => env('ADMIN_FIRST_NAME'),
                'last_name' => env('ADMIN_LAST_NAME'),
                'password' => env('ADMIN_PASSWORD'),
                'role' => Role::Admin,
            ],
        );
    }
}
