# TìmTrẻ.vn — Deployment Runbook

## 1. Mục đích

Tài liệu này mô tả quy trình triển khai và vận hành hệ thống TìmTrẻ.vn trên môi trường production.

Runbook dùng để:

* Deploy lại hệ thống từ đầu trên VPS.
* Kiểm tra trạng thái các service.
* Restart Queue Worker và Laravel Reverb.
* Xem log khi hệ thống gặp lỗi.
* Thực hiện backup và kiểm tra backup.
* Rollback khi một lần deploy mới gây lỗi.
* Xử lý một số sự cố production thường gặp.

> Không ghi secret thật, password, APP_KEY, database password hoặc API key vào tài liệu này.

---

## 2. Thông tin hệ thống Production

### VPS

* Hệ điều hành: Ubuntu 24.04 LTS
* Nhà cung cấp VPS hiện tại: Google Cloud Compute Engine
* User vận hành: user non-root có quyền sudo
* Timezone: `Asia/Ho_Chi_Minh`

### Application

```text
Application path:
/var/www/timtre

Public web root:
/var/www/timtre/public

Production environment file:
/var/www/timtre/.env
```

### Stack

* Nginx
* PHP 8.2 + PHP-FPM
* MySQL 8
* Redis
* Composer
* Node.js 20+
* Supervisor
* Laravel Queue Worker
* Laravel Reverb

---

## 3. Secrets và file `.env`

File production:

```bash
/var/www/timtre/.env
```

File này:

* Không được commit lên Git.
* Không gửi vào group chat công khai.
* Không đưa giá trị thật vào tài liệu Runbook.

Các secret production như:

```text
APP_KEY
DB_PASSWORD
MAIL_PASSWORD
AI_SERVICE_URL
X_INTERNAL_KEY
FCM credentials
OCR/eKYC API keys
```

được lấy từ người phụ trách cấu hình production/Leader của nhóm.

Khi cần tạo lại môi trường, dùng:

```bash
cd /var/www/timtre
cp .env.example .env
nano .env
```

Sau khi điền đầy đủ biến môi trường:

```bash
php artisan config:clear
php artisan config:cache
```

---

# 4. Kiểm tra VPS trước khi deploy

SSH vào VPS:

```bash
ssh <USER>@<VPS_IP>
```

Kiểm tra timezone:

```bash
date
timedatectl
```

Timezone phải là:

```text
Asia/Ho_Chi_Minh
```

Nếu chưa đúng:

```bash
sudo timedatectl set-timezone Asia/Ho_Chi_Minh
```

---

## 4.1 Kiểm tra Firewall

```bash
sudo ufw status
```

Các port public cần mở:

```text
22/tcp    SSH
80/tcp    HTTP
443/tcp   HTTPS
```

Không mở MySQL hoặc Redis ra Internet.

Kiểm tra nhanh:

```bash
sudo ufw status numbered
```

---

## 4.2 Kiểm tra Fail2ban

```bash
sudo systemctl status fail2ban
```

Kiểm tra jail SSH:

```bash
sudo fail2ban-client status sshd
```

Fail2ban được cấu hình để chặn brute-force SSH sau nhiều lần đăng nhập sai.

---

## 4.3 Kiểm tra các thành phần bắt buộc

PHP:

```bash
php -v
```

PHP-FPM:

```bash
sudo systemctl status php8.2-fpm
```

MySQL:

```bash
mysql --version
```

Redis:

```bash
redis-cli ping
```

Kết quả mong đợi:

```text
PONG
```

Composer:

```bash
composer --version
```

Node.js:

```bash
node -v
npm -v
```

Python:

```bash
python3 --version
```

Nginx:

```bash
nginx -v
sudo systemctl status nginx
```

---

# 5. Deploy Laravel Production

## 5.1 Clone source code

Nếu server chưa có project:

```bash
cd /var/www
sudo git clone <REPOSITORY_URL> timtre
sudo chown -R $USER:www-data /var/www/timtre
cd /var/www/timtre
```

Nếu project đã tồn tại:

```bash
cd /var/www/timtre
git status
git pull origin main
```

---

## 5.2 Cài PHP dependencies

```bash
cd /var/www/timtre

composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction
```

---

## 5.3 Build frontend

```bash
npm install
npm run build
```

---

## 5.4 Cấu hình environment

Nếu là lần deploy đầu tiên:

```bash
cp .env.example .env
nano .env
```

Production bắt buộc:

```env
APP_ENV=production
APP_DEBUG=false
```

Không commit file `.env`.

---

