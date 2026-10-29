# Thiết kế Cơ sở dữ liệu (Database Design)

Cơ sở dữ liệu sử dụng bảng mã `utf8mb4` và collation `utf8mb4_unicode_ci` nhằm hỗ trợ đầy đủ các bộ ký tự đa ngôn ngữ trên thế giới (Tiếng Việt, Hán tự, Kana, Hangul, chữ Ả Rập, Emoji,...).

## 1. Danh sách các bảng chi tiết

### `users`
Bảng người dùng hệ thống:
- `id` (bigint, PK)
- `name` (varchar 255)
- `email` (varchar 255, unique)
- `password` (varchar 255)
- `role` (varchar 20, default: 'member' - Enum: `member`, `admin`)
- `remember_token` (varchar 100, nullable)
- `created_at`, `updated_at` (timestamps)

### `languages`
Danh mục ngôn ngữ hỗ trợ học tập (được cấu hình động, tách biệt khỏi locale giao diện):
- `id` (bigint, PK)
- `code` (varchar 30, unique) - Mã ngôn ngữ theo chuẩn BCP 47 (ví dụ: `vi`, `en`, `ja`, `ko`, `zh-Hans`, `zh-Hant`, `pt-BR`, `ar`). Không đổi sau khi tạo.
- `name` (varchar 100) - Tên hiển thị bằng tiếng Việt (ví dụ: `Tiếng Việt`, `Tiếng Nhật`).
- `native_name` (varchar 100) - Tên bản địa (ví dụ: `Tiếng Việt`, `日本語`).
- `direction` (varchar 10, default: 'ltr') - Hướng chữ của nội dung (`ltr` hoặc `rtl`), độc lập với hướng giao diện.
- `is_active` (boolean, default: true) - Trạng thái cho phép tạo bộ từ mới.
- `sort_order` (int, default: 0) - Thứ tự ưu tiên hiển thị.
- `created_at`, `updated_at` (timestamps)
- **Indexes:** `unique(code)`, `index(is_active, sort_order)`.

### `decks`
Bộ từ vựng do người dùng tạo:
- `id` (bigint, PK)
- `user_id` (bigint, FK -> `users.id`, onDelete: cascade) - Chủ sở hữu bộ từ, luôn được xác định phía server.
- `name` (varchar 150) - Tên bộ từ vựng (tối đa 150 ký tự).
- `description` (text, nullable) - Mô tả bộ từ (tối đa 2.000 ký tự).
- `learning_language_id` (bigint, FK -> `languages.id`, onDelete: restrict) - Ngôn ngữ đang học.
- `meaning_language_id` (bigint, FK -> `languages.id`, onDelete: restrict) - Ngôn ngữ giải nghĩa (có thể trùng `learning_language_id` để học đơn ngữ).
- `visibility` (varchar 20, default: 'private' - Enum: `private`, `public`) - Trạng thái hiển thị.
- `created_at`, `updated_at` (timestamps)
- **Indexes:** `index(user_id, created_at)`, `index(visibility, created_at)`, `index(learning_language_id)`, `index(meaning_language_id)`.

### `vocabulary_entries`
Mục từ vựng thuộc bộ từ (một từ có thể có nhiều mục từ nếu khác cách dùng):
- `id` (bigint, PK)
- `deck_id` (bigint, FK -> `decks.id`, onDelete: cascade)
- `term` (varchar 255) - Từ hoặc cụm từ cần học.
- `pronunciation` (varchar 255, nullable) - Phiên âm IPA, Pinyin, Romaji... (tùy chọn).
- `note` (text, nullable) - Ghi chú chung của mục từ trong bộ từ (tối đa 2.000 ký tự).
- `created_at`, `updated_at` (timestamps)
- **Indexes:** `index(deck_id, created_at)`, `index(term)`.

### `entry_meanings`
Danh sách các ý nghĩa của mục từ (mỗi mục từ có ít nhất 1 nghĩa, tối đa 20 nghĩa):
- `id` (bigint, PK)
- `vocabulary_entry_id` (bigint, FK -> `vocabulary_entries.id`, onDelete: cascade)
- `part_of_speech` (varchar 100, nullable) - Từ loại (ví dụ: Danh từ, Động từ, n, v, adj...).
- `meaning` (text, nullable) - Nội dung giải nghĩa tùy chọn (tối đa 2.000 ký tự).
- `sort_order` (int, default: 0) - Thứ tự hiển thị của nghĩa.
- `created_at`, `updated_at` (timestamps)
- **Indexes:** `index(vocabulary_entry_id, sort_order)`.

### `entry_examples`
Danh sách câu ví dụ cho từng nghĩa (mỗi nghĩa tối đa 10 ví dụ):
- `id` (bigint, PK)
- `entry_meaning_id` (bigint, FK -> `entry_meanings.id`, onDelete: cascade)
- `sentence` (text) - Câu ví dụ bằng ngôn ngữ học (tối đa 2.000 ký tự).
- `translation` (text, nullable) - Bản dịch câu ví dụ bằng ngôn ngữ giải nghĩa (tối đa 2.000 ký tự).
- `sort_order` (int, default: 0) - Thứ tự hiển thị ví dụ.
- `created_at`, `updated_at` (timestamps)
- **Indexes:** `index(entry_meaning_id, sort_order)`.

