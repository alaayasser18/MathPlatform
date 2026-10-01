<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->unique()->numerify('01#########'),
            'parent_phone' => fake()->numerify('01#########'),
            'grade_id' => Grade::factory(),
            'governorate' => fake()->city(),
            'registered_at' => now()->toDateString(),
        ];
    }
}