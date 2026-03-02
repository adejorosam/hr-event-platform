<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $country = $this->faker->randomElement(['USA', 'Germany']);

        $base = [
            'name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'salary' => $this->faker->numberBetween(30000, 150000),
            'country' => $country,
        ];

        return match ($country) {
            'USA' => array_merge($base, [
                'ssn' => $this->faker->numerify('###-##-####'),
                'address' => $this->faker->address(),
            ]),
            'Germany' => array_merge($base, [
                'goal' => $this->faker->sentence(),
                'tax_id' => 'DE' . $this->faker->numerify('#########'),
            ]),
        };
    }

    public function usa(): static
    {
        return $this->state(fn () => [
            'country' => 'USA',
            'ssn' => $this->faker->numerify('###-##-####'),
            'address' => $this->faker->address(),
            'goal' => null,
            'tax_id' => null,
        ]);
    }

    public function germany(): static
    {
        return $this->state(fn () => [
            'country' => 'Germany',
            'ssn' => null,
            'address' => null,
            'goal' => $this->faker->sentence(),
            'tax_id' => 'DE' . $this->faker->numerify('#########'),
        ]);
    }
}
