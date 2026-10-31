@extends('layouts.app')

@section('title', $entry->term . ' | ' . $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Breadcrumbs & Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2 text-xs text-slate-500">
            @auth
                @if (auth()->id() === $deck->user_id)
                    <a href="{{ route('decks.index') }}" class="hover:text-indigo-600">Bộ từ của tôi</a>
                @else
                    <a href="{{ route('explore') }}" class="hover:text-indigo-600">Khám phá</a>
                @endif
            @else
                <a href="{{ route('explore') }}" class="hover:text-indigo-600">Khám phá</a>
            @endauth
            <span>&rsaquo;</span>
            <a href="{{ route('decks.show', $deck) }}" class="hover:text-indigo-600 truncate max-w-[200px]">{{ $deck->name }}</a>
            <span>&rsaquo;</span>
            <span class="font-medium text-slate-900 truncate max-w-[150px]">{{ $entry->term }}</span>
        </div>

        <a href="{{ route('decks.show', $deck) }}"
           class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            &larr; Về bộ từ
        </a>
    </div>

    <!-- Khối chi tiết mục từ vựng -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 border-b border-slate-100 pb-6">
            <div>
                <!-- Từ vựng gốc -->
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900"
                    lang="{{ $deck->learningLanguage->code }}"
                    dir="{{ $deck->learningLanguage->direction }}">
                    {{ $entry->term }}
                </h1>

                <!-- Phiên âm & Phát âm -->
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    @if ($entry->pronunciation)
                        <div class="text-base font-mono text-indigo-600"
                             lang="{{ $deck->learningLanguage->code }}"
                             dir="{{ $deck->learningLanguage->direction }}">
                            /{{ $entry->pronunciation }}/
                        </div>
                    @endif

                    <!-- Nút phát âm thanh -->
                    <div id="audioPlayContainer" class="{{ $entry->hasAudio() ? '' : 'hidden' }}">
                        <button type="button"
                                id="btnPlayAudio"
                                onclick="playAudio('{{ $entry->audio_url }}')"
                                class="inline-flex items-center space-x-1.5 rounded-full bg-indigo-50 border border-indigo-200/80 px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition cursor-pointer">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            </svg>
                            <span>Nghe phát âm</span>
                        </button>
                    </div>
                </div>

                <!-- Khu vực Quản lý Audio cho người biên soạn -->
                @can('update', $deck)
                    <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button"
                                    id="btnGenerateAudio"
                                    onclick="generateAudio(false)"
                                    class="inline-flex items-center space-x-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition cursor-pointer">
                                <span id="generateSpinner" class="hidden">
                                    <svg class="h-3.5 w-3.5 animate-spin text-slate-700" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </span>
                                <span id="generateBtnText">{{ $entry->hasAudio() ? 'Tạo lại phát âm (TTS)' : 'Tạo phát âm (TTS)' }}</span>
                            </button>

                            @if ($entry->hasAudio())
                                <span id="audioMetadataBadge" class="inline-flex items-center rounded-lg bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-2xs font-medium text-emerald-800">
                                    ✓ Đã có âm thanh ({{ $entry->audio_source === 'manual' ? 'Thủ công' : ($entry->audio_voice ?? 'Azure TTS') }})
                                </span>
                            @else
                                <span id="audioMetadataBadge" class="inline-flex items-center rounded-lg bg-slate-50 border border-slate-200 px-2.5 py-1 text-2xs font-medium text-slate-500">
                                    Chưa có âm thanh
                                </span>
                            @endif
                        </div>

                        <!-- Thông báo trạng thái tạo audio -->
                        <div id="audioNotice" class="hidden text-xs font-medium"></div>
                    </div>
                @endcan
            </div>

            <!-- Nút thao tác của chủ sở hữu -->
            @can('update', $deck)
                <div class="flex items-center space-x-2 shrink-0">
                    <a href="{{ route('decks.entries.edit', [$deck, $entry]) }}"
                       class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        Chỉnh sửa
                    </a>

                    <form method="POST" action="{{ route('decks.entries.destroy', [$deck, $entry]) }}"
                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa mục từ « {{ addslashes($entry->term) }} »?');"
                          class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                            Xóa
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <!-- Ghi chú chung -->
        @if ($entry->note)
            <div class="rounded-2xl bg-amber-50/60 border border-amber-200/80 p-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-amber-800 mb-1">Ghi chú mục từ</div>
                <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">{{ $entry->note }}</p>
            </div>
        @endif

        <!-- Danh sách các ý nghĩa -->
        <div class="space-y-6 pt-2">
            <h2 class="text-lg font-bold text-slate-900">
                Các ý nghĩa ({{ $entry->meanings->count() }})
            </h2>

            <div class="space-y-5">
                @foreach ($entry->meanings as $meaning)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-5 space-y-4">
                        <div class="flex items-start">
                            <span class="mr-3 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white font-mono text-xs font-bold">
                                {{ $meaning->sort_order }}
                            </span>

                            <div class="space-y-1 flex-1">
                                @if ($meaning->part_of_speech)
                                    <span class="inline-block rounded-md bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800">
                                        {{ $meaning->part_of_speech }}
                                    </span>
                                @endif

                                @if ($meaning->meaning)
                                    <p class="text-base text-slate-900 leading-relaxed font-medium"
                                       lang="{{ $deck->meaningLanguage->code }}"
                                       dir="{{ $deck->meaningLanguage->direction }}">
                                        {{ $meaning->meaning }}
                                    </p>
                                @else
                                    <p class="text-sm italic text-slate-400">
                                        (Chưa có nội dung ý nghĩa)
                                    </p>
                                @endif
                            </div>
                        </div>

                        <!-- Danh sách ví dụ của nghĩa -->
                        @if ($meaning->examples->count() > 0)
                            <div class="mt-3 border-t border-slate-200/80 pt-3 pl-9 space-y-3">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Ví dụ minh họa</span>

                                <div class="space-y-2.5">
                                    @foreach ($meaning->examples as $example)
                                        <div class="rounded-xl bg-white p-3 border border-slate-200/70 space-y-1">
                                            <!-- Câu ví dụ theo ngôn ngữ học -->
                                            <p class="text-sm font-medium text-slate-900"
                                               lang="{{ $deck->learningLanguage->code }}"
                                               dir="{{ $deck->learningLanguage->direction }}">
                                                {{ $example->sentence }}
                                            </p>

                                            <!-- Bản dịch câu ví dụ -->
                                            @if ($example->translation)
                                                <p class="text-xs text-slate-600"
                                                   lang="{{ $deck->meaningLanguage->code }}"
                                                   dir="{{ $deck->meaningLanguage->direction }}">
                                                    {{ $example->translation }}
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentAudio = null;

    function playAudio(url) {
        if (!url) return;
        if (currentAudio) {
            currentAudio.pause();
            currentAudio.currentTime = 0;
        }
        currentAudio = new Audio(url);
        currentAudio.play().catch(e => console.warn('Lỗi phát âm thanh:', e));
    }

    async function generateAudio(force = false) {
        const btn = document.getElementById('btnGenerateAudio');
        const spinner = document.getElementById('generateSpinner');
        const btnText = document.getElementById('generateBtnText');
        const notice = document.getElementById('audioNotice');
        const badge = document.getElementById('audioMetadataBadge');
        const playContainer = document.getElementById('audioPlayContainer');
        const playBtn = document.getElementById('btnPlayAudio');

        if (!btn) return;

        btn.disabled = true;
        if (spinner) spinner.classList.remove('hidden');
        if (notice) {
            notice.className = 'text-xs font-medium text-indigo-600 block';
            notice.innerText = 'Đang tạo âm thanh…';
        }

        try {
            const resp = await fetch('{{ route('audio.entries.generate', $entry) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ force: force })
            });

            const data = await resp.json();

            if (data.status === 'manual_audio_preserved') {
                if (confirm(data.message + ' Bạn có muốn ghi đè bằng giọng Azure TTS không?')) {
                    return generateAudio(true);
                } else {
                    notice.className = 'text-xs font-medium text-slate-500 block';
                    notice.innerText = 'Đã giữ nguyên tệp âm thanh thủ công.';
                }
            } else if (data.success) {
                notice.className = 'text-xs font-medium text-emerald-600 block';
                notice.innerText = 'Đã tạo âm thanh.';
                btnText.innerText = 'Tạo lại phát âm (TTS)';

                if (badge) {
                    badge.className = 'inline-flex items-center rounded-lg bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-2xs font-medium text-emerald-800';
                    badge.innerText = '✓ Đã có âm thanh';
                }

                if (data.url) {
                    playContainer.classList.remove('hidden');
                    playBtn.onclick = () => playAudio(data.url);
                    playAudio(data.url);
                }
            } else {
                notice.className = 'text-xs font-medium text-rose-600 block';
                notice.innerText = data.message || 'Chưa tạo được âm thanh. Hãy thử lại sau.';
            }
        } catch (e) {
            notice.className = 'text-xs font-medium text-rose-600 block';
            notice.innerText = 'Chưa tạo được âm thanh. Hãy thử lại sau.';
        } finally {
            btn.disabled = false;
            if (spinner) spinner.classList.add('hidden');
        }
    }
</script>
@endpush

