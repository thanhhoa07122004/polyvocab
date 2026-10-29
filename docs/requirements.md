# Yêu cầu hệ thống & Nghiệp vụ (Requirements)

## 1. Yêu cầu chức năng (Functional Requirements)

### 1.1 Quản lý người dùng & xác thực
- Đăng ký, đăng nhập, đặt lại mật khẩu, quản lý hồ sơ.
- Phân quyền và bảo mật dữ liệu: Người dùng chỉ có quyền xem và sửa các bộ từ/dữ liệu của chính mình.

### 1.2 Quản lý bộ từ vựng (Vocabulary Decks & Words)
- Tạo, sửa, xóa các bộ từ (Decks).
- Thiết lập ngôn ngữ nguồn và ngôn ngữ đích cho từng bộ từ.
- Thêm từ mới với các thông tin: từ gốc, nghĩa, loại từ, phát âm/phiên âm, câu ví dụ, ghi chú, audio.
- Tìm kiếm, lọc và phân loại từ vựng theo tags hoặc độ khó.
- Nhập/Xuất từ vựng hàng loạt (CSV/Excel/JSON).

### 1.3 Cơ chế học & Luyện tập (Study & Spaced Repetition)
- Luyện tập qua Flashcard lật mặt trước/sau.
- Ghi nhận mức độ ghi nhớ (Lại, Khó, Tốt, Dễ) sau mỗi lượt ôn.
- Tự động lên lịch ôn tập cho các ngày tiếp theo dựa trên thuật toán Spaced Repetition.
- Hỗ trợ phát âm audio mẫu của từ vựng.

### 1.4 Thống kê & Báo cáo (Analytics & Progress)
- Đếm số từ cần ôn hôm nay (Due count).
- Thống kê tỷ lệ nhớ từ theo từng cấp độ ghi nhớ.
- Theo dõi streak học liên tục hàng ngày.

## 2. Yêu cầu phi chức năng (Non-Functional Requirements)
- **Hiệu năng & Tốc độ:** Phản hồi nhanh với Livewire 4, tải trang mượt mà.
- **Bảo mật:** Kiểm tra quyền thông qua Policy/Gate, xác thực server-side, bảo vệ CSRF.
- **Tính khả dụng & Responsive:** Tối ưu hiển thị cho cả thiết bị di động (mobile-first), máy tính bảng và desktop.
- **Khả năng mở rộng:** Kiến trúc phân lớp rõ ràng (Actions, Services), dễ dàng tích hợp thêm thuật toán hoặc dịch vụ TTS (Text-to-Speech).

## 3. Điều kiện hoàn thành (Definition of Done)
- Chức năng có validation đầy đủ cả client lẫn server.
- Không phát sinh lỗi console hoặc runtime exception.
- Có migration chuẩn hóa và dữ liệu mẫu (Seeder) phục vụ kiểm thử.
