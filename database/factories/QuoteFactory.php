<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use App\Services\OfferteNummerService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'offerte_nummer' => app(OfferteNummerService::class)->genereer(),
            'titel' => $this->faker->sentence(4),
            'status' => $this->faker->randomElement(['concept', 'verzonden', 'gewonnen', 'verloren']),
            'geldig_tot' => $this->faker->dateTimeBetween('now', '+3 months'),
            'totaal' => $this->faker->randomFloat(2, 500, 50000),
            'versie' => 1,
        ];
    }

    public function concept(): static
    {
        return $this->state(['status' => 'concept']);
    }

    public function verzonden(): static
    {
        return $this->state(['status' => 'verzonden']);
    }

    public function gewonnen(): static
    {
        return $this->state(['status' => 'gewonnen']);
    }

    public function verloren(): static
    {
        return $this->state(['status' => 'verloren']);
    }
}
