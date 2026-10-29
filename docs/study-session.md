# Vận hành Phiên học tập (Study Session Architecture)

Tài liệu mô tả quy trình khởi tạo, duy trì trạng thái, lưu trữ tự động và hoàn tất phiên học cho PolyVocab.

## 1. Vòng đời phiên học (Session Lifecycle)
- **Khởi tạo:**
  - `POST /decks/{deck}/study/new/start` (Học từ mới).
  - `POST /study/review/start` (Ôn tập SRS).
  - `POST /study/quiz/start` (Kiểm tra đánh giá).
  - Áp dụng kỹ thuật kiểm tra phiên đang hoạt động (`status = 'in_progress'`) để ngăn chặn tạo trùng lặp phiên khi người dùng tải lại trang (reload).
- **Trạng thái (`status`):**
  - `in_progress`: Đang diễn ra.
  - `paused`: Người học chủ động tạm dừng phiên để làm việc khác; có thể tiếp tục bất cứ lúc nào từ Bảng điều khiển.
  - `completed`: Đã hoàn thành tất cả các mục nghĩa và nộp bài.
  - `abandoned`: Người học chọn kết thúc phiên cũ để tạo phiên mới; các nghĩa đã hoàn thành trước đó vẫn được lưu an toàn.

## 2. Kỹ thuật chia nhóm nhỏ (Chunking)
- Để tránh quá tải nhận thức, các phiên học từ mới chia nhỏ thành từng nhóm mặc định **5 nghĩa** (`chunk_size = 5`).
- **Giai đoạn Làm quen (`familiarization`):** Người học tiếp cận lần lượt 5 nghĩa trong nhóm để hiểu rõ từ vựng, phiên âm, từ loại, ý nghĩa và câu ví dụ.
- **Chuyển tiếp tự động:** Ngay khi cả 5 nghĩa trong nhóm hoàn thành làm quen, phiên tự động chuyển sang giai đoạn **Luyện nhớ (`retention_practice`)** cho chính nhóm đó trước khi mở khóa nhóm tiếp theo.

## 3. Tự lưu trữ & Cơ chế an toàn mạng (Network Resilience)
- **Auto-save:** Mỗi thao tác trả lời được gửi lên server qua API (`/familiarize`, `/practice`, `/save-answer`).
- **Giữ dữ liệu:** Khi kết nối mạng bị gián đoạn, câu trả lời của người học không bị mất; giao diện hiển thị cảnh báo lỗi mạng kèm nút *"Thử lại"* an toàn.
- **Xác nhận máy chủ:** Chỉ chuyển mục tiếp theo khi server phản hồi HTTP 200 thành công.
- **Trạng thái lưu:** Thanh trên hiển thị chỉ báo thời gian thực: "Đang lưu...", "Đã đồng bộ", "Lưu thất bại".

## 4. Tương tác chuyên sâu trong phiên
- **Đánh dấu khó (`POST /toggle-difficult`):** Người học có thể bật/tắt đánh dấu khó cho mục hiện tại ngay trong phiên làm quen hoặc luyện nhớ. Thay đổi được đồng bộ tức thì vào `StudyRecord`.
- **Tự khai báo "Tôi đã biết" (`POST /quick-verify`):** 
  - Đưa câu hỏi kiểm tra nhanh để xác thực năng lực thực tế.
  - Nếu trả lời đúng: Ghi nhận tự khai báo (`is_self_declared_known = true`) và cấp lịch ôn thử thách ngắn 2 ngày. Tuyệt đối không tự động gán nhãn thành thạo vĩnh viễn.
  - Nếu trả lời sai: Giữ nguyên trong luồng làm quen và luyện nhớ bình thường.
- **Yêu cầu gợi ý (`POST /hint`):** Cấp chữ cái đầu tiên hoặc câu ví dụ ngữ cảnh; đồng thời đánh dấu `used_hint = true` ở server.
- **Kiểm tra ghép cặp thời gian thực (`POST /check-pair`):** Kiểm tra từng cặp từ - nghĩa ngay khi người dùng chọn; nếu đúng sẽ khóa cặp ngay tại giao diện.

## 5. Tạm dừng vs Kết thúc phiên
- **Tạm dừng (`POST /pause`):** Giữ nguyên phiên ở trạng thái `paused`. Thẻ phiên hiển thị nổi bật trên Bảng điều khiển và trang chi tiết bộ từ với nút "Tiếp tục phiên học".
- **Kết thúc phiên (`POST /abandon`):** Chốt kết quả các nghĩa đã hoàn thành trước đó; giải phóng các nghĩa chưa học/chưa ôn về hàng đợi ban đầu. Không để tồn tại phiên ma hoặc tạo trùng phiên.

