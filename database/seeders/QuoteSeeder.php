<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $clients = Client::all();

        $clients->each(function (Client $client) use ($user) {
            $quote = Quote::factory()->create([
                'client_id' => $client->id,
                'user_id' => $user->id,
            ]);

            $quote->sections()->createMany([
                ['titel' => 'Aanpak', 'content' => ['tekst' => 'Onze bewezen aanpak garandeert resultaat.'], 'volgorde' => 1],
                ['titel' => 'Werkzaamheden', 'content' => ['tekst' => 'Overzicht van alle werkzaamheden.'], 'volgorde' => 2],
            ]);

            $quote->investments()->createMany([
                ['omschrijving' => 'Strategie & concept', 'bedrag' => 1500, 'volgorde' => 1],
                ['omschrijving' => 'Ontwerp', 'bedrag' => 2500, 'volgorde' => 2],
                ['omschrijving' => 'Ontwikkeling', 'bedrag' => 4000, 'volgorde' => 3],
            ]);
        });
    }
}
