<?php

namespace Tests\Feature;

use App\Models\Deck;
use App\Models\EntryMeaning;
use App\Models\Language;
use App\Models\TtsUsage;
use App\Models\User;
use App\Models\VocabularyEntry;
use App\Services\Audio\AzureSpeechTtsService;
use App\Services\Study\ExerciseEligibilityService;
use App\Services\Study\QuestionGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AzureTtsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Language $learningLang;
    protected Language $meaningLang;
    protected Deck $publicDeck;
    protected Deck $privateDeck;
    protected VocabularyEntry $entry;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->create(['role' => 'member']);
        $this->otherUser = User::factory()->create(['role' => 'member']);

        $this->learningLang = Language::create([
            'code' => 'en',
            'name' => 'Tiếng Anh',
            'native_name' => 'English',
            'direction' => 'ltr',
            'tts_locale' => 'en-US',
            'tts_voice' => 'en-US-JennyNeural',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->meaningLang = Language::create([
            'code' => 'vi',
            'name' => 'Tiếng Việt',
            'native_name' => 'Tiếng Việt',
            'direction' => 'ltr',
            'tts_locale' => 'vi-VN',
            'tts_voice' => 'vi-VN-HoaiMyNeural',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->publicDeck = new Deck([
            'learning_language_id' => $this->learningLang->id,
            'meaning_language_id' => $this->meaningLang->id,
            'name' => 'Bộ từ công khai',
            'visibility' => 'public',
        ]);
        $this->publicDeck->user_id = $this->user->id;
        $this->publicDeck->save();

        $this->privateDeck = new Deck([
            'learning_language_id' => $this->learningLang->id,
            'meaning_language_id' => $this->meaningLang->id,
            'name' => 'Bộ từ riêng tư',
            'visibility' => 'private',
        ]);
        $this->privateDeck->user_id = $this->user->id;
        $this->privateDeck->save();

        $this->entry = new VocabularyEntry([
            'term' => 'Hello',
            'pronunciation' => 'həˈloʊ',
        ]);
        $this->entry->deck_id = $this->publicDeck->id;
        $this->entry->save();

        $this->entry->meanings()->create([
            'meaning' => 'Xin chào',
            'sort_order' => 1,
        ]);
    }

    public function test_service_detects_unconfigured_status(): void
    {
        Config::set('services.azure_speech.tts_enabled', false);

        /** @var AzureSpeechTtsService $service */
        $service = app(AzureSpeechTtsService::class);
        $this->assertFalse($service->isConfigured());

        $result = $service->generateForEntry($this->entry, $this->user);
        $this->assertFalse($result['success']);
        $this->assertSame('not_configured', $result['status']);
    }

    public function test_synthesizes_speech_and_saves_audio_file(): void
    {
        Config::set('services.azure_speech.tts_enabled', true);
        Config::set('services.azure_speech.key', 'fake-azure-key');
        Config::set('services.azure_speech.region', 'southeastasia');

        $fakeMp3Content = 'FAKE_MP3_BINARY_DATA_STREAM';

        Http::fake([
            'https://southeastasia.tts.speech.microsoft.com/cognitiveservices/v1' => Http::response($fakeMp3Content, 200, [
                'Content-Type' => 'audio/mpeg',
            ]),
        ]);

        /** @var AzureSpeechTtsService $service */
        $service = app(AzureSpeechTtsService::class);
        $result = $service->generateForEntry($this->entry, $this->user);

        $this->assertTrue($result['success']);
        $this->assertSame('created', $result['status']);
        $this->assertNotNull($result['audio_path']);

        // Xác nhận file được lưu trong Storage local
        Storage::disk('local')->assertExists($result['audio_path']);
        $this->assertSame($fakeMp3Content, Storage::disk('local')->get($result['audio_path']));

        // Xác nhận DB VocabularyEntry đã được cập nhật
        $this->entry->refresh();
        $this->assertSame($result['audio_path'], $this->entry->audio_path);
        $this->assertSame('azure_tts', $this->entry->audio_source);
        $this->assertSame('en-US-JennyNeural', $this->entry->audio_voice);
        $this->assertNotEmpty($this->entry->audio_hash);
        $this->assertSame(5, $this->entry->audio_char_count); // 'Hello' = 5 ký tự

        // Xác nhận lịch sử ký tự sử dụng
        $this->assertDatabaseHas('tts_usages', [
            'vocabulary_entry_id' => $this->entry->id,
            'characters_used' => 5,
            'locale' => 'en-US',
            'voice' => 'en-US-JennyNeural',
        ]);
    }

    public function test_does_not_duplicate_generation_if_hash_matches_and_file_exists(): void
    {
        Config::set('services.azure_speech.tts_enabled', true);
        Config::set('services.azure_speech.key', 'fake-azure-key');
        Config::set('services.azure_speech.region', 'southeastasia');

        $fakeMp3Content = 'EXISTING_AUDIO';
        $existingPath = 'audio/entries/pre_existing.mp3';
        Storage::disk('local')->put($existingPath, $fakeMp3Content);

        $hash = hash('sha256', 'Hello|en-US|en-US-JennyNeural');
        $this->entry->update([
            'audio_path' => $existingPath,
            'audio_source' => 'azure_tts',
            'audio_voice' => 'en-US-JennyNeural',
            'audio_hash' => $hash,
            'audio_char_count' => 5,
        ]);

        Http::fake();

        /** @var AzureSpeechTtsService $service */
        $service = app(AzureSpeechTtsService::class);
        $result = $service->generateForEntry($this->entry, $this->user);

        $this->assertTrue($result['success']);
        $this->assertSame('reused', $result['status']);
        $this->assertSame($existingPath, $result['audio_path']);

        // Đảm bảo không gọi Azure khi đã có tệp trùng hash
        Http::assertNothingSent();
    }

    public function test_preserves_manual_audio_unless_explicitly_forced(): void
    {
        Config::set('services.azure_speech.tts_enabled', true);
        Config::set('services.azure_speech.key', 'fake-azure-key');
        Config::set('services.azure_speech.region', 'southeastasia');

        $manualPath = 'audio/entries/manual_upload.mp3';
        Storage::disk('local')->put($manualPath, 'MANUAL_AUDIO_DATA');

        $this->entry->update([
            'audio_path' => $manualPath,
            'audio_source' => 'manual',
        ]);

        /** @var AzureSpeechTtsService $service */
        $service = app(AzureSpeechTtsService::class);

        // Gọi không force: phải giữ nguyên audio manual
        $result = $service->generateForEntry($this->entry, $this->user, force: false);
        $this->assertFalse($result['success']);
        $this->assertSame('manual_audio_preserved', $result['status']);
        $this->entry->refresh();
        $this->assertSame($manualPath, $this->entry->audio_path);

        // Gọi với force = true: cho phép ghi đè
        Http::fake([
            'https://southeastasia.tts.speech.microsoft.com/cognitiveservices/v1' => Http::response('NEW_TTS_AUDIO', 200),
        ]);

        $forceResult = $service->generateForEntry($this->entry, $this->user, force: true);
        $this->assertTrue($forceResult['success']);
        $this->assertSame('created', $forceResult['status']);
        $this->entry->refresh();
        $this->assertSame('azure_tts', $this->entry->audio_source);
    }

    public function test_handles_quota_exceeded(): void
    {
        Config::set('services.azure_speech.tts_enabled', true);
        Config::set('services.azure_speech.key', 'fake-azure-key');
        Config::set('services.azure_speech.region', 'southeastasia');
        Config::set('services.azure_speech.monthly_character_limit', 10);

        // Tạo usage giả đã dùng 10 ký tự
        TtsUsage::create([
            'user_id' => $this->user->id,
            'vocabulary_entry_id' => $this->entry->id,
            'characters_used' => 10,
            'locale' => 'en-US',
            'voice' => 'en-US-JennyNeural',
            'created_at' => now(),
        ]);

        Http::fake();

        /** @var AzureSpeechTtsService $service */
        $service = app(AzureSpeechTtsService::class);
        $result = $service->generateForEntry($this->entry, $this->user);

        $this->assertFalse($result['success']);
        $this->assertSame('quota_exceeded', $result['status']);
        Http::assertNothingSent();
    }

    public function test_handles_azure_api_error_and_does_not_delete_old_audio(): void
    {
        Config::set('services.azure_speech.tts_enabled', true);
        Config::set('services.azure_speech.key', 'fake-azure-key');
        Config::set('services.azure_speech.region', 'southeastasia');

        $oldAudioPath = 'audio/entries/old_audio.mp3';
        Storage::disk('local')->put($oldAudioPath, 'OLD_VALID_AUDIO');
        $this->entry->update([
            'audio_path' => $oldAudioPath,
            'audio_source' => 'azure_tts',
            'audio_voice' => 'en-US-JennyNeural',
            'audio_hash' => 'old_hash',
        ]);

        Http::fake([
            'https://southeastasia.tts.speech.microsoft.com/cognitiveservices/v1' => Http::response('Server Error', 500),
        ]);

        /** @var AzureSpeechTtsService $service */
        $service = app(AzureSpeechTtsService::class);
        $result = $service->generateForEntry($this->entry, $this->user, force: true);

        $this->assertFalse($result['success']);
        $this->assertSame('error', $result['status']);

        // File audio cũ không bị xóa khi tái tạo thất bại
        $this->entry->refresh();
        $this->assertSame($oldAudioPath, $this->entry->audio_path);
        Storage::disk('local')->assertExists($oldAudioPath);
    }

    public function test_access_permissions_for_audio_stream(): void
    {
        $audioFile = 'audio/entries/test.mp3';
        Storage::disk('local')->put($audioFile, 'AUDIO_CONTENT');

        // 1. Audio của bộ từ công khai: khách xem được
        $this->entry->update(['audio_path' => $audioFile]);

        $response = $this->get(route('audio.entries.play', $this->entry));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'audio/mpeg');

        // 2. Audio của bộ từ riêng tư:
        $privateEntry = new VocabularyEntry([
            'term' => 'Secret',
            'audio_path' => $audioFile,
        ]);
        $privateEntry->deck_id = $this->privateDeck->id;
        $privateEntry->save();

        // Khách truy cập -> 404
        $this->get(route('audio.entries.play', $privateEntry))->assertNotFound();

        // Thành viên khác truy cập -> 404
        $this->actingAs($this->otherUser)
            ->get(route('audio.entries.play', $privateEntry))
            ->assertNotFound();

        // Chủ sở hữu truy cập -> 200 OK
        $this->actingAs($this->user)
            ->get(route('audio.entries.play', $privateEntry))
            ->assertOk();
    }

    public function test_only_deck_owner_can_generate_audio(): void
    {
        Config::set('services.azure_speech.tts_enabled', true);
        Config::set('services.azure_speech.key', 'fake-azure-key');

        // Người dùng khác không được quyền generate
        $this->actingAs($this->otherUser)
            ->postJson(route('audio.entries.generate', $this->entry))
            ->assertForbidden();

        // Chủ sở hữu được quyền generate
        Http::fake([
            '*' => Http::response('NEW_AUDIO', 200),
        ]);

        $this->actingAs($this->user)
            ->postJson(route('audio.entries.generate', $this->entry))
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_question_generator_does_not_leak_term_in_listen_exercises(): void
    {
        $this->entry->update(['audio_path' => 'audio/entries/hello.mp3']);
        $meaning = $this->entry->meanings->first();

        /** @var QuestionGeneratorService $questionService */
        $questionService = app(QuestionGeneratorService::class);

        // 1. Listen and type
        $listenTypeQ = $questionService->generateQuestion($meaning, 'listen_and_type', $this->publicDeck);
        $clientListenTypeQ = $questionService->stripAnswersForClient($listenTypeQ);

        $this->assertArrayHasKey('audio_url', $clientListenTypeQ);
        $this->assertArrayNotHasKey('term', $clientListenTypeQ);
        $this->assertArrayNotHasKey('audio_term', $clientListenTypeQ);
        $this->assertArrayNotHasKey('correct_answer', $clientListenTypeQ);

        // 2. Listen and choose
        $listenChooseQ = $questionService->generateQuestion($meaning, 'listen_and_choose', $this->publicDeck);
        $clientListenChooseQ = $questionService->stripAnswersForClient($listenChooseQ);

        $this->assertArrayHasKey('audio_url', $clientListenChooseQ);
        $this->assertArrayNotHasKey('term', $clientListenChooseQ);
        $this->assertArrayNotHasKey('audio_term', $clientListenChooseQ);
        $this->assertArrayNotHasKey('meaning', $clientListenChooseQ);
        $this->assertArrayNotHasKey('correct_answer', $clientListenChooseQ);
    }

    public function test_exercise_eligibility_reflects_audio_status(): void
    {
        /** @var ExerciseEligibilityService $eligibilityService */
        $eligibilityService = app(ExerciseEligibilityService::class);

        // Khi chưa có file audio Azure TTS, dạng bài nghe vẫn khả dụng qua Web Speech API trình duyệt
        $this->entry->update(['audio_path' => null]);
        $resultsWithoutAudio = $eligibilityService->evaluateDecksExercises([$this->publicDeck->id]);

        $this->assertTrue($resultsWithoutAudio['listen_and_type']['is_eligible']);
        $this->assertFalse($resultsWithoutAudio['listen_and_type']['has_server_audio']);

        // Sau khi có audio Azure TTS, đánh dấu đã có server audio chất lượng cao
        $this->entry->update(['audio_path' => 'audio/entries/sample.mp3']);
        $resultsWithAudio = $eligibilityService->evaluateDecksExercises([$this->publicDeck->id]);

        $this->assertTrue($resultsWithAudio['listen_and_type']['is_eligible']);
        $this->assertTrue($resultsWithAudio['listen_and_type']['has_server_audio']);
        $this->assertSame(1, $resultsWithAudio['listen_and_type']['server_audio_count']);
    }

    public function test_admin_can_update_language_tts_configuration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.languages.update', $this->learningLang), [
                'name' => 'Tiếng Anh (Mỹ)',
                'native_name' => 'English (US)',
                'direction' => 'ltr',
                'tts_locale' => 'en-US',
                'tts_voice' => 'en-US-GuyNeural',
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.languages.index'));

        $this->learningLang->refresh();
        $this->assertSame('en-US-GuyNeural', $this->learningLang->tts_voice);
    }

    public function test_owner_can_generate_all_audio_for_deck(): void
    {
        Config::set('services.azure_speech.tts_enabled', true);
        Config::set('services.azure_speech.key', 'fake-azure-key');

        Http::fake([
            '*' => Http::response('AUDIO_DATA', 200),
        ]);

        $this->actingAs($this->user)
            ->postJson(route('decks.audio.generate_all', $this->publicDeck))
            ->assertOk()
            ->assertJson(['success' => true, 'generated_count' => 1]);

        $this->entry->refresh();
        $this->assertNotNull($this->entry->audio_path);
    }
}
