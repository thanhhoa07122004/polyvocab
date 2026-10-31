<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateEntryAudioJob;
use App\Models\Deck;
use App\Models\VocabularyEntry;
use App\Services\Audio\AzureSpeechTtsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AudioController extends Controller
{
    public function __construct(
        protected AzureSpeechTtsService $ttsService
    ) {}

    /**
     * Phát âm thanh của một mục từ vựng qua endpoint bảo mật.
     * Kiểm tra quyền truy cập theo bộ từ: nếu là bộ riêng tư, chỉ chủ sở hữu được nghe.
     */
    public function play(Request $request, VocabularyEntry $entry): StreamedResponse
    {
        $deck = $entry->deck;

        // Nếu bộ từ riêng tư và người dùng không có quyền xem, trả về 404 để không lộ tài nguyên
        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        if (! Gate::allows('view', $entry)) {
            abort(404);
        }

        if (! $entry->hasAudio() || ! Storage::disk('local')->exists((string) $entry->audio_path)) {
            abort(404, 'Âm thanh không tồn tại hoặc đã bị xóa.');
        }

        return Storage::disk('local')->response(
            (string) $entry->audio_path,
            null,
            [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]
        );
    }

    /**
     * Yêu cầu tạo hoặc tạo lại phát âm cho một mục từ vựng.
     * Chỉ người có quyền chỉnh sửa mục từ mới được tạo/tạo lại.
     */
    public function generate(Request $request, VocabularyEntry $entry): JsonResponse
    {
        $deck = $entry->deck;

        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('update', $entry);

        // Giới hạn tần suất yêu cầu tạo audio theo người dùng (tối đa 15 yêu cầu mỗi phút)
        $userId = $request->user()?->id ?? $request->ip();
        $rateLimitKey = "tts:rate_limit:{$userId}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, 15)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'success' => false,
                'status' => 'rate_limited',
                'message' => "Bạn đang thao tác quá nhanh. Vui lòng chờ {$seconds} giây.",
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        $force = (bool) $request->boolean('force', false);
        $async = (bool) $request->boolean('async', false);

        if ($async) {
            GenerateEntryAudioJob::dispatch($entry->id, $request->user()?->id, $force);

            return response()->json([
                'success' => true,
                'status' => 'queued',
                'message' => 'Đang tạo âm thanh…',
            ]);
        }

        // Tạo trực tiếp để phản hồi ngay trên giao diện
        $result = $this->ttsService->generateForEntry($entry, $request->user(), $force);

        return response()->json($result);
    }

    /**
     * Lấy trạng thái âm thanh hiện tại của mục từ vựng.
     */
    public function status(Request $request, VocabularyEntry $entry): JsonResponse
    {
        $deck = $entry->deck;

        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('view', $entry);

        $hasAudio = $entry->hasAudio() && Storage::disk('local')->exists((string) $entry->audio_path);

        return response()->json([
            'entry_id' => $entry->id,
            'has_audio' => $hasAudio,
            'audio_url' => $hasAudio ? route('audio.entries.play', ['entry' => $entry->id]) : null,
            'audio_source' => $entry->audio_source,
            'audio_voice' => $entry->audio_voice,
            'is_manual' => $entry->isManualAudio(),
            'is_tts' => $entry->isTtsAudio(),
            'service_configured' => $this->ttsService->isConfigured(),
        ]);
    }

    /**
     * Tạo âm thanh Azure TTS tự động cho toàn bộ các mục từ trong một bộ từ chưa có audio.
     */
    public function generateAllForDeck(Request $request, Deck $deck): JsonResponse
    {
        if ($deck->isPrivate() && (! $request->user() || $request->user()->id !== $deck->user_id)) {
            abort(404);
        }

        Gate::authorize('update', $deck);

        if (! $this->ttsService->isConfigured()) {
            return response()->json([
                'success' => false,
                'status' => 'not_configured',
                'message' => 'Dịch vụ Azure TTS chưa được cấu hình key trong .env. Bạn vẫn có thể luyện nghe bình thường bằng giọng đọc tích hợp của trình duyệt!',
            ]);
        }

        $entries = $deck->entries()->whereNull('audio_path')->get();
        if ($entries->isEmpty()) {
            return response()->json([
                'success' => true,
                'status' => 'already_done',
                'message' => 'Tất cả các từ trong bộ từ này đã có âm thanh phát âm.',
                'generated_count' => 0,
            ]);
        }

        $generated = 0;
        foreach ($entries as $entry) {
            $res = $this->ttsService->generateForEntry($entry, $request->user());
            if ($res['success']) {
                $generated++;
            }
        }

        return response()->json([
            'success' => true,
            'status' => 'completed',
            'message' => "Đã tạo phát âm thành công cho {$generated} từ vựng.",
            'generated_count' => $generated,
        ]);
    }
}

