# THIẾT KẾ CƠ SỞ DỮ LIỆU
## Website Tìm Kiếm Trẻ Em Thất Lạc — Tích hợp AI Nhận Diện Khuôn Mặt & Bản Đồ Định Vị Vị Trí — v2.4 (Production-Ready)

**Stack:** MySQL 8.0 · Laravel 12 · Chuẩn: ISO/IEC 9075, Silicon Valley best practices, thực tế Việt Nam  
**Ngày:** 01/06/2026 · Nhóm: Khang · Linh · Thanh · Nhân  
**Ghi chú v2.4 (so với v2.3):**
- ✅ Bổ sung 4 cột SEO vào bảng `missing_children`: `slug`, `meta_title`, `meta_description`, `og_image_url`
- ✅ Bổ sung 2 cột còn thiếu vào bảng `clues`: `is_hidden`, `verified_at`
- ✅ Cập nhật Indexes bảng `missing_children`: thêm `UNIQUE INDEX uq_missing_slug (slug)`
- ✅ Cập nhật Checklist bảo mật: thêm mục SEO & slug
- ✅ Cập nhật Lịch sử phiên bản

---

## 1. NAMING CONVENTIONS

| Quy tắc | Ví dụ |
|---|---|
| Tên bảng: `snake_case`, số nhiều | `missing_children`, `face_match_results` |
| Tên cột: `snake_case` | `full_name`, `missing_date` |
| FK: `{table_singular}_id` | `user_id`, `province_id` |
| PK: luôn là `id` BIGINT UNSIGNED AUTO_INCREMENT | `id` |
| Timestamps chuẩn Laravel | `created_at`, `updated_at`, `deleted_at` |
| Boolean: prefix `is_` hoặc `has_` | `is_approved`, `has_face` |
| Enum string: uppercase | `'MALE'`, `'PENDING'` |

---

## 2. SƠ ĐỒ QUAN HỆ (ERD — Text)

```
provinces (34 tỉnh/thành — hiệu lực 12/06/2025)
    ↑ n:1
users ──────────────────────────────────────────────────────┐
    │ 1:n                                                     │
    ├── missing_children ──────── face_match_results ──── found_children
    │       │ 1:n                        │                    │ 1:n
    │       ├── age_progression_photos   │                    └── photos (polymorphic)
    │       ├── photos (polymorphic)     │
    │       ├── comments (polymorphic)   │
    │       └── clues                   │
    │                                   │
    ├── found_children                  │
    ├── messages (from/to)              │
    ├── notifications                  │
    ├── chatbot_sessions               │
    └── fcm_tokens ──────────────────────┘
                                        │
activity_logs (Spatie — polymorphic) ───┘
```

---

## 3. CHI TIẾT CÁC BẢNG

---

### 3.1. `provinces` — Tỉnh / Thành phố

> **34 đơn vị hành chính cấp tỉnh sau sáp nhập, hiệu lực 12/06/2025**  
> Nguồn: Nghị quyết của Quốc hội về sắp xếp đơn vị hành chính 2025

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | TINYINT UNSIGNED | — | NO | AUTO_INCREMENT | PK, 34 tỉnh < 255 |
| `name` | VARCHAR | 100 | NO | — | Tên đầy đủ tiếng Việt |
| `code` | CHAR | 6 | NO | — | Mã ISO 3166-2:VN (VD: `VN-HN`, `VN-DNA`) — tăng lên 6 để chứa mã 5 ký tự |
| `short_code` | CHAR | 3 | NO | — | Mã ngắn dùng nội bộ (VD: `HAN`) |
| `region` | ENUM | — | NO | — | `'BAC'`, `'TRUNG'`, `'NAM'` |
| `is_active` | TINYINT(1) | — | NO | `1` | Dự phòng nếu có sáp nhập tiếp |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |

**Indexes:**
```sql
PRIMARY KEY (id)
UNIQUE INDEX uq_provinces_code (code)
UNIQUE INDEX uq_provinces_short_code (short_code)
INDEX idx_provinces_region (region)
```

**Seed data — 34 tỉnh/thành (hiệu lực 12/06/2025):**

> **Chú thích cột `merged_from`:** Ghi chú các tỉnh thành phần trước khi sáp nhập — không lưu trong DB, chỉ để tham chiếu.

| id | name | code | short_code | region | merged_from (tham chiếu) |
|---|---|---|---|---|---|
| 1 | Hà Nội | VN-HN | HAN | BAC | Hà Nội (giữ nguyên) |
| 2 | TP Huế | VN-HUE | HUE | TRUNG | Thừa Thiên Huế (đổi tên) |
| 3 | Quảng Ninh | VN-QN | QNI | BAC | Quảng Ninh (giữ nguyên) |
| 4 | Cao Bằng | VN-CB | CBA | BAC | Cao Bằng (giữ nguyên) |
| 5 | Lạng Sơn | VN-LSN | LSN | BAC | Lạng Sơn (giữ nguyên) |
| 6 | Lai Châu | VN-LCH | LCH | BAC | Lai Châu (giữ nguyên) |
| 7 | Điện Biên | VN-DB | DBN | BAC | Điện Biên (giữ nguyên) |
| 8 | Sơn La | VN-SL | SLA | BAC | Sơn La (giữ nguyên) |
| 9 | Thanh Hóa | VN-TH | THA | TRUNG | Thanh Hóa (giữ nguyên) |
| 10 | Nghệ An | VN-NA | NAN | TRUNG | Nghệ An (giữ nguyên) |
| 11 | Hà Tĩnh | VN-HT | HTI | TRUNG | Hà Tĩnh (giữ nguyên) |
| 12 | Tuyên Quang | VN-TQ | TQG | BAC | Hà Giang + Tuyên Quang |
| 13 | Lào Cai | VN-LC | LCA | BAC | Lào Cai + Yên Bái |
| 14 | Thái Nguyên | VN-TN | TNG | BAC | Thái Nguyên + Bắc Kạn |
| 15 | Phú Thọ | VN-PT | PTH | BAC | Hòa Bình + Vĩnh Phúc + Phú Thọ |
| 16 | Bắc Ninh | VN-BN | BNI | BAC | Bắc Ninh + Bắc Giang |
| 17 | Hưng Yên | VN-HY | HYE | BAC | Hưng Yên + Thái Bình |
| 18 | TP Hải Phòng | VN-HP | HPG | BAC | TP Hải Phòng + Hải Dương |
| 19 | Ninh Bình | VN-NB | NBH | BAC | Hà Nam + Nam Định + Ninh Bình |
| 20 | Quảng Trị | VN-QT | QTR | TRUNG | Quảng Bình + Quảng Trị |
| 21 | TP Đà Nẵng | VN-DN | DAN | TRUNG | Quảng Nam + TP Đà Nẵng |
| 22 | Quảng Ngãi | VN-QNG | QNG | TRUNG | Kon Tum + Quảng Ngãi |
| 23 | Gia Lai | VN-GL | GLA | TRUNG | Gia Lai + Bình Định |
| 24 | Khánh Hòa | VN-KH | KHA | TRUNG | Ninh Thuận + Khánh Hòa |
| 25 | Lâm Đồng | VN-LD | LDO | NAM | Đắk Nông + Bình Thuận + Lâm Đồng |
| 26 | Đắk Lắk | VN-DL | DLK | TRUNG | Phú Yên + Đắk Lắk |
| 27 | TP Hồ Chí Minh | VN-HC | HCM | NAM | Bà Rịa-Vũng Tàu + Bình Dương + TP HCM |
| 28 | Đồng Nai | VN-DNA | DNA | NAM | Bình Phước + Đồng Nai |
| 29 | Tây Ninh | VN-TAY | TAY | NAM | Tây Ninh + Long An |
| 30 | TP Cần Thơ | VN-CT | CTO | NAM | Sóc Trăng + Hậu Giang + TP Cần Thơ |
| 31 | Vĩnh Long | VN-VL | VLG | NAM | Bến Tre + Vĩnh Long + Trà Vinh |
| 32 | Đồng Tháp | VN-DTP | DTP | NAM | Tiền Giang + Đồng Tháp |
| 33 | Cà Mau | VN-CM | CMU | NAM | Bạc Liêu + Cà Mau |
| 34 | An Giang | VN-AG | AGI | NAM | Kiên Giang + An Giang |

