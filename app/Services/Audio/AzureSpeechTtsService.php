<?php

namespace App\Services\Audio;

use App\Exceptions\AzureTtsException;
use App\Exceptions\AzureTtsNotConfiguredException;
use App\Exceptions\AzureTtsQuotaExceededException;
use App\Models\TtsUsage;
use App\Models\User;
use App\Models\VocabularyEntry;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AzureSpeechTtsService
{
    /**
     * Mặc định giọng đọc theo ngôn ngữ ISO (BCP-47 locale & voice).
     * Được sử dụng làm giá trị dự phòng nếu ngôn ngữ trong DB chưa cấu hình.
     */
    public const DEFAULT_VOICES = [
        'vi' => ['locale' => 'vi-VN', 'voice' => 'vi-VN-HoaiMyNeural'],
        'en' => ['locale' => 'en-US', 'voice' => 'en-US-JennyNeural'],
        'zh' => ['locale' => 'zh-CN', 'voice' => 'zh-CN-XiaoxiaoNeural'],
        'ja' => ['locale' => 'ja-JP', 'voice' => 'ja-JP-NanamiNeural'],
        'ko' => ['locale' => 'ko-KR', 'voice' => 'ko-KR-SunHiNeural'],
    ];

    /**
     * Kiểm tra xem dịch vụ Azure Speech đã được cấu hình đầy đủ chưa.
     */
    public function isConfigured(): bool
    {
        $enabled = config('services.azure_speech.tts_enabled', false);
        $key = config('services.azure_speech.key');
        $region = config('services.azure_speech.region');

        return ! empty($enabled) && ! empty($key) && ! empty($region);
    }

    /**
     * Lấy region đang cấu hình.
     */
    public function getRegion(): string
    {
        return (string) config('services.azure_speech.region', 'southeastasia');
    }

    /**
     * Lấy tổng số ký tự đã sử dụng trong tháng hiện tại (ước tính).
     */
    public function getMonthlyUsage(?Carbon $date = null): int
    {
        $date = $date ?? Carbon::now();
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        return (int) TtsUsage::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('characters_used');
    }

    /**
     * Kiểm tra xem đã vượt quá hạn mức ký tự hàng tháng chưa.
     */
    public function isMonthlyLimitReached(): bool
    {
        $limit = (int) config('services.azure_speech.monthly_character_limit', 500000);
        if ($limit <= 0) {
            return false;
        }

        return $this->getMonthlyUsage() >= $limit;
    }

    /**
     * Tạo SSML an toàn, escape toàn bộ ký tự đặc biệt của XML.
     */
    public function buildSsml(string $text, string $locale, string $voice): string
    {
        $escapedText = htmlspecialchars(trim($text), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return "<speak version='1.0' xmlns='http://www.w3.org/2001/10/synthesis' xml:lang='{$locale}'>"
            . "<voice xml:lang='{$locale}' name='{$voice}'>{$escapedText}</voice>"
            . '</speak>';
    }

    /**
     * Gọi Azure Speech REST API để tổng hợp giọng nói từ văn bản.
     *
     * @throws AzureTtsNotConfiguredException
     * @throws AzureTtsQuotaExceededException
     * @throws AzureTtsException
     */
    public function synthesizeSpeech(string $text, string $locale, string $voice): string
    {
        if (! $this->isConfigured()) {
            throw new AzureTtsNotConfiguredException('Dịch vụ Azure TTS chưa được cấu hình hoặc đang tắt.');
        }

        $cleanText = trim($text);
        $charCount = mb_strlen($cleanText);

        $maxChars = (int) config('services.azure_speech.max_term_characters', 250);
        if ($charCount === 0 || $charCount > $maxChars) {
            throw new AzureTtsException("Độ dài văn bản phải từ 1 đến {$maxChars} ký tự.");
        }

        if ($this->isMonthlyLimitReached()) {
            throw new AzureTtsQuotaExceededException('Đã đạt giới hạn hạn mức ký tự tháng của Azure TTS.');
        }

        $region = $this->getRegion();
        $endpoint = "https://{$region}.tts.speech.microsoft.com/cognitiveservices/v1";
        $key = (string) config('services.azure_speech.key');
        $ssml = $this->buildSsml($cleanText, $locale, $voice);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $key,
                    'Content-Type' => 'application/ssml+xml',
                    'X-Microsoft-OutputFormat' => 'audio-16khz-128kbitrate-mono-mp3',
                    'User-Agent' => 'PolyVocab-TTS',
                ])
                ->withBody($ssml, 'application/ssml+xml')
                ->post($endpoint);

            if ($response->successful()) {
                $audioBytes = $response->body();
                if (empty($audioBytes)) {
                    throw new AzureTtsException('Azure Speech trả về nội dung âm thanh rỗng.');
                }

                return $audioBytes;
            }

            $status = $response->status();
            $bodyPreview = Str::limit($response->body(), 200);

            Log::error('Azure Speech TTS Error', [
                'status' => $status,
                'region' => $region,
                'locale' => $locale,
                'voice' => $voice,
                'response' => $bodyPreview,
            ]);

            if ($status === 401 || $status === 403) {
                throw new AzureTtsException('Lỗi xác thực hoặc vượt hạn mức gói Azure.');
            }

            if ($status === 429) {
                throw new AzureTtsException('Hệ thống đang bị giới hạn tốc độ yêu cầu (rate limit). Vui lòng thử lại sau giây lát.');
            }

            throw new AzureTtsException("Lỗi máy chủ phát âm (Mã lỗi: {$status}).");
        } catch (ConnectionException $e) {
            Log::error('Azure Speech Connection Timeout', ['region' => $region]);
            throw new AzureTtsException('Kết nối đến dịch vụ phát âm bị quá thời gian chờ (timeout).');
        }
    }

    /**
     * Tạo hoặc tái sử dụng audio cho một mục từ vựng.
     *
     * @return array{success: bool, status: string, message: string, audio_path?: string, url?: string}
     */
    public function generateForEntry(VocabularyEntry $entry, ?User $user = null, bool $force = false): array
    {
        $term = trim($entry->term);
        if ($term === '') {
            return [
                'success' => false,
                'status' => 'empty_term',
                'message' => 'Từ vựng không được để trống.',
            ];
        }

        // Bảo toàn audio tải lên thủ công nếu chưa được xác nhận thay thế rõ ràng
        if ($entry->isManualAudio() && ! $force) {
            return [
                'success' => false,
                'status' => 'manual_audio_preserved',
                'message' => 'Mục từ đang có audio tải lên thủ công. Hãy xác nhận nếu bạn muốn thay thế bằng TTS.',
            ];
        }

        // Xác định ngôn ngữ và giọng
        $deck = $entry->deck;
        $learningLang = $deck?->learningLanguage;
        $code = strtolower((string) ($learningLang?->code ?? 'en'));

        $locale = $learningLang?->tts_locale;
        $voice = $learningLang?->tts_voice;

        if (empty($locale) || empty($voice)) {
            $default = self::DEFAULT_VOICES[$code] ?? self::DEFAULT_VOICES['en'];
            $locale = $locale ?: $default['locale'];
            $voice = $voice ?: $default['voice'];
        }

        // Tính hash nhận diện dựa trên: từ gốc + locale + voice
        $audioHash = hash('sha256', $term . '|' . $locale . '|' . $voice);

        // Kiểm tra tái sử dụng tệp nếu nội dung và giọng không đổi và file vẫn còn tồn tại
        if (! $force && $entry->audio_hash === $audioHash && ! empty($entry->audio_path) && Storage::disk('local')->exists($entry->audio_path)) {
            return [
                'success' => true,
                'status' => 'reused',
                'message' => 'Đã có sẵn âm thanh phát âm.',
                'audio_path' => $entry->audio_path,
                'url' => route('audio.entries.play', ['entry' => $entry->id]),
            ];
        }

        // Chống nhiều tiến trình tạo đồng thời cho cùng 1 mục từ
        $lockKey = "tts:generate:entry:{$entry->id}";
        $lock = Cache::lock($lockKey, 30);

        if (! $lock->get()) {
            return [
                'success' => false,
                'status' => 'busy',
                'message' => 'Đang có tiến trình tạo âm thanh cho từ này. Vui lòng chờ giây lát.',
            ];
        }

        try {
            $audioBytes = $this->synthesizeSpeech($term, $locale, $voice);
            $charCount = mb_strlen($term);

            // Lưu tệp với tên ngẫu nhiên an toàn, không chứa đáp án trong đường dẫn
            $filename = 'audio/entries/' . Str::random(40) . '.mp3';
            Storage::disk('local')->put($filename, $audioBytes);

            // Ghi nhận lịch sử sử dụng ký tự
            TtsUsage::create([
                'user_id' => $user?->id,
                'vocabulary_entry_id' => $entry->id,
                'characters_used' => $charCount,
                'locale' => $locale,
                'voice' => $voice,
                'created_at' => Carbon::now(),
            ]);

            // Cập nhật thông tin audio vào VocabularyEntry
            // Lưu ý: Nếu tạo lại thành công mới cập nhật đường dẫn mới
            $oldPath = $entry->audio_path;

            $entry->update([
                'audio_path' => $filename,
                'audio_source' => 'azure_tts',
                'audio_voice' => $voice,
                'audio_hash' => $audioHash,
                'audio_char_count' => $charCount,
            ]);

            // Nếu có audio cũ khác file mới và không còn dùng nữa, có thể dọn dẹp an toàn
            if ($oldPath && $oldPath !== $filename && Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }

            return [
                'success' => true,
                'status' => 'created',
                'message' => 'Đã tạo âm thanh.',
                'audio_path' => $filename,
                'url' => route('audio.entries.play', ['entry' => $entry->id]),
            ];
        } catch (AzureTtsNotConfiguredException $e) {
            return [
                'success' => false,
                'status' => 'not_configured',
                'message' => 'Dịch vụ Azure TTS chưa được kích hoạt hoặc chưa cấu hình API key.',
            ];
        } catch (AzureTtsQuotaExceededException $e) {
            return [
                'success' => false,
                'status' => 'quota_exceeded',
                'message' => 'Đã đạt giới hạn hạn mức ký tự Azure TTS hàng tháng. Vui lòng liên hệ quản trị viên.',
            ];
        } catch (AzureTtsException $e) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Chưa tạo được âm thanh. Hãy thử lại sau.',
            ];
        } catch (\Throwable $e) {
            Log::error('Unexpected TTS Error', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Chưa tạo được âm thanh. Hãy thử lại sau.',
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * Lấy danh sách giọng đọc có sẵn từ Azure Speech.
     * Có cache 24h để tránh request liên tục đến Azure.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchAvailableVoices(?string $locale = null): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $region = $this->getRegion();
        $cacheKey = "azure_voices:{$region}";

        $voices = Cache::remember($cacheKey, 86400, function () use ($region) {
            $endpoint = "https://{$region}.tts.speech.microsoft.com/cognitiveservices/voices/list";
            $key = (string) config('services.azure_speech.key');

            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Ocp-Apim-Subscription-Key' => $key,
                    ])
                    ->get($endpoint);

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Throwable $e) {
                Log::warning('Could not fetch Azure voices list: ' . $e->getMessage());
            }

            return [];
        });

        if (! is_array($voices)) {
            return [];
        }

        if ($locale) {
            $localeLower = strtolower($locale);
            return array_values(array_filter($voices, function ($item) use ($localeLower) {
                return strtolower($item['Locale'] ?? '') === $localeLower;
            }));
        }

        return $voices;
    }
}

