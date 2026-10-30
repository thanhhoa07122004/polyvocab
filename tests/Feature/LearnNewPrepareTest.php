<?php

namespace Tests\Feature;

use App\Enums\DeckVisibility;
use App\Enums\StudyMode;
use App\Models\Deck;
use App\Models\EntryMeaning;
use App\Models\Language;
use App\Models\User;
use App\Models\VocabularyEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnNewPrepareTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Deck $deck;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $lang1 = Language::factory()->create(['code' => 'en', 'name' => 'Tiếng Anh']);
        $lang2 = Language::factory()->create(['code' => 'vi', 'name' => 'Tiếng Việt']);

        $this->deck = Deck::factory()->create([
            'user_id' => $this->user->id,
            'learning_language_id' => $lang1->id,
            'meaning_language_id' => $lang2->id,
            'visibility' => DeckVisibility::PRIVATE,
            'name' => 'Từ vựng IELTS',
        ]);
    }

    public function test_prepare_screen_displays_updated_wording_and_layout(): void
    {
        // Tạo 8 mục từ chưa học
        for ($i = 1; $i <= 8; $i++) {
            $entry = VocabularyEntry::factory()->create([
                'deck_id' => $this->deck->id,
                'term' => 'Word ' . $i,
            ]);
            EntryMeaning::factory()->create([
                'vocabulary_entry_id' => $entry->id,
                'meaning' => 'Nghĩa ' . $i,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('study.new.prepare', $this->deck));

        $response->assertOk();

        // 1. Tiêu đề và lời giới thiệu mới
        $response->assertSee('Học từ mới');
        $response->assertSee('Chọn số lượng để bắt đầu học bộ “Từ vựng IELTS”.', false);
        $response->assertDontSee('Làm quen và củng cố các nét nghĩa chưa học trong bộ từ');

        // 2. Câu chữ mới về số lượng và ngôn ngữ
        $response->assertSee('Ngôn ngữ');
        $response->assertSee('Số từ muốn học');
        $response->assertSee('8 từ');

        // 3. Preset số lượng: Có nút 5 từ, nút Tất cả · 8 từ, nút Tùy chỉnh
        $response->assertSee('5 từ');
        $response->assertSee('Tất cả · 8 từ');
        $response->assertSee('Tùy chỉnh');

        // 4. Luồng học mới: Làm quen từ mới → Kết quả
        $response->assertSee('Làm quen từ mới → Kết quả', false);
        $response->assertSee('Bắt đầu làm quen');
        $response->assertSee('Tiến độ được lưu tự động sau mỗi từ.');
    }

    public function test_start_session_validates_count(): void
    {
        $response = $this->actingAs($this->user)->post(route('study.new.start', $this->deck), [
            'count' => 0,
        ]);

        $response->assertSessionHasErrors(['count']);
    }
}
