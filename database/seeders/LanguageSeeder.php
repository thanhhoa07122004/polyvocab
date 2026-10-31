<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Dùng firstOrCreate để bảo đảm khi chạy lại không tạo trùng
     * và không ghi đè bất kỳ chỉnh sửa nào đã có từ trước của người quản trị.
     */
    public function run(): void
    {
        $languages = [
            [
                'code' => 'vi',
                'name' => 'Tiếng Việt',
                'native_name' => 'Tiếng Việt',
                'direction' => 'ltr',
                'tts_locale' => 'vi-VN',
                'tts_voice' => 'vi-VN-HoaiMyNeural',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'en',
                'name' => 'Tiếng Anh',
                'native_name' => 'English',
                'direction' => 'ltr',
                'tts_locale' => 'en-US',
                'tts_voice' => 'en-US-JennyNeural',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'zh',
                'name' => 'Tiếng Trung',
                'native_name' => '中文',
                'direction' => 'ltr',
                'tts_locale' => 'zh-CN',
                'tts_voice' => 'zh-CN-XiaoxiaoNeural',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'ja',
                'name' => 'Tiếng Nhật',
                'native_name' => '日本語',
                'direction' => 'ltr',
                'tts_locale' => 'ja-JP',
                'tts_voice' => 'ja-JP-NanamiNeural',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'ko',
                'name' => 'Tiếng Hàn',
                'native_name' => '한국어',
                'direction' => 'ltr',
                'tts_locale' => 'ko-KR',
                'tts_voice' => 'ko-KR-SunHiNeural',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($languages as $item) {
            $lang = Language::firstOrCreate(
                ['code' => $item['code']],
                $item
            );

            // Cập nhật tts_locale và tts_voice nếu chưa có
            if (empty($lang->tts_locale) || empty($lang->tts_voice)) {
                $lang->update([
                    'tts_locale' => $item['tts_locale'],
                    'tts_voice' => $item['tts_voice'],
                ]);
            }
        }

        Language::clearActiveCache();
    }
}

