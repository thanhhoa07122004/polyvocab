# Lộ trình phát triển PolyVocab (Roadmap)

## Giai đoạn 1: Nền tảng & Khởi tạo (Hoàn thành)
- [x] Khởi tạo dự án Laravel 12, PHP 8.2, Livewire 4, Tailwind CSS v4.
- [x] Tạo layout chính và trang chủ ban đầu.
- [x] Thiết lập cấu trúc thư mục phát triển lâu dài (Actions, Services, docs).
- [x] Xây dựng bộ tài liệu dự án và quy tắc làm việc cho AI Agent (`AGENTS.md`).
- [x] Triển khai hệ thống xác thực (Auth), phân quyền `member`/`admin`, Gate `access-admin`.

## Giai đoạn 2: Quản lý Ngôn ngữ, Bộ từ & Từ vựng (Đã hoàn thành)
- [x] Thiết kế migration bảng `languages` (chuẩn BCP 47, hướng chữ `direction` LTR/RTL, cache tự động làm mới).
- [x] Thiết kế migration bảng `decks` (chế độ `visibility` Private/Public, foreign key ràng buộc ngôn ngữ, index tối ưu).
- [x] CRUD Quản lý ngôn ngữ dành riêng cho Quản trị viên (LanguagePolicy, bất biến mã code, toggle bật/tắt, không xóa).
- [x] Seeder `LanguageSeeder` hỗ trợ tái thực thi an toàn (idempotent), không ghi đè dữ liệu quản trị viên đã chỉnh sửa.
- [x] Quản lý bộ từ cá nhân ("Bộ từ của tôi") cho thành viên: tìm kiếm, lọc ngôn ngữ/trạng thái, phân trang, tạo/sửa/xóa an toàn, xác nhận trước khi xóa.
- [x] Trang "Khám phá" bộ từ công khai cho khách và thành viên, bảo mật nghiêm ngặt không làm rò rỉ bộ từ riêng tư (HTTP 404).
- [x] Thiết kế migration cho các bảng từ vựng: `vocabulary_entries`, `entry_meanings`, `entry_examples` với ràng buộc cascade delete.
- [x] Action `SaveVocabularyEntryAction` xử lý lưu/sửa mục từ trong DB transaction, giữ nguyên ID, xác minh ID lồng nhau tránh giả mạo.
- [x] Phân quyền `VocabularyEntryPolicy` kế thừa quy tắc bảo mật của Deck, chống truy cập chéo ID trên URL (HTTP 404).
- [x] Giao diện quản lý từ vựng trong bộ từ: danh sách mục từ kèm tìm kiếm/phân trang, trang chi tiết mục từ, form thêm/sửa động nhiều nghĩa và ví dụ.
- [ ] Tính năng import/export từ file CSV/Excel/JSON.

## Giai đoạn 3: Hệ thống Học tập, SRS, Phiên học & Đánh giá (Hoàn thành)
- [x] Thiết kế cấu trúc bảng `study_records`, `study_sessions`, `study_session_items`.
- [x] Thiết lập nguyên tắc cốt lõi: Đơn vị tiến độ là một nghĩa (`EntryMeaning`).
- [x] Trang tổng quan học tập (Dashboard): 3 chế độ "Học từ mới", "Ôn tập" (kèm số nghĩa đến hạn thực tế), "Kiểm tra", card phiên dở dang "Tiếp tục".
- [x] Xử lý đầy đủ các trạng thái rỗng và hướng dẫn (chưa có bộ từ, có bộ chưa có từ, chưa có lịch ôn, không có từ đến hạn kèm thời điểm ôn gần nhất).
- [x] Màn hình chuẩn bị học từ mới (`prepare_new`): chọn số lượng nhanh (5, 10, 20), danh sách 8 dạng bài học với đánh giá điều kiện khả dụng và lý do vô hiệu hóa.
- [x] Phương pháp chia nhóm nhỏ (Chunking mặc định 5 nghĩa) để làm quen trước khi luyện nhớ.
- [x] Trình chạy phiên học (Study Session Runner): phân cấp thị giác nổi bật từ vựng, nghĩa, ví dụ, xử lý Web Speech API phát âm, "Đánh dấu khó", và "Tôi đã biết nghĩa này" (tự khai báo, thử thách ngắn, không tự đánh dấu thành thạo).
- [x] Giao diện 8 dạng bài học & Tương tác: Flashcard (lật mặt trước/sau không phụ thuộc animation), Trắc nghiệm từ/nghĩa, Ghép cặp (2 cột trên PC, chạm chọn), Nghe & chọn, Nghe & viết (tắt auto-correct, hỗ trợ IME), Điền khuyết, Sắp xếp chữ, phím tắt chống xung đột IME/input.
- [x] Quy tắc phản hồi học và ôn (Đúng/Sai/Bỏ qua/Gợi ý/Tự đánh giá, điều hướng chủ động, re-queue giới hạn tối đa 2 lần trước khi báo "Cần luyện thêm").
- [x] Trang chuẩn bị ôn tập SRS: lọc bộ từ/ngôn ngữ, số lượng đến hạn, quá hạn là tập con của đến hạn, chế độ "Luyện thêm ngoài lịch".
- [x] Chính sách ôn tập Spaced Repetition (Review Policy v1): các bước giãn cách 1-3-7-14-30 ngày, finalize 1 lần mỗi phiên, bảo toàn kết quả lần đầu.
- [x] Hệ thống Kiểm tra & Đánh giá (Assessment / Quiz): 2 chế độ (Luyện kiểm tra & Bài kiểm tra chính thức), bảo mật không lộ đáp án trong đề thi, auto-save, đếm ngược thời gian, question palette, chấm điểm khách quan.
- [x] Màn hình kết quả chi tiết theo dạng bài & Lịch sử cá nhân (`/study/history`).
- [x] Điểm vào Trung tâm học tập từ trang bộ từ (`decks.show`): hiển thị 4 chỉ số thực tế, thẻ phiên dở dang, 4 nút chế độ học tập (Học từ mới, Ôn đến hạn, Luyện theo dạng bài, Luyện từ khó) kèm lý do khi bị vô hiệu hóa.
- [x] Màn hình chuẩn bị và luồng Luyện tập theo dạng bài & Luyện từ khó (`/study/practice` & `/decks/{id}/study/practice`) với cam kết không thay đổi lịch ôn SRS.
- [x] Tương tác nâng cao trong phiên: Đánh dấu khó tức thì, Yêu cầu gợi ý từ server, Ghép cặp kiểm tra thời gian thực, Xác thực nhanh "Tôi đã biết" qua câu hỏi kiểm chứng.
- [ ] Tính năng import/export từ file CSV/Excel/JSON.
- [ ] Xây dựng lớp ánh xạ cấu hình phát âm TTS đa ngôn ngữ nâng cao (mapping BCP 47 sang TTS Voice Provider, xử lý fallback).

## Giai đoạn 4: Thống kê & Gamification
- [ ] Dashboard theo dõi tiến độ: số từ cần ôn hôm nay (due count), streak ngày học liên tục.
- [ ] Biểu đồ thống kê lượng từ đã thuộc.

## Giai đoạn 5: Hoàn thiện & Triển khai
- [ ] Đa ngôn ngữ giao diện (Localization).
- [ ] Tối ưu hóa hiệu năng, kiểm thử tự động toàn diện.
- [ ] Chuẩn bị kịch bản triển khai production.