## 5.5 Application key

Chỉ chạy khi tạo production environment mới và chưa có `APP_KEY`:

```bash
php artisan key:generate
```

Không chạy lại lệnh này tùy tiện trên production đang hoạt động vì có thể ảnh hưởng dữ liệu đã mã hóa.

---

## 5.6 Database migration

Kiểm tra kết nối DB trước:

```bash
php artisan migrate:status
```

Chạy migration:

```bash
php artisan migrate --force
```

`--force` là bắt buộc khi chạy migration trong production.

---

## 5.7 Quyền thư mục Laravel

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 5.8 Clear và tạo cache production

```bash
php artisan optimize:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

# 6. Nginx + PHP-FPM

File cấu hình Nginx:

```text
/etc/nginx/sites-available/timtre.conf
```

Kiểm tra syntax:

```bash
sudo nginx -t
```

Nếu kết quả OK:

```bash
sudo systemctl reload nginx
```

Kiểm tra:

```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
```

Application phải chạy bằng:

```text
Nginx + PHP-FPM
```

Không sử dụng:

```bash
php artisan serve
```

trên production.

---

# 7. SSL / HTTPS

Kiểm tra certificate:

```bash
sudo certbot certificates
```

Kiểm tra timer renew:

```bash
systemctl status certbot.timer
```

Test renew:

```bash
sudo certbot renew --dry-run
```

Kiểm tra redirect HTTP → HTTPS:

```bash
curl -I http://<DOMAIN>
```

Phải redirect sang HTTPS.

Kiểm tra HTTPS:

```bash
curl -I https://<DOMAIN>
```

Kiểm tra `.env` không truy cập public được:

```bash
curl -I https://<DOMAIN>/.env
```

Kết quả phải là:

```text
403
```

hoặc:

```text
404
```
# 8. Supervisor — Queue Worker & Laravel Reverb

Các process nền của Laravel được quản lý bằng Supervisor.

File cấu hình:

```text
/etc/supervisor/conf.d/timtre-worker.conf
/etc/supervisor/conf.d/timtre-reverb.conf
```

Sau khi sửa file Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
```

Kiểm tra trạng thái:

```bash
sudo supervisorctl status
```

Các process Queue Worker và Reverb phải ở trạng thái:

```text
RUNNING
```

---

## 8.1 Restart Queue Worker

Restart toàn bộ Queue Worker:

```bash
sudo supervisorctl restart timtre-worker:*
```

Nếu cấu hình chỉ có một process:

```bash
sudo supervisorctl restart timtre-worker
```

Kiểm tra lại:

```bash
sudo supervisorctl status
```

---

## 8.2 Restart Laravel Reverb

```bash
sudo supervisorctl restart timtre-reverb
```

Kiểm tra:

```bash
sudo supervisorctl status timtre-reverb
```

---

## 8.3 Restart toàn bộ Supervisor process của TìmTrẻ.vn

```bash
sudo supervisorctl restart all
```

Chỉ dùng lệnh này khi cần restart toàn bộ process Supervisor trên VPS.

---

## 8.4 Xem log Queue Worker

```bash
cd /var/www/timtre
tail -f storage/logs/worker.log
```

Nếu cần xem Laravel log chính:

```bash
tail -f storage/logs/laravel.log
```

---

## 8.5 Xem log Reverb

```bash
cd /var/www/timtre
tail -f storage/logs/reverb.log
```

Thoát chế độ theo dõi log:

```text
Ctrl + C
```

---

# 9. Backup Database & Storage

Script backup:

```text
/var/www/timtre/scripts/backup.sh
```

Cron backup:

```text
/etc/cron.d/timtre-backup
```

Backup được cấu hình chạy tự động hàng ngày vào khoảng 02:00 sáng.

---

## 9.1 Chạy backup thủ công

```bash
cd /var/www/timtre
bash scripts/backup.sh
```

Sau khi chạy, kiểm tra thư mục backup được cấu hình trong script:

```bash
ls -lah /var/backups/timtre
```

Nếu đường dẫn thực tế trong `backup.sh` khác, sử dụng đúng đường dẫn khai báo trong script.

Backup hợp lệ phải có:

```text
Database backup (.sql.gz)
Storage backup (.tar.gz hoặc định dạng nén tương đương)
```

---

## 9.2 Kiểm tra Cron

Xem cấu hình:

```bash
sudo cat /etc/cron.d/timtre-backup
```

Kiểm tra cron service:

```bash
sudo systemctl status cron
```

Xem log liên quan cron:

