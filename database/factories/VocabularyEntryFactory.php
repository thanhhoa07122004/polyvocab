<?php

namespace Database\Factories;

use App\Models\Deck;
use App\Models\VocabularyEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VocabularyEntry>
 */
class VocabularyEntryFactory extends Factory
{
    protected $model = VocabularyEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deck_id' => Deck::factory(),
            'term' => fake()->word(),
            'pronunciation' => fake()->optional()->word(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}

