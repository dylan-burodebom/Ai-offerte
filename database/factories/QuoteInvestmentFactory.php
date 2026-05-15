<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\QuoteInvestment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteInvestment>
 */
class QuoteInvestmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quote_id' => Quote::factory(),
            'omschrijving' => $this->faker->randomElement([
                'Strategie & concept',
                'Ontwerp',
                'Ontwikkeling',
                'Content creatie',
                'Projectmanagement',
                'Hosting & beheer',
            ]),
            'bedrag' => $this->faker->randomFloat(2, 250, 10000),
            'volgorde' => $this->faker->numberBetween(0, 10),
        ];
    }
}
