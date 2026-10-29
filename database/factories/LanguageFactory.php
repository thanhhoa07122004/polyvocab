<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->lexify('???');

        return [
            'code' => $code,
            'name' => 'Ngôn ngữ ' . $code,
            'native_name' => 'Native ' . $code,
            'direction' => 'ltr',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Trạng thái không hoạt động.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Chiều từ phải sang trái (RTL).
     */
    public function rtl(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'rtl',
        ]);
    }
}

