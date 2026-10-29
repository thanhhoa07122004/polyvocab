# Yêu cầu hệ thống & Nghiệp vụ (Requirements)

## 1. Yêu cầu chức năng (Functional Requirements)

### 1.1 Quản lý người dùng & xác thực (Đã hoàn thành)
- Đăng ký, đăng nhập, phân quyền `member` và `admin`.
- Gate `access-admin` bảo vệ khu vực quản trị.
- Bảo mật dữ liệu người dùng: người dùng chỉ quản lý bộ từ của chính mình.

### 1.2 Quản lý ngôn ngữ học (Đã hoàn thành)
- Danh mục ngôn ngữ học lưu trong bảng `languages`, tách biệt hoàn toàn khỏi ngôn ngữ giao diện (locale).
- Hỗ trợ mã ngôn ngữ chuẩn BCP 47 (`vi`, `en`, `ja`, `ko`, `zh-Hans`, `zh-Hant`, `pt-BR`, `ar`...) không giới hạn 2 ký tự.
- Quản trị viên có quyền xem, tìm kiếm, phân trang, thêm mới, sửa tên/thứ tự hiển thị và bật/tắt ngôn ngữ. Mã `code` là bất biến sau khi tạo. Không hỗ trợ xóa ngôn ngữ để bảo vệ tính toàn vẹn khóa ngoại.
- Tự động làm mới cache danh mục ngôn ngữ hoạt động khi có thay đổi.
- Hỗ trợ hướng chữ `direction` (`ltr` hoặc `rtl`), áp dụng độc lập theo ngôn ngữ nội dung học.

### 1.3 Quản lý bộ từ vựng (Vocabulary Decks) (Đã hoàn thành)
- Thành viên tạo, xem, sửa, xóa các bộ từ (`decks`) của chính mình.
- Thiết lập ngôn ngữ đang học (`learning_language_id`) và ngôn ngữ giải nghĩa (`meaning_language_id`), cho phép trùng nhau để hỗ trợ học đơn ngữ.
- Chế độ hiển thị: `private` (mặc định) và `public`.
- Bộ từ `private` chỉ chủ sở hữu được xem; người khác (kể cả khách, thành viên khác hoặc admin) khi truy cập nhận HTTP 404 để không tiết lộ sự tồn tại.
- Trang "Khám phá" cho phép khách và thành viên tìm kiếm, lọc bộ từ công khai; tìm kiếm không bao giờ để lộ bộ từ private.
- Ngôn ngữ bị tắt không cho phép dùng cho lựa chọn mới, nhưng bộ từ đang sử dụng ngôn ngữ đó vẫn xem và sửa thông tin khác được.

### 1.4 Quản lý từ vựng trong bộ từ (Vocabulary Entries) (Đã hoàn thành)
- Chỉ chủ sở hữu bộ từ mới có quyền thêm, sửa, xóa mục từ vựng trong bộ từ đó.
- Cấu trúc dữ liệu 3 tầng: `vocabulary_entries` -> `entry_meanings` (tối thiểu 1 nghĩa, tối đa 20 nghĩa) -> `entry_examples` (tùy chọn, tối đa 10 ví dụ mỗi nghĩa).
- Các trường: từ gốc (`term`), phiên âm tùy chọn (`pronunciation`), ghi chú chung của mục từ trong bộ (`note`), từ loại (`part_of_speech`), ý nghĩa (`meaning`), câu ví dụ (`sentence`), bản dịch câu ví dụ (`translation`).
- Cho phép nhiều mục từ có cùng `term`. Lưu trữ toàn văn `utf8mb4`.
- Phân tách ngôn ngữ nội dung: `term`, `pronunciation`, `sentence` theo ngôn ngữ học của bộ; `meaning`, `translation` theo ngôn ngữ giải nghĩa của bộ. Áp dụng thuộc tính `lang` và `dir` cho từng vùng hiển thị.
- Tìm kiếm từ vựng theo từ gốc và nội dung ý nghĩa; nhóm điều kiện nghiêm ngặt theo `deck_id` hiện tại, phân trang giữ query string.
- Action ghi dữ liệu nhiều bảng trong Database Transaction (`DB::transaction`), bảo đảm tính toàn vẹn khi có lỗi.
- Khi chỉnh sửa: giữ nguyên ID các nghĩa và ví dụ còn tồn tại, chỉ xóa dòng bị loại bỏ. Xác minh nghiêm ngặt quan hệ sở hữu của các ID lồng nhau.
- Ngăn chặn truy cập sai lệch giữa bộ từ và mục từ trên URL (HTTP 404).

### 1.5 Nhập & Xuất từ vựng *(Giai đoạn tiếp theo)*
- Nhập/Xuất từ vựng hàng loạt từ file CSV/Excel/JSON.

### 1.6 Phát âm & Audio *(Giai đoạn sau)*
- Khi triển khai audio/TTS, không mặc định mã ngôn ngữ học là locale/voice của nhà cung cấp.
- Cần xây dựng lớp ánh xạ cấu hình riêng biệt (mapping layer) giữa mã ngôn ngữ BCP 47 và giọng đọc/dịch vụ TTS.
- Xử lý graceful degradation khi nhà cung cấp chưa hỗ trợ phát âm cho ngôn ngữ tương ứng.

### 1.7 Cơ chế học & Luyện tập (Study & Spaced Repetition) *(Giai đoạn sau)*
- Luyện tập qua Flashcard lật mặt trước/sau.
- Ghi nhận mức độ ghi nhớ (Lại, Khó, Tốt, Dễ) sau mỗi lượt ôn.
- Tự động lên lịch ôn tập cho các ngày tiếp theo dựa trên thuật toán Spaced Repetition (SRS).

### 1.8 Thống kê & Báo cáo (Analytics & Progress) *(Giai đoạn sau)*
- Đếm số từ cần ôn hôm nay (Due count).
- Thống kê tỷ lệ nhớ từ theo từng cấp độ ghi nhớ.
- Theo dõi streak học liên tục hàng ngày.

## 2. Yêu cầu phi chức năng (Non-Functional Requirements)
- **Hiệu năng & Tốc độ:** Tải trang nhanh, eager loading tránh N+1 (`meanings.examples`), phân trang giữ query filter, cache danh mục ngôn ngữ.
- **Bảo mật:** Kiểm tra quyền qua Policy/Gate trên từng route và action, server-side validation, chống CSRF, ẩn sự tồn tại của dữ liệu private (HTTP 404), chống giả mạo `user_id`/`deck_id`/nested ID, escape HTML chống XSS.
- **Tính khả dụng & Responsive:** Thiết kế tối ưu cho cả điện thoại (mobile) và máy tính (desktop).
- **Trải nghiệm người dùng:** Có trạng thái rỗng (empty state), trạng thái lỗi, thông báo thành công, ngăn gửi form lặp và xác nhận khi xóa.

## 3. Điều kiện hoàn thành (Definition of Done)
- Chức năng có validation đầy đủ cả client lẫn server.
- Không phát sinh lỗi console hoặc runtime exception.
- Có migration chuẩn hóa và dữ liệu mẫu (Seeder) có tính idempotent phục vụ kiểm thử.
- Đầy đủ Feature Tests bao phủ các trường hợp phân quyền và nghiệp vụ.
