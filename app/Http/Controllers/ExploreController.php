<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExploreController extends Controller
{
    /**
     * Danh sách các bộ từ công khai dành cho khách và thành viên.
     */
    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $languageId = $request->query('language_id');

        // Luôn bảo đảm chỉ lấy các bộ từ công khai
        $query = Deck::query()
            ->with(['user', 'learningLanguage', 'meaningLanguage'])
            ->public()
            ->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($languageId) {
            $query->where(function ($q) use ($languageId) {
                $q->where('learning_language_id', $languageId)
                    ->orWhere('meaning_language_id', $languageId);
            });
        }

        $decks = $query->paginate(12)->withQueryString();

        // Lấy danh sách ngôn ngữ để làm bộ lọc
        $languages = Language::orderBy('sort_order')->orderBy('name')->get();

        return view('decks.explore', compact('decks', 'languages', 'search', 'languageId'));
    }
}

