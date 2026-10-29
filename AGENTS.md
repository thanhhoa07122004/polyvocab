# Quy tắc dự án PolyVocab

## Công nghệ
- Laravel 12, PHP 8.2, MariaDB.
- Blade, Tailwind CSS, Livewire 4.
- Kiểm tra composer.json và package.json để biết phiên bản thực tế.
- Không tự đổi framework hoặc nâng major version.

## Trước khi làm
- Đọc tài liệu liên quan trong docs/.
- Kiểm tra code và migration đang có.
- Tái sử dụng chức năng hiện có.
- Chỉ thực hiện trong phạm vi nhiệm vụ được yêu cầu.

## Tổ chức code
- Giữ cấu trúc chuẩn Laravel.
- Không đặt nghiệp vụ trong Blade.
- Controller và Livewire component xử lý giao tiếp.
- Nghiệp vụ nhiều bước đặt trong app/Actions.
- Thuật toán tái sử dụng đặt trong app/Services.
- Không tạo lớp trung gian không cần thiết.

## Database
- Thay đổi cấu trúc bằng migration.
- Không tạo hoặc sửa bảng thủ công thay cho migration.
- Không sửa migration đã dùng trên môi trường chung.
- Không chạy migrate:fresh hoặc xóa dữ liệu khi chưa được yêu cầu.
- Dùng transaction khi nhiều thao tác ghi cần thành công cùng nhau.

## Bảo mật
- Validation phía server.
- Kiểm tra quyền bằng Policy hoặc Gate.
- Kiểm tra quyền trong từng Livewire action.
- Không tin ID hoặc user_id do trình duyệt gửi lên.
- Không in nội dung .env hoặc đưa bí mật vào Git.

## Giao diện
- Giao diện mặc định tiếng Việt.
- Dùng tệp dịch khi triển khai đa ngôn ngữ.
- Phân biệt ngôn ngữ giao diện và ngôn ngữ đang học.
- Tái sử dụng Blade component.
- Có trạng thái loading, rỗng và lỗi.
- Hỗ trợ màn hình điện thoại.

## Kiểm tra và bàn giao
- Kiểm thử nghiệp vụ và quyền truy cập quan trọng.
- Dùng database riêng cho kiểm thử.
- Chạy build khi sửa giao diện.
- Cập nhật tài liệu khi thay đổi cách vận hành.
- Báo rõ đã kiểm tra gì, kết quả và phần còn thiếu.
