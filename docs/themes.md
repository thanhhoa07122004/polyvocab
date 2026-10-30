# Hệ thống Chủ đề Giao diện PolyVocab (Visual Themes & Color Tokens)

Tài liệu hướng dẫn chi tiết về cấu trúc token, hệ thống phối màu đồng bộ, tiêu chuẩn độ tương phản và quy trình mở rộng chủ đề cho dự án PolyVocab.

---

## 1. Danh sách 5 chủ đề giao diện

PolyVocab hỗ trợ 5 chủ đề màu sắc được tính toán kỹ lưỡng theo tiêu chuẩn tiếp cận **WCAG 2.1 AA** (độ tương phản chữ bình thường $\ge 4.5:1$, chữ lớn và tiêu đề $\ge 3:1$):

| Tên chủ đề | Mã định danh (`value`) | Nền trang (`bg-page`) | Nền khối nội dung (`bg-card`) | Nền nổi / Menu (`bg-popover`) | Nền đang chọn (`bg-selected`) | Chữ chính (`text-main`) | Màu nhấn (`primary`) | Chế độ (`color-scheme`) |
|---|---|---|---|---|---|---|---|---|
| **Trắng** | `light` | `#FFFFFF` | `#F8FAFC` | `#FFFFFF` | `#EEF2FF` | `#0F172A` | `#4338CA` | `light` |
| **Tối** | `dark` | `#12161C` | `#1A2028` | `#222B36` | `#243248` | `#E8EDF5` | `#93A4F8` | `dark` |
| **Hồng pastel** | `rose_pastel` | `#FFF1F5` | `#FFFFFF` | `#FFFFFF` | `#FFF0F5` | `#4A2535` | `#A82D60` | `light` |
| **Tím pastel tối** | `plum_dark` | `#1E1926` | `#272033` | `#322A40` | `#3E3054` | `#F1ECF7` | `#BFA6E8` | `dark` |
| **Xanh dương nhạt** | `blue_light` | `#EFF6FF` | `#FFFFFF` | `#FFFFFF` | `#EFF6FF` | `#18324F` | `#1D4ED8` | `light` |

> [!NOTE]
> - **Chủ đề Tím pastel tối (`plum_dark`)**: Phối màu mận tím sâu lắng (`#1E1926`), các thẻ khối nội dung (`#272033`), thẻ con/thống kê (`#322A40`). Màu nhấn tím hoa cà pastel (`#BFA6E8`) thanh nhã, **tuyệt đối không dùng tím neon hay dải màu xám đục**, chữ và số thống kê (xanh lục ngọc, vàng mật ong, hồng phấn) nổi bật tự nhiên và rất dịu mắt.
> - **Chủ đề Tối (`dark`)**: Nền than thẫm nguyên bản (`#12161C`), phân lớp thẻ (`#1A2028`), thẻ thống kê (`#222B36`), viền nét mảnh (`#2D3745`), chữ sáng kem dịu (`#E8EDF5`).
> - Menu chọn chủ đề (`#theme-menu-panel`) đồng bộ theo màu của chủ đề đang áp dụng: nền tối (`#222B36` / `#322A40`), chữ sáng rõ nét, hàng đang chọn dùng nền nhấn tối và dấu tick.
> - Mục tiêu là giảm chói, phân cấp thị giác rõ và hỗ trợ đọc lâu; hệ thống không cam kết chủ đề sẽ ngăn ngừa hoàn toàn mỏi mắt.

---

## 2. Hệ thống Token màu sắc dùng chung (`resources/css/app.css`)

Mỗi chủ đề định nghĩa đầy đủ 10 nhóm token theo vai trò:

### 2.1. Nền & Khung (Surfaces & Cards)
- `--theme-bg-page`: Nền trang tổng thể.
- `--theme-bg-surface`: Nền header, footer, thanh điều hướng.
- `--theme-bg-card`: Nền khung nội dung, flashcard, modal dialog.
- `--theme-bg-card-subtle`: Nền khối thông tin phụ, thẻ thống kê tiến độ bên trong thẻ cha.
- `--theme-bg-popover`: Nền menu dropdown nổi, popup chọn chủ đề.
- `--theme-bg-hover`: Nền khi di chuột lên hàng/thẻ.
- `--theme-bg-selected`: Nền trạng thái đang chọn (Selected Item Card, Option Active).
- `--theme-bg-disabled`: Nền dạng bài hoặc thẻ không khả dụng (tối chìm êm mắt, không xám trắng).
- `--theme-bg-muted`: Nền trạng thái hover, badge xám, thanh tiến độ tổng.
- `--theme-bg-overlay`: Lớp phủ mờ (backdrop) khi mở hộp thoại modal.

### 2.2. Chữ & Phân cấp thị giác (Typography)
- `--theme-text-main`: Chữ tiêu đề, từ vựng đang học, nội dung quan trọng (đạt tương phản $\ge 13:1$).
- `--theme-text-muted`: Chữ giải nghĩa, phiên âm, nhãn phụ (đạt tương phản $\ge 8:1$).
- `--theme-text-subtle`: Chữ ghi chú nhỏ, thời gian, biểu tượng phụ (đạt tương phản $\ge 4.5:1$).
- `--theme-text-disabled`: Chữ nhãn trên thẻ không khả dụng (vẫn giữ độ rõ $\ge 4.5:1$, không giảm opacity).
- `--theme-text-inverse`: Chữ nghịch đảo dùng trên nền màu nhấn hoặc nút chính.

