<?php

namespace App\Http\Controllers;

use App\Enums\DeckVisibility;
use App\Models\Deck;
use App\Models\EntryMeaning;
use App\Models\Language;
use App\Services\Study\StudyProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeckController extends Controller
{
    public function __construct(
        protected ?StudyProgressService $progressService = null
    ) {
        $this->progressService = $this->progressService ?? app(StudyProgressService::class);
    }
    /**
     * Danh sách bộ từ của người dùng đang đăng nhập ("Bộ từ của tôi").
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $search = trim((string) $request->query('search', ''));
        $languageId = $request->query('language_id');
        $visibility = $request->query('visibility');

        $query = Deck::query()
            ->with(['learningLanguage', 'meaningLanguage'])
            ->where('user_id', $user->id)
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

        if ($visibility && in_array($visibility, [DeckVisibility::PRIVATE->value, DeckVisibility::PUBLIC->value], true)) {
            $query->where('visibility', $visibility);
        }

        $decks = $query->paginate(12)->withQueryString();

        // Lấy danh sách ngôn ngữ để làm bộ lọc
        $languages = Language::orderBy('sort_order')->orderBy('name')->get();

        return view('decks.index', compact('decks', 'languages', 'search', 'languageId', 'visibility'));
    }

    /**
     * Form tạo bộ từ mới.
     */
    public function create(): View
    {
        Gate::authorize('create', Deck::class);

        $activeLanguages = Language::active()->get();

        return view('decks.create', compact('activeLanguages'));
    }

    /**
     * Lưu bộ từ mới.
     * Chủ sở hữu luôn được gán từ auth user phía server, không nhận user_id từ request.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Deck::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'learning_language_id' => [
                'required',
                Rule::exists('languages', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'meaning_language_id' => [
                'required',
                Rule::exists('languages', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'visibility' => ['required', Rule::enum(DeckVisibility::class)],
        ], [
            'name.required' => 'Tên bộ từ không được để trống.',
            'name.max' => 'Tên bộ từ không được vượt quá 150 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 2.000 ký tự.',
            'learning_language_id.required' => 'Vui lòng chọn ngôn ngữ đang học.',
            'learning_language_id.exists' => 'Ngôn ngữ đang học không hợp lệ hoặc đã bị tạm tắt.',
            'meaning_language_id.required' => 'Vui lòng chọn ngôn ngữ giải nghĩa.',
            'meaning_language_id.exists' => 'Ngôn ngữ giải nghĩa không hợp lệ hoặc đã bị tạm tắt.',
            'visibility.required' => 'Vui lòng chọn chế độ hiển thị.',
            'visibility.Illuminate\Validation\Rules\Enum' => 'Chế độ hiển thị không hợp lệ.',
        ]);

        $deck = new Deck();
        $deck->name = $validated['name'];
        $deck->description = $validated['description'] ?? null;
        $deck->learning_language_id = $validated['learning_language_id'];
        $deck->meaning_language_id = $validated['meaning_language_id'];
        $deck->visibility = DeckVisibility::from($validated['visibility']);
        $deck->user_id = $request->user()->id;
        $deck->save();

        return redirect()
            ->route('decks.show', $deck)
            ->with('success', 'Đã tạo bộ từ mới thành công.');
    }

    /**
     * Hiển thị chi tiết bộ từ kèm danh sách mục từ vựng, tìm kiếm và phân trang.
     * Bộ từ private chỉ chủ sở hữu được xem. Người khác (kể cả admin) nhận HTTP 404 để không lộ sự tồn tại.
     */
    public function show(Request $request, Deck $deck): View
    {
        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        $deck->load(['user', 'learningLanguage', 'meaningLanguage']);

        $search = trim((string) $request->query('search', ''));

        $entriesQuery = $deck->entries()
            ->with([
                'meanings' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
                'meanings.examples',
            ])
            ->latest();

        if ($search !== '') {
            $entriesQuery->where(function ($q) use ($search) {
                $q->where('term', 'like', "%{$search}%")
                    ->orWhereHas('meanings', function ($mq) use ($search) {
                        $mq->where('meaning', 'like', "%{$search}%");
                    });
            });
        }

        $entries = $entriesQuery->paginate(15)->withQueryString();
        $totalEntriesCount = $deck->entries()->count();
        $totalMeaningsCount = EntryMeaning::whereHas('vocabularyEntry', fn ($q) => $q->where('deck_id', $deck->id))->count();

        $studyStats = null;
        if ($request->user()) {
            $studyStats = $this->progressService->getDeckStudyStats($request->user(), $deck);
        }

        return view('decks.show', compact('deck', 'entries', 'search', 'totalEntriesCount', 'totalMeaningsCount', 'studyStats'));
    }

    /**
     * Form chỉnh sửa bộ từ.
     */
    public function edit(Request $request, Deck $deck): View
    {
        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('update', $deck);

        // Lấy danh sách ngôn ngữ hoạt động, cộng thêm ngôn ngữ hiện tại của deck nếu nó đã bị tắt
        $activeLanguages = Language::active()->get();

        if (! $activeLanguages->contains('id', $deck->learning_language_id)) {
            $learningLang = $deck->learningLanguage;
            if ($learningLang) {
                $activeLanguages->push($learningLang);
            }
        }

        if (! $activeLanguages->contains('id', $deck->meaning_language_id)) {
            $meaningLang = $deck->meaningLanguage;
            if ($meaningLang) {
                $activeLanguages->push($meaningLang);
            }
        }

        return view('decks.edit', compact('deck', 'activeLanguages'));
    }

    /**
     * Cập nhật thông tin bộ từ.
     * Cho phép giữ lại ngôn ngữ hiện tại nếu ngôn ngữ đó đã bị tắt; nhưng nếu chọn ngôn ngữ mới thì phải active.
     */
    public function update(Request $request, Deck $deck): RedirectResponse
    {
        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('update', $deck);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'learning_language_id' => [
                'required',
                function ($attribute, $value, $fail) use ($deck) {
                    if ((int) $value === (int) $deck->learning_language_id) {
                        return;
                    }
                    $existsActive = Language::where('id', $value)->where('is_active', true)->exists();
                    if (! $existsActive) {
                        $fail('Ngôn ngữ đang học đã chọn không hợp lệ hoặc không còn hoạt động.');
                    }
                },
            ],
            'meaning_language_id' => [
                'required',
                function ($attribute, $value, $fail) use ($deck) {
                    if ((int) $value === (int) $deck->meaning_language_id) {
                        return;
                    }
                    $existsActive = Language::where('id', $value)->where('is_active', true)->exists();
                    if (! $existsActive) {
                        $fail('Ngôn ngữ giải nghĩa đã chọn không hợp lệ hoặc không còn hoạt động.');
                    }
                },
            ],
            'visibility' => ['required', Rule::enum(DeckVisibility::class)],
        ], [
            'name.required' => 'Tên bộ từ không được để trống.',
            'name.max' => 'Tên bộ từ không được vượt quá 150 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 2.000 ký tự.',
            'learning_language_id.required' => 'Vui lòng chọn ngôn ngữ đang học.',
            'meaning_language_id.required' => 'Vui lòng chọn ngôn ngữ giải nghĩa.',
            'visibility.required' => 'Vui lòng chọn chế độ hiển thị.',
            'visibility.Illuminate\Validation\Rules\Enum' => 'Chế độ hiển thị không hợp lệ.',
        ]);

        $deck->name = $validated['name'];
        $deck->description = $validated['description'] ?? null;
        $deck->learning_language_id = $validated['learning_language_id'];
        $deck->meaning_language_id = $validated['meaning_language_id'];
        $deck->visibility = DeckVisibility::from($validated['visibility']);
        $deck->save();

        return redirect()
            ->route('decks.show', $deck)
            ->with('success', 'Đã cập nhật thông tin bộ từ thành công.');
    }

    /**
     * Xóa bộ từ.
     */
    public function destroy(Request $request, Deck $deck): RedirectResponse
    {
        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('delete', $deck);

        $deckName = $deck->name;
        $deck->delete();

        return redirect()
            ->route('decks.index')
            ->with('success', "Đã xóa bộ từ \"{$deckName}\" thành công.");
    }
}

