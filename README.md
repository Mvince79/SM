# 🔍 Website Tìm Kiếm Trẻ Em Thất Lạc

Tích hợp AI Nhận Diện Khuôn Mặt và Bản Đồ Định Vị Vị Trí — Đồ án Tốt nghiệp FPT

**Stack:** PHP 8.3 · Laravel 13 · Filament v5 · Livewire 3 · MySQL 8.0 · Redis · Python FastAPI + DeepFace

---

## 📋 Yêu cầu hệ thống

Cài đặt các phần mềm sau trước khi bắt đầu:

| Phần mềm | Phiên bản tối thiểu | Tải về                                             |
| -------- | ------------------- | -------------------------------------------------- |
| PHP      | 8.3 (NTS x64)       | https://windows.php.net/download                   |
| Composer | 2.x                 | https://getcomposer.org/download                   |
| MySQL    | 8.0                 | https://dev.mysql.com/downloads/installer          |
| Redis    | 3.x (Windows)       | https://github.com/microsoftarchive/redis/releases |
| Git      | 2.x                 | https://git-scm.com/download/win                   |
| Node.js  | 20.x LTS            | https://nodejs.org (chỉ cần nếu build assets)      |

### PHP Extensions bắt buộc

Sau khi cài PHP, đảm bảo các extension sau được bật trong `php.ini`:

```ini
extension=curl
extension=redis        ; Cài từ https://windows.php.net/downloads/pecl/releases/redis/
extension=fileinfo
extension=mbstring
extension=mysqli
extension=pdo_mysql
extension=openssl
extension=gd
extension=zip
```

> ⚠️ **Extension `redis`:** Tải file `php_redis-x.x.x-8.3-nts-vs16-x64.zip` từ link trên,
> giải nén lấy `php_redis.dll`, copy vào thư mục `ext/` của PHP,
> rồi thêm `extension=redis` vào `php.ini`.

---

## ⚡ Cài đặt nhanh (dưới 10 phút)

### Bước 1: Clone repo

```bash
git clone https://github.com/<your-org>/SM.git
cd SM
```

### Bước 2: Cài dependencies

```bash
composer install
npm install
```

### Bước 3: Tạo file môi trường

```bash
cp .env.example .env
php artisan key:generate
```

### Bước 4: Cấu hình `.env`

Mở file `.env` vừa tạo, sửa các thông tin sau cho phù hợp với máy bạn:

```dotenv
# Database — đổi password theo MySQL của bạn
DB_DATABASE=missing_children_db
DB_USERNAME=root
DB_PASSWORD=your_mysql_password

# Google OAuth — lấy từ Google Cloud Console
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
```

> Các biến còn lại giữ nguyên mặc định là chạy được trên local.

### Bước 5: Tạo database

Mở MySQL Workbench hoặc chạy lệnh:

```bash
mysql -u root -p -e "CREATE DATABASE missing_children_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Bước 6: Chạy migration và seeder

```bash
php artisan migrate --seed
```

Kết quả mong đợi:

- ✅ 15 bảng được tạo
- ✅ 34 tỉnh thành được seed vào bảng `provinces`

### Bước 7: Biên dịch assets & Khởi động server

```bash
# Biên dịch assets (hoặc chạy 'npm run dev' trong lúc phát triển)
npm run build

# Khởi động máy chủ ảo
php artisan serve
```

Mở trình duyệt vào: **http://127.0.0.1:8000**

---

## ✅ Kiểm tra môi trường

Sau khi cài xong, chạy các lệnh sau để xác nhận mọi thứ hoạt động:

```bash
# 1. Kiểm tra kết nối MySQL
php artisan tinker
DB::connection()->getPdo();
# → Phải trả về object PDO, không có lỗi

# 2. Kiểm tra kết nối Redis
Cache::set('ping', 'pong');
Cache::get('ping');
# → Phải trả về "pong"

# 3. Kiểm tra số bảng sau migration
DB::select('SHOW TABLES');
# → Phải có đúng 15 bảng

# 4. Kiểm tra số tỉnh thành
DB::table('provinces')->count();
# → Phải trả về 34
```

---

## 🗂️ Cấu trúc thư mục chính

```
SM/
├── app/
│   ├── Http/Controllers/     # Controllers (Auth, CRUD, API)
│   ├── Models/               # Eloquent Models
│   ├── Providers/
│   │   └── Filament/
│   │       └── AdminPanelProvider.php   # Cấu hình Filament Admin
│   └── Notifications/        # Mail OTP, Push Notification
├── database/
│   ├── migrations/           # 15 file migration theo thứ tự
│   └── seeders/
│       └── ProvincesSeeder.php   # 34 tỉnh thành (hiệu lực từ 12/06/2025)
├── resources/views/          # Blade templates
├── routes/
│   └── web.php               # Routes chính
├── .env.example              # Template biến môi trường
└── README.md
```

---

## 🌿 Quy trình Git

### Nhánh

| Nhánh                    | Mục đích                                                  |
| ------------------------ | --------------------------------------------------------- |
| `main`                   | Production — chỉ merge cuối sprint, sau khi test đầy đủ   |
| `develop`                | Tích hợp — tất cả feature merge vào đây qua PR            |
| `feature/PB-XX-ten-ngan` | Một nhánh per task — ví dụ: `feature/PB-01-project-setup` |

### Workflow hàng ngày

```bash
# 1. Tạo nhánh mới từ develop
git checkout develop
git pull origin develop
git checkout -b feature/PB-XX-ten-task

# 2. Làm việc, commit thường xuyên
git add .
git commit -m "feat: mô tả ngắn gọn"

