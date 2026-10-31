@extends('layouts.app')

@section('title', 'Sửa từ: ' . $entry->term . ' | ' . $deck->name . ' | ' . config('app.name'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('decks.index') }}" class="hover:text-indigo-600">Bộ từ của tôi</a>
                <span>&rsaquo;</span>
                <a href="{{ route('decks.show', $deck) }}" class="hover:text-indigo-600 truncate max-w-[200px]">{{ $deck->name }}</a>
                <span>&rsaquo;</span>
                <a href="{{ route('decks.entries.show', [$deck, $entry]) }}" class="hover:text-indigo-600 truncate max-w-[150px]">{{ $entry->term }}</a>
                <span>&rsaquo;</span>
                <span>Chỉnh sửa</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Chỉnh sửa mục từ vựng
            </h1>
        </div>
        <a href="{{ route('decks.entries.show', [$deck, $entry]) }}"
           class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            &larr; Xem mục từ
        </a>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
        <form method="POST" action="{{ route('decks.entries.update', [$deck, $entry]) }}">
            @csrf
            @method('PUT')

            @include('decks.entries._form')
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let formCurrentAudio = null;

    function playFormAudio(url) {
        if (!url) return;
        if (formCurrentAudio) {
            formCurrentAudio.pause();
            formCurrentAudio.currentTime = 0;
        }
        formCurrentAudio = new Audio(url);
        formCurrentAudio.play().catch(e => console.warn('Lỗi phát âm thanh:', e));
    }

    async function generateFormAudio(force = false) {
        const btn = document.getElementById('formBtnGenerateAudio');
        const spinner = document.getElementById('formGenerateSpinner');
        const btnText = document.getElementById('formGenerateBtnText');
        const notice = document.getElementById('formAudioNotice');
        const badge = document.getElementById('formAudioBadge');
        const playContainer = document.getElementById('formAudioPlayContainer');
        const playBtn = document.getElementById('formBtnPlayAudio');

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
                    return generateFormAudio(true);
                } else {
                    notice.className = 'text-xs font-medium text-slate-500 block';
                    notice.innerText = 'Đã giữ nguyên tệp âm thanh thủ công.';
                }
            } else if (data.success) {
                notice.className = 'text-xs font-medium text-emerald-600 block';
                notice.innerText = 'Đã tạo âm thanh.';
                btnText.innerText = 'Tạo lại phát âm (TTS)';

                if (badge) {
                    badge.className = 'inline-flex items-center rounded-lg bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-2xs font-semibold text-emerald-800';
                    badge.innerText = '✓ Đã có âm thanh';
                }

                if (data.url) {
                    playContainer.classList.remove('hidden');
                    playBtn.onclick = () => playFormAudio(data.url);
                    playFormAudio(data.url);
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

