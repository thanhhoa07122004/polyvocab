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
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'en',
                'name' => 'Tiếng Anh',
                'native_name' => 'English',
                'direction' => 'ltr',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'zh',
                'name' => 'Tiếng Trung',
                'native_name' => '中文',
                'direction' => 'ltr',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'ja',
                'name' => 'Tiếng Nhật',
                'native_name' => '日本語',
                'direction' => 'ltr',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'ko',
                'name' => 'Tiếng Hàn',
                'native_name' => '한국어',
                'direction' => 'ltr',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($languages as $item) {
            Language::firstOrCreate(
                ['code' => $item['code']],
                $item
            );
        }

        Language::clearActiveCache();
    }
}