> **Lưu ý v2.2:**
> - `VN-DN` = TP Đà Nẵng (id=21), `VN-DNA` = Đồng Nai (id=28) — **không còn trùng**, đã loại bỏ `VN-DN2`.
> - Cột `code` nâng từ `CHAR(5)` lên `CHAR(6)` để chứa mã 5 ký tự như `VN-DNA`, `VN-TAY`, `VN-HUE`, `VN-DTP`.
> - `short_code` vẫn `CHAR(3)` — toàn bộ 34 giá trị đã kiểm tra không trùng.
> - Ninh Bình (id=19) = sáp nhập Hà Nam + Nam Định + Ninh Bình → vùng `BAC` (hành chính + địa lý đúng).

---

### 3.2. `users` — Tài khoản người dùng

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `full_name` | VARCHAR | 100 | NO | — | Họ tên đầy đủ |
| `email` | VARCHAR | 191 | NO | — | UNIQUE — 191 vì utf8mb4 index limit |
| `email_verified_at` | TIMESTAMP | — | YES | NULL | NULL = chưa xác thực |
| `phone` | VARCHAR | 15 | YES | NULL | Định dạng E.164: `+84912345678` |
| `phone_verified_at` | TIMESTAMP | — | YES | NULL | Dự phòng xác thực SĐT sau |
| `password` | VARCHAR | 255 | YES | NULL | NULL nếu đăng nhập Google OAuth |
| `google_id` | VARCHAR | 100 | YES | NULL | Google OAuth sub |
| `avatar_url` | VARCHAR | 500 | YES | NULL | URL ảnh đại diện |
| `role` | ENUM | — | NO | `'USER'` | `'ADMIN'`, `'MODERATOR'`, `'USER'` |
| `status` | ENUM | — | NO | `'ACTIVE'` | `'ACTIVE'`, `'BANNED'`, `'PENDING'` |
| `ban_reason` | TEXT | — | YES | NULL | Lý do khóa tài khoản |
| `province_id` | TINYINT UNSIGNED | — | YES | NULL | FK → provinces.id |
| `notify_lat` | DECIMAL | (10,7) | YES | NULL | Vĩ độ nhận thông báo |
| `notify_lng` | DECIMAL | (10,7) | YES | NULL | Kinh độ nhận thông báo |
| `notify_radius_km` | TINYINT UNSIGNED | — | NO | `50` | Bán kính (km): 10/30/50/100 |
| `otp_code` | VARCHAR | **255** | YES | NULL | Bcrypt hash của OTP 6 chữ số — hash dài ~60 ký tự, cần VARCHAR(255) |
| `otp_expires_at` | TIMESTAMP | — | YES | NULL | Thời hạn OTP |
| `remember_token` | VARCHAR | 100 | YES | NULL | Laravel remember me |
| `last_active_at` | TIMESTAMP | — | YES | NULL | Lần cuối online |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |
| `deleted_at` | TIMESTAMP | — | YES | NULL | Soft delete (GDPR Art.17) |

**Indexes:**
```sql
PRIMARY KEY (id)
UNIQUE INDEX uq_users_email (email)
UNIQUE INDEX uq_users_google_id (google_id)
INDEX idx_users_role (role)
INDEX idx_users_status (status)
INDEX idx_users_province_id (province_id)
INDEX idx_users_notify_location (notify_lat, notify_lng)
INDEX idx_users_deleted_at (deleted_at)
```

**Foreign Keys:**
```sql
CONSTRAINT fk_users_province FOREIGN KEY (province_id)
    REFERENCES provinces(id) ON DELETE SET NULL ON UPDATE CASCADE
```

> **Bảo mật OTP (fix v2.2):** `otp_code` lưu dạng bcrypt hash (`Hash::make()`), **không lưu plain text**. So sánh bằng `Hash::check()`. Bcrypt output dài ~60 ký tự → `VARCHAR(255)` để có buffer an toàn cho các thuật toán hash khác trong tương lai (Argon2 output có thể dài hơn).

---

### 3.3. `fcm_tokens` — Push Notification Token

> Lưu FCM token (Firebase Cloud Messaging) cho browser push & mobile PWA.  
> Tách ra bảng riêng vì 1 user có thể dùng nhiều thiết bị.

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `user_id` | BIGINT UNSIGNED | — | NO | — | FK → users.id |
| `token` | VARCHAR | 500 | NO | — | FCM device token |
| `device_type` | ENUM | — | NO | `'WEB'` | `'WEB'`, `'ANDROID'`, `'IOS'` |
| `device_name` | VARCHAR | 100 | YES | NULL | VD: `'Chrome on Windows'` |
| `is_active` | TINYINT(1) | — | NO | `1` | Token còn hiệu lực |
| `last_used_at` | TIMESTAMP | — | YES | NULL | Lần cuối gửi notification thành công |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |

**Indexes:**
```sql
PRIMARY KEY (id)
UNIQUE INDEX uq_fcm_token (token)
INDEX idx_fcm_user_id (user_id)
INDEX idx_fcm_is_active (is_active)
```

**Foreign Keys:**
```sql
CONSTRAINT fk_fcm_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
```

---

### 3.4. `missing_children` — Tin đăng trẻ mất tích

