<?php

namespace App\Actions\Vocabulary;

use App\Models\Deck;
use App\Models\EntryExample;
use App\Models\EntryMeaning;
use App\Models\VocabularyEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BatchCreateVocabularyEntriesAction
{
    public function __construct(
        protected SaveVocabularyEntryAction $singleAction
    ) {}

    /**
     * Thêm nhiều từ vựng mới vào bộ từ cùng một lúc trong một database transaction.
     *
     * @param  array<int, array<string, mixed>>  $entriesData
     * @return Collection<int, VocabularyEntry>
     */
    public function execute(Deck $deck, array $entriesData): Collection
    {
        return DB::transaction(function () use ($deck, $entriesData) {
            $createdEntries = collect();

            foreach ($entriesData as $item) {
                $term = trim($item['term'] ?? '');
                if ($term === '') {
                    continue;
                }

                // Nếu cấu trúc có chứa danh sách meanings đầy đủ, dùng singleAction
                if (isset($item['meanings']) && is_array($item['meanings'])) {
                    $entry = $this->singleAction->execute($deck, $item);
                    $createdEntries->push($entry);
                    continue;
                }

                // Cấu trúc gọn (giao diện thêm nhanh nhiều từ)
                $entry = new VocabularyEntry();
                $entry->deck_id = $deck->id;
                $entry->term = $term;
                $entry->pronunciation = isset($item['pronunciation']) && trim((string) $item['pronunciation']) !== ''
                    ? trim((string) $item['pronunciation'])
                    : null;
                $entry->note = isset($item['note']) && trim((string) $item['note']) !== ''
                    ? trim((string) $item['note'])
                    : null;
                $entry->save();

                // Tạo 1 nghĩa tương ứng
                $meaning = new EntryMeaning();
                $meaning->vocabulary_entry_id = $entry->id;
                $meaning->part_of_speech = isset($item['part_of_speech']) && trim((string) $item['part_of_speech']) !== ''
                    ? trim((string) $item['part_of_speech'])
                    : null;
                $meaning->meaning = isset($item['meaning']) && trim((string) $item['meaning']) !== ''
                    ? trim((string) $item['meaning'])
                    : null;
                $meaning->sort_order = 1;
                $meaning->save();

                // Tạo 1 câu ví dụ nếu có
                $sentence = isset($item['example_sentence']) ? trim((string) $item['example_sentence']) : '';
                if ($sentence !== '') {
                    $example = new EntryExample();
                    $example->entry_meaning_id = $meaning->id;
                    $example->sentence = $sentence;
                    $example->translation = isset($item['example_translation']) && trim((string) $item['example_translation']) !== ''
                        ? trim((string) $item['example_translation'])
                        : null;
                    $example->sort_order = 1;
                    $example->save();
                }

                $createdEntries->push($entry->load(['meanings.examples']));
            }

            return $createdEntries;
        });
    }
}

