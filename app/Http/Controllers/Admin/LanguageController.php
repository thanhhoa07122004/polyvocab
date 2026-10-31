<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LanguageController extends Controller
{
    /**
     * Hiển thị danh sách ngôn ngữ kèm tìm kiếm và phân trang.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Language::class);

        $search = trim((string) $request->query('search', ''));

        $languages = Language::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('native_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.languages.index', compact('languages', 'search'));
    }

    /**
     * Form tạo mới ngôn ngữ.
     */
    public function create(): View
    {
        Gate::authorize('create', Language::class);

        return view('admin.languages.create');
    }

    /**
     * Lưu ngôn ngữ mới vào cơ sở dữ liệu.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Language::class);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[a-zA-Z0-9\-_]+$/',
                'unique:languages,code',
            ],
            'name' => ['required', 'string', 'max:100'],
            'native_name' => ['required', 'string', 'max:100'],
            'direction' => ['required', Rule::in(['ltr', 'rtl'])],
            'tts_locale' => ['nullable', 'string', 'max:30'],
            'tts_voice' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.required' => 'Mã ngôn ngữ không được để trống.',
            'code.regex' => 'Mã ngôn ngữ chỉ bao gồm chữ cái, số, dấu gạch nối (-) hoặc gạch dưới (_).',
            'code.unique' => 'Mã ngôn ngữ này đã tồn tại trong hệ thống.',
            'name.required' => 'Tên tiếng Việt không được để trống.',
            'native_name.required' => 'Tên bản địa không được để trống.',
            'direction.in' => 'Hướng chữ phải là LTR hoặc RTL.',
        ]);

        $validated['code'] = trim($validated['code']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        Language::create($validated);

        return redirect()
            ->route('admin.languages.index')
            ->with('success', "Đã thêm ngôn ngữ {$validated['name']} ({$validated['code']}) thành công.");
    }

    /**
     * Form chỉnh sửa ngôn ngữ. Mã ngôn ngữ không được phép thay đổi.
     */
    public function edit(Language $language): View
    {
        Gate::authorize('update', $language);

        return view('admin.languages.edit', compact('language'));
    }

    /**
     * Cập nhật thông tin ngôn ngữ (không cho đổi code).
     */
    public function update(Request $request, Language $language): RedirectResponse
    {
        Gate::authorize('update', $language);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'native_name' => ['required', 'string', 'max:100'],
            'direction' => ['required', Rule::in(['ltr', 'rtl'])],
            'tts_locale' => ['nullable', 'string', 'max:30'],
            'tts_voice' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Tên tiếng Việt không được để trống.',
            'native_name.required' => 'Tên bản địa không được để trống.',
            'direction.in' => 'Hướng chữ phải là LTR hoặc RTL.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        // Không cho phép thay đổi code
        unset($validated['code']);

        $language->update($validated);

        return redirect()
            ->route('admin.languages.index')
            ->with('success', "Đã cập nhật thông tin ngôn ngữ {$language->name} thành công.");
    }

    /**
     * Bật hoặc tắt trạng thái hoạt động của ngôn ngữ.
     */
    public function toggleActive(Language $language): RedirectResponse
    {
        Gate::authorize('toggle', $language);

        $language->is_active = ! $language->is_active;
        $language->save();

        $statusText = $language->is_active ? 'kích hoạt' : 'tắt';

        return redirect()
            ->back()
            ->with('success', "Đã {$statusText} ngôn ngữ {$language->name} ({$language->code}).");
    }
}

