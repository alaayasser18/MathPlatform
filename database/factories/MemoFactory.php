<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\Memo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Memo>
 */
class MemoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grade_id' => Grade::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'file_url' => 'https://example.com/memo.pdf',
            'file_size' => '2.5 MB',
            'pages' => 45,
            'year' => '2024 / 2025',
            'cover_image_url' => 'https://example.com/cover.jpg',
            'features' => ['شرح مبسط', 'أسئلة وتدريبات'],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
