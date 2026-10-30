@php
    $themeService = app(\App\Services\ThemeService::class);
    $activeTheme = $themeService->resolveTheme(auth()->user());
    $availableThemes = $themeService->getAvailableThemes();
@endphp

<div class="relative" id="theme-switcher-container">
    {{-- Trigger button tối giản --}}
    <button type="button"
            id="theme-switcher-button"
            class="flex items-center space-x-1.5 rounded-xl border border-slate-300/80 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition shadow-2xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            title="Đổi chủ đề giao diện"
            aria-label="Đổi chủ đề giao diện"
            aria-expanded="false"
            onclick="window.toggleThemeMenu(event)">
        <span class="relative inline-flex h-3.5 w-3.5 shrink-0 rounded-full border border-slate-300 shadow-2xs overflow-hidden"
              id="theme-active-swatch"
              style="background: linear-gradient(135deg, {{ $activeTheme->previewColors()['bg'] }} 50%, {{ $activeTheme->previewColors()['primary'] }} 50%);">
        </span>
        <span class="hidden md:inline font-medium" id="theme-active-label">{{ $activeTheme->label() }}</span>
        <svg class="w-3 h-3 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
        </svg>
    </button>

    {{-- Dropdown menu tối giản gọn gàng: 5 lựa chọn, có swatch vát chéo --}}
    <div id="theme-menu-panel"
         class="hidden absolute right-0 mt-2 w-44 rounded-xl border border-slate-200 bg-white p-1 shadow-lg z-50 ring-1 ring-black/5 animate-in fade-in zoom-in-95 duration-100"
         role="menu"
         aria-orientation="vertical"
         aria-labelledby="theme-switcher-button">

        <div class="space-y-0.5">
            @foreach ($availableThemes as $themeItem)
                @php
                    $isActive = ($activeTheme->value === $themeItem['value']);
                    $bgGrad = "linear-gradient(135deg, {$themeItem['preview_colors']['bg']} 50%, {$themeItem['preview_colors']['primary']} 50%)";
                @endphp
                <button type="button"
                        class="theme-option-btn w-full flex items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-xs transition cursor-pointer {{ $isActive ? 'theme-option-active font-semibold' : 'theme-option-inactive font-medium' }}"
                        data-theme-key="{{ $themeItem['value'] }}"
                        data-color-scheme="{{ $themeItem['color_scheme'] }}"
                        data-label="{{ $themeItem['label'] }}"
                        data-swatch-gradient="{{ $bgGrad }}"
                        onclick="window.selectTheme('{{ $themeItem['value'] }}', '{{ $themeItem['color_scheme'] }}', '{{ $themeItem['label'] }}', '{{ $bgGrad }}')">

                    <div class="flex items-center space-x-2.5">
                        {{-- Swatch màu tròn vát chéo chuẩn đẹp --}}
                        <span class="relative inline-flex h-3.5 w-3.5 shrink-0 rounded-full border border-slate-300/80 shadow-2xs overflow-hidden"
                              style="background: {{ $bgGrad }};"></span>

                        <span class="theme-option-label">
                            {{ $themeItem['label'] }}
                        </span>
                    </div>

                    {{-- Dấu tích khi đang chọn --}}
                    <span class="theme-check-indicator text-indigo-600 shrink-0 {{ $isActive ? '' : 'hidden' }}"
                          data-theme-for="{{ $themeItem['value'] }}">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</div>
