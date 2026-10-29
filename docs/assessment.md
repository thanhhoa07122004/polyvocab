# Đánh giá & Kiểm tra kiến thức (Assessment & Quiz System)

Tài liệu mô tả hệ thống bài kiểm tra khách quan và luyện kiểm tra trong PolyVocab.

## 1. Hai chế độ kiểm tra

### 1.1. Luyện kiểm tra (Practice Quiz)
- Phản hồi kết quả (Đúng/Sai kèm giải thích) ngay sau từng câu.
- Hỗ trợ nút *"Gợi ý"*: Khi thí sinh dùng gợi ý, câu hỏi đó tự động nhận **0 điểm** (tránh gian lận điểm số).
- Cho phép xem lại các câu đã làm; câu đã chấm không được sửa lại đáp án.
- Kết quả không tác động đến lịch ôn tập SRS thường kỳ.

### 1.2. Bài kiểm tra chính thức (Formal Test)
- **Bảo mật tuyệt đối:** Server loại bỏ toàn bộ trường đáp án đúng (`correct_answer`, `correct_id`, `is_correct`) trước khi gửi cấu trúc đề thi về client.
- Chỉ hiển thị trạng thái *"Đã lưu"*, không tiết lộ đúng/sai hay lời giải trước khi nộp bài.
- Không có nút gợi ý.
- **Bảng số câu (Question Palette):** Hiển thị trực quan trạng thái từng câu (Đã làm, Chưa làm, Đánh dấu xem lại) cho phép nhảy nhanh đến bất kỳ câu hỏi nào.
- Thí sinh được quyền sửa đổi đáp án thoải mái trước thời điểm nộp bài; server ghi nhận đáp án mới nhất.
- Hỗ trợ đánh dấu xem lại (`🚩 Flag for review`).
- Điểm số và bảng giải thích chi tiết chỉ được trả về sau khi bài thi đã được nộp hoặc hết thời gian.

---

## 2. Kiểm soát Thời gian & Tự động nộp bài
- Đối với bài thi có giới hạn thời gian:
  - Server lưu mốc `started_at` và `expires_at`.
  - Đồng hồ JavaScript trên trình duyệt chỉ đóng vai trò hiển thị đếm ngược.
  - Ngay khi `now() > expires_at`, server từ chối ghi nhận thêm câu trả lời và tự động kích hoạt tiến trình chấm điểm (`finalizeQuizSubmission`).
- Cơ chế chặn nộp trùng lặp (Idempotent submission).

---

## 3. Quy tắc chấm điểm (Scoring Rules)
- **Trọng số:** Mỗi câu hỏi có trọng số bằng nhau mặc định (1.0 điểm).
- **Điểm:** Trả lời đúng nhận 1.0 điểm; trả lời sai hoặc bỏ trống nhận 0 điểm (không áp dụng điểm âm).
- **Ghép cặp:** Điểm số tỷ lệ thuận theo số cặp đúng (ví dụ: đúng 3/4 cặp đạt 0.75 điểm).
- **Điểm phần trăm:**
  $$\text{Điểm phần trăm} = \frac{\text{Tổng điểm đạt được}}{\text{Tổng số câu hỏi}} \times 100$$
- Kết quả bài kiểm tra độc lập hoàn toàn với lịch lặp lại ngắt quãng SRS.

