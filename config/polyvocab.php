<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Giới hạn cấu trúc mục từ vựng (Vocabulary Entries)
    |--------------------------------------------------------------------------
    |
    | Các cấu hình giới hạn số lượng nghĩa và ví dụ cho mỗi mục từ nhằm bảo
    | đảm hiệu năng hệ thống và trải nghiệm người dùng tối ưu.
    |
    */

    'max_meanings_per_entry' => 20,
    'max_examples_per_meaning' => 10,

    /*
    |--------------------------------------------------------------------------
    | Cấu hình Học tập & Lặp lại ngắt quãng (Study & SRS)
    |--------------------------------------------------------------------------
    |
    | Các thông số cấu hình chế độ học từ mới, ôn tập và kiểm tra.
    | Không sử dụng số cứng (hardcoded) trong code nghiệp vụ.
    |
    */

    'study' => [
        // Số nghĩa học mới mặc định và các mốc chọn nhanh
        'default_new_meanings_count' => 10,
        'batch_presets' => [5, 10, 20],
        'min_batch_size' => 1,
        'max_batch_size' => 50,

        // Kích thước nhóm nhỏ (chunking) để làm quen trước khi luyện nhớ
        'chunk_size' => 5,

        // Cấu hình Spaced Repetition (SRS)
        'default_ease_factor' => 2.50,
        'min_ease_factor' => 1.30,
        'initial_interval_days' => 1,
        'self_known_initial_interval_days' => 2, // Tự khai báo "Tôi đã biết" chỉ cấp lịch ngắn thử thách, không thành thạo ngay

        // Chính sách ôn tập (Review Schedule Policy v1)
        'review_policy' => [
            'version' => 'v1',
            // Các bước giãn cách mặc định theo ngày
            'interval_steps' => [1, 3, 7, 14, 30],
            // Khi trả lời sai, bỏ qua hoặc dùng gợi ý: đưa về bước đầu và ôn lại sau 10 phút
            'lapse_interval_minutes' => 10,
            // Tự đánh giá "Nhớ": giữ bước hiện tại, ôn lại sau 1 ngày
            'self_remember_interval_days' => 1,
            // Tối đa số lượt thử lại câu sai trong cùng phiên trước khi đánh dấu "Cần luyện thêm"
            'max_session_retries' => 3,
        ],

        // Cấu hình Luyện tập (Practice)
        'practice' => [
            'default_count' => 10,
            'max_count' => 50,
            'count_presets' => [5, 10, 20],
        ],

        // Cấu hình Kiểm tra & Đánh giá (Assessment / Quiz)
        'quiz' => [
            'default_count' => 10,
            'max_count' => 50,
            'default_time_limit_minutes' => 15,
            'max_time_limit_minutes' => 120,
            'count_presets' => [5, 10, 20, 30],
        ],

        // Cấu hình AI Đặt câu cho dạng bài Điền từ
        'ai_cloze' => [
            'choice_ratio' => (float) env('AI_CLOZE_CHOICE_RATIO', 0.5), // Tỷ lệ dạng trắc nghiệm 4 đáp án (0.5 = 50%)
        ],

        // 8 dạng bài học & luyện nhớ theo yêu cầu hệ thống
        'exercise_types' => [
            'flashcard' => [
                'id' => 'flashcard',
                'name' => 'Thẻ ghi nhớ (Flashcard)',
                'description' => 'Xem từ hoặc nghĩa, tự gợi nhớ rồi lật mặt sau đối chiếu (Tự đánh giá).',
                'example' => 'Mặt trước: "Hello" -> Mặt sau: "Xin chào"',
                'requires_examples' => false,
                'requires_audio' => false,
                'min_deck_meanings' => 1,
            ],
            'multiple_choice_term_to_meaning' => [
                'id' => 'multiple_choice_term_to_meaning',
                'name' => 'Trắc nghiệm: Chọn nghĩa đúng',
                'description' => 'Đọc từ vựng và chọn 1 trong các phương án nghĩa chính xác.',
                'example' => '"Apple" -> A. Quả táo, B. Quả cam, C. Quả lê...',
                'requires_examples' => false,
                'requires_audio' => false,
                'min_deck_meanings' => 2,
            ],
            'multiple_choice_meaning_to_term' => [
                'id' => 'multiple_choice_meaning_to_term',
                'name' => 'Trắc nghiệm: Chọn từ đúng',
                'description' => 'Đọc giải nghĩa và chọn từ vựng tương ứng phù hợp.',
                'example' => '"Quả táo" -> A. Apple, B. Orange, C. Banana...',
                'requires_examples' => false,
                'requires_audio' => false,
                'min_deck_meanings' => 2,
            ],
            'matching_pairs' => [
                'id' => 'matching_pairs',
                'name' => 'Ghép cặp Từ - Nghĩa',
                'description' => 'Nối từng từ với nghĩa tương ứng. Hai cột độc lập, đúng sẽ khóa cặp.',
                'example' => 'Nối "Apple" với "Quả táo", "Book" với "Quyển sách"...',
                'requires_examples' => false,
                'requires_audio' => false,
                'min_deck_meanings' => 2,
            ],
            'listen_and_choose' => [
                'id' => 'listen_and_choose',
                'name' => 'Nghe và chọn',
                'description' => 'Nghe phát âm từ vựng và chọn từ hoặc nghĩa chính xác (không lộ chữ trước khi chọn).',
                'example' => 'Nghe [Audio] -> Chọn: A. Apple, B. Apply...',
                'requires_examples' => false,
                'requires_audio' => true,
                'min_deck_meanings' => 2,
            ],
            'listen_and_type' => [
                'id' => 'listen_and_type',
                'name' => 'Nghe và viết (Chính tả)',
                'description' => 'Nghe phát âm âm thanh và gõ lại đúng chính tả (hỗ trợ IME đa ngôn ngữ).',
                'example' => 'Nghe [Audio] -> Nhập: "Hello"',
                'requires_examples' => false,
                'requires_audio' => true,
                'min_deck_meanings' => 1,
            ],
            'fill_in_the_blank' => [
                'id' => 'fill_in_the_blank',
                'name' => 'Điền từ vào chỗ trống',
                'description' => 'Đọc câu ví dụ có chỗ trống [ ______ ] và nhập từ vựng phù hợp ngữ cảnh.',
                'example' => '"I eat an [ ______ ] every day." -> Apple',
                'requires_examples' => true,
                'requires_audio' => false,
                'min_deck_meanings' => 1,
            ],
            'word_unscramble' => [
                'id' => 'word_unscramble',
                'name' => 'Sắp xếp chữ cái / mảnh ghép',
                'description' => 'Nhìn nghĩa và ghép các mảnh ký tự xáo trộn thành từ vựng hoàn chỉnh.',
                'example' => 'Nghĩa: "Quả táo" -> Ghép [a][p][p][l][e] -> Apple',
                'requires_examples' => false,
                'requires_audio' => false,
                'min_deck_meanings' => 1,
            ],
        ],
    ],
];
