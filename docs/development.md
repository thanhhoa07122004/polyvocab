# Chạy dự án trên Windows

## Yêu cầu hệ thống
- PHP 8.2 trở lên, tương thích Laravel 12.
- Composer.
- Node.js và npm.
- MariaDB hoặc MySQL.

Bật database trong XAMPP.

## Các lệnh chạy môi trường phát triển
Mở **Terminal 1**:
```powershell
php artisan serve
```

Mở **Terminal 2**:
```powershell
npm.cmd run dev
```

Địa chỉ truy cập:
`http://127.0.0.1:8000`

## Ghi chú thêm
- Chạy migration: `php artisan migrate`
- Xóa cache cấu hình: `php artisan config:clear`
- Build assets cho production: `npm run build`
