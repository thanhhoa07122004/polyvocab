# Thiết kế Cơ sở dữ liệu (Database Design)

## 1. Danh sách các bảng dự kiến

### `users`
Bảng người dùng mặc định của Laravel:
- `id` (bigint, PK)
- `name` (varchar)
- `email` (varchar, unique)
- `password` (varchar)
- `created_at`, `updated_at`

### `languages`
Danh mục ngôn ngữ hỗ trợ học tập:
- `id` (bigint, PK)
- `code` (varchar: 'en', 'ja', 'vi', 'ko', 'zh', ...)
- `name` (varchar: 'English', 'Japanese', 'Tiếng Việt', ...)
- `native_name` (varchar)

### `decks`
Bộ từ vựng do người dùng tạo:
- `id` (bigint, PK)
- `user_id` (bigint, FK -> users.id)
- `name` (varchar)
- `description` (text, nullable)
- `source_language_id` (bigint, FK -> languages.id)
- `target_language_id` (bigint, FK -> languages.id)
- `is_public` (boolean, default false)
- `created_at`, `updated_at`

### `vocabularies`
Danh sách từ vựng thuộc bộ từ:
- `id` (bigint, PK)
- `deck_id` (bigint, FK -> decks.id)
- `term` (varchar) - Từ gốc
- `meaning` (text) - Ý nghĩa
- `phonetic` (varchar, nullable) - Phiên âm IPA hoặc romaji/pinyin
- `part_of_speech` (varchar, nullable) - Loại từ
- `example_sentence` (text, nullable) - Câu ví dụ
- `example_translation` (text, nullable) - Dịch câu ví dụ
- `audio_url` (varchar, nullable) - Link file âm thanh phát âm
- `notes` (text, nullable) - Ghi chú thêm
- `created_at`, `updated_at`

### `study_records`
Lịch sử và trạng thái thuật toán Spaced Repetition cho từng từ:
- `id` (bigint, PK)
- `user_id` (bigint, FK -> users.id)
- `vocabulary_id` (bigint, FK -> vocabularies.id)
- `repetition_level` (int, default 0) - Cấp độ ghi nhớ
- `ease_factor` (decimal: 2.5 default) - Hệ số dễ
- `interval_days` (int, default 0) - Khoảng cách ngày ôn tiếp theo
- `due_date` (date) - Ngày cần ôn tiếp theo
- `review_count` (int, default 0) - Tổng số lượt đã ôn
- `lapse_count` (int, default 0) - Số lần quên (trả lời sai)
- `last_reviewed_at` (timestamp, nullable) - Thời gian ôn gần nhất
- `created_at`, `updated_at`

## 2. Quan hệ chính (Relationships)
- `User` hasMany `Deck`
- `Deck` belongsTo `User`
- `Deck` hasMany `Vocabulary`
- `Vocabulary` belongsTo `Deck`
- `Vocabulary` hasMany `StudyRecord`
- `User` hasMany `StudyRecord`
