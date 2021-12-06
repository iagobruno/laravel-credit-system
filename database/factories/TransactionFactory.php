<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $amount = $this->faker->numberBetween(-10_000, 10_000);

        return [
            'amount' => $amount,
            'description' => $amount >= 0 ? 'Added credits' : 'Bought an item'
        ];
    }
}
