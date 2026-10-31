<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\VocabularyEntry;
use App\Services\Audio\AzureSpeechTtsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateEntryAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Số lần thử tối đa khi job gặp sự cố.
     */
    public int $tries = 2;

    /**
     * Thời gian chờ giữa các lần thử lại (giây).
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [5, 15];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $entryId,
        public ?int $userId = null,
        public bool $force = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AzureSpeechTtsService $ttsService): void
    {
        $entry = VocabularyEntry::with('deck.learningLanguage')->find($this->entryId);
        if (! $entry) {
            return;
        }

        $user = $this->userId ? User::find($this->userId) : null;

        $result = $ttsService->generateForEntry($entry, $user, $this->force);

        if (! $result['success'] && in_array($result['status'], ['busy', 'error'], true)) {
            Log::info("GenerateEntryAudioJob retry or stopped for entry {$this->entryId}: {$result['message']}");
        }
    }
}