> **v2.4:** Bổ sung 4 cột SEO: `slug`, `meta_title`, `meta_description`, `og_image_url`

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `user_id` | BIGINT UNSIGNED | — | NO | — | FK → users.id |
| `case_code` | VARCHAR | 20 | YES | NULL | Mã hồ sơ: `MC-2026-00001` (auto-gen) |
| `slug` | VARCHAR | 200 | YES | NULL | **[v2.4 — SEO]** URL-friendly: `nguyen-van-an-mc-2026-00001` — auto-gen từ `full_name` + `case_code` |
| `full_name` | VARCHAR | 100 | NO | — | Tên trẻ |
| `dob` | DATE | — | YES | NULL | Ngày sinh (nếu biết) |
| `age_estimate` | TINYINT UNSIGNED | — | YES | NULL | Tuổi ước tính (nếu không rõ ngày sinh) |
| `gender` | ENUM | — | NO | — | `'MALE'`, `'FEMALE'`, `'UNKNOWN'` |
| `skin_tone` | ENUM | — | YES | NULL | `'LIGHT'`, `'MEDIUM'`, `'DARK'` |
| `height_cm` | SMALLINT UNSIGNED | — | YES | NULL | Chiều cao (cm), VD: 120 |
| `weight_kg` | DECIMAL | (5,2) | YES | NULL | Cân nặng (kg), VD: 25.50 |
| `province_id` | TINYINT UNSIGNED | — | NO | — | FK → provinces.id |
| `district` | VARCHAR | 100 | YES | NULL | Quận/Huyện nơi mất tích |
| `ward` | VARCHAR | 100 | YES | NULL | Phường/Xã nơi mất tích |
| `address_detail` | VARCHAR | 500 | YES | NULL | Địa chỉ cụ thể |
| `lat` | DECIMAL | (10,7) | YES | NULL | Vĩ độ vị trí mất tích |
| `lng` | DECIMAL | (10,7) | YES | NULL | Kinh độ vị trí mất tích |
| `missing_date` | DATE | — | NO | — | Ngày mất tích |
| `missing_time` | TIME | — | YES | NULL | Giờ mất tích (nếu biết) |
| `clothes_desc` | TEXT | — | YES | NULL | Mô tả quần áo lúc mất tích |
| `clothes_color` | VARCHAR | 100 | YES | NULL | Màu sắc quần áo chính |
| `special_marks` | TEXT | — | YES | NULL | Dấu vết đặc biệt (sẹo, bớt, xăm...) |
| `circumstances` | TEXT | — | YES | NULL | Hoàn cảnh mất tích |
| `reward_info` | VARCHAR | 300 | YES | NULL | Thông tin tiền thưởng (VD: "500.000đ") |
| `contact_name` | VARCHAR | 100 | NO | — | Tên người liên hệ |
| `contact_phone` | VARCHAR | 15 | NO | — | SĐT liên hệ — **KHÔNG trả về API public** |
| `contact_relation` | VARCHAR | 50 | YES | NULL | Quan hệ với trẻ (cha/mẹ/ông bà...) |
| `status` | ENUM | — | NO | `'PENDING'` | `'PENDING'`, `'APPROVED'`, `'FOUND'`, `'CLOSED'`, `'REJECTED'` |
| `approved_at` | TIMESTAMP | — | YES | NULL | Thời điểm admin duyệt |
| `approved_by` | BIGINT UNSIGNED | — | YES | NULL | FK → users.id (admin) |
| `rejection_reason` | TEXT | — | YES | NULL | Lý do từ chối |
| `found_at` | TIMESTAMP | — | YES | NULL | Thời điểm đánh dấu đã tìm thấy |
| `auto_expire_notified_at` | TIMESTAMP | — | YES | NULL | Đã gửi email cảnh báo xóa tự động |
| `qr_code_path` | VARCHAR | 500 | YES | NULL | Đường dẫn ảnh QR Code |
| `view_count` | INT UNSIGNED | — | NO | `0` | Lượt xem |
| `share_count` | INT UNSIGNED | — | NO | `0` | Lượt chia sẻ |
| `meta_title` | VARCHAR | 160 | YES | NULL | **[v2.4 — SEO]** Tiêu đề trang tối đa 160 ký tự — VD: `"Tìm kiếm bé Nguyễn Văn An, 8 tuổi, mất tích tại TP HCM"` |
| `meta_description` | TEXT | — | YES | NULL | **[v2.4 — SEO]** Mô tả trang cho Google/Facebook — tối đa 320 ký tự khuyến nghị |
| `og_image_url` | VARCHAR | 500 | YES | NULL | **[v2.4 — SEO]** URL ảnh Open Graph cho Facebook/Zalo share — fallback về `primary_photo` nếu NULL |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |
| `deleted_at` | TIMESTAMP | — | YES | NULL | Soft delete — GDPR Art.17 |

**Indexes:**
```sql
PRIMARY KEY (id)
UNIQUE INDEX uq_missing_case_code (case_code)
UNIQUE INDEX uq_missing_slug (slug)                          -- [v2.4] SEO slug unique
INDEX idx_missing_user_id (user_id)
INDEX idx_missing_province_id (province_id)
INDEX idx_missing_status (status)
INDEX idx_missing_gender (gender)
INDEX idx_missing_date (missing_date)
INDEX idx_missing_location (lat, lng)
INDEX idx_missing_deleted_at (deleted_at)
FULLTEXT INDEX ft_missing_search (full_name, clothes_desc, special_marks, circumstances)
```

**Foreign Keys:**
```sql
CONSTRAINT fk_missing_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE

CONSTRAINT fk_missing_province FOREIGN KEY (province_id)
    REFERENCES provinces(id) ON DELETE RESTRICT ON UPDATE CASCADE

CONSTRAINT fk_missing_approved_by FOREIGN KEY (approved_by)
    REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
```

> **Ghi chú thiết kế:**
> - `contact_phone` lưu trong DB nhưng **không trả về trong API public** — chỉ qua chat nội bộ sau khi login. Tuân thủ Nghị định 13/2023/NĐ-CP.
> - `ward` (phường/xã) bổ sung vì thực tế tìm kiếm ở VN thường dùng đến cấp phường.
> - `reward_info` — thực tế Việt Nam gia đình hay đăng kèm thông tin tiền thưởng; lưu dạng text tự do.
> - **[v2.4] `slug`** auto-gen bằng `Str::slug($full_name) . '-' . strtolower($case_code)` trong `MissingChild::booted()`. VD: `nguyen-van-an-mc-2026-00001`. Route chi tiết chuyển sang `/missing/{slug}` thay cho `/missing/{id}`.
> - **[v2.4] `meta_title`** nếu NULL → fallback: `"Tìm kiếm {full_name}, {age} tuổi | TimKiemTreEm"`.
> - **[v2.4] `og_image_url`** nếu NULL → fallback về `primary_photo.path_thumb` (public URL). Dùng cho thẻ `<meta property="og:image">` — quan trọng khi share lên Facebook/Zalo vì tỷ lệ click tăng đáng kể khi có ảnh preview.

---

