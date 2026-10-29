# Giao diện các dạng bài học & Tương tác (Learning UI)

Tài liệu mô tả chi tiết giao diện và tương tác cho các dạng bài tập học từ mới, ôn tập và kiểm tra trong PolyVocab.

## 1. Nguyên tắc thiết kế chung
- **Đơn vị tiến độ:** Một nét nghĩa (`EntryMeaning`). Từ vựng có thể có nhiều nét nghĩa riêng biệt.
- **Phân cấp thị giác:**
  - Từ vựng luôn là tâm điểm nổi bật nhất (`font-serif`, chữ lớn).
  - Ý nghĩa nằm ngay bên dưới từ.
  - Ngữ cảnh & câu ví dụ đặt trong khối tách biệt, dễ đọc kèm bản dịch.
- **Thân thiện di động:**
  - Các lựa chọn xếp dọc theo chiều cuộn tự nhiên.
  - Vùng chạm (tap target) tối thiểu 44-48px.
  - Thanh thao tác (Action Bar) có đệm an toàn, không che khuất nội dung hoặc bàn phím ảo.
  - Không phụ thuộc vào hover hoặc kéo thả chuột.
- **Khả năng tiếp cận & Trạng thái:**
  - Không chỉ dùng màu sắc để báo đúng/sai; luôn kết hợp chữ rõ ràng và biểu tượng (`✓`, `✗`).
  - Hỗ trợ phím tắt số (`1`, `2`, `3`, `4`), `Space` và `Enter`.
  - Tự động tạm khóa phím tắt khi con trỏ đang ở trong ô nhập liệu (`input`, `textarea`) hoặc khi người dùng đang ghép ký tự đa ngôn ngữ bằng bộ gõ IME (`event.isComposing`).

---

## 2. Chi tiết 8 dạng bài học

### 2.1. Thẻ ghi nhớ (Flashcard)
- **Mặt trước:** Hiển thị từ vựng gốc, phiên âm và từ loại.
- **Mặt sau:** Ẩn ban đầu; hiển thị rõ nét nghĩa và câu ví dụ minh họa sau khi người dùng bấm nút *"🔄 Lật thẻ / Xem nghĩa"*. Không phụ thuộc thuần túy vào hiệu ứng 3D lật thẻ.
- **Đánh giá:** Sau khi lật thẻ mới xuất hiện 2 nút tự đánh giá:
  - *"✗ Chưa nhớ"* (Đưa về bước đầu, nhắc lại sau 10 phút).
  - *"✓ Đã nhớ"* (Giữ bước hiện tại, nhắc lại sau 1 ngày).

### 2.2. Trắc nghiệm Chọn nghĩa / Chọn từ
- **Từ -> Nghĩa:** Hiển thị từ gốc, 4 phương án nghĩa dạng thẻ dọc.
- **Nghĩa -> Từ:** Hiển thị nét nghĩa, 4 phương án từ vựng dạng thẻ dọc.
- **Quy trình:** Người học chọn phương án (trạng thái highlight rõ rệt) rồi bấm *"Kiểm tra"*. Sau khi kiểm tra, các lựa chọn bị khóa và hệ thống đưa ra phản hồi đối chiếu.

### 2.3. Ghép cặp (Matching Pairs)
- **Bố cục:** Máy tính hiển thị 2 cột (Cột Từ bên trái, Cột Nghĩa bên phải). Trên điện thoại các thẻ xếp ngăn nắp, dễ phân biệt.
- **Thao tác:** Bấm 1 mục bên cột Từ (highlight xanh) -> Bấm 1 mục bên cột Nghĩa.
- **Kết quả:** Cặp ghép đúng sẽ chuyển màu xanh, bị vô hiệu hóa (khóa) để không bấm lại. Cặp ghép sai báo rung nhẹ và reset lựa chọn. Điểm số được tính tỷ lệ theo số cặp ghép đúng.

