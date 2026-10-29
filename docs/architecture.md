# Kiến trúc hệ thống (Architecture)

## 1. Ngăn xếp công nghệ (Tech Stack)
- **Framework:** Laravel 12 (PHP 8.2+).
- **Frontend / UI:** Blade Components, Tailwind CSS v4, Livewire 4.
- **Build Tool:** Vite 6.
- **Database:** MariaDB / MySQL.

## 2. Cấu trúc thư mục & Trách nhiệm các tầng

```
polyvocab/
├── app/
│   ├── Actions/               # Nghiệp vụ nhiều bước
│   │   ├── Vocabulary/        # Thêm, sửa, xóa, import từ vựng
│   │   └── Study/             # Bắt đầu phiên học, ghi nhận kết quả ôn tập
│   ├── Http/
│   │   ├── Controllers/       # Điều hướng HTTP requests truyền thống
│   │   └── Middleware/        # Lớp lọc request (Auth, Localization...)
│   ├── Livewire/              # Các UI Component tương tác thời gian thực
│   ├── Models/                # Định nghĩa Eloquent Model và quan hệ
│   ├── Policies/              # Phân quyền người dùng (Authorization)
│   └── Services/              # Thuật toán tái sử dụng và xử lý chuyên sâu
│       ├── Study/             # Thuật toán Spaced Repetition (tính khoảng cách ôn)
│       └── Audio/             # Tích hợp dịch vụ TTS và xử lý âm thanh phát âm
├── database/
│   ├── migrations/            # Quản lý cấu trúc cơ sở dữ liệu
│   └── seeders/               # Dữ liệu mẫu ban đầu
├── docs/                      # Tài liệu dự án
│   └── decisions/             # Ghi nhận các quyết định kỹ thuật và lý do
└── resources/
    ├── css/                   # Tailwind CSS styling
    ├── js/                    # JavaScript
    └── views/                 # Blade templates và layouts
```

## 3. Nguyên tắc thiết kế
- **Fat Services / Actions, Skinny Controllers:** Livewire component và Controller chỉ đóng vai trò giao tiếp, nhận input, gọi Action/Service tương ứng và trả về view.
- **Không đặt nghiệp vụ trong Blade:** Toàn bộ tính toán trạng thái phải được chuẩn bị trước trong Component hoặc Model.
- **Độc lập và Modularity:** Service thuật toán ôn tập không phụ thuộc chặt vào tầng HTTP.
