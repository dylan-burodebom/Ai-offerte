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
            User::factory()->make(['name' => 'Dylan'])->toArray(),
        );

        $this->call([
            ClientSeeder::class,
            QuoteSeeder::class,
        ]);
    }
}
