<?php

namespace App\Http\Controllers;

use App\Actions\Vocabulary\BatchCreateVocabularyEntriesAction;
use App\Actions\Vocabulary\SaveVocabularyEntryAction;
use App\Http\Requests\SaveVocabularyEntryRequest;
use App\Models\Deck;
use App\Models\VocabularyEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VocabularyEntryController extends Controller
{
    /**
     * Form tạo mục từ vựng mới trong bộ từ.
     */
    public function create(Request $request, Deck $deck): View
    {
        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('create', [VocabularyEntry::class, $deck]);

        $deck->load(['learningLanguage', 'meaningLanguage']);

        return view('decks.entries.create', compact('deck'));
    }

    /**
     * Lưu mục từ vựng mới vào bộ từ (hỗ trợ lưu một hoặc nhiều từ cùng lúc).
     */
    public function store(
        SaveVocabularyEntryRequest $request,
        Deck $deck,
        SaveVocabularyEntryAction $singleAction,
        BatchCreateVocabularyEntriesAction $batchAction
    ): RedirectResponse {
        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('create', [VocabularyEntry::class, $deck]);

        // Thêm nhiều từ cùng lúc
        if ($request->has('entries')) {
            $createdEntries = $batchAction->execute($deck, (array) $request->input('entries', []));
            $count = $createdEntries->count();

            return redirect()
                ->route('decks.show', $deck)
                ->with('success', "Đã thêm thành công {$count} từ mới vào bộ từ.");
        }

        // Thêm 1 từ đơn lẻ
        $entry = $singleAction->execute($deck, $request->validated());

        return redirect()
            ->route('decks.entries.show', [$deck, $entry])
            ->with('success', 'Đã thêm mục từ vựng mới vào bộ từ thành công.');
    }

    /**
     * Xem chi tiết một mục từ vựng.
     */
    public function show(Request $request, Deck $deck, VocabularyEntry $entry): View
    {
        abort_unless((int) $entry->deck_id === (int) $deck->id, 404);

        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('view', $entry);

        $deck->load(['learningLanguage', 'meaningLanguage', 'user']);
        $entry->load([
            'meanings' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            'meanings.examples' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
        ]);

        return view('decks.entries.show', compact('deck', 'entry'));
    }

    /**
     * Form chỉnh sửa mục từ vựng.
     */
    public function edit(Request $request, Deck $deck, VocabularyEntry $entry): View
    {
        abort_unless((int) $entry->deck_id === (int) $deck->id, 404);

        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('update', $entry);

        $deck->load(['learningLanguage', 'meaningLanguage']);
        $entry->load([
            'meanings' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            'meanings.examples' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
        ]);

        return view('decks.entries.edit', compact('deck', 'entry'));
    }

    /**
     * Cập nhật mục từ vựng.
     */
    public function update(
        SaveVocabularyEntryRequest $request,
        Deck $deck,
        VocabularyEntry $entry,
        SaveVocabularyEntryAction $action
    ): RedirectResponse {
        abort_unless((int) $entry->deck_id === (int) $deck->id, 404);

        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('update', $entry);

        $action->execute($deck, $request->validated(), $entry);

        return redirect()
            ->route('decks.entries.show', [$deck, $entry])
            ->with('success', 'Đã cập nhật mục từ vựng thành công.');
    }

    /**
     * Xóa mục từ vựng khỏi bộ từ.
     */
    public function destroy(Request $request, Deck $deck, VocabularyEntry $entry): RedirectResponse
    {
        abort_unless((int) $entry->deck_id === (int) $deck->id, 404);

        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('delete', $entry);

        $term = $entry->term;
        $entry->delete();

        return redirect()
            ->route('decks.show', $deck)
            ->with('success', "Đã xóa mục từ « {$term} » khỏi bộ từ thành công.");
    }
}

