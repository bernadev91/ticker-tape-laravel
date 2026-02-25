<?php

namespace Database\Factories;

use App\Models\Calculation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Calculation>
 */
class CalculationFactory extends Factory
{
    protected $model = Calculation::class;

    public function definition(): array
    {
        return [
            'session_token' => $this->faker->uuid(),
            'expression' => '1+1',
            'result' => '2',
            'had_error' => false,
            'error_message' => null,
        ];
    }
}