### 3.5. `found_children` — Tin đăng tìm thấy trẻ

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `user_id` | BIGINT UNSIGNED | — | NO | — | FK → users.id (người báo tin) |
| `province_id` | TINYINT UNSIGNED | — | NO | — | FK → provinces.id |
| `district` | VARCHAR | 100 | YES | NULL | Quận/Huyện phát hiện |
| `ward` | VARCHAR | 100 | YES | NULL | Phường/Xã phát hiện |
| `address_detail` | VARCHAR | 500 | YES | NULL | Địa chỉ cụ thể |
| `lat` | DECIMAL | (10,7) | YES | NULL | Vĩ độ nơi phát hiện |
| `lng` | DECIMAL | (10,7) | YES | NULL | Kinh độ nơi phát hiện |
| `found_date` | DATE | — | NO | — | Ngày phát hiện |
| `found_time` | TIME | — | YES | NULL | Giờ phát hiện |
| `gender_estimate` | ENUM | — | YES | NULL | `'MALE'`, `'FEMALE'`, `'UNKNOWN'` |
| `age_estimate` | TINYINT UNSIGNED | — | YES | NULL | Tuổi ước tính |
| `condition_desc` | TEXT | — | YES | NULL | Tình trạng sức khỏe, tâm lý |
| `clothes_desc` | TEXT | — | YES | NULL | Mô tả quần áo |
| `special_marks` | TEXT | — | YES | NULL | Đặc điểm nhận dạng |
| `current_location` | VARCHAR | 300 | YES | NULL | Đang ở đâu (đồn PA, BV, mái ấm...) |
| `handover_agency` | VARCHAR | 200 | YES | NULL | Cơ quan đã bàn giao (CA phường, BV...) |
| `status` | ENUM | — | NO | `'PENDING'` | `'PENDING'`, `'APPROVED'`, `'MATCHED'`, `'CLOSED'` |
| `approved_at` | TIMESTAMP | — | YES | NULL | — |
| `approved_by` | BIGINT UNSIGNED | — | YES | NULL | FK → users.id (admin) |
| `ai_processed_at` | TIMESTAMP | — | YES | NULL | Thời điểm AI đã xử lý xong |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |
| `deleted_at` | TIMESTAMP | — | YES | NULL | Soft delete |

**Indexes:**
```sql
PRIMARY KEY (id)
INDEX idx_found_user_id (user_id)
INDEX idx_found_province_id (province_id)
INDEX idx_found_status (status)
INDEX idx_found_date (found_date)
INDEX idx_found_location (lat, lng)
FULLTEXT INDEX ft_found_search (condition_desc, clothes_desc, special_marks)
```

**Foreign Keys:**
```sql
CONSTRAINT fk_found_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE

CONSTRAINT fk_found_province FOREIGN KEY (province_id)
    REFERENCES provinces(id) ON DELETE RESTRICT ON UPDATE CASCADE

CONSTRAINT fk_found_approved_by FOREIGN KEY (approved_by)
    REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
```

> `handover_agency` — thực tế VN khi phát hiện trẻ thường bàn giao cho công an phường hoặc bệnh viện; thông tin này quan trọng để gia đình biết đến đâu nhận.

---

### 3.6. `photos` — Ảnh (Polymorphic)

> Dùng cho cả `missing_children` và `found_children`

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `model_type` | VARCHAR | 100 | NO | — | `'App\Models\MissingChild'` hoặc `'App\Models\FoundChild'` |
| `model_id` | BIGINT UNSIGNED | — | NO | — | ID của record tương ứng |
| `path_original` | VARCHAR | 500 | NO | — | Đường dẫn ảnh gốc (full resolution) — auth only |
| `path_thumb` | VARCHAR | 500 | YES | NULL | Ảnh thumbnail 200×200 (public) |
| `path_blurred` | VARCHAR | 500 | YES | NULL | Ảnh làm mờ (danh sách public, chưa đăng nhập) |
| `file_size` | INT UNSIGNED | — | YES | NULL | Kích thước file (bytes) |
| `mime_type` | VARCHAR | 50 | YES | NULL | `image/jpeg`, `image/png`, `image/webp` |
| `width` | SMALLINT UNSIGNED | — | YES | NULL | Chiều rộng ảnh gốc (px) |
| `height` | SMALLINT UNSIGNED | — | YES | NULL | Chiều cao ảnh gốc (px) |
| `has_face` | TINYINT(1) | — | NO | `0` | AI detect: có khuôn mặt không |
| `face_encoding` | TEXT | — | YES | NULL | JSON face vector — **ENCRYPTED AES-256** |
| `is_primary` | TINYINT(1) | — | NO | `0` | Ảnh đại diện chính của hồ sơ |
| `sort_order` | TINYINT UNSIGNED | — | NO | `0` | Thứ tự hiển thị |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |

**Indexes:**
```sql
PRIMARY KEY (id)
INDEX idx_photos_polymorphic (model_type, model_id)
INDEX idx_photos_has_face (has_face)
INDEX idx_photos_is_primary (is_primary)
```

> **Bảo mật:** `face_encoding` phải mã hóa bằng `Crypt::encryptString()` trước INSERT. Không bao giờ trả về qua API public. Giải mã chỉ tại AI service layer.  
> **Polymorphic:** Không có DB-level FK — cascade xóa được xử lý bằng Laravel Observer.

---

### 3.7. `face_match_results` — Kết quả AI Face Matching

> UNIQUE constraint cấp độ ảnh `(missing_photo_id, found_photo_id)` — không phải cấp hồ sơ — để cho phép chạy lại AI với cặp ảnh khác nhau trên cùng hồ sơ.

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `missing_id` | BIGINT UNSIGNED | — | NO | — | FK → missing_children.id |
| `found_id` | BIGINT UNSIGNED | — | NO | — | FK → found_children.id |
| `missing_photo_id` | BIGINT UNSIGNED | — | YES | NULL | FK → photos.id (ảnh missing dùng) |
| `found_photo_id` | BIGINT UNSIGNED | — | YES | NULL | FK → photos.id (ảnh found dùng) |
| `score` | DECIMAL | (5,4) | NO | — | Score 0.0000–1.0000 |
| `is_matched` | TINYINT(1) | — | NO | `0` | Score >= 0.70 → true |
| `source` | ENUM | — | NO | `'REALTIME'` | `'REALTIME'`, `'AGE_PROGRESSION'`, `'MANUAL'` |
| `model_used` | VARCHAR | 50 | YES | NULL | `'ArcFace'`, `'FaceNet'`, `'DeepFace'` |
| `status` | ENUM | — | NO | `'PENDING'` | `'PENDING'`, `'CONFIRMED'`, `'DISMISSED'` |
| `confirmed_by` | BIGINT UNSIGNED | — | YES | NULL | FK → users.id (admin xác nhận) |
| `confirmed_at` | TIMESTAMP | — | YES | NULL | — |
| `notified_at` | TIMESTAMP | — | YES | NULL | Đã gửi thông báo cho 2 người đăng |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |

