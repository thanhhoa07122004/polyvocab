# Hướng dẫn Triển khai & Vận hành (Deployment & Operations)

## 1. Yêu cầu máy chủ sản xuất (Production Server)
- Nginx hoặc Apache.
- PHP 8.2+ với các extension: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, PDO_MYSQL, Tokenizer, XML.
- MariaDB 10.6+ hoặc MySQL 8.0+.
- Composer và Node.js.
- Redis (khuyến nghị cho Cache và Queue).

## 2. Quy trình triển khai
1. Clone mã nguồn từ repository.
2. Thiết lập `.env` cho môi trường production:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - Cấu hình kết nối DB, Redis, Mail.
3. Cài đặt dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```
4. Chạy migrations:
   ```bash
   php artisan migrate --force
   ```
5. Tối ưu bộ đệm (Caching):
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## 3. Quản lý Queue & Scheduler
- **Scheduler:** Thêm cron job trên server để chạy hàng phút:
  ```cron
  * * * * * cd /path-to-polyvocab && php artisan schedule:run >> /dev/null 2>&1
  ```
- **Queue Worker:** Dùng Supervisor để giám sát tiến trình xử lý job nền:
  ```ini
  [program:polyvocab-worker]
  process_name=%(program_name)s_%(process_num)02d
  command=php /path-to-polyvocab/artisan queue:work --sleep=3 --tries=3
  autostart=true
  autorestart=true
  numprocs=2
  ```

## 4. Chiến lược Sao lưu (Backup)
- Sao lưu Database định kỳ hàng ngày.
- Lưu trữ bản backup tại dịch vụ lưu trữ an toàn tách biệt.