# 3. Push và tạo PR
git push origin feature/PB-XX-ten-task
# → Tạo Pull Request trên GitHub, target nhánh develop
# → Cần ít nhất 1 người review (Khang) trước khi merge
```

### Quy ước commit message

| Prefix      | Dùng khi                                                 |
| ----------- | -------------------------------------------------------- |
| `feat:`     | Tính năng mới — `feat: add OTP email verification`       |
| `fix:`      | Sửa lỗi — `fix: correct provinces.code CHAR length to 6` |
| `chore:`    | Cài đặt, cấu hình — `chore: install Laravel Socialite`   |
| `refactor:` | Tái cấu trúc, không thay đổi behavior                    |
| `docs:`     | Chỉ README, comment                                      |
| `test:`     | Thêm hoặc sửa test                                       |

---

## 🔧 Cấu hình MySQL bổ sung (Full-text Search)

Để tính năng tìm kiếm toàn văn tiếng Việt hoạt động đúng, cần cấu hình MySQL:

**Mở file `my.ini`** (thường ở `C:\ProgramData\MySQL\MySQL Server 8.0\my.ini`)

Thêm vào section `[mysqld]`:

```ini
[mysqld]
innodb_ft_min_token_size=2
```

Restart MySQL Service, rồi chạy lại:

```bash
php artisan migrate:fresh --seed
```

> ⚠️ Nếu không làm bước này, tính năng tìm kiếm ở Sprint 1 sẽ bị lỗi với từ tiếng Việt ngắn.

---

## 🔑 Cài đặt Google OAuth (PB-04)

1. Vào **Google Cloud Console** → https://console.cloud.google.com
2. Tạo project mới hoặc chọn project hiện tại
3. Vào **APIs & Services → Credentials → Create Credentials → OAuth 2.0 Client ID**
4. Application type: **Web application**
5. Authorized redirect URIs thêm: `http://127.0.0.1:8000/auth/google/callback`
6. Copy **Client ID** và **Client Secret** vào `.env`:

```dotenv
GOOGLE_CLIENT_ID=your_client_id_here
GOOGLE_CLIENT_SECRET=your_client_secret_here
```

---

## 🤖 Cài đặt AI Microservice (Sprint 3 — chỉ cần cho Thanh)

> Phần này chỉ cần thiết khi làm Sprint 3. Sprint 0–2 không cần.

```bash
# Yêu cầu Python 3.11
python --version

# Cài DeepFace
pip install deepface fastapi uvicorn

# Test thử nhận diện 1 ảnh (CPU mode)
python -c "from deepface import DeepFace; print(DeepFace.analyze('test.jpg', actions=['age']))"
```

> ⚠️ ArcFace model cần ~2GB RAM. Ghi lại mức RAM sử dụng để báo cáo trước Sprint 5 (deploy VPS).

---

## 📱 Cài đặt Mailtrap (test gửi email OTP)

1. Đăng ký tài khoản miễn phí tại https://mailtrap.io
2. Vào **Email Testing → Inboxes → SMTP Settings**
3. Cập nhật `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
```

> Tất cả thành viên dùng chung 1 account Mailtrap — Khang tạo và share credentials qua chat nhóm.

---

## 🚨 Xử lý sự cố thường gặp

### `Class "Redis" not found`

Extension phpredis chưa được bật. Xem mục **PHP Extensions bắt buộc** ở trên.

### `SQLSTATE[HY000] [1045] Access denied for user 'root'`

Sai password MySQL. Sửa `DB_PASSWORD` trong `.env`.

### `php artisan migrate` lỗi FK constraint

Chạy theo đúng thứ tự migration. Nếu bị lỗi giữa chừng:

```bash
php artisan migrate:fresh --seed
```

### `Target class [App\Http\Controllers\...] does not exist`

Chạy:

```bash
composer dump-autoload
```

### `Vite manifest not found`

```bash
npm install
npm run dev
```

Hoặc trong `.env` đặt `APP_ENV=local` (Vite chỉ cần thiết khi dùng Blade với Vite assets).

---

## 👥 Nhóm

| Thành viên | Vai trò                    | Liên hệ                       |
| ---------- | -------------------------- | ----------------------------- |
| **Khang**  | Trưởng nhóm · Kiến trúc sư | Auth, DB Schema, Code Review  |
| **Linh**   | Full-stack · UI            | Blade/Livewire, Forms, Search |
| **Thanh**  | Backend · AI               | Queue, Jobs, AI Pipeline      |
| **Nhân**   | Backend · Admin            | Filament, Map, Deploy         |

---

## 📅 Lịch Sprint

| Sprint       | Thời gian          | Mục tiêu chính                       |
| ------------ | ------------------ | ------------------------------------ |
| **Sprint 0** | 14/05 – 20/05/2026 | Môi trường · Migration · Auth        |
| Sprint 1     | 21/05 – 03/06/2026 | CRUD tin đăng · Tìm kiếm · Bản đồ    |
| Sprint 2     | 04/06 – 17/06/2026 | Tương tác cộng đồng · Admin          |
| Sprint 3     | 18/06 – 01/07/2026 | AI nhận diện · Queue · Realtime      |
| Sprint 4     | 02/07 – 15/07/2026 | AI dự đoán tuổi · Geo-Push · Chatbot |
| Sprint 5     | 16/07 – 29/07/2026 | Bảo mật · Tối ưu · Deploy VPS        |
| Sprint 6     | 30/07 – 05/08/2026 | Demo · Sửa lỗi · Bảo vệ đồ án        |

---

_Tài liệu nội bộ — Website Tìm Kiếm Trẻ Em Thất Lạc · Sprint 0 v1.0_