**Indexes:**
```sql
PRIMARY KEY (id)
UNIQUE INDEX uq_face_match_photos (missing_photo_id, found_photo_id)
INDEX idx_face_match_missing_id (missing_id)
INDEX idx_face_match_found_id (found_id)
INDEX idx_face_match_score (score)
INDEX idx_face_match_is_matched (is_matched)
INDEX idx_face_match_status (status)
```

**Foreign Keys:**
```sql
CONSTRAINT fk_fmr_missing FOREIGN KEY (missing_id)
    REFERENCES missing_children(id) ON DELETE CASCADE ON UPDATE CASCADE

CONSTRAINT fk_fmr_found FOREIGN KEY (found_id)
    REFERENCES found_children(id) ON DELETE CASCADE ON UPDATE CASCADE

CONSTRAINT fk_fmr_missing_photo FOREIGN KEY (missing_photo_id)
    REFERENCES photos(id) ON DELETE SET NULL ON UPDATE CASCADE

CONSTRAINT fk_fmr_found_photo FOREIGN KEY (found_photo_id)
    REFERENCES photos(id) ON DELETE SET NULL ON UPDATE CASCADE

CONSTRAINT fk_fmr_confirmed_by FOREIGN KEY (confirmed_by)
    REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
```

---

### 3.8. `age_progression_photos` — Ảnh khuôn mặt theo tuổi (AI)

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `missing_id` | BIGINT UNSIGNED | — | NO | — | FK → missing_children.id |
| `original_photo_id` | BIGINT UNSIGNED | — | YES | NULL | FK → photos.id (ảnh gốc dùng) |
| `image_path` | VARCHAR | 500 | NO | — | Đường dẫn ảnh đã age progression |
| `age_at_missing` | TINYINT UNSIGNED | — | YES | NULL | Tuổi lúc mất tích |
| `estimated_age` | TINYINT UNSIGNED | — | NO | — | Tuổi ước tính trong ảnh mới |
| `years_elapsed` | TINYINT UNSIGNED | — | NO | — | Số năm đã trôi qua |
| `model_used` | VARCHAR | 100 | YES | NULL | `'FRAN'`, `'HRFAE'`, `'Replicate/fran'` |
| `api_provider` | VARCHAR | 50 | YES | NULL | `'replicate'`, `'local'`, `'opencv_fallback'` |
| `is_approved` | TINYINT(1) | — | NO | `0` | Admin đã duyệt ảnh |
| `approved_by` | BIGINT UNSIGNED | — | YES | NULL | FK → users.id |
| `generated_at` | TIMESTAMP | — | NO | — | Thời điểm AI tạo ảnh |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |

**Indexes:**
```sql
PRIMARY KEY (id)
INDEX idx_age_prog_missing_id (missing_id)
INDEX idx_age_prog_is_approved (is_approved)
INDEX idx_age_prog_generated_at (generated_at)
```

**Foreign Keys:**
```sql
CONSTRAINT fk_age_prog_missing FOREIGN KEY (missing_id)
    REFERENCES missing_children(id) ON DELETE CASCADE ON UPDATE CASCADE

CONSTRAINT fk_age_prog_photo FOREIGN KEY (original_photo_id)
    REFERENCES photos(id) ON DELETE SET NULL ON UPDATE CASCADE

CONSTRAINT fk_age_prog_approved_by FOREIGN KEY (approved_by)
    REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
```

---

### 3.9. `clues` — Manh mối cộng đồng

> **v2.4:** Bổ sung 2 cột còn thiếu so với migration thực tế: `is_hidden`, `verified_at`

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `missing_id` | BIGINT UNSIGNED | — | NO | — | FK → missing_children.id |
| `user_id` | BIGINT UNSIGNED | — | YES | NULL | FK → users.id (NULL = ẩn danh) |
| `clue_type` | ENUM | — | NO | `'SIGHTING'` | `'SIGHTING'`, `'RUMOR'`, `'PHOTO'`, `'VIDEO'`, `'OTHER'` |
| `description` | TEXT | — | NO | — | Nội dung manh mối |
| `lat` | DECIMAL | (10,7) | YES | NULL | Vĩ độ GPS khi gửi manh mối |
| `lng` | DECIMAL | (10,7) | YES | NULL | Kinh độ GPS khi gửi manh mối |
| `gps_shared_at` | TIMESTAMP | — | YES | NULL | Thời điểm người dùng bấm "Chia sẻ vị trí" — NULL = không share GPS |
| `location_accuracy_m` | SMALLINT UNSIGNED | — | YES | NULL | Độ chính xác GPS (mét) từ `position.coords.accuracy` |
| `occurred_at` | DATETIME | — | YES | NULL | Thời điểm xảy ra (nếu biết) |
| `image_path` | VARCHAR | 500 | YES | NULL | Ảnh kèm theo (nếu có) |
| `is_verified` | TINYINT(1) | — | NO | `0` | Admin đã xác minh |
| `verified_by` | BIGINT UNSIGNED | — | YES | NULL | FK → users.id (admin xác minh) |
| `verified_at` | TIMESTAMP | — | YES | NULL | **[v2.4]** Thời điểm admin xác minh — NULL = chưa xác minh |
| `is_anonymous` | TINYINT(1) | — | NO | `0` | Ẩn danh — không hiện tên user |
| `is_hidden` | TINYINT(1) | — | NO | `0` | **[v2.4]** Admin ẩn manh mối (spam/vi phạm) — không hiện trên trang public |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |

**Indexes:**
```sql
PRIMARY KEY (id)
INDEX idx_clues_missing_id (missing_id)
INDEX idx_clues_user_id (user_id)
INDEX idx_clues_clue_type (clue_type)
INDEX idx_clues_is_verified (is_verified)
INDEX idx_clues_location (lat, lng)
```

**Foreign Keys:**
```sql
CONSTRAINT fk_clues_missing FOREIGN KEY (missing_id)
    REFERENCES missing_children(id) ON DELETE CASCADE ON UPDATE CASCADE

CONSTRAINT fk_clues_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE

CONSTRAINT fk_clues_verified_by FOREIGN KEY (verified_by)
    REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
```

> **GPS Realtime (v2.3):** Khi người dùng gửi manh mối, frontend gọi `navigator.geolocation.getCurrentPosition()` và truyền `lat`, `lng`, `accuracy` lên server. Cột `gps_shared_at` đặt bằng `NOW()` để phân biệt manh mối có GPS với manh mối nhập tay.  
> **[v2.4] `is_hidden`:** Admin có thể ẩn manh mối spam mà không xóa hẳn — dữ liệu vẫn giữ để audit log. Tương tự `is_hidden` trong bảng `comments`.  
> **[v2.4] `verified_at`:** Timestamp khi admin click "Xác minh" — dùng để hiển thị "Xác minh lúc 10:30 15/05/2026" trên trang chi tiết, tăng độ tin cậy cho cộng đồng.

