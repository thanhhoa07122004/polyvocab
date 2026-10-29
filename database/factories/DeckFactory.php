<?php

namespace Database\Factories;

use App\Enums\DeckVisibility;
use App\Models\Deck;
use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deck>
 */
class DeckFactory extends Factory
{
    protected $model = Deck::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'learning_language_id' => Language::factory(),
            'meaning_language_id' => Language::factory(),
            'visibility' => DeckVisibility::PRIVATE,
        ];
    }

    /**
     * Trạng thái công khai.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => DeckVisibility::PUBLIC,
        ]);
    }

    /**
     * Trạng thái riêng tư.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => DeckVisibility::PRIVATE,
        ]);
    }
}

