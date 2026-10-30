<?php

namespace Tests\Feature;

use App\Enums\Theme;
use App\Models\User;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_theme(): void
    {
        $user = User::factory()->create([
            'theme' => Theme::LIGHT,
        ]);

        $this->actingAs($user);

        $response = $this->patchJson(route('settings.theme.update'), [
            'theme' => 'dark',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'theme' => 'dark',
                'label' => 'Tối',
                'color_scheme' => 'dark',
            ]);

        $user->refresh();
        $this->assertSame(Theme::DARK, $user->theme);
        $this->assertSame('dark', $user->resolvedTheme());
        $this->assertSame('dark', $user->resolvedColorScheme());
    }

    public function test_authenticated_user_can_switch_to_all_five_themes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $expectedThemes = [
            'light' => ['label' => 'Trắng', 'color_scheme' => 'light'],
            'dark' => ['label' => 'Tối', 'color_scheme' => 'dark'],
            'rose_pastel' => ['label' => 'Hồng pastel', 'color_scheme' => 'light'],
            'plum_dark' => ['label' => 'Tím pastel tối', 'color_scheme' => 'dark'],
            'blue_light' => ['label' => 'Xanh dương nhạt', 'color_scheme' => 'light'],
        ];

        foreach ($expectedThemes as $themeKey => $data) {
            $response = $this->patchJson(route('settings.theme.update'), [
                'theme' => $themeKey,
            ]);

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'theme' => $themeKey,
                    'label' => $data['label'],
                    'color_scheme' => $data['color_scheme'],
                ]);

            $user->refresh();
            $this->assertSame($themeKey, $user->theme->value);
        }
    }

    public function test_guest_can_update_theme_cookie_without_login(): void
    {
        $response = $this->patchJson(route('settings.theme.update'), [
            'theme' => 'rose_pastel',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'theme' => 'rose_pastel',
                'label' => 'Hồng pastel',
                'color_scheme' => 'light',
            ])
            ->assertCookie(ThemeService::COOKIE_NAME, 'rose_pastel');
    }

    public function test_invalid_theme_is_rejected_with_validation_error(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->patchJson(route('settings.theme.update'), [
            'theme' => 'invalid_neon_theme',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['theme']);
    }

    public function test_theme_service_resolves_user_theme_first(): void
    {
        $service = app(ThemeService::class);

        $user = User::factory()->create([
            'theme' => Theme::PLUM_DARK,
        ]);

        $resolved = $service->resolveTheme($user);
        $this->assertSame(Theme::PLUM_DARK, $resolved);
    }

    public function test_theme_service_falls_back_to_light_by_default(): void
    {
        $service = app(ThemeService::class);

        $user = User::factory()->create([
            'theme' => null,
        ]);

        $resolved = $service->resolveTheme($user);
        $this->assertSame(Theme::LIGHT, $resolved);
    }

    public function test_theme_switcher_renders_in_layout(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('theme-switcher-button')
            ->assertSee('Trắng')
            ->assertSee('Tối')
            ->assertSee('Hồng pastel')
            ->assertSee('Tím pastel tối')
            ->assertSee('Xanh dương nhạt')
            ->assertSee('data-theme="light"', false);
    }
}

