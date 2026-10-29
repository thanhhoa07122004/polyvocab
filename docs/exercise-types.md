# Đặc tả 8 Dạng Bài Tập & Cơ Chế Chấm Điểm (Exercise Types)

Tài liệu này quy định chi tiết cấu trúc dữ liệu, điều kiện tạo câu hỏi, giao diện người dùng và cơ chế chấm điểm phía server cho 8 dạng bài trong hệ thống PolyVocab.

---

## 1. Nguyên tắc chung

- **Đơn vị luyện tập:** Mỗi câu hỏi luôn gắn với một nét nghĩa cụ thể (`EntryMeaning`).
- **Bảo mật đáp án:** Server là nguồn duy nhất tạo câu hỏi và chấm điểm. Trong chế độ bài kiểm tra chính thức (`formal_test`), thuộc tính đáp án (`correct_answer`, `correct_id`, `is_correct`) bị loại bỏ hoàn toàn trước khi trả dữ liệu về trình duyệt.
- **Không tự động chuyển câu:** Sau khi chấm điểm, người học chủ động xem phản hồi và bấm "Tiếp tục".
- **Hỗ trợ đa ngôn ngữ:** Chuẩn hóa Unicode NFC, loại bỏ khoảng trắng thừa và dấu câu viền khi so sánh văn bản. Tự động vô hiệu hóa phím tắt khi người học đang nhập văn bản hoặc đang ghép ký tự bằng bộ gõ IME (`e.isComposing`).

---

## 2. Chi tiết 8 dạng bài

### 1. Thẻ ghi nhớ (Flashcard)
- **ID cấu hình:** `flashcard`
- **Mục đích:** Tự gợi nhớ và tự đánh giá khả năng nhớ nghĩa.
- **Mặt trước:** Hiển thị từ vựng hoặc nghĩa (tùy theo thiết lập chiều thẻ).
- **Mặt sau:** Sau khi bấm "Xem đáp án / Lật thẻ", mặt sau hiển thị giải nghĩa, phiên âm và ví dụ minh họa ngữ cảnh.
- **Thao tác chấm:** Người học chọn "Đã nhớ" (`self_remember`) hoặc "Chưa nhớ" (`self_forgot`).
- **Ghi nhận:** Được gắn nhãn "Tự đánh giá", không tính vào tỷ lệ đúng khách quan của bài kiểm tra. Nếu chọn "Chưa nhớ", thẻ được xếp lại vào hàng đợi trong phiên.

### 2. Trắc nghiệm: Chọn nghĩa đúng
- **ID cấu hình:** `multiple_choice_term_to_meaning`
- **Điều kiện:** Bộ từ có tối thiểu 2 nghĩa khác nhau.
- **Hiển thị:** Từ vựng, phiên âm, từ loại và ngữ cảnh (nếu có). Bên dưới là danh sách các phương án nghĩa (mục tiêu 4 phương án, tối thiểu 2 phương án).
- **Thao tác:** Bấm chọn 1 phương án rồi bấm "Kiểm tra". Cho phép đổi lựa chọn thoải mái trước khi nộp.
- **Chấm điểm:** Server so sánh ID phương án người dùng chọn với ID nghĩa chính xác.

### 3. Trắc nghiệm: Chọn từ đúng
- **ID cấu hình:** `multiple_choice_meaning_to_term`
- **Điều kiện:** Bộ từ có tối thiểu 2 từ vựng (`term`) khác nhau.
- **Hiển thị:** Giải nghĩa và từ loại, ẩn từ vựng cần tìm. Các phương án lựa chọn là các từ vựng khác nhau trong bộ.
- **Thao tác & Chấm điểm:** Chọn phương án và nộp; server chấm điểm khách quan.

### 4. Ghép cặp Từ - Nghĩa (Matching Pairs)
- **ID cấu hình:** `matching_pairs`
- **Điều kiện:** Bộ từ có tối thiểu 2 nghĩa khác nhau (mặc định tạo 4 cặp).
- **Giao diện:**
  - Máy tính: Hai cột riêng biệt (Cột Từ bên trái, Cột Nghĩa bên phải).
  - Điện thoại: Bố cục dạng thẻ hàng dọc dễ bấm, không phụ thuộc kéo thả.
- **Thao tác:** Bấm một mục bên từ rồi bấm một mục bên nghĩa.
- **Kiểm tra thời gian thực:** Gọi API `/check-pair` phía server. Cặp đúng sẽ chuyển sang màu xanh và bị khóa tương tác. Cặp sai sẽ nháy đỏ cảnh báo và cho phép chọn lại.

### 5. Nghe và chọn (Listen and Choose)
- **ID cấu hình:** `listen_and_choose`
- **Điều kiện:** Bộ từ có tối thiểu 2 nghĩa khác nhau.
- **Hiển thị:** Nút phát âm thanh nổi bật (tích hợp Web Speech API). Không hiển thị văn bản từ vựng để tránh làm lộ đáp án trước khi người dùng trả lời.
- **Phương án:** Danh sách các lựa chọn nghĩa đặt ở dưới.
- **Thao tác:** Nghe phát âm, chọn nghĩa phù hợp và bấm "Kiểm tra".

### 6. Nghe và viết (Listen and Type - Chính tả)
- **ID cấu hình:** `listen_and_type`
- **Hiển thị:** Nút phát âm thanh và ô nhập văn bản.
- **Bảo vệ chống gian lận:** Ô nhập tắt hoàn toàn `autocomplete="off"`, `autocorrect="off"`, `autocapitalize="off"`, `spellcheck="false"`.
- **Hỗ trợ gõ IME:** Không tự can thiệp làm gián đoạn khi người học đang gõ tiếng Việt (Telex/VNI) hoặc tiếng Nhật/Trung/Hàn.
- **Chấm điểm:** Server chuẩn hóa Unicode NFC, so khớp văn bản chính xác và hiển thị diff đáp án sau khi nộp.

### 7. Điền từ vào chỗ trống (Fill in the blank / Cloze)
- **ID cấu hình:** `fill_in_the_blank`
- **Điều kiện:** Nghĩa có ít nhất một câu ví dụ minh họa (`requires_examples = true`).
- **Hiển thị:** Câu ví dụ có chỗ trống dạng `[ ______ ]` và bản dịch ví dụ bên dưới để làm ngữ cảnh.
- **Thao tác:** Người học nhập từ còn thiếu vào ô nhập và bấm "Kiểm tra".
- **Sau khi chấm:** Hiển thị toàn bộ câu ví dụ hoàn chỉnh có từ vựng.

### 8. Sắp xếp chữ cái / mảnh ghép (Word Unscramble)
- **ID cấu hình:** `word_unscramble`
- **Giao diện:**
  - Vùng Đáp án (Answer Tray): Chứa các mảnh ký tự người dùng đã chọn theo thứ tự.
  - Vùng Mảnh ký tự (Tile Tray): Chứa các ký tự của từ đã được xáo trộn ngẫu nhiên.
- **Thao tác:**
  - Bấm vào mảnh ở vùng dưới để chuyển lên vùng đáp án.
  - Bấm vào mảnh ở vùng đáp án để gỡ bỏ trả về.
  - Có nút "Làm lại" để hoàn tác toàn bộ.
- **Xử lý ký tự:** Tách ký tự theo chuẩn UTF-8 (`mb_str_split`), cấp ID duy nhất cho từng mảnh để hỗ trợ các từ có ký tự lặp lại (ví dụ: chữ 'p' trong "Apple").
- **Chấm điểm:** Server so khớp chuỗi ghép được với từ gốc.

