<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'dylan.burodebom@gmail.com'],
            [
                'name' => 'Dylan',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            ClientSeeder::class,
        ]);
    }
}