---

### 3.10. `comments` — Bình luận (Polymorphic + Self-referencing)

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `commentable_type` | VARCHAR | 100 | NO | — | `'App\Models\MissingChild'` hoặc `'App\Models\FoundChild'` |
| `commentable_id` | BIGINT UNSIGNED | — | NO | — | ID bài đăng |
| `user_id` | BIGINT UNSIGNED | — | NO | — | FK → users.id |
| `parent_id` | BIGINT UNSIGNED | — | YES | NULL | FK → comments.id (reply — self-ref) |
| `body` | TEXT | — | NO | — | Nội dung bình luận |
| `is_approved` | TINYINT(1) | — | NO | `1` | Mặc định duyệt ngay |
| `is_hidden` | TINYINT(1) | — | NO | `0` | Admin ẩn (spam/vi phạm) |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |
| `deleted_at` | TIMESTAMP | — | YES | NULL | Soft delete |

**Indexes:**
```sql
PRIMARY KEY (id)
INDEX idx_comments_polymorphic (commentable_type, commentable_id)
INDEX idx_comments_user_id (user_id)
INDEX idx_comments_parent_id (parent_id)
INDEX idx_comments_is_approved (is_approved)
```

**Foreign Keys:**
```sql
CONSTRAINT fk_comments_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE

CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id)
    REFERENCES comments(id) ON DELETE CASCADE ON UPDATE CASCADE
```

---

### 3.11. `messages` — Chat trực tiếp

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `from_user_id` | BIGINT UNSIGNED | — | NO | — | FK → users.id |
| `to_user_id` | BIGINT UNSIGNED | — | NO | — | FK → users.id |
| `missing_id` | BIGINT UNSIGNED | — | YES | NULL | FK → missing_children.id (ngữ cảnh) |
| `body` | TEXT | — | YES | NULL | Nội dung tin nhắn văn bản |
| `attachment_path` | VARCHAR | 500 | YES | NULL | Đường dẫn file đính kèm |
| `attachment_type` | ENUM | — | YES | NULL | `'IMAGE'`, `'VIDEO'`, `'FILE'` |
| `read_at` | TIMESTAMP | — | YES | NULL | NULL = chưa đọc |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |

**Check Constraint (DB-level — MySQL 8.0+):**
```sql
CONSTRAINT chk_messages_content
    CHECK (body IS NOT NULL OR attachment_path IS NOT NULL)
```

**Indexes:**
```sql
PRIMARY KEY (id)
INDEX idx_messages_from_user (from_user_id)
INDEX idx_messages_to_user (to_user_id)
INDEX idx_messages_missing_id (missing_id)
INDEX idx_messages_read_at (read_at)
INDEX idx_messages_conversation (from_user_id, to_user_id, missing_id)
```

**Foreign Keys:**
```sql
CONSTRAINT fk_messages_from FOREIGN KEY (from_user_id)
    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE

CONSTRAINT fk_messages_to FOREIGN KEY (to_user_id)
    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE

CONSTRAINT fk_messages_missing FOREIGN KEY (missing_id)
    REFERENCES missing_children(id) ON DELETE SET NULL ON UPDATE CASCADE
```

---

### 3.12. `chatbot_sessions` — Phiên Chatbot

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `session_token` | VARCHAR | 64 | NO | — | Token ẩn danh cho người chưa đăng nhập |
| `user_id` | BIGINT UNSIGNED | — | YES | NULL | FK → users.id (NULL nếu chưa login) |
| `intent` | ENUM | — | YES | NULL | `'REPORT_MISSING'`, `'REPORT_FOUND'`, `'SEARCH'`, `'GENERAL'` |
| `current_step` | VARCHAR | 50 | YES | NULL | Bước hiện tại trong flow |
| `collected_data` | JSON | — | YES | NULL | Dữ liệu đã thu thập |
| `status` | ENUM | — | NO | `'ACTIVE'` | `'ACTIVE'`, `'COMPLETED'`, `'ABANDONED'` |
| `completed_action` | VARCHAR | 100 | YES | NULL | Hành động cuối |
| `ip_address` | VARCHAR | 45 | YES | NULL | IP người dùng (IPv4/IPv6) |
| `user_agent` | VARCHAR | 300 | YES | NULL | Browser info |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |

**Indexes:**
```sql
PRIMARY KEY (id)
UNIQUE INDEX uq_chatbot_session_token (session_token)
INDEX idx_chatbot_user_id (user_id)
INDEX idx_chatbot_status (status)
INDEX idx_chatbot_created_at (created_at)
```

**Foreign Keys:**
```sql
CONSTRAINT fk_chatbot_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
```

---

### 3.13. `notifications` — Thông báo (Laravel default)

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | CHAR | 36 | NO | — | PK — UUID |
| `type` | VARCHAR | 255 | NO | — | Class notification đầy đủ |
| `notifiable_type` | VARCHAR | 255 | NO | — | `'App\Models\User'` |
| `notifiable_id` | BIGINT UNSIGNED | — | NO | — | FK → users.id |
| `data` | JSON | — | NO | — | Payload thông báo |
| `read_at` | TIMESTAMP | — | YES | NULL | NULL = chưa đọc |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |

**Indexes:**
```sql
PRIMARY KEY (id)
INDEX idx_notifications_notifiable (notifiable_type, notifiable_id)
INDEX idx_notifications_read_at (read_at)
```

---

### 3.14. `activity_logs` — Nhật ký hành động (Spatie Activity Log)

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `log_name` | VARCHAR | 255 | YES | `'default'` | `'admin'`, `'user'`, `'ai'` |
| `description` | TEXT | — | NO | — | Mô tả hành động |
| `subject_type` | VARCHAR | 255 | YES | NULL | Model bị tác động |
| `subject_id` | BIGINT UNSIGNED | — | YES | NULL | ID model bị tác động |
| `event` | VARCHAR | 255 | YES | NULL | `'created'`, `'updated'`, `'deleted'` |
| `causer_type` | VARCHAR | 255 | YES | NULL | `'App\Models\User'` |
| `causer_id` | BIGINT UNSIGNED | — | YES | NULL | FK → users.id |
| `properties` | JSON | — | YES | NULL | Dữ liệu trước/sau thay đổi (`old`, `attributes`) |
| `batch_uuid` | CHAR | 36 | YES | NULL | Nhóm actions liên quan |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |

---

### 3.15. `personal_access_tokens` — API Token (Laravel Sanctum)

