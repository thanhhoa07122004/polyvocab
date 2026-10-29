# Tài liệu Phân quyền & Bảo mật (Permissions & Authorization)

Hệ thống PolyVocab triển khai kiểm soát quyền nghiêm ngặt phía server thông qua Laravel Gate và Policy, không bao giờ tin tưởng dữ liệu do client gửi lên.

## 1. Vai trò người dùng (Roles)

Hệ thống quản lý vai trò bằng PHP Enum `App\Enums\UserRole`:
- **`member` (Thành viên / Người học):**
  - Vai trò mặc định khi đăng ký tài khoản.
  - Quản lý các bộ từ vựng và mục từ cá nhân của chính mình.
  - Xem và tìm kiếm các bộ từ công khai của cộng đồng.
  - Không thể tự nâng quyền lên `admin`.
- **`admin` (Quản trị viên hệ thống):**
  - Quản lý danh mục ngôn ngữ học (thêm mới, chỉnh sửa thông tin hiển thị, bật/tắt hoạt động).
  - Quản lý và theo dõi thông tin người dùng trong trang quản trị (`/admin`).
  - **Lưu ý quan trọng về quyền riêng tư:** Quản trị viên **không** tự động có quyền xem bộ từ riêng tư (`private`) hoặc chỉnh sửa/xóa bộ từ/mục từ của người khác.

## 2. Gate và Policy

### `access-admin` (Gate)
- Kiểm tra người dùng hiện tại có vai trò `UserRole::ADMIN` hay không.
- Áp dụng trên nhóm route `/admin/*` và các thành phần giao diện quản trị.

### `LanguagePolicy`
- **`viewAny`:** Chỉ quản trị viên (`admin`) được xem danh sách ngôn ngữ trong trang quản trị.
- **`create`:** Chỉ quản trị viên được thêm ngôn ngữ mới.
- **`update`:** Chỉ quản trị viên được cập nhật ngôn ngữ. Mã ngôn ngữ (`code`) là bất biến sau khi tạo.
- **`toggle`:** Chỉ quản trị viên được bật/tắt trạng thái hoạt động của ngôn ngữ.
- **`delete`:** Luôn trả về `false`. Không hỗ trợ xóa ngôn ngữ để bảo vệ tính toàn vẹn khóa ngoại của các bộ từ và từ vựng.

### `DeckPolicy`
- **`viewAny`:** Cho phép thành viên đã đăng nhập xem danh sách bộ từ của chính họ.
- **`view`:**
  - Bộ từ công khai (`public`): Cho phép khách (`guest`) và bất kỳ người dùng nào xem.
  - Bộ từ riêng tư (`private`): **Chỉ chủ sở hữu (`user_id === auth()->id()`) mới có quyền xem.**
  - **Bảo mật phản hồi:** Khách, thành viên khác hoặc quản trị viên khi truy cập trực tiếp URL của bộ từ `private` sẽ nhận mã phản hồi **HTTP 404 (Not Found)** thay vì 403 để hoàn toàn không làm lộ sự tồn tại của bộ từ.
- **`create`:** Cho phép tất cả thành viên đã xác thực tạo bộ từ mới. Thuộc tính `user_id` luôn được gán tự động từ `auth()->id()` phía server, không nhận từ form request.
- **`update`:** **Chỉ chủ sở hữu** có quyền chỉnh sửa bộ từ của mình (`$deck->user_id === $user->id`).
- **`delete`:** **Chỉ chủ sở hữu** có quyền xóa bộ từ của mình (`$deck->user_id === $user->id`).

