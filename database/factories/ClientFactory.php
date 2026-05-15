<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'naam' => $this->faker->company(),
            'contactpersoon' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'telefoon' => $this->faker->phoneNumber(),
            'sector' => $this->faker->randomElement(['Marketing', 'IT', 'Bouw', 'Zorg', 'Onderwijs', 'Retail']),
            'adres' => $this->faker->streetAddress(),
            'postcode' => $this->faker->postcode(),
            'stad' => $this->faker->city(),
        ];
    }
}
