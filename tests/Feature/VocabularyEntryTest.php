<?php

namespace Tests\Feature;

use App\Enums\DeckVisibility;
use App\Enums\UserRole;
use App\Models\Deck;
use App\Models\EntryExample;
use App\Models\EntryMeaning;
use App\Models\Language;
use App\Models\User;
use App\Models\VocabularyEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VocabularyEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherUser;
    private User $admin;
    private Language $langEn;
    private Language $langVi;
    private Deck $publicDeck;
    private Deck $privateDeck;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Owner Alice', 'role' => UserRole::MEMBER]);
        $this->otherUser = User::factory()->create(['name' => 'Bob Member', 'role' => UserRole::MEMBER]);
        $this->admin = User::factory()->create(['name' => 'Admin Charlie', 'role' => UserRole::ADMIN]);

        $this->langEn = Language::factory()->create(['code' => 'en', 'name' => 'Tiếng Anh', 'direction' => 'ltr', 'is_active' => true]);
        $this->langVi = Language::factory()->create(['code' => 'vi', 'name' => 'Tiếng Việt', 'direction' => 'ltr', 'is_active' => true]);

        $this->publicDeck = Deck::factory()->for($this->owner)->public()->create([
            'name' => 'English Public Deck',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $this->privateDeck = Deck::factory()->for($this->owner)->private()->create([
            'name' => 'English Private Deck',
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);
    }

    public function test_owner_can_add_multiple_words_at_once_in_batch(): void
    {
        $payload = [
            'entries' => [
                [
                    'term' => 'cat',
                    'pronunciation' => 'kæt',
                    'part_of_speech' => 'Danh từ',
                    'meaning' => 'Con mèo',
                    'example_sentence' => 'The cat is sleeping on the couch.',
                    'example_translation' => 'Con mèo đang ngủ trên ghế sofa.',
                ],
                [
                    'term' => 'dog',
                    'pronunciation' => 'dɔːɡ',
                    'part_of_speech' => 'Danh từ',
                    'meaning' => 'Con chó',
                ],
                [
                    'term' => 'bird',
                    // Để trống meaning, pronunciation
                ],
                [
                    // Khung thứ 4 hoàn toàn trống -> tự động được bỏ qua không báo lỗi
                    'term' => '',
                    'meaning' => '',
                    'pronunciation' => '',
                    'example_sentence' => '',
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)->post(route('decks.entries.store', $this->publicDeck), $payload);

        $response->assertRedirect(route('decks.show', $this->publicDeck));
        $response->assertSessionHas('success');

        $this->assertEquals(3, $this->publicDeck->entries()->count());

        $cat = VocabularyEntry::where('term', 'cat')->first();
        $this->assertNotNull($cat);
        $this->assertEquals('kæt', $cat->pronunciation);
        $this->assertEquals('Con mèo', $cat->meanings->first()->meaning);
        $this->assertEquals('The cat is sleeping on the couch.', $cat->meanings->first()->examples->first()->sentence);

        $dog = VocabularyEntry::where('term', 'dog')->first();
        $this->assertNotNull($dog);
        $this->assertEquals('Con chó', $dog->meanings->first()->meaning);

        $bird = VocabularyEntry::where('term', 'bird')->first();
        $this->assertNotNull($bird);
        $this->assertNull($bird->meanings->first()->meaning);
    }

    public function test_batch_creation_fails_if_all_words_are_empty(): void
    {
        $payload = [
            'entries' => [
                ['term' => '', 'meaning' => ''],
                ['term' => '   ', 'meaning' => ''],
            ],
        ];

        $response = $this->actingAs($this->owner)->post(route('decks.entries.store', $this->publicDeck), $payload);
        $response->assertSessionHasErrors(['entries']);
        $this->assertEquals(0, $this->publicDeck->entries()->count());
    }

    public function test_owner_can_add_vocabulary_entry_with_multiple_meanings_and_examples(): void
    {
        $payload = [
            'term' => 'resilient',
            'pronunciation' => 'rɪˈzɪliənt',
            'note' => 'Tính từ thường gặp trong các bài báo kinh tế và đời sống.',
            'meanings' => [
                [
                    'part_of_speech' => 'Tính từ',
                    'meaning' => 'Kiên cường, có khả năng phục hồi nhanh chóng sau khó khăn.',
                    'examples' => [
                        [
                            'sentence' => 'He is remarkably resilient in times of crisis.',
                            'translation' => 'Anh ấy kiên cường một cách đáng kinh ngạc trong thời kỳ khủng hoảng.',
                        ],
                        [
                            'sentence' => 'The economy proved to be resilient.',
                            'translation' => 'Nền kinh tế đã chứng tỏ sự kiên cường và tự phục hồi.',
                        ],
                    ],
                ],
                [
                    'part_of_speech' => 'Tính từ (vật lý)',
                    'meaning' => 'Đàn hồi, có thể trở lại hình dạng ban đầu sau khi bị uốn cong.',
                    'examples' => [
                        [
                            'sentence' => 'Rubber is a resilient material.',
                            'translation' => 'Cao su là một vật liệu có tính đàn hồi cao.',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)->post(route('decks.entries.store', $this->publicDeck), $payload);

        $entry = VocabularyEntry::where('term', 'resilient')->first();
        $this->assertNotNull($entry);
        $response->assertRedirect(route('decks.entries.show', [$this->publicDeck, $entry]));

        $this->assertEquals($this->publicDeck->id, $entry->deck_id);
        $this->assertEquals('rɪˈzɪliənt', $entry->pronunciation);
        $this->assertCount(2, $entry->meanings);
        $this->assertEquals(2, $entry->meanings[0]->examples()->count());
        $this->assertEquals(1, $entry->meanings[1]->examples()->count());
    }

    public function test_cannot_spoof_deck_id_to_create_entry_in_another_users_deck(): void
    {
        $victimDeck = Deck::factory()->for($this->otherUser)->public()->create([
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        $payload = [
            'term' => 'hacked_word',
            'meanings' => [
                ['meaning' => 'Hacked meaning'],
            ],
            // Cố tình gửi deck_id trong body
            'deck_id' => $victimDeck->id,
        ];

        // Gửi tới deck của victim -> bị Forbidden
        $response = $this->actingAs($this->owner)->post(route('decks.entries.store', $victimDeck), $payload);
        $response->assertForbidden();
        $this->assertEquals(0, VocabularyEntry::where('term', 'hacked_word')->count());
    }

    public function test_validation_fails_when_term_is_missing_or_whitespace_or_no_meaning_structure(): void
    {
        // Thiếu term
        $response1 = $this->actingAs($this->owner)->post(route('decks.entries.store', $this->publicDeck), [
            'term' => '   ',
            'meanings' => [
                ['meaning' => 'Nghĩa hợp lệ'],
            ],
        ]);
        $response1->assertSessionHasErrors(['term']);

        // Không có cấu trúc nghĩa nào trong danh sách
        $response2 = $this->actingAs($this->owner)->post(route('decks.entries.store', $this->publicDeck), [
            'term' => 'valid term',
            'meanings' => [],
        ]);
        $response2->assertSessionHasErrors(['meanings']);
    }

    public function test_meaning_content_is_optional_and_can_be_left_blank(): void
    {
        // Tạo mục từ mà không điền nội dung ý nghĩa (để trống)
        $response = $this->actingAs($this->owner)->post(route('decks.entries.store', $this->publicDeck), [
            'term' => 'meaningless-test',
            'meanings' => [
                [
                    'part_of_speech' => 'Danh từ',
                    'meaning' => '', // Không bắt buộc điền
                    'examples' => [
                        [
                            'sentence' => 'Sample sentence without meaning.',
                        ],
                    ],
                ],
            ],
        ]);

        $entry = VocabularyEntry::where('term', 'meaningless-test')->first();
        $this->assertNotNull($entry);
        $response->assertRedirect(route('decks.entries.show', [$this->publicDeck, $entry]));

        $meaning = $entry->meanings()->first();
        $this->assertNotNull($meaning);
        $this->assertNull($meaning->meaning);
        $this->assertEquals('Danh từ', $meaning->part_of_speech);
    }

    public function test_exceeding_max_meanings_or_examples_limits_is_rejected(): void
    {
        $maxMeanings = (int) config('polyvocab.max_meanings_per_entry', 20);
        $maxExamples = (int) config('polyvocab.max_examples_per_meaning', 10);

        // Vượt quá số nghĩa cho phép (21 nghĩa)
        $excessMeanings = [];
        for ($i = 0; $i <= $maxMeanings; $i++) {
            $excessMeanings[] = ['meaning' => "Nghĩa số {$i}"];
        }

        $response = $this->actingAs($this->owner)->post(route('decks.entries.store', $this->publicDeck), [
            'term' => 'overloaded',
            'meanings' => $excessMeanings,
        ]);
        $response->assertSessionHasErrors(['meanings']);

        // Vượt quá số ví dụ cho phép (11 ví dụ trong 1 nghĩa)
        $excessExamples = [];
        for ($j = 0; $j <= $maxExamples; $j++) {
            $excessExamples[] = ['sentence' => "Sentence {$j}"];
        }

        $response2 = $this->actingAs($this->owner)->post(route('decks.entries.store', $this->publicDeck), [
            'term' => 'overloaded examples',
            'meanings' => [
                [
                    'meaning' => 'Nghĩa có quá nhiều ví dụ',
                    'examples' => $excessExamples,
                ],
            ],
        ]);
        $response2->assertSessionHasErrors(['meanings.0.examples']);
    }

    public function test_submitting_translation_without_sentence_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post(route('decks.entries.store', $this->publicDeck), [
            'term' => 'test word',
            'meanings' => [
                [
                    'meaning' => 'Nghĩa test',
                    'examples' => [
                        [
                            'sentence' => '', // Thiếu câu ví dụ
                            'translation' => 'Chỉ có bản dịch mà không có câu ví dụ',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['meanings.0.examples.0.sentence']);
    }

    public function test_deck_with_inactive_language_still_allows_owner_to_manage_vocabulary(): void
    {
        // Bộ từ đang sử dụng ngôn ngữ đã bị vô hiệu hóa
        $inactiveLang = Language::factory()->inactive()->create(['code' => 'la', 'name' => 'Tiếng Latin']);
        $deckWithInactive = Deck::factory()->for($this->owner)->create([
            'learning_language_id' => $inactiveLang->id,
            'meaning_language_id' => $this->langVi->id,
        ]);

        // Chủ sở hữu vẫn thêm được từ vựng bình thường
        $response = $this->actingAs($this->owner)->post(route('decks.entries.store', $deckWithInactive), [
            'term' => 'carpe diem',
            'meanings' => [
                ['meaning' => 'Hãy nắm bắt lấy ngày hôm nay'],
            ],
        ]);

        $entry = VocabularyEntry::where('term', 'carpe diem')->first();
        $this->assertNotNull($entry);
        $response->assertRedirect(route('decks.entries.show', [$deckWithInactive, $entry]));
    }

    public function test_owner_can_update_entry_preserving_existing_ids_and_deleting_removed_rows(): void
    {
        // Tạo entry ban đầu với 2 nghĩa, mỗi nghĩa 1 ví dụ
        $entry = VocabularyEntry::factory()->for($this->publicDeck)->create(['term' => 'initial term']);
        $meaning1 = EntryMeaning::factory()->for($entry)->create(['meaning' => 'Nghĩa 1', 'sort_order' => 1]);
        $meaning2 = EntryMeaning::factory()->for($entry)->create(['meaning' => 'Nghĩa 2 cần xóa', 'sort_order' => 2]);
        $example1 = EntryExample::factory()->for($meaning1)->create(['sentence' => 'Ex 1', 'sort_order' => 1]);

        $payload = [
            'term' => 'updated term',
            'meanings' => [
                [
                    'id' => $meaning1->id, // Giữ nghĩa 1
                    'part_of_speech' => 'Danh từ',
                    'meaning' => 'Nghĩa 1 đã sửa',
                    'examples' => [
                        [
                            'id' => $example1->id, // Giữ ví dụ 1
                            'sentence' => 'Ex 1 updated',
                            'translation' => 'Dịch 1',
                        ],
                        [
                            // Thêm ví dụ mới
                            'sentence' => 'Ex 1 new sentence',
                            'translation' => 'Dịch mới',
                        ],
                    ],
                ],
                [
                    // Thêm nghĩa 3 mới, không gửi nghĩa 2 (nghĩa 2 sẽ bị xóa)
                    'part_of_speech' => 'Động từ',
                    'meaning' => 'Nghĩa 3 hoàn toàn mới',
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)->put(route('decks.entries.update', [$this->publicDeck, $entry]), $payload);
        $response->assertRedirect(route('decks.entries.show', [$this->publicDeck, $entry]));

        $entry->refresh();
        $this->assertEquals('updated term', $entry->term);

        // ID của nghĩa 1 và ví dụ 1 phải được giữ nguyên
        $this->assertDatabaseHas('entry_meanings', ['id' => $meaning1->id, 'meaning' => 'Nghĩa 1 đã sửa']);
        $this->assertDatabaseHas('entry_examples', ['id' => $example1->id, 'sentence' => 'Ex 1 updated']);

        // Nghĩa 2 bị xóa
        $this->assertDatabaseMissing('entry_meanings', ['id' => $meaning2->id]);

        // Nghĩa 3 được tạo mới
        $this->assertDatabaseHas('entry_meanings', ['vocabulary_entry_id' => $entry->id, 'meaning' => 'Nghĩa 3 hoàn toàn mới']);
    }

    public function test_cannot_pass_foreign_meaning_or_example_id_belonging_to_another_entry(): void
    {
        // Entry 1 của Owner
        $entry1 = VocabularyEntry::factory()->for($this->publicDeck)->create(['term' => 'entry 1']);
        $meaning1 = EntryMeaning::factory()->for($entry1)->create(['meaning' => 'meaning 1']);

        // Entry 2 của người khác (hoặc deck khác)
        $otherDeck = Deck::factory()->for($this->otherUser)->create();
        $entry2 = VocabularyEntry::factory()->for($otherDeck)->create(['term' => 'entry 2']);
        $foreignMeaning = EntryMeaning::factory()->for($entry2)->create(['meaning' => 'foreign meaning']);
        $foreignExample = EntryExample::factory()->for($foreignMeaning)->create(['sentence' => 'foreign sentence']);

        // Hacker cố tình gửi foreignMeaning->id vào update của entry 1
        $payloadWithForeignMeaning = [
            'term' => 'entry 1 modified',
            'meanings' => [
                [
                    'id' => $foreignMeaning->id, // ID không thuộc entry1
                    'meaning' => 'Cố tình chiếm ID của mục khác',
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)->put(route('decks.entries.update', [$this->publicDeck, $entry1]), $payloadWithForeignMeaning);
        $response->assertSessionHasErrors(['meanings']);

        // Hacker cố tình gửi foreignExample->id vào update của meaning 1
        $payloadWithForeignExample = [
            'term' => 'entry 1 modified',
            'meanings' => [
                [
                    'id' => $meaning1->id,
                    'meaning' => 'meaning 1',
                    'examples' => [
                        [
                            'id' => $foreignExample->id, // ID ví dụ không thuộc meaning1
                            'sentence' => 'Cố tình chiếm ví dụ',
                        ],
                    ],
                ],
            ],
        ];

        $response2 = $this->actingAs($this->owner)->put(route('decks.entries.update', [$this->publicDeck, $entry1]), $payloadWithForeignExample);
        $response2->assertSessionHasErrors(['examples']);
    }

    public function test_transaction_rolls_back_when_any_error_occurs_preventing_partial_data(): void
    {
        $initialEntryCount = VocabularyEntry::count();
        $initialMeaningCount = EntryMeaning::count();

        // Gửi dữ liệu hợp lệ ở nghĩa 1 nhưng lỗi ngoại lệ ở ID nghĩa 2 (không thuộc entry)
        $entry = VocabularyEntry::factory()->for($this->publicDeck)->create();

        try {
            $action = app(\App\Actions\Vocabulary\SaveVocabularyEntryAction::class);
            $action->execute($this->publicDeck, [
                'term' => 'test rollback',
                'meanings' => [
                    ['meaning' => 'Nghĩa mới 1'],
                    ['id' => 999999, 'meaning' => 'ID ảo gây nổ validation'],
                ],
            ], $entry);
        } catch (\Throwable $e) {
            // Mong đợi lỗi
        }

        // Kiểm tra không có nghĩa mới 1 nào bị lưu dở dang
        $this->assertDatabaseMissing('entry_meanings', ['meaning' => 'Nghĩa mới 1']);
    }

    public function test_access_permissions_for_public_and_private_decks(): void
    {
        $publicEntry = VocabularyEntry::factory()->for($this->publicDeck)->create(['term' => 'public word']);
        EntryMeaning::factory()->for($publicEntry)->create(['meaning' => 'Public Meaning']);

        $privateEntry = VocabularyEntry::factory()->for($this->privateDeck)->create(['term' => 'private word']);
        EntryMeaning::factory()->for($privateEntry)->create(['meaning' => 'Private Meaning']);

        // Khách xem được entry thuộc public deck
        $this->get(route('decks.entries.show', [$this->publicDeck, $publicEntry]))->assertOk();

        // Khách truy cập entry thuộc private deck -> 404
        $this->get(route('decks.entries.show', [$this->privateDeck, $privateEntry]))->assertNotFound();

        // Người dùng khác truy cập entry thuộc private deck -> 404
        $this->actingAs($this->otherUser)
            ->get(route('decks.entries.show', [$this->privateDeck, $privateEntry]))
            ->assertNotFound();

        // Quản trị viên truy cập entry thuộc private deck -> 404
        $this->actingAs($this->admin)
            ->get(route('decks.entries.show', [$this->privateDeck, $privateEntry]))
            ->assertNotFound();

        // Chủ sở hữu xem được bình thường
        $this->actingAs($this->owner)
            ->get(route('decks.entries.show', [$this->privateDeck, $privateEntry]))
            ->assertOk();

        // Người khác không được vào trang edit của public entry dù biết ID (403)
        $this->actingAs($this->otherUser)
            ->get(route('decks.entries.edit', [$this->publicDeck, $publicEntry]))
            ->assertForbidden();

        // Quản trị viên không được vào trang edit của public entry của người khác (403)
        $this->actingAs($this->admin)
            ->get(route('decks.entries.edit', [$this->publicDeck, $publicEntry]))
            ->assertForbidden();
    }

    public function test_mismatched_deck_and_entry_in_url_returns_404(): void
    {
        $entryInPublicDeck = VocabularyEntry::factory()->for($this->publicDeck)->create();

        // URL dùng privateDeck nhưng ID lại là của entryInPublicDeck
        $this->actingAs($this->owner)
            ->get(route('decks.entries.show', [$this->privateDeck, $entryInPublicDeck]))
            ->assertNotFound();

        $this->actingAs($this->owner)
            ->get(route('decks.entries.edit', [$this->privateDeck, $entryInPublicDeck]))
            ->assertNotFound();

        $this->actingAs($this->owner)
            ->delete(route('decks.entries.destroy', [$this->privateDeck, $entryInPublicDeck]))
            ->assertNotFound();
    }

    public function test_search_within_deck_never_returns_entries_from_other_decks(): void
    {
        $secretTerm = 'QuantumSuperposition';

        // Entry trong bộ 1
        VocabularyEntry::factory()->for($this->publicDeck)->create(['term' => $secretTerm]);

        // Entry trong bộ 2 với cùng từ khóa
        $deck2 = Deck::factory()->for($this->owner)->public()->create([
            'learning_language_id' => $this->langEn->id,
            'meaning_language_id' => $this->langVi->id,
        ]);
        VocabularyEntry::factory()->for($deck2)->create(['term' => $secretTerm . '_Deck2']);

        // Tìm kiếm trong deck 1
        $response = $this->get(route('decks.show', [$this->publicDeck, 'search' => $secretTerm]));

        $response->assertOk();
        $response->assertSee($secretTerm);
        $response->assertDontSee($secretTerm . '_Deck2');
    }

    public function test_html_content_in_term_meaning_or_example_is_escaped_safely(): void
    {
        $xssTerm = '<script>alert("xss")</script>';
        $xssMeaning = '<img src=x onerror=alert(1)>';

        $entry = VocabularyEntry::factory()->for($this->publicDeck)->create(['term' => $xssTerm]);
        EntryMeaning::factory()->for($entry)->create(['meaning' => $xssMeaning]);

        $response = $this->get(route('decks.entries.show', [$this->publicDeck, $entry]));
        $response->assertOk();
        // Kiểm tra HTML được escape an toàn
        $response->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
        $response->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
    }

    public function test_deleting_entry_cascades_and_leaves_no_orphaned_meanings_or_examples(): void
    {
        $entry = VocabularyEntry::factory()->for($this->publicDeck)->create(['term' => 'to be deleted']);
        $meaning = EntryMeaning::factory()->for($entry)->create();
        $example = EntryExample::factory()->for($meaning)->create();

        $this->actingAs($this->owner)->delete(route('decks.entries.destroy', [$this->publicDeck, $entry]));

        $this->assertDatabaseMissing('vocabulary_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('entry_meanings', ['id' => $meaning->id]);
        $this->assertDatabaseMissing('entry_examples', ['id' => $example->id]);
    }

    public function test_deleting_deck_cascades_all_entries_meanings_and_examples(): void
    {
        $entry = VocabularyEntry::factory()->for($this->publicDeck)->create();
        $meaning = EntryMeaning::factory()->for($entry)->create();
        $example = EntryExample::factory()->for($meaning)->create();

        // Xóa bộ từ
        $this->actingAs($this->owner)->delete(route('decks.destroy', $this->publicDeck));

        $this->assertDatabaseMissing('decks', ['id' => $this->publicDeck->id]);
        $this->assertDatabaseMissing('vocabulary_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('entry_meanings', ['id' => $meaning->id]);
        $this->assertDatabaseMissing('entry_examples', ['id' => $example->id]);
    }
}