| Cột | Kiểu | Độ dài | Null | Default | Ghi chú |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | — | NO | AUTO_INCREMENT | PK |
| `tokenable_type` | VARCHAR | 255 | NO | — | Polymorphic model |
| `tokenable_id` | BIGINT UNSIGNED | — | NO | — | Model ID |
| `name` | VARCHAR | 255 | NO | — | Tên token (VD: `'mobile-app'`) |
| `token` | VARCHAR | 64 | NO | — | UNIQUE — SHA-256 hash |
| `abilities` | TEXT | — | YES | NULL | JSON array abilities |
| `last_used_at` | TIMESTAMP | — | YES | NULL | — |
| `expires_at` | TIMESTAMP | — | YES | NULL | — |
| `created_at` | TIMESTAMP | — | YES | NULL | — |
| `updated_at` | TIMESTAMP | — | YES | NULL | — |

---

## 4. TỔNG QUAN MỐI QUAN HỆ (Relationship Map)

| Từ | Quan hệ | Đến | FK | ON DELETE | ON UPDATE |
|---|---|---|---|---|---|
| `users` | n:1 | `provinces` | `users.province_id` | SET NULL | CASCADE |
| `users` | 1:n | `missing_children` | `missing_children.user_id` | RESTRICT | CASCADE |
| `users` | 1:n | `found_children` | `found_children.user_id` | RESTRICT | CASCADE |
| `users` | 1:n | `fcm_tokens` | `fcm_tokens.user_id` | CASCADE | CASCADE |
| `users` | 1:n | `clues` | `clues.user_id` | SET NULL | CASCADE |
| `users` | 1:n | `clues` (xác minh) | `clues.verified_by` | SET NULL | CASCADE |
| `users` | 1:n | `comments` | `comments.user_id` | CASCADE | CASCADE |
| `users` | 1:n | `messages` (gửi) | `messages.from_user_id` | CASCADE | CASCADE |
| `users` | 1:n | `messages` (nhận) | `messages.to_user_id` | CASCADE | CASCADE |
| `users` | 1:n | `chatbot_sessions` | `chatbot_sessions.user_id` | SET NULL | CASCADE |
| `users` | 1:n | `face_match_results` (duyệt) | `face_match_results.confirmed_by` | SET NULL | CASCADE |
| `users` | 1:n | `age_progression_photos` (duyệt) | `age_progression_photos.approved_by` | SET NULL | CASCADE |
| `missing_children` | n:1 | `provinces` | `missing_children.province_id` | RESTRICT | CASCADE |
| `missing_children` | n:m | `found_children` | qua `face_match_results` | CASCADE | CASCADE |
| `missing_children` | 1:n | `age_progression_photos` | `age_progression_photos.missing_id` | CASCADE | CASCADE |
| `missing_children` | 1:n | `clues` | `clues.missing_id` | CASCADE | CASCADE |
| `missing_children` | 1:n | `messages` (ngữ cảnh) | `messages.missing_id` | SET NULL | CASCADE |
| `missing_children` | poly | `photos` | `model_type + model_id` | Observer | — |
| `missing_children` | poly | `comments` | `commentable_type + commentable_id` | Observer | — |
| `found_children` | n:1 | `provinces` | `found_children.province_id` | RESTRICT | CASCADE |
| `found_children` | poly | `photos` | `model_type + model_id` | Observer | — |
| `photos` | 1:n | `face_match_results` | `missing_photo_id` | SET NULL | CASCADE |
| `photos` | 1:n | `face_match_results` | `found_photo_id` | SET NULL | CASCADE |
| `photos` | 1:n | `age_progression_photos` | `original_photo_id` | SET NULL | CASCADE |
| `comments` | 1:n | `comments` | `comments.parent_id` | CASCADE (self-ref) | CASCADE |

---

## 5. MIGRATION ORDER (Thứ tự tạo bảng)

```
-- Domain tables (thứ tự phụ thuộc FK)
1.  provinces
2.  users
3.  fcm_tokens
4.  missing_children
5.  found_children
6.  photos
7.  face_match_results
8.  age_progression_photos
9.  clues
10. comments
11. messages
12. chatbot_sessions

-- Laravel/package tables (chạy artisan command)
13. notifications          → php artisan notifications:table
14. activity_log           → php artisan activitylog:migration
15. personal_access_tokens → php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
16. jobs                   → php artisan queue:table
17. failed_jobs            → php artisan queue:failed-table
18. cache                  → php artisan cache:table
19. sessions               → php artisan session:table

-- Alter migrations (Sprint 2+)
20. alter_missing_children_case_code_nullable
21. add_seo_fields_to_missing_children        → [v2.4] slug, meta_title, meta_description, og_image_url
22. add_verified_at_is_hidden_to_clues        → [v2.4] verified_at, is_hidden
```

---

## 6. GLOBAL DATABASE SETTINGS

```sql
CREATE DATABASE missing_children_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

SET GLOBAL default_storage_engine = InnoDB;
SET GLOBAL time_zone = '+07:00';
SET GLOBAL innodb_ft_min_token_size = 2;
```

---

## 7. QUERY MẪU QUAN TRỌNG

### 7.1. Tìm kiếm gần vị trí (Haversine + Bounding Box)

```sql
SELECT id, full_name, missing_date,
    (6371 * ACOS(
        COS(RADIANS(:lat)) * COS(RADIANS(lat)) *
        COS(RADIANS(lng) - RADIANS(:lng)) +
        SIN(RADIANS(:lat)) * SIN(RADIANS(lat))
    )) AS distance_km
FROM missing_children
WHERE status = 'APPROVED'
  AND deleted_at IS NULL
  AND lat BETWEEN :lat - (:radius_km / 111.0) AND :lat + (:radius_km / 111.0)
  AND lng BETWEEN :lng - (:radius_km / (111.0 * COS(RADIANS(:lat)))) AND :lng + (:radius_km / (111.0 * COS(RADIANS(:lat))))
HAVING distance_km <= :radius_km
ORDER BY distance_km ASC
LIMIT 20;
```

### 7.2. Geo-Push: tìm users trong bán kính để gửi notification

```sql
SELECT u.id, u.email, f.token AS fcm_token
FROM users u
LEFT JOIN fcm_tokens f ON f.user_id = u.id AND f.is_active = 1
WHERE u.status = 'ACTIVE'
  AND u.notify_lat IS NOT NULL
  AND u.notify_lat BETWEEN :post_lat - (u.notify_radius_km / 111.0)
                       AND :post_lat + (u.notify_radius_km / 111.0)
  AND (6371 * ACOS(
        COS(RADIANS(:post_lat)) * COS(RADIANS(u.notify_lat)) *
        COS(RADIANS(u.notify_lng) - RADIANS(:post_lng)) +
        SIN(RADIANS(:post_lat)) * SIN(RADIANS(u.notify_lat))
      )) <= u.notify_radius_km;
```

### 7.3. Full-text search

```sql
SELECT *, MATCH(full_name, clothes_desc, special_marks, circumstances)
    AGAINST (:keyword IN BOOLEAN MODE) AS relevance
FROM missing_children
WHERE status = 'APPROVED'
  AND deleted_at IS NULL
  AND MATCH(full_name, clothes_desc, special_marks, circumstances)
      AGAINST (:keyword IN BOOLEAN MODE)
ORDER BY relevance DESC
LIMIT 20;
```

