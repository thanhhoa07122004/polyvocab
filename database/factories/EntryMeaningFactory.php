<?php

namespace Database\Factories;

use App\Models\EntryMeaning;
use App\Models\VocabularyEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EntryMeaning>
 */
class EntryMeaningFactory extends Factory
{
    protected $model = EntryMeaning::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vocabulary_entry_id' => VocabularyEntry::factory(),
            'part_of_speech' => fake()->randomElement(['Danh từ', 'Động từ', 'Tính từ', 'Phó từ', null]),
            'meaning' => fake()->sentence(),
            'sort_order' => 1,
        ];
    }
}

