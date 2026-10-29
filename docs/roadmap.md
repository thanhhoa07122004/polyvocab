# Lộ trình phát triển PolyVocab (Roadmap)

## Giai đoạn 1: Nền tảng & Khởi tạo (Hoàn thành)
- [x] Khởi tạo dự án Laravel 12, PHP 8.2, Livewire 4, Tailwind CSS v4.
- [x] Tạo layout chính và trang chủ ban đầu.
- [x] Thiết lập cấu trúc thư mục phát triển lâu dài (Actions, Services, docs).
- [x] Xây dựng bộ tài liệu dự án và quy tắc làm việc cho AI Agent (`AGENTS.md`).

## Giai đoạn 2: Quản lý Bộ từ & Từ vựng
- [ ] Thiết kế và chạy migration cho bảng `languages`, `decks`, `vocabularies`.
- [ ] Xây dựng Livewire component CRUD cho Deck (Tạo, sửa, xóa danh sách bộ từ).
- [ ] Xây dựng giao diện nhập và quản lý từ vựng trong từng Deck.
- [ ] Tính năng import/export từ file CSV/Excel.

## Giai đoạn 3: Thuật toán Ôn tập & Flashcard
- [ ] Thiết kế cấu trúc bảng `study_records`.
- [ ] Cài đặt thuật toán lặp lại ngắt quãng (Spaced Repetition) trong `app/Services/Study/`.
- [ ] Xây dựng Livewire Flashcard Study Component với hiệu ứng lật thẻ và phím tắt.
- [ ] Tích hợp phát âm audio (Web Speech API hoặc TTS Service).

## Giai đoạn 4: Thống kê & Gamification
- [ ] Dashboard theo dõi tiến độ: số từ cần ôn hôm nay, streak ngày học liên tục.
- [ ] Biểu đồ thống kê lượng từ đã thuộc.

## Giai đoạn 5: Hoàn thiện & Triển khai
- [ ] Đa ngôn ngữ giao diện (Localization).
- [ ] Tối ưu hóa hiệu năng, kiểm thử tự động (Feature/Unit tests).
- [ ] Chuẩn bị kịch bản triển khai production.
