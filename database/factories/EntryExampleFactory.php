<?php

namespace Database\Factories;

use App\Models\EntryExample;
use App\Models\EntryMeaning;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EntryExample>
 */
class EntryExampleFactory extends Factory
{
    protected $model = EntryExample::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_meaning_id' => EntryMeaning::factory(),
            'sentence' => fake()->sentence(),
            'translation' => fake()->optional()->sentence(),
            'sort_order' => 1,
        ];
    }
}