### 7.4. Auto-expire: hồ sơ chưa cập nhật > 2 năm

```sql
SELECT id, full_name, user_id, updated_at
FROM missing_children
WHERE status = 'APPROVED'
  AND deleted_at IS NULL
  AND updated_at < DATE_SUB(NOW(), INTERVAL 2 YEAR)
  AND auto_expire_notified_at IS NULL;
```

### 7.5. Lấy conversation chat giữa 2 user

```sql
SELECT * FROM messages
WHERE (
    (from_user_id = :user_a AND to_user_id = :user_b)
    OR
    (from_user_id = :user_b AND to_user_id = :user_a)
)
AND missing_id = :missing_id
ORDER BY created_at ASC
LIMIT 50 OFFSET :offset;
```

### 7.6. Dashboard: thống kê theo tỉnh

```sql
SELECT p.name AS province_name,
       COUNT(mc.id) AS total_missing,
       SUM(CASE WHEN mc.status = 'FOUND' THEN 1 ELSE 0 END) AS total_found,
       ROUND(
           SUM(CASE WHEN mc.status = 'FOUND' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(mc.id), 0),
       1) AS success_rate
FROM provinces p
LEFT JOIN missing_children mc ON mc.province_id = p.id AND mc.deleted_at IS NULL
GROUP BY p.id, p.name
ORDER BY total_missing DESC;
```

### 7.7. Lấy tất cả manh mối có GPS để hiển thị marker realtime

```sql
SELECT
    c.id,
    c.missing_id,
    c.clue_type,
    ROUND(c.lat, 2) AS lat_public,
    ROUND(c.lng, 2) AS lng_public,
    c.location_accuracy_m,
    c.occurred_at,
    c.gps_shared_at,
    mc.full_name AS child_name,
    mc.case_code
FROM clues c
JOIN missing_children mc ON mc.id = c.missing_id
WHERE c.lat IS NOT NULL
  AND c.gps_shared_at IS NOT NULL
  AND c.is_hidden = 0
  AND mc.status = 'APPROVED'
  AND mc.deleted_at IS NULL
  AND c.occurred_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY c.gps_shared_at DESC
LIMIT 200;
```

### 7.8. [v2.4] Lấy hồ sơ theo slug cho trang chi tiết SEO

```sql
-- Route: GET /missing/{slug}
-- Thay thế cho: GET /missing/{id}
SELECT mc.*, p.name AS province_name
FROM missing_children mc
LEFT JOIN provinces p ON p.id = mc.province_id
WHERE mc.slug = :slug
  AND mc.status = 'APPROVED'
  AND mc.deleted_at IS NULL
LIMIT 1;

-- Sitemap: lấy tất cả slug đang APPROVED để generate sitemap.xml
SELECT slug, updated_at
FROM missing_children
WHERE status = 'APPROVED'
  AND deleted_at IS NULL
  AND slug IS NOT NULL
ORDER BY updated_at DESC;
```

---

## 8. CHECKLIST BẢO MẬT & TUÂN THỦ

| Hạng mục | Trạng thái | Ghi chú |
|---|---|---|
| Face encoding mã hóa AES-256 | ✅ | `Crypt::encryptString()` trước INSERT |
| OTP lưu dạng bcrypt hash | ✅ | `Hash::make()`, so sánh bằng `Hash::check()` |
| Contact phone ẩn API public | ✅ | Không serialize trong Resource |
| Soft delete toàn bộ hồ sơ | ✅ | `deleted_at` trên `missing_children`, `found_children`, `comments`, `users` |
| Right to Erasure (GDPR Art.17) | ✅ | Hard delete ảnh + face_encoding theo yêu cầu |
| Ảnh blur trên danh sách public | ✅ | `path_blurred` trong `photos` |
| Auto-expire 2 năm | ✅ | `auto_expire_notified_at` + Laravel Scheduler |
| Rate limiting | ✅ | Tầng Route (Laravel Throttle) |
| Audit log admin | ✅ | Spatie Activity Log |
| Ảnh full-res chỉ cho auth user | ✅ | `path_original` chỉ trả về cho authenticated user |
| Tuân thủ Nghị định 13/2023/NĐ-CP | ✅ | Không public SĐT, mã hóa face data |
| GPS realtime — user consent | ✅ | Frontend hỏi quyền `navigator.geolocation` |
| GPS realtime — làm tròn tọa độ public | ✅ | `ROUND(lat,2)` / `ROUND(lng,2)` (~1km) |
| GPS realtime — throttle submit manh mối | ✅ | Rate limit: 10r/min (Laravel Throttle) |
| GPS realtime — log `gps_shared_at` | ✅ | Timestamp riêng khi user share GPS |
| **[v2.4] SEO slug unique** | ✅ | `UNIQUE INDEX uq_missing_slug` — tránh duplicate URL |
| **[v2.4] OG image fallback** | ✅ | `og_image_url` NULL → fallback `primary_photo.path_thumb` — không bao giờ thiếu ảnh khi share |
| **[v2.4] meta_description độ dài** | ✅ | Khuyến nghị ≤ 320 ký tự — enforce ở tầng Laravel validation |
| **[v2.4] Clue `is_hidden`** | ✅ | Admin ẩn manh mối mà không xóa — audit trail giữ nguyên |

---

## 9. LỊCH SỬ PHIÊN BẢN

| Phiên bản | Ngày | Những thay đổi chính |
|---|---|---|
| v2.0 | 13/05/2026 | Thiết kế ban đầu |
| v2.1 | 14/05/2026 | Bổ sung `chatbot_sessions`, `fcm_tokens`, `clue_type`, `reward_info`, `attachment_path`, fix UNIQUE `face_match_results`, sửa 34 tỉnh |
| v2.2 | 14/05/2026 | Fix ISO code; sửa `otp_code`; bổ sung FK `fk_clues_verified_by`; CHECK constraint `messages`; tăng `provinces.code` CHAR(6); tối ưu Haversine |
| v2.3 | 15/05/2026 | Làm rõ tên đề tài; thêm `gps_shared_at` và `location_accuracy_m` vào `clues`; query 7.7 GPS realtime; checklist bảo mật GPS |
| **v2.4** | **01/06/2026** | **Bổ sung 4 cột SEO vào `missing_children`: `slug` (UNIQUE), `meta_title`, `meta_description`, `og_image_url`; bổ sung 2 cột thiếu vào `clues`: `is_hidden`, `verified_at`; thêm query 7.8 (slug lookup + sitemap); cập nhật Migration Order; cập nhật Checklist bảo mật** |

---

*Phiên bản: 2.4 — 01/06/2026*  
*Dựa trên: `DATABASE_DESIGN_TimKiemTreEmThatLac_v2.3.md` + rà soát migration thực tế Sprint 2*