```bash
sudo grep CRON /var/log/syslog | tail -50
```

Nếu script sử dụng file log riêng thì xem file log được khai báo trong `backup.sh`.

---

## 9.3 Restore Database

Không restore trực tiếp vào database production khi chưa xác nhận file backup hợp lệ.

Giải nén:

```bash
gunzip -c <BACKUP_FILE.sql.gz> > restore.sql
```

Restore vào database test:

```bash
mysql -u <DB_USER> -p <TEST_DATABASE> < restore.sql
```

Sau đó kiểm tra dữ liệu:

```bash
mysql -u <DB_USER> -p <TEST_DATABASE>
```

---

# 10. Quy trình Deploy phiên bản mới

Khi code mới đã được review và merge vào branch production:

```bash
cd /var/www/timtre
```

Kiểm tra trạng thái:

```bash
git status
```

Lấy code mới:

```bash
git pull origin main
```

Cài dependency production:

```bash
composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction
```

Build frontend:

```bash
npm install
npm run build
```

Chạy migration:

```bash
php artisan migrate --force
```

Clear cache cũ:

```bash
php artisan optimize:clear
```

Tạo cache production:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Restart Queue Worker:

```bash
php artisan queue:restart
```

Sau đó kiểm tra Supervisor:

```bash
sudo supervisorctl status
```

Reload Nginx nếu có thay đổi cấu hình:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Kiểm tra website:

```bash
curl -I https://<DOMAIN>
```

---

# 11. Kiểm tra sau Deploy

Sau mỗi lần deploy cần kiểm tra:

```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo supervisorctl status
redis-cli ping
```

Kiểm tra Laravel:

```bash
cd /var/www/timtre
php artisan about
```

Kiểm tra route:

```bash
php artisan route:list
```

Kiểm tra migration:

```bash
php artisan migrate:status
```

Kiểm tra website:

```bash
curl -I https://<DOMAIN>
```

Kiểm tra log:

```bash
tail -n 100 storage/logs/laravel.log
```

---

# 12. Xem Log khi Production gặp lỗi

## Laravel

```bash
cd /var/www/timtre
tail -f storage/logs/laravel.log
```

## Nginx Access Log

```bash
sudo tail -f /var/log/nginx/access.log
```

## Nginx Error Log

```bash
sudo tail -f /var/log/nginx/error.log
```

## PHP-FPM

```bash
sudo journalctl -u php8.2-fpm -n 100 --no-pager
```

Theo dõi realtime:

```bash
sudo journalctl -u php8.2-fpm -f
```

## Supervisor

```bash
sudo supervisorctl status
```

## MySQL

```bash
sudo journalctl -u mysql -n 100 --no-pager
```

## Redis

```bash
sudo journalctl -u redis-server -n 100 --no-pager
```

---

# 13. Rollback khi Deploy lỗi

Nếu phiên bản mới gây lỗi nghiêm trọng, rollback về commit trước.

## 13.1 Xác định commit ổn định

```bash
cd /var/www/timtre
git log --oneline -10
```

Ghi lại commit hiện tại:

```bash
git rev-parse HEAD
```

---

## 13.2 Checkout commit ổn định trước đó

```bash
git checkout <GOOD_COMMIT_HASH>
```

Cài lại dependency theo commit đó:

```bash
composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction
```

Build frontend:

```bash
npm install
npm run build
```

Clear và rebuild cache:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Restart Queue:

```bash
php artisan queue:restart
```

Kiểm tra Supervisor:

```bash
sudo supervisorctl status
```

Kiểm tra website:

```bash
curl -I https://<DOMAIN>
```

> Nếu lần deploy lỗi có migration thay đổi database, không tự ý chạy `migrate:rollback` trên production khi chưa kiểm tra migration đó có làm mất dữ liệu hay không.

---

# 14. Troubleshooting thường gặp

## 14.1 Queue Worker không chạy

### Triệu chứng

Job không được xử lý, notification/email/AI job bị pending.

Kiểm tra:

```bash
sudo supervisorctl status
```

