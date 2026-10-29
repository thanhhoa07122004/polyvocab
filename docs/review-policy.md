# Chính sách Ôn tập Spaced Repetition (Review Policy v1)

Tài liệu mô tả chính sách và thuật toán lặp lại ngắt quãng (SRS) ban đầu của PolyVocab.

## 1. Thông số & Bước giãn cách
- **Phiên bản:** `v1`.
- **Đơn vị tiến độ:** Một nét nghĩa (`EntryMeaning`).
- **Các bước giãn cách mặc định:** `[1, 3, 7, 14, 30]` ngày.
- **Thời gian ôn lại khi quên / lapse:** `10 phút`.
- **Khoảng cách tự đánh giá "Đã nhớ":** `1 ngày`.

---

## 2. Quy tắc cập nhật lịch ôn trong phiên ôn tập
1. **Lần trả lời có chấm đầu tiên đúng (không dùng gợi ý, không bỏ qua):**
   - Tăng tối đa 1 bước trong danh sách giãn cách (`repetition_level = repetition_level + 1`).
   - Lịch ôn tiếp theo: `due_at = now() + interval_days`.
2. **Sai ('fail'), bỏ qua ('skip') hoặc có dùng gợi ý:**
   - Không tăng bước, đưa tiến độ về bước đầu (`repetition_level = 0`).
   - Lên lịch ôn lại khẩn cấp sau **10 phút** (`due_at = now() + 10 minutes`).
   - Tăng bộ đếm quên (`lapse_count++`).
3. **Tự đánh giá "Đã nhớ" (Flashcard):**
   - Giữ nguyên bước giãn cách hiện tại.
   - Đặt hạn ôn lại ngắn để thử thách: sau **1 ngày** (`due_at = now() + 1 day`).
4. **Tự đánh giá "Chưa nhớ" (Flashcard):**
   - Đưa về bước đầu (`repetition_level = 0`), ôn lại sau **10 phút**.
5. **Nguyên tắc Finalize 1 lần:**
   - Trong một phiên ôn, mỗi nghĩa chỉ finalize lịch ôn **đúng một lần duy nhất** dựa trên lần thử đầu tiên.
   - Việc luyện lại câu sai trong cùng phiên không xóa kết quả khó ban đầu và không làm giãn lịch ôn lặp lại.
6. **Thời điểm tính hạn:**
   - Dựa trên thời điểm server ghi nhận request (`recorded_at`), không dựa vào thời điểm người dùng quay lại xem kết quả nhiều ngày sau.

---

## 3. Chế độ "Luyện thêm ngoài lịch" & Luyện tập tự do
- **Luyện thêm ngoài lịch (`is_extra_practice = true`):** Người dùng có thể chọn luyện thêm các nghĩa đã học ngay cả khi chưa đến hạn. Mặc định **không thay đổi lịch ôn SRS chuẩn**.
- **Luyện theo dạng bài (`custom_practice`) & Luyện từ khó (`difficult_practice`):**
  - Tuyệt đối không thay đổi chu kỳ giãn cách, cấp độ lặp lại hoặc ngày đến hạn của SRS (`isSrsModifying() === false`).
  - Kết quả chỉ được lưu trữ vào lịch sử luyện tập cá nhân để thống kê.
  - Luyện từ khó không tự ý bỏ nhãn khó khi người dùng chỉ trả lời đúng 1 lần; việc gỡ nhãn khó do người dùng chủ động điều khiển.
- **Giới hạn số lần thử lại câu sai trong phiên (`max_session_retries`):** Mặc định tối đa 3 lần trả lời có chấm cho mỗi nghĩa trong cùng một phiên; nếu hết lượt vẫn chưa nhớ, hệ thống đánh dấu "Cần luyện thêm" và cho phép tiếp tục phiên.

