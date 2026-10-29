<?php

namespace Tests\Feature;

use App\Enums\DeckVisibility;
use App\Enums\UserRole;
use App\Models\Deck;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeckTest extends TestCase
{
    use RefreshDatabase;

    private User $user1;
    private User $user2;
    private User $admin;
    private Language $langVi;
    private Language $langEn;
    private Language $langAr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create(['name' => 'Alice', 'role' => UserRole::MEMBER]);
        $this->user2 = User::factory()->create(['name' => 'Bob', 'role' => UserRole::MEMBER]);
        $this->admin = User::factory()->create(['name' => 'Admin User', 'role' => UserRole::ADMIN]);

        $this->langVi = Language::factory()->create(['code' => 'vi', 'name' => 'Tiếng Việt', 'direction' => 'ltr', 'is_active' => true]);
        $this->langEn = Language::factory()->create(['code' => 'en', 'name' => 'Tiếng Anh', 'direction' => 'ltr', 'is_active' => true]);
        $this->langAr = Language::factory()->create(['code' => 'ar', 'name' => 'Tiếng Ả Rập', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_active' => true]);
    }

    public function test_member_can_create_deck_and_owner_is_always_authenticated_user(): void
    {
        $payload = [
            'name' => 'Từ vựng IELTS Band 7.0',
            'description' => 'Tập hợp từ vựng học thuật thông dụng',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => DeckVisibility::PRIVATE->value,
            // Giả lập hacker cố tình gửi user_id của người khác
            'user_id' => $this->user2->id,
        ];

        $response = $this->actingAs($this->user1)->post(route('decks.store'), $payload);

        $deck = Deck::first();
        $this->assertNotNull($deck);
        $response->assertRedirect(route('decks.show', $deck));

        // Phải thuộc về user1 (người đăng nhập), không thể bị chiếm đoạt qua user_id gửi lên
        $this->assertEquals($this->user1->id, $deck->user_id);
        $this->assertEquals('Từ vựng IELTS Band 7.0', $deck->name);
        $this->assertEquals(DeckVisibility::PRIVATE, $deck->visibility);
    }

    public function test_learning_and_meaning_language_can_be_the_same(): void
    {
        $payload = [
            'name' => 'English Monolingual Advanced',
            'description' => 'Học định nghĩa bằng chính tiếng Anh',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langEn->id,
            'visibility' => DeckVisibility::PUBLIC->value,
        ];

        $response = $this->actingAs($this->user1)->post(route('decks.store'), $payload);

        $deck = Deck::first();
        $this->assertNotNull($deck);
        $response->assertRedirect(route('decks.show', $deck));
        $this->assertEquals($this->langEn->id, $deck->learning_language_id);
        $this->assertEquals($this->langEn->id, $deck->meaning_language_id);
    }

    public function test_invalid_visibility_is_rejected(): void
    {
        $payload = [
            'name' => 'Bộ từ thử nghiệm',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => 'unsupported_visibility',
        ];

        $response = $this->actingAs($this->user1)->post(route('decks.store'), $payload);
        $response->assertSessionHasErrors(['visibility']);
    }

    public function test_inactive_language_cannot_be_used_when_creating_deck(): void
    {
        $inactiveLang = Language::factory()->inactive()->create(['code' => 'la', 'name' => 'Tiếng Latin']);

        $payload = [
            'name' => 'Tiếng Latin cơ bản',
            'learning_language_id' => $inactiveLang->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => DeckVisibility::PRIVATE->value,
        ];

        $response = $this->actingAs($this->user1)->post(route('decks.store'), $payload);
        $response->assertSessionHasErrors(['learning_language_id']);
        $this->assertEquals(0, Deck::count());
    }

    public function test_my_decks_page_only_shows_authenticated_users_decks(): void
    {
        $myDeck = Deck::factory()->for($this->user1)->create([
            'name' => 'Bộ từ của Alice',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => DeckVisibility::PRIVATE,
        ]);

        $otherDeck = Deck::factory()->for($this->user2)->create([
            'name' => 'Bộ từ của Bob',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => DeckVisibility::PRIVATE,
        ]);

        $response = $this->actingAs($this->user1)->get(route('decks.index'));

        $response->assertOk();
        $response->assertSee('Bộ từ của Alice');
        $response->assertDontSee('Bộ từ của Bob');
    }

    public function test_guest_and_other_users_can_view_public_deck(): void
    {
        $publicDeck = Deck::factory()->for($this->user1)->public()->create([
            'name' => 'Bộ từ công khai tiếng Anh',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        // Khách chưa đăng nhập xem được
        $guestResponse = $this->get(route('decks.show', $publicDeck));
        $guestResponse->assertOk();
        $guestResponse->assertSee('Bộ từ công khai tiếng Anh');
        $guestResponse->assertSee('Chưa có từ vựng');

        // Thành viên khác xem được
        $otherResponse = $this->actingAs($this->user2)->get(route('decks.show', $publicDeck));
        $otherResponse->assertOk();
        $otherResponse->assertSee('Bộ từ công khai tiếng Anh');
    }

    public function test_private_deck_returns_404_for_guest_other_members_and_admin(): void
    {
        $privateDeck = Deck::factory()->for($this->user1)->private()->create([
            'name' => 'Bí mật của Alice',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        // Khách nhận 404 (không tiết lộ sự tồn tại)
        $this->get(route('decks.show', $privateDeck))->assertNotFound();

        // Thành viên khác nhận 404
        $this->actingAs($this->user2)->get(route('decks.show', $privateDeck))->assertNotFound();

        // Quản trị viên không sở hữu cũng nhận 404
        $this->actingAs($this->admin)->get(route('decks.show', $privateDeck))->assertNotFound();

        // Chủ sở hữu xem được bình thường
        $this->actingAs($this->user1)->get(route('decks.show', $privateDeck))->assertOk();
    }

    public function test_only_owner_can_edit_and_delete_their_deck(): void
    {
        $deck = Deck::factory()->for($this->user1)->public()->create([
            'name' => 'Bộ từ ban đầu',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        // Thành viên khác không thể vào trang sửa (403)
        $this->actingAs($this->user2)->get(route('decks.edit', $deck))->assertForbidden();

        // Thành viên khác không thể cập nhật (403)
        $this->actingAs($this->user2)->put(route('decks.update', $deck), [
            'name' => 'Cố tình sửa',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => DeckVisibility::PUBLIC->value,
        ])->assertForbidden();

        // Quản trị viên cũng không được tự động sửa bộ từ của người khác (403)
        $this->actingAs($this->admin)->put(route('decks.update', $deck), [
            'name' => 'Admin cố tình sửa',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => DeckVisibility::PUBLIC->value,
        ])->assertForbidden();

        // Chủ sở hữu sửa thành công
        $this->actingAs($this->user1)->put(route('decks.update', $deck), [
            'name' => 'Tên đã được cập nhật',
            'description' => 'Mô tả mới',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => DeckVisibility::PRIVATE->value,
        ])->assertRedirect(route('decks.show', $deck));

        $deck->refresh();
        $this->assertEquals('Tên đã được cập nhật', $deck->name);
        $this->assertEquals(DeckVisibility::PRIVATE, $deck->visibility);

        // Thành viên khác không thể xóa (nhận 404 vì deck hiện là private)
        $this->actingAs($this->user2)->delete(route('decks.destroy', $deck))->assertNotFound();

        // Quản trị viên không thể xóa bộ từ private của người khác (404)
        $this->actingAs($this->admin)->delete(route('decks.destroy', $deck))->assertNotFound();

        // Chủ sở hữu xóa thành công
        $this->actingAs($this->user1)->delete(route('decks.destroy', $deck))
            ->assertRedirect(route('decks.index'));

        $this->assertDatabaseMissing('decks', ['id' => $deck->id]);
    }

    public function test_deck_with_inactive_language_can_still_update_name_and_description(): void
    {
        $inactiveLang = Language::factory()->inactive()->create(['code' => 'ru', 'name' => 'Tiếng Nga']);

        // Bộ từ đã tạo từ trước khi ngôn ngữ bị tắt
        $deck = Deck::factory()->for($this->user1)->create([
            'name' => 'Bộ từ tiếng Nga cũ',
            'description' => 'Mô tả cũ',
            'learning_language_id' => $inactiveLang->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => DeckVisibility::PUBLIC,
        ]);

        // Cập nhật tên và mô tả, giữ nguyên ngôn ngữ đang tắt
        $response = $this->actingAs($this->user1)->put(route('decks.update', $deck), [
            'name' => 'Bộ từ tiếng Nga đã đổi tên',
            'description' => 'Mô tả mới cập nhật',
            'learning_language_id' => $inactiveLang->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => DeckVisibility::PUBLIC->value,
        ]);

        $response->assertRedirect(route('decks.show', $deck));
        $deck->refresh();
        $this->assertEquals('Bộ từ tiếng Nga đã đổi tên', $deck->name);
        $this->assertEquals('Mô tả mới cập nhật', $deck->description);

        // Nhưng nếu cố đổi sang một ngôn ngữ KHÁC cũng đang bị tắt thì bị từ chối
        $anotherInactiveLang = Language::factory()->inactive()->create(['code' => 'it', 'name' => 'Tiếng Ý']);
        $invalidResponse = $this->actingAs($this->user1)->put(route('decks.update', $deck), [
            'name' => 'Đổi sang tiếng Ý tắt',
            'learning_language_id' => $anotherInactiveLang->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => DeckVisibility::PUBLIC->value,
        ]);
        $invalidResponse->assertSessionHasErrors(['learning_language_id']);
    }

    public function test_public_decks_search_and_filter_never_leaks_private_decks(): void
    {
        $keyword = 'PolyglotSecretKeyword';

        // Bộ từ private có chứa từ khóa
        Deck::factory()->for($this->user1)->private()->create([
            'name' => $keyword . ' Riêng tư',
            'description' => 'Nội dung tuyệt mật ' . $keyword,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        // Bộ từ public có chứa từ khóa
        Deck::factory()->for($this->user2)->public()->create([
            'name' => $keyword . ' Công khai',
            'description' => 'Nội dung cộng đồng ' . $keyword,
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $response = $this->get(route('explore', ['search' => $keyword]));

        $response->assertOk();
        $response->assertSee($keyword . ' Công khai');
        $response->assertDontSee($keyword . ' Riêng tư');
        $response->assertDontSee('Nội dung tuyệt mật');
    }

    public function test_deck_with_rtl_language_renders_with_rtl_direction(): void
    {
        $deck = Deck::factory()->for($this->user1)->public()->create([
            'name' => 'تعلم اللغة العربية',
            'learning_language_id' => $this->langAr->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $response = $this->get(route('decks.show', $deck));
        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
    }

    public function test_newly_added_bcp47_language_can_be_used_in_deck_without_code_changes(): void
    {
        // Admin tạo một ngôn ngữ BCP 47 mới (ví dụ zh-Hant: Tiếng Trung Phồn thể)
        $newLang = Language::create([
            'code' => 'zh-Hant',
            'name' => 'Tiếng Trung Phồn thể',
            'native_name' => '繁體中文',
            'direction' => 'ltr',
            'sort_order' => 15,
            'is_active' => true,
        ]);

        // Thành viên chọn ngôn ngữ mới này để tạo bộ từ
        $response = $this->actingAs($this->user1)->post(route('decks.store'), [
            'name' => 'Từ vựng Đài Loan',
            'learning_language_id' => $newLang->id,
            'meaning_language_id' => $this->langVi->id,
            'visibility' => DeckVisibility::PUBLIC->value,
        ]);

        $deck = Deck::where('name', 'Từ vựng Đài Loan')->first();
        $this->assertNotNull($deck);
        $response->assertRedirect(route('decks.show', $deck));

        $showResponse = $this->get(route('decks.show', $deck));
        $showResponse->assertOk();
        $showResponse->assertSee('Tiếng Trung Phồn thể');
        $showResponse->assertSee('繁體中文');
        $showResponse->assertSee('zh-Hant');
    }
}