Nếu worker ở trạng thái STOPPED/FATAL:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart timtre-worker:*
```

Xem log:

```bash
cd /var/www/timtre
tail -n 100 storage/logs/worker.log
tail -n 100 storage/logs/laravel.log
```

Kiểm tra Redis:

```bash
redis-cli ping
```

Phải trả về:

```text
PONG
```

Kiểm tra queue config:

```bash
php artisan config:show queue
```

Sau khi sửa `.env`:

```bash
php artisan config:clear
php artisan config:cache
sudo supervisorctl restart timtre-worker:*
```

---

## 14.2 SSL / HTTPS gặp lỗi hoặc certificate gần hết hạn

Kiểm tra certificate:

```bash
sudo certbot certificates
```

Test auto-renew:

```bash
sudo certbot renew --dry-run
```

Kiểm tra Certbot timer:

```bash
systemctl status certbot.timer
```

Nếu certificate cần renew:

```bash
sudo certbot renew
sudo nginx -t
sudo systemctl reload nginx
```

Nếu SSL vẫn lỗi, kiểm tra:

```bash
sudo nginx -t
sudo tail -n 100 /var/log/nginx/error.log
```

Đồng thời kiểm tra DNS domain có còn trỏ đúng VPS hay không.

---

## 14.3 Laravel Reverb không hoạt động

Kiểm tra Supervisor:

```bash
sudo supervisorctl status timtre-reverb
```

Restart:

```bash
sudo supervisorctl restart timtre-reverb
```

Xem log:

```bash
cd /var/www/timtre
tail -n 100 storage/logs/reverb.log
tail -n 100 storage/logs/laravel.log
```

Nếu vừa thay đổi `.env`:

```bash
php artisan config:clear
php artisan config:cache
sudo supervisorctl restart timtre-reverb
```

---

## 14.4 Website trả lỗi 500

Xem Laravel log:

```bash
cd /var/www/timtre
tail -n 100 storage/logs/laravel.log
```

Xem Nginx:

```bash
sudo tail -n 100 /var/log/nginx/error.log
```

Kiểm tra quyền:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

Clear cache:

```bash
php artisan optimize:clear
php artisan config:cache
```

Kiểm tra PHP-FPM:

```bash
sudo systemctl status php8.2-fpm
```

Restart nếu cần:

```bash
sudo systemctl restart php8.2-fpm
```

---

## 14.5 Database không kết nối được

Kiểm tra MySQL:

```bash
sudo systemctl status mysql
```

Kiểm tra trực tiếp:

```bash
mysql -u <DB_USER> -p
```

Kiểm tra `.env`:

```bash
cd /var/www/timtre
nano .env
```

Sau khi sửa:

```bash
php artisan config:clear
php artisan config:cache
```

Test Laravel:

```bash
php artisan migrate:status
```

---

## 14.6 Redis không hoạt động

```bash
redis-cli ping
```

Nếu không trả về `PONG`:

```bash
sudo systemctl status redis-server
sudo systemctl restart redis-server
redis-cli ping
```

Sau đó restart Queue Worker:

```bash
sudo supervisorctl restart timtre-worker:*
```

---

# 15. Checklist vận hành nhanh

Khi cần kiểm tra toàn hệ thống:

```bash
date
sudo ufw status
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status redis-server
sudo supervisorctl status
redis-cli ping
sudo certbot certificates
```

Kiểm tra ứng dụng:

```bash
cd /var/www/timtre

php artisan about
php artisan migrate:status
curl -I https://<DOMAIN>
```

---

# 16. Checklist xác nhận F-02

* [ ] Runbook mô tả được quá trình deploy lại hệ thống từ đầu.
* [ ] Có vị trí production `.env`.
* [ ] Không chứa giá trị secret thật.
* [ ] Có lệnh restart Queue Worker.
* [ ] Có lệnh restart Laravel Reverb.
* [ ] Có hướng dẫn xem Laravel/Nginx/PHP-FPM log.
* [ ] Có hướng dẫn backup Database + Storage.
* [ ] Có quy trình deploy phiên bản mới.
* [ ] Có quy trình rollback khi deploy lỗi.
* [ ] Có Troubleshooting Queue Worker.
* [ ] Có Troubleshooting SSL.
* [ ] Có Troubleshooting Reverb.
* [ ] Có thêm troubleshooting lỗi 500, Database và Redis.
* [ ] Một thành viên chưa từng deploy đã đọc và kiểm tra các lệnh trên VPS.

---

# 17. Xác nhận người review

Người review Runbook:

```text
Tên: ______________________
Ngày kiểm tra: ______________________
```

Các bước đã thử:

```text
[ ] SSH vào VPS
[ ] Kiểm tra Nginx
[ ] Kiểm tra PHP-FPM
[ ] Kiểm tra Supervisor
[ ] Kiểm tra Redis
[ ] Kiểm tra SSL
[ ] Kiểm tra log
```

Kết quả:

```text
[ ] PASS
[ ] Cần chỉnh sửa
```

Ghi chú:

```text
____________________________________________________
____________________________________________________
```
