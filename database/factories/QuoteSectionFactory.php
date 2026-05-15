<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\QuoteSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteSection>
 */
class QuoteSectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quote_id' => Quote::factory(),
            'titel' => $this->faker->randomElement(['Aanpak', 'Werkzaamheden', 'Planning', 'Deliverables', 'Over ons']),
            'content' => [
                'tekst' => $this->faker->paragraphs(2, true),
            ],
            'volgorde' => $this->faker->numberBetween(0, 10),
        ];
    }
}
