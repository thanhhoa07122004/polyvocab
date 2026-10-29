<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->member = User::factory()->create(['role' => UserRole::MEMBER]);
    }

    public function test_guests_and_members_cannot_access_language_management(): void
    {
        // Khách không được phép truy cập
        $this->get(route('admin.languages.index'))->assertRedirect(route('login'));
        $this->get(route('admin.languages.create'))->assertRedirect(route('login'));

        // Thành viên thông thường bị cấm (403)
        $this->actingAs($this->member)
            ->get(route('admin.languages.index'))
            ->assertForbidden();

        $this->actingAs($this->member)
            ->get(route('admin.languages.create'))
            ->assertForbidden();

        $this->actingAs($this->member)
            ->post(route('admin.languages.store'), [
                'code' => 'fr',
                'name' => 'Tiếng Pháp',
                'native_name' => 'Français',
                'direction' => 'ltr',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_view_language_list_with_search_and_pagination(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'Tiếng Anh', 'native_name' => 'English']);
        Language::factory()->create(['code' => 'ja', 'name' => 'Tiếng Nhật', 'native_name' => '日本語']);

        $response = $this->actingAs($this->admin)->get(route('admin.languages.index'));
        $response->assertOk();
        $response->assertSee('Tiếng Anh');
        $response->assertSee('Tiếng Nhật');

        // Tìm kiếm theo mã hoặc tên
        $searchResponse = $this->actingAs($this->admin)->get(route('admin.languages.index', ['search' => 'ja']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Tiếng Nhật');
        $searchResponse->assertDontSee('Tiếng Anh');
    }

    public function test_admin_can_create_a_valid_language(): void
    {
        $payload = [
            'code' => 'es',
            'name' => 'Tiếng Tây Ban Nha',
            'native_name' => 'Español',
            'direction' => 'ltr',
            'sort_order' => 10,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.languages.store'), $payload);

        $response->assertRedirect(route('admin.languages.index'));
        $this->assertDatabaseHas('languages', [
            'code' => 'es',
            'name' => 'Tiếng Tây Ban Nha',
            'native_name' => 'Español',
            'direction' => 'ltr',
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    public function test_language_code_must_be_unique(): void
    {
        Language::factory()->create(['code' => 'vi', 'name' => 'Tiếng Việt']);

        $response = $this->actingAs($this->admin)->post(route('admin.languages.store'), [
            'code' => 'vi',
            'name' => 'Tiếng Việt Bản Mới',
            'native_name' => 'Tiếng Việt',
            'direction' => 'ltr',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_admin_can_update_language_details_but_code_remains_unchanged(): void
    {
        $language = Language::factory()->create([
            'code' => 'de',
            'name' => 'Tiếng Đức Cũ',
            'native_name' => 'Deutsch Old',
            'direction' => 'ltr',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.languages.update', $language), [
            'code' => 'de-modified', // Cố tình đổi code
            'name' => 'Tiếng Đức Mới',
            'native_name' => 'Deutsch',
            'direction' => 'ltr',
            'sort_order' => 5,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.languages.index'));

        $language->refresh();
        $this->assertEquals('de', $language->code); // Code không được phép đổi
        $this->assertEquals('Tiếng Đức Mới', $language->name);
        $this->assertEquals('Deutsch', $language->native_name);
        $this->assertEquals(5, $language->sort_order);
    }

    public function test_admin_can_toggle_language_active_status(): void
    {
        $language = Language::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->patch(route('admin.languages.toggle', $language))
            ->assertRedirect();

        $this->assertFalse($language->fresh()->is_active);

        $this->actingAs($this->admin)
            ->patch(route('admin.languages.toggle', $language))
            ->assertRedirect();

        $this->assertTrue($language->fresh()->is_active);
    }

    public function test_language_seeder_is_idempotent_and_does_not_overwrite_customizations(): void
    {
        // Chạy lần đầu
        $this->seed(LanguageSeeder::class);

        $this->assertDatabaseHas('languages', ['code' => 'vi', 'name' => 'Tiếng Việt']);
        $this->assertDatabaseHas('languages', ['code' => 'en', 'name' => 'Tiếng Anh']);
        $this->assertDatabaseHas('languages', ['code' => 'zh', 'name' => 'Tiếng Trung']);
        $this->assertDatabaseHas('languages', ['code' => 'ja', 'name' => 'Tiếng Nhật']);
        $this->assertDatabaseHas('languages', ['code' => 'ko', 'name' => 'Tiếng Hàn']);
        $this->assertEquals(5, Language::count());

        // Admin chỉnh sửa tên và tắt tiếng Nhật
        $ja = Language::where('code', 'ja')->first();
        $ja->update([
            'name' => 'Nhật ngữ tùy chỉnh',
            'is_active' => false,
        ]);

        // Chạy lại Seeder
        $this->seed(LanguageSeeder::class);

        // Số lượng không đổi (không nhân đôi)
        $this->assertEquals(5, Language::count());

        // Chỉnh sửa của người dùng không bị ghi đè
        $ja->refresh();
        $this->assertEquals('Nhật ngữ tùy chỉnh', $ja->name);
        $this->assertFalse($ja->is_active);
    }

    public function test_cache_is_refreshed_when_language_is_modified(): void
    {
        $language = Language::factory()->create(['code' => 'fr', 'is_active' => true]);

        // Đưa vào cache
        $activeLangs = Language::getActiveCached();
        $this->assertTrue($activeLangs->contains('code', 'fr'));

        // Toggle tắt
        $this->actingAs($this->admin)->patch(route('admin.languages.toggle', $language));

        // Cache phải được làm mới
        $newActive = Language::getActiveCached();
        $this->assertFalse($newActive->contains('code', 'fr'));
    }

    public function test_adding_new_rtl_language_outside_sample_data_works_without_code_change(): void
    {
        // Thêm ngôn ngữ Ả Rập (ar - RTL)
        $response = $this->actingAs($this->admin)->post(route('admin.languages.store'), [
            'code' => 'ar',
            'name' => 'Tiếng Ả Rập',
            'native_name' => 'العربية',
            'direction' => 'rtl',
            'sort_order' => 20,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.languages.index'));

        $ar = Language::where('code', 'ar')->first();
        $this->assertNotNull($ar);
        $this->assertTrue($ar->isRtl());
        $this->assertEquals('rtl', $ar->direction);

        // Thành viên vào trang tạo deck sẽ thấy xuất hiện trong danh sách chọn
        $createPage = $this->actingAs($this->member)->get(route('decks.create'));
        $createPage->assertOk();
        $createPage->assertSee('Tiếng Ả Rập');
        $createPage->assertSee('العربية');
    }
}

