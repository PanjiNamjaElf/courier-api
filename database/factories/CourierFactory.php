<?php

namespace Database\Factories;

use App\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Courier>
 */
class CourierFactory extends Factory
{
    /**
     * Data bawaan kurir palsu untuk test dan seeder.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName().' '.fake()->lastName(),
            'phone' => '08'.fake()->unique()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'level' => fake()->numberBetween(Courier::MIN_LEVEL, Courier::MAX_LEVEL),
            'registered_at' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
        ];
    }
}