### 2.3. Đường viền (Borders & Dividers)
- `--theme-border`: Đường viền thẻ, khung bài tập, đường phân cách chuẩn.
- `--theme-border-strong`: Đường viền ô nhập liệu khi bình thường.
- `--theme-border-subtle`: Đường kẻ chia dòng mờ bên trong thẻ.
- `--theme-border-selected`: Đường viền nổi bật của thẻ/mục đang được chọn.

### 2.4. Màu nhấn & Trạng thái chọn (Primary & Selection)
- `--theme-primary`: Nút hành động chính, viền thẻ/lựa chọn đang chọn.
- `--theme-primary-hover`: Màu khi rê chuột lên nút chính.
- `--theme-primary-fg`: Màu chữ trên nền nút chính.
- `--theme-primary-subtle`: Nền badge đang kích hoạt, nền lựa chọn đang được chọn (`selected`).
- `--theme-primary-subtle-border`: Viền của lựa chọn đang được chọn.
- `--theme-primary-subtle-text`: Chữ trên badge đang chọn.

### 2.5. Ô nhập liệu (Form Controls)
- `--theme-input-bg`: Nền trường nhập, select, textarea.
- `--theme-input-border`: Viền trường nhập.
- `--theme-input-text`: Chữ người dùng gõ.
- `--theme-input-placeholder`: Chữ hướng dẫn mờ.
- `--theme-focus-ring`: Quầng sáng chỉ báo focus bàn phím rõ nét.

### 2.6. Nút phụ & Trung tính (Secondary Buttons)
- `--theme-btn-sec-bg`: Nền nút phụ.
- `--theme-btn-sec-border`: Viền nút phụ.
- `--theme-btn-sec-text`: Chữ nút phụ.
- `--theme-btn-sec-hover`: Nền nút phụ khi hover.

### 2.7. Thông báo & Trạng thái chấm bài (Feedback Tokens)
- **Thành công (Success / Đúng)**:
  - `--theme-success-bg`, `--theme-success-border`, `--theme-success-text` (`#6EE7B7`).
  - Nút nộp/tiếp tục: `--theme-success-btn-bg`, `--theme-success-btn-hover`.
- **Lỗi / Chưa đúng (Danger / Wrong)**:
  - `--theme-danger-bg`, `--theme-danger-border`, `--theme-danger-text` (`#FDA4AF`).
  - Nút kết thúc/hủy: `--theme-danger-btn-bg`, `--theme-danger-btn-hover`.
- **Cảnh báo (Warning)**:
  - `--theme-warning-bg`, `--theme-warning-border`, `--theme-warning-text` (`#FCD34D`).
- **Thông tin (Info)**:
  - `--theme-info-bg`, `--theme-info-border`, `--theme-info-text` (`#7DD3FC` / `#D8C7F3`).

### 2.8. Giai đoạn học tập (Study Stages)
- `--theme-study-accent`: Màu nhấn tím/xanh riêng cho giai đoạn Luyện nhớ (Retention).
- `--theme-study-accent-subtle`, `--theme-study-accent-border`, `--theme-study-accent-text`.

---

## 3. Đo lường Độ tương phản (WCAG 2.1 Contrast Ratios)

Kết quả đo bằng thuật toán chuẩn WCAG (Relative Luminance):

| Chủ đề | Chữ chính trên nền thẻ | Chữ chính trên nền đang chọn | Chữ phụ trên nền thẻ | Chữ nhỏ (`subtle`) | Chữ trên nút chính | Tiêu chuẩn WCAG |
|---|---|---|---|---|---|---|
| **Trắng** | **17.06 : 1** | **14.20 : 1** | **7.24 : 1** | **4.55 : 1** | **7.90 : 1** | ✅ WCAG AAA |
| **Tối** | **13.94 : 1** | **11.85 : 1** | **8.70 : 1** | **5.45 : 1** | **8.55 : 1** | ✅ WCAG AAA / AA |
| **Hồng pastel** | **13.07 : 1** | **11.50 : 1** | **7.48 : 1** | **4.79 : 1** | **6.54 : 1** | ✅ WCAG AAA / AA |
| **Tím pastel tối** | **13.49 : 1** | **10.50 : 1** | **8.26 : 1** | **5.15 : 1** | **8.60 : 1** | ✅ WCAG AAA / AA |
| **Xanh dương nhạt** | **13.06 : 1** | **12.00 : 1** | **7.66 : 1** | **4.55 : 1** | **6.70 : 1** | ✅ WCAG AAA / AA |

Tất cả các thông số thống kê trong màn hình bộ từ và học tập đều có độ tương phản vượt trội:
- Số nghĩa chưa học (Xanh lục ngọc `#6EE7B7` trên `#272033`): `10.28 : 1` (WCAG AAA)
- Số nghĩa đến hạn (Vàng hổ phách `#FCD34D` trên `#272033`): `10.86 : 1` (WCAG AAA)
- Số nghĩa khó (Hồng phấn `#FDA4AF` trên `#272033`): `8.28 : 1` (WCAG AAA)
- Số tổng số nghĩa (`#F1ECF7` trên thẻ con `#322A40`): `11.73 : 1` (WCAG AAA)
