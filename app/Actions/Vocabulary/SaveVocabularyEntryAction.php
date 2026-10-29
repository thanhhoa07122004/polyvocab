<?php

namespace App\Actions\Vocabulary;

use App\Models\Deck;
use App\Models\EntryExample;
use App\Models\EntryMeaning;
use App\Models\VocabularyEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveVocabularyEntryAction
{
    /**
     * Lưu hoặc cập nhật mục từ vựng, danh sách nghĩa và câu ví dụ trong một transaction.
     *
     * @throws ValidationException
     */
    public function execute(Deck $deck, array $data, ?VocabularyEntry $entry = null): VocabularyEntry
    {
        return DB::transaction(function () use ($deck, $data, $entry) {
            $isUpdating = $entry !== null;

            if (! $isUpdating) {
                $entry = new VocabularyEntry();
                $entry->deck_id = $deck->id;
            }

            $entry->term = trim($data['term']);
            $entry->pronunciation = isset($data['pronunciation']) && trim($data['pronunciation']) !== ''
                ? trim($data['pronunciation'])
                : null;
            $entry->note = isset($data['note']) && trim($data['note']) !== ''
                ? trim($data['note'])
                : null;
            $entry->save();

            $existingMeanings = $isUpdating
                ? $entry->meanings()->with('examples')->get()->keyBy('id')
                : collect();

            $incomingMeaningIds = [];

            $meaningsData = $data['meanings'] ?? [];
            $mIndex = 0;

            foreach ($meaningsData as $meaningItem) {
                $mIndex++;
                $meaningId = isset($meaningItem['id']) && ! empty($meaningItem['id'])
                    ? (int) $meaningItem['id']
                    : null;

                if ($meaningId) {
                    if (! $existingMeanings->has($meaningId)) {
                        throw ValidationException::withMessages([
                            'meanings' => 'ID nghĩa không hợp lệ hoặc không thuộc mục từ này.',
                        ]);
                    }
                    $meaningModel = $existingMeanings->get($meaningId);
                } else {
                    $meaningModel = new EntryMeaning();
                    $meaningModel->vocabulary_entry_id = $entry->id;
                }

                $meaningModel->part_of_speech = isset($meaningItem['part_of_speech']) && trim($meaningItem['part_of_speech']) !== ''
                    ? trim($meaningItem['part_of_speech'])
                    : null;
                $meaningModel->meaning = isset($meaningItem['meaning']) && trim((string) $meaningItem['meaning']) !== ''
                    ? trim($meaningItem['meaning'])
                    : null;
                $meaningModel->sort_order = $mIndex;
                $meaningModel->save();

                $incomingMeaningIds[] = $meaningModel->id;

                // Xử lý danh sách ví dụ của nghĩa này
                $existingExamples = $meaningId && $existingMeanings->has($meaningId)
                    ? $existingMeanings->get($meaningId)->examples->keyBy('id')
                    : collect();

                $incomingExampleIds = [];
                $examplesData = $meaningItem['examples'] ?? [];
                $eIndex = 0;

                foreach ($examplesData as $exampleItem) {
                    // Nếu ví dụ hoàn toàn rỗng câu, bỏ qua
                    $sentence = isset($exampleItem['sentence']) ? trim($exampleItem['sentence']) : '';
                    if ($sentence === '') {
                        continue;
                    }

                    $eIndex++;
                    $exampleId = isset($exampleItem['id']) && ! empty($exampleItem['id'])
                        ? (int) $exampleItem['id']
                        : null;

                    if ($exampleId) {
                        if (! $existingExamples->has($exampleId)) {
                            throw ValidationException::withMessages([
                                'examples' => 'ID ví dụ không hợp lệ hoặc không thuộc nghĩa này.',
                            ]);
                        }
                        $exampleModel = $existingExamples->get($exampleId);
                    } else {
                        $exampleModel = new EntryExample();
                        $exampleModel->entry_meaning_id = $meaningModel->id;
                    }

                    $exampleModel->sentence = $sentence;
                    $exampleModel->translation = isset($exampleItem['translation']) && trim($exampleItem['translation']) !== ''
                        ? trim($exampleItem['translation'])
                        : null;
                    $exampleModel->sort_order = $eIndex;
                    $exampleModel->save();

                    $incomingExampleIds[] = $exampleModel->id;
                }

                // Xóa các ví dụ đã bị người dùng loại bỏ khỏi nghĩa này
                $meaningModel->examples()->whereNotIn('id', $incomingExampleIds)->delete();
            }

            // Xóa các nghĩa đã bị người dùng loại bỏ khỏi mục từ
            $entry->meanings()->whereNotIn('id', $incomingMeaningIds)->delete();

            return $entry->load('meanings.examples');
        });
    }
}