### `study_records`
Lịch sử và trạng thái thuật toán Spaced Repetition (SRS) cho từng nghĩa từ vựng (đơn vị tiến độ là một nghĩa):
- `id` (bigint, PK)
- `user_id` (bigint, FK -> `users.id`, onDelete: cascade)
- `deck_id` (bigint, FK -> `decks.id`, onDelete: cascade)
- `entry_meaning_id` (bigint, FK -> `entry_meanings.id`, onDelete: cascade)
- `repetition_level` (int, default 0) - số lần củng cố thành công
- `ease_factor` (decimal: 5, 2, default: 2.50)
- `interval_days` (int, default: 0)
- `due_at` (timestamp, nullable) - thời điểm đến hạn ôn tập
- `review_count` (int, default 0)
- `lapse_count` (int, default 0)
- `is_self_declared_known` (boolean, default: false) - tự khai báo "Tôi đã biết nghĩa này"
- `is_difficult` (boolean, default: false) - đánh dấu từ khó
- `last_studied_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- **Indexes:** `unique(user_id, entry_meaning_id)`, `index(user_id, due_at)`, `index(user_id, deck_id, due_at)`.

### `study_sessions`
Phiên học tập (học từ mới, ôn tập, kiểm tra) của người dùng:
- `id` (bigint, PK)
- `user_id` (bigint, FK -> `users.id`, onDelete: cascade)
- `deck_id` (bigint, FK -> `decks.id`, onDelete: cascade)
- `mode` (varchar 30, default: 'learn_new') - Enum: `learn_new`, `review`, `custom_practice`, `difficult_practice`, `quiz`
- `stage` (varchar 30, default: 'familiarization') - Enum: `familiarization`, `retention_practice`
- `status` (varchar 30, default: 'in_progress') - Enum: `in_progress`, `paused`, `completed`, `abandoned`
- `chunk_size` (int, default: 5) - kích thước nhóm làm quen trước khi luyện nhớ
- `current_chunk` (int, default: 0) - nhóm hiện tại
- `settings` (json, nullable) - lưu các tùy chọn bài học
- `last_interacted_at` (timestamp, nullable)
- `created_at`, `updated_at` (timestamps)
- **Indexes:** `index(user_id, status)`, `index(user_id, deck_id, status)`.

### `study_session_items`
Chi tiết các mục nghĩa trong một phiên học:
- `id` (bigint, PK)
- `study_session_id` (bigint, FK -> `study_sessions.id`, onDelete: cascade)
- `entry_meaning_id` (bigint, FK -> `entry_meanings.id`, onDelete: cascade)
- `sort_order` (int, default: 0)
- `chunk_index` (int, default: 0) - thuộc nhóm 0, 1, 2...
- `stage` (varchar 30, default: 'familiarization')
- `familiarized_at` (timestamp, nullable) - thời điểm hoàn thành làm quen
- `practiced_at` (timestamp, nullable) - thời điểm hoàn thành luyện nhớ
- `is_difficult` (boolean, default: false)
- `is_self_declared_known` (boolean, default: false)
- `practice_result` (varchar 20, nullable) - `pass`, `fail`, `skip`
- `created_at`, `updated_at` (timestamps)
- **Indexes:** `index(study_session_id, chunk_index, sort_order)`, `index(study_session_id, entry_meaning_id)`.

## 2. Quan hệ giữa các bảng (Relationships)
- `User` hasMany `Deck`
- `User` hasMany `StudyRecord`
- `User` hasMany `StudySession`
- `Deck` belongsTo `User`
- `Deck` belongsTo `Language` (as `learningLanguage`)
- `Deck` belongsTo `Language` (as `meaningLanguage`)
- `Language` hasMany `Deck` (as `learningDecks`, `meaningDecks`)
- `Deck` hasMany `VocabularyEntry` (as `entries`)
- `Deck` hasMany `StudyRecord`
- `Deck` hasMany `StudySession`
- `VocabularyEntry` belongsTo `Deck`
- `VocabularyEntry` hasMany `EntryMeaning` (as `meanings`)
- `EntryMeaning` belongsTo `VocabularyEntry` (as `vocabularyEntry`)
- `EntryMeaning` hasMany `EntryExample` (as `examples`)
- `EntryMeaning` hasMany `StudyRecord`
- `EntryMeaning` hasMany `StudySessionItem`
- `EntryExample` belongsTo `EntryMeaning` (as `entryMeaning`)
- `StudySession` belongsTo `User`
- `StudySession` belongsTo `Deck`
- `StudySession` hasMany `StudySessionItem` (as `items`)
- `StudySessionItem` belongsTo `StudySession` (as `session`)
- `StudySessionItem` belongsTo `EntryMeaning` (as `entryMeaning`)
