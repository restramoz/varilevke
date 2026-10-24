<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (! is_string($password) || strlen($password) < 8) {
            $this->command?->error('ADMIN_PASSWORD (min 8 chars) must be set in .env before seeding an admin.');

            return;
        }

        // Admin from env (bcrypt hashed here), never committed as plaintext.
        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@varilevke.test')],
            [
                'name' => env('ADMIN_NAME', 'Varilevke Admin'),
                'password' => Hash::make($password),
                'is_admin' => true,
            ],
        );

        $this->call([
            SettingSeeder::class,
            CatalogItemSeeder::class,
            RegionSeeder::class,
        ]);
    }
}