### `VocabularyEntryPolicy`
- Tái sử dụng và kế thừa các nguyên tắc truy cập của bộ từ chứa mục từ (`$entry->deck`):
  - **`view`:**
    - Khách và người dùng khác chỉ xem được mục từ thuộc bộ từ `public`.
    - Mục từ thuộc bộ từ `private` chỉ chủ sở hữu được xem; khách, thành viên khác hoặc quản trị viên đều nhận mã **HTTP 404**.
  - **`create`:** Chỉ chủ sở hữu bộ từ (`$deck->user_id === $user->id`) mới có quyền thêm mục từ mới.
  - **`update`:** Chỉ chủ sở hữu bộ từ mới có quyền chỉnh sửa mục từ.
  - **`delete`:** Chỉ chủ sở hữu bộ từ mới có quyền xóa mục từ.
- **Kiểm tra tính nhất quán URL:** Đối với các route lồng nhau như `/decks/{deck}/entries/{entry}`, hệ thống luôn kiểm tra `$entry->deck_id === $deck->id`. Nếu không khớp, trả về ngay **HTTP 404** để ngăn chặn tấn công truy cập chéo.
- **Xác minh ID lồng nhau phía server:** Khi cập nhật mục từ, Action `SaveVocabularyEntryAction` luôn xác minh toàn bộ `id` của ý nghĩa và `id` của câu ví dụ gửi lên thực sự thuộc về mục từ đang sửa. Mọi hành vi tiêm ID của mục từ hoặc nghĩa khác đều bị từ chối và throw `ValidationException`.

## 3. Quy tắc ngôn ngữ hoạt động & Bộ từ

- **Ngôn ngữ bị tắt (`is_active = false`):**
  - Không xuất hiện trong danh sách lựa chọn khi tạo bộ từ mới hoặc khi đổi sang ngôn ngữ khác.
  - Bộ từ đã tạo từ trước với ngôn ngữ này vẫn xem được bình thường và vẫn cho phép cập nhật tên, mô tả hoặc chế độ hiển thị (giữ nguyên ngôn ngữ đã chọn).
  - Trạng thái ngôn ngữ độc lập với quyền truy cập bộ từ.
  - Thành viên sở hữu bộ từ sử dụng ngôn ngữ đã tắt vẫn có toàn quyền quản lý, thêm, sửa, xóa mục từ vựng trong bộ từ đó.

## 4. Hướng văn bản & Mã ngôn ngữ (Text Direction & Language Tags)

- Áp dụng độc lập cho từng vùng nội dung theo đúng cặp ngôn ngữ của bộ từ:
  - **Từ vựng, phiên âm, câu ví dụ:** Áp dụng `lang="{{ $deck->learningLanguage->code }}"` và `dir="{{ $deck->learningLanguage->direction }}"`.
  - **Ý nghĩa, bản dịch câu ví dụ:** Áp dụng `lang="{{ $deck->meaningLanguage->code }}"` và `dir="{{ $deck->meaningLanguage->direction }}"`.
- Toàn bộ văn bản hiển thị qua Blade template đều được escape (`{{ ... }}`) nhằm triệt tiêu nguy cơ XSS.

## 5. Quyền riêng tư Phiên học & Lịch sử cá nhân (Study & History Privacy)

- **Phiên học & Phiên kiểm tra (`study_sessions`):**
  - Chỉ người dùng sở hữu phiên (`$session->user_id === auth()->id()`) mới có quyền truy cập, tương tác, làm bài kiểm tra hoặc xem kết quả.
  - Quản trị viên (`admin`) không mặc định có quyền xem các phiên học hoặc bài kiểm tra riêng tư của thành viên khác (trả về HTTP 403).
- **Lịch sử cá nhân (`/study/history`):**
  - Chỉ hiển thị lịch sử của chính người dùng đang đăng nhập.
  - Khi quyền truy cập vào một bộ từ bị thu hồi (hoặc chuyển sang private), nội dung từ vựng chi tiết sẽ không bị lộ ra ngoài.
  - Không xóa lịch sử học tập chỉ vì bộ từ hoặc nghĩa nguồn bị xóa; lịch sử vẫn được bảo toàn để phản ánh trung thực quá trình học tập của thí sinh.
