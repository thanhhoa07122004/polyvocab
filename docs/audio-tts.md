# Tích hợp Microsoft Azure Speech Text-to-Speech (TTS)

Tài liệu này hướng dẫn chi tiết về cơ chế phát âm đa ngôn ngữ sử dụng dịch vụ **Microsoft Azure Speech Text-to-Speech (TTS)** trong hệ thống PolyVocab, phục vụ cho việc học từ mới (làm quen), luyện tập theo dạng bài nghe và làm bài kiểm tra.

---

## 1. Hướng dẫn tạo tài nguyên Azure Speech gói Free (F0)

1. Truy cập vào [Azure Portal](https://portal.azure.com/) và đăng nhập vào tài khoản Azure của bạn.
2. Tìm kiếm dịch vụ **Speech Services** (hoặc **Azure AI services > Speech**) trong ô tìm kiếm.
3. Bấm **Create** (Tạo mới) để tạo tài nguyên Speech.
4. Điền các thông tin cơ bản:
   - **Subscription**: Chọn gói thuê bao của bạn.
   - **Resource group**: Chọn nhóm tài nguyên hiện có hoặc tạo mới (ví dụ: `rg-polyvocab`).
   - **Region**: Chọn khu vực gần người dùng để có độ trễ thấp nhất (Khuyến nghị: `Southeast Asia` - Đông Nam Á hoặc `East US`).
   - **Name**: Đặt tên tài nguyên đại diện (ví dụ: `polyvocab-speech-service`).
   - **Pricing tier**: Chọn **Free F0** (Gói miễn phí: 0.5 triệu ký tự văn bản mỗi tháng, tối đa 20 giao dịch/phút).
5. Bấm **Review + create**, sau đó bấm **Create**.
6. Sau khi quá trình triển khai hoàn tất, bấm **Go to resource**.
7. Vào mục **Keys and Endpoint** (Khóa và Điểm cuối) ở menu bên trái:
   - Copy **Key 1** (hoặc Key 2).
   - Ghi lại giá trị **Location/Region** (ví dụ: `southeastasia`).

> [!WARNING]
> Tuyệt đối không commit API Key vào Git, không ghi trong file JavaScript client-side và không chia sẻ khóa trong log hệ thống.

---

## 2. Điền cấu hình vào `.env` trên máy chủ / môi trường nội bộ

Mở tệp `.env` trong thư mục gốc của dự án và điền các biến cấu hình sau:

```dotenv
# Bật tính năng Azure Speech TTS
AZURE_SPEECH_TTS_ENABLED=true

# Khóa bí mật lấy từ Azure Portal
AZURE_SPEECH_KEY=your_azure_speech_key_here

# Region tương ứng với tài nguyên Azure đã tạo (ví dụ: southeastasia, eastus)
AZURE_SPEECH_REGION=southeastasia

# Hạn mức ký tự hàng tháng nội bộ (ước tính, gói F0 là 500.000 ký tự)
AZURE_SPEECH_MONTHLY_LIMIT=500000

# Độ dài tối đa của từ hoặc cụm từ được phép gửi đến Azure (mặc định: 250 ký tự)
AZURE_SPEECH_MAX_TERM_CHARS=250
```

Trong tệp `.env.example`, các biến này đã được khai báo với giá trị mẫu không nhạy cảm:
- `AZURE_SPEECH_TTS_ENABLED=false`
- `AZURE_SPEECH_KEY=`
- `AZURE_SPEECH_REGION=southeastasia`

Nếu `AZURE_SPEECH_TTS_ENABLED=false` hoặc thiếu `AZURE_SPEECH_KEY`, hệ thống sẽ thông báo trạng thái "Chưa cấu hình dịch vụ âm thanh" một cách an toàn và không tạo audio giả.

---

## 3. Cấu hình giọng theo ngôn ngữ học

PolyVocab lưu cấu hình giọng mặc định trong bảng `languages` (`tts_locale` và `tts_voice`). Quản trị viên hệ thống có thể tùy chỉnh các giá trị này tại trang **Quản trị > Quản lý ngôn ngữ** (`/admin/languages`).

### Danh sách giọng Neural mặc định:

| Mã ngôn ngữ | Tên ngôn ngữ | Azure Locale (BCP-47) | Giọng mặc định (Voice Name) |
|---|---|---|---|
| `vi` | Tiếng Việt | `vi-VN` | `vi-VN-HoaiMyNeural` (hoặc `vi-VN-NamMinhNeural`) |
| `en` | Tiếng Anh | `en-US` | `en-US-JennyNeural` (hoặc `en-US-GuyNeural`) |
| `zh` | Tiếng Trung | `zh-CN` | `zh-CN-XiaoxiaoNeural` (hoặc `zh-CN-YunxiNeural`) |
| `ja` | Tiếng Nhật | `ja-JP` | `ja-JP-NanamiNeural` (hoặc `ja-JP-KeitaNeural`) |
| `ko` | Tiếng Hàn | `ko-KR` | `ko-KR-SunHiNeural` (hoặc `ko-KR-InJoonNeural`) |

### Bổ sung ngôn ngữ mới:
Khi hệ thống thêm ngôn ngữ mới (ví dụ tiếng Pháp `fr` hay tiếng Đức `de`), quản trị viên chỉ cần nhập mã `tts_locale` (ví dụ `fr-FR`) và `tts_voice` (ví dụ `fr-FR-DeniseNeural`) trong form tạo/sửa ngôn ngữ. **Toàn bộ trang học và tạo câu hỏi tự động tương thích mà không cần viết lại mã nguồn**.

---

## 4. Chạy Laravel Queue Worker

Để xử lý việc tạo phát âm dưới nền (không làm nghẽn request HTTP):

```powershell
php artisan queue:work --tries=2 --backoff=5,15
```

Job `App\Jobs\GenerateEntryAudioJob` sẽ:
- Thực hiện gọi Azure REST API ở phía server.
- Sử dụng Distributed Cache Lock (`tts:generate:entry:{id}`) chống trùng lặp.
- Tự động thử lại tối đa 2 lần nếu xảy ra sự cố mạng tạm thời.

---

## 5. Quy trình tạo, lưu trữ và nghe thử âm thanh

1. **Người có quyền biên soạn** (chủ sở hữu bộ từ):
   - Mở trang chi tiết từ vựng (`/decks/{deck}/entries/{entry}`) hoặc trang chỉnh sửa (`/decks/{deck}/entries/{entry}/edit`).
   - Bấm nút **"Tạo phát âm (TTS)"**.
   - Giao diện hiển thị trạng thái *"Đang tạo âm thanh…"*. Khi hoàn tất, âm thanh sẽ tự động phát thử và trạng thái chuyển thành *"Đã tạo âm thanh."*
   - Có thể bấm **"Tạo lại phát âm (TTS)"** khi sửa từ vựng hoặc đổi giọng đọc.
2. **Bảo toàn Audio thủ công (Manual Audio)**:
   - Nếu từ vựng đang dùng tệp audio do người dùng tự tải lên (`audio_source = 'manual'`), hệ thống **không tự ý ghi đè**.
   - Hộp thoại cảnh báo sẽ yêu cầu xác nhận rõ ràng trước khi thay thế.
3. **Cơ chế tái sử dụng & Chống tạo trùng (Cache / Hash Reuse)**:
   - Hệ thống tính hash SHA-256 từ: `term + locale + voice`.
   - Nếu từ vựng, ngôn ngữ và giọng đọc không đổi và tệp vẫn còn trên đĩa, hệ thống tái sử dụng ngay lập tức mà không gửi request sang Azure.
4. **Lưu trữ bảo mật**:
   - Tệp MP3 được lưu tại đĩa riêng tư `storage/app/audio/entries/{random_hash}.mp3`.
   - Tên tệp ngẫu nhiên không chứa từ khóa đáp án.

---

## 6. Phân quyền và Bảo mật đường dẫn âm thanh

- Đường dẫn stream âm thanh: `GET /audio/entries/{entry}` qua `AudioController@play`.
- **Bộ từ công khai**: Người dùng khách và thành viên khác có thể nghe bình thường.
- **Bộ từ riêng tư**: Server kiểm tra quyền sở hữu thông qua `VocabularyEntryPolicy` và `DeckPolicy`. Nếu khách hoặc người dùng khác truy cập, server trả về mã **404 Not Found** (không tiết lộ sự tồn tại của bộ từ riêng tư).

---

## 7. Sử dụng trong các chế độ học và bài luyện tập

1. **Học từ mới (Familiarization)**:
   - Trên mặt trước flashcard, có nút **🔊 Nghe phát âm** khi từ vựng đã có audio.
   - Bấm nút mới phát âm, có thể bấm nghe lại nhiều lần.
   - Sử dụng bộ điều khiển âm thanh duy nhất (`currentPlayingAudio`), chống hiện tượng âm thanh phát chồng chéo khi bấm nhanh liên tục.
   - Tự động dừng âm thanh khi lật thẻ, chuyển từ, tạm dừng phiên học hoặc rời khỏi trang.
2. **Luyện tập dạng nghe ("Nghe và chọn" & "Nghe và viết")**:
   - Bộ lọc điều kiện (`ExerciseEligibilityService`) chỉ kích hoạt dạng bài nghe khi có từ vựng đã được tạo audio hợp lệ.
   - Server chỉ truyền `audio_url` xuống trình duyệt; trường `term`, `audio_term` và đáp án đúng bị ẩn hoàn toàn khỏi mã HTML/JSON trước khi người học nộp câu trả lời.
   - Nếu từ vựng cụ thể nào bị lỗi file audio, hệ thống chuyển sang dạng bài tương đương (như *Nhìn nghĩa gõ từ* hoặc *Trắc nghiệm chọn từ*) để không tính sai oan cho người học.

---

## 8. Giới hạn sử dụng và xử lý lỗi

- **Bảng theo dõi `tts_usages`**: Ghi nhận số ký tự thực tế đã gửi đến Azure cho mỗi lần phát âm cùng `user_id` và thời gian để quản trị viên đối chiếu hạn mức.
- **Giới hạn độ dài từ**: Tối đa 250 ký tự (`AZURE_SPEECH_MAX_TERM_CHARS`).
- **Giới hạn tốc độ (Rate Limiting)**: Tối đa 15 yêu cầu tạo phát âm mỗi phút trên mỗi người dùng.
- **Xử lý mã lỗi Azure**:
  - `401 / 403`: Báo lỗi xác thực tài khoản hoặc hạn mức Azure.
  - `429`: Báo hệ thống đang bị giới hạn tốc độ yêu cầu (rate limit) và yêu cầu thử lại sau.
  - `Timeout`: Báo quá thời gian chờ kết nối.
  - **Không xóa file audio cũ** nếu thao tác tạo lại gặp lỗi mạng.
- **Thông báo giao diện ngắn gọn, thân thiện**:
  - *"Đang tạo âm thanh…"*
  - *"Đã tạo âm thanh."*
  - *"Chưa tạo được âm thanh. Hãy thử lại sau."*