### 2.4. Nghe và chọn (Listen & Choose)
- Nút phát âm âm thanh nổi bật ở giữa.
- Khi chưa nộp, ẩn hoàn toàn văn bản từ vựng gốc để tránh làm lộ đáp án.
- Các phương án nghĩa nằm bên dưới.

### 2.5. Nghe và viết (Listen & Type)
- Nút phát âm thanh và nghe lại.
- Ô nhập text có nhãn truy cập (`aria-label`), tắt các tính năng làm lộ đáp án của trình duyệt: `autocomplete="off"`, `autocorrect="off"`, `spellcheck="false"`.
- Hỗ trợ đầy đủ bộ gõ tiếng Việt (Telex, VNI) và các bộ gõ Hán tự/Kana/Hangul.

### 2.6. Điền từ vào chỗ trống (Fill in the blank / Cloze)
- Câu ví dụ có chỗ trống khuyết từ rõ ràng: `[ ______ ]`.
- Ô nhập text riêng biệt bên dưới kèm bản dịch câu ví dụ làm ngữ cảnh.
- Sau khi chấm, hiển thị câu hoàn chỉnh đầy đủ.

### 2.7. Sắp xếp chữ (Word Unscramble)
- Tách biệt rõ ràng 2 vùng: Vùng đáp án (các chữ cái đã chọn) và Vùng mảnh chữ cái (các ký tự rời rạc đã xáo trộn).
- Thao tác chạm: Bấm mảnh để đưa vào đáp án, bấm chữ trong đáp án để trả về vùng mảnh.
- Có nút *"Làm lại"* (Reset) để hoàn nguyên toàn bộ mảnh chữ cái.

---

## 4. Điểm vào từ trang Bộ từ (Study Center on Deck Detail)
Trang chi tiết bộ từ (`/decks/{id}`) tích hợp **Trung tâm học tập bộ từ**:
- **4 chỉ số thống kê thực tế:**
  1. Tổng số mục từ và tổng số nghĩa ("Một từ có thể có nhiều nghĩa").
  2. Số nghĩa chưa học.
  3. Số nghĩa đến hạn, kèm số lượng quá hạn nếu có.
  4. Số nghĩa được đánh dấu khó hoặc có kết quả yếu.
- **Thẻ phiên dở dang (Active Session):** Hiển thị rõ chế độ, giai đoạn, tiến độ nghĩa kèm 2 nút: "Tiếp tục phiên" và "Kết thúc / Hủy phiên cũ".
- **4 nút chế độ học tập kèm kiểm tra khả dụng:**
  1. *Học từ mới*: Vô hiệu hóa nếu không còn nghĩa mới, kèm thông báo "Đã học hết tất cả các nghĩa trong bộ từ này!".
  2. *Ôn đến hạn*: Vô hiệu hóa nếu số đến hạn bằng 0, kèm thông báo "Chưa có nghĩa nào đến hạn ôn tập hôm nay."
  3. *Luyện theo dạng bài*: Mở màn hình chuẩn bị luyện tập tự do (`/decks/{id}/study/practice`).
  4. *Luyện từ khó*: Mở màn hình luyện tập lọc riêng các nghĩa khó (`scope=difficult`). Vô hiệu hóa nếu chưa có mục nào đánh dấu khó.
- **Đối với khách (Guest):** Hiển thị hộp thông báo mời đăng nhập để lưu trữ tiến độ cá nhân và kích hoạt lịch ôn SRS.

---

## 5. Màn hình chuẩn bị phiên Luyện tập (`/study/practice`)
- Hiển thị thông báo minh bạch: *"Phiên luyện này không thay đổi lịch ôn Spaced Repetition (SRS) và không tính vào mục tiêu học mới."*
- Cho phép chọn phạm vi: Toàn bộ, Đã học, Chưa học hoặc Chỉ các nghĩa khó.
- Chọn nhanh số lượng (5, 10, 20) hoặc tùy chỉnh trong giới hạn cấu hình (1 - 50).
- Hiển thị danh sách các dạng bài khả dụng, số nghĩa đáp ứng điều kiện và lý do nếu dạng bài bị tạm tắt.

