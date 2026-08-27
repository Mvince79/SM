# HƯỚNG DẪN CẬP NHẬT TÀI LIỆU LUẬN VĂN

> **File gốc:** `TinhPV10_Nhom6_WebsiteTimKiemTreEmThatLacTichHopAINhanDienKhuonMatVaBanDo.docx`
> **Dựa trên:** BACKLOG_Task_Update.md + codebase hiện tại
> **Ngày:** 2026-08-26

---

## 📋 TỔNG QUAN CẦN CẬP NHẬT

| Phần | Hành động | Mức độ |
|------|-----------|--------|
| **4.4/4.5 Công nghệ** | Xóa Bootstrap/Google Maps, thêm Tailwind/FastAPI/DeepFace/Reverb/Horizon | 🔴 Bắt buộc |
| **4.2 UseCase** | Xóa UC Third-Party Verifier, thêm ~15 UC mới | 🔴 Bắt buộc |
| **5.2 Database** | Sửa 5 bảng cũ, thêm 14 bảng mới | 🔴 Bắt buộc |
| **5.3 Giao diện** | Thêm 4 màn hình mới | 🟠 Nên làm |
| **Chương 6-7** | Chưa có — cần viết | 🟡 Tùy chọn |

---

## 🔴 PHẦN 1: CẬP NHẬT CÔNG NGHỆ (4.4, 4.5)

### 1.1 — Sửa mục 4.4 "Công nghệ Frontend"

**TÌM:** Đoạn có chữ "Bootstrap" và "Google Maps API"

**XÓA hoặc THAY THẾ:**
```
Bootstrap 5 → Responsive layout, component hệ thống
```
**THÀNH:**
```
Tailwind CSS 4 + Vite 7 → Utility-first CSS framework, build tool cho asset pipeline
```

**XÓA:**
```
Google Maps API → Bản đồ, tính toán khoảng cách, reverse geocoding
```
**THÊM vào cùng mục:**
```
Leaflet.js + Leaflet.heat → Bản đồ tương tác, clustering, heatmap
```

### 1.2 — Sửa mục 4.5 "Công nghệ Backend & AI"

**TÌM:** Đoạn liệt kê công nghệ backend

**THÊM các mục sau (nếu chưa có):**

```
• Python FastAPI (port 8001) → Microservice xử lý AI face matching
• DeepFace + ArcFace → Mô hình nhận diện khuôn mặt, vector embedding
• Laravel Reverb + Echo → WebSocket realtime cho chat và notification
• Laravel Horizon → Queue monitoring và job scheduling
• Laravel Sanctum → API authentication
• Laravel Socialite → Google OAuth login
• Spatie Laravel Activitylog → Audit logging
```

**GHI CHÚ:** Nếu mục 4.5 có đề cập "Python Flask" hoặc "Django" → thay bằng "Python FastAPI".

---

## 🔴 PHẦN 2: CẬP NHẬT USE CASE (4.2)

### 2.1 — XÓA bảng "Bên thứ ba xác minh (Third-Party Verifier)"

**TÌM:** Bảng số 4.4 (hoặc bảng có tên "Third-Party Verifier", "Bên thứ ba xác minh")

**HÀNH ĐỘNG:**
- **XÓA TOÀN BỘ** bảng này (bao gồm các hàng: "Tiếp nhận hồ sơ bàn giao", "Xác nhận đã nhận trẻ", "Cập nhật trạng thái điểm bàn giao")
- **LÝ DO:** Role THIRD_PARTY_VERIFIER đã bị xóa khỏi hệ thống (G-01), thay bằng danh bạ điểm tiếp nhận công khai

### 2.2 — XÓA hoặc SỬA bảng "Quản lý điểm bàn giao" (nếu có)

**TÌM:** Bảng có tên chứa "Quản lý điểm bàn giao" hoặc "Handover Point"

**HÀNH ĐỘNG:**
- Nếu bảng này mô tả chức năng dành cho **verifier** → **XÓA**
- Nếu bảng này mô tả chức năng dành cho **admin** → giữ lại, sửa mô tả cho đúng

### 2.3 — THÊM các Use Case mới

**CHÈN vào cuối mục 4.2 (sau các bảng Use Case hiện có), thêm các bảng sau:**

#### Bảng mới 1: Tìm kiếm bằng ảnh AI (công khai)

| Use Case | Mã | Actor | Mô tả |
|----------|-----|-------|-------|
| Tải ảnh lên để so khớp | UC-PHOTO-SEARCH-01 | Người dùng công khai | Người qua đường chụp/tải ảnh trẻ → AI so khớp với toàn bộ hồ sơ trẻ mất tích, trả tối đa 5 kết quả với điểm % |
| Xem kết quả carousel | UC-PHOTO-SEARCH-02 | Người dùng công khai | Hiển thị kết quả dạng thẻ cuộn ngang, mỗi thẻ có ảnh, tên, tuổi, điểm %, nút "Xem chi tiết" và "Báo tin ngay" |
| Báo tin từ kết quả AI | UC-PHOTO-SEARCH-03 | Người dùng công khai | Từ kết quả AI, bấm "Báo tin ngay" → mở form đăng tin found_children với liên kết missing_children đã chọn |

#### Bảng mới 2: Xác nhận đoàn tụ

| Use Case | Mã | Actor | Mô tả |
|----------|-----|-------|-------|
| Chủ hồ sơ xác nhận đoàn tụ | UC-REUNION-01 | Chủ hồ sơ (USER) | Bấm "Xác nhận đã nhận lại con" → status chuyển REUNITED, tự động gỡ khỏi trang chủ/bản đồ |
| Admin xác minh đoàn tụ | UC-REUNION-02 | Admin | Xác nhận hộ khi gia đình không phản hồi sau 7 ngày, bắt buộc nhập lý do (min 20 ký tự) |
| Hệ thống nhắc tự động | UC-REUNION-03 | System | Gửi nhắc notification khi HANDED_OVER quá 3 ngày; escalate cho admin quá 7 ngày |

#### Bảng mới 3: Reverse-geocode địa giới

| Use Case | Mã | Actor | Mô tả |
|----------|-----|-------|-------|
| Tra cứu Tỉnh/Xã từ lat/lng | UC-GEO-01 | Hệ thống | User chọn điểm trên bản đồ → tự động xác định Tỉnh/Thành phố + Xã/Phường (ĐÚNG 2 cấp — cấp Huyện/Quận đã bãi bỏ từ 01/07/2025) bằng GeoJSON nội bộ, không gọi API ngoài |
| Kiểm tra điểm trong lãnh thổ VN | UC-GEO-02 | Hệ thống | Chặn submit nếu điểm chọn ngoài Việt Nam |

#### Bảng mới 4: Location Picker thống nhất

| Use Case | Mã | Actor | Mô tả |
|----------|-----|-------|-------|
| Chọn vị trí trên bản đồ | UC-LOC-01 | Người dùng | Component bản đồ Leaflet + nút GPS, chỉ ghi lat/lng (không còn input địa chỉ tự do) |
| Tái sử dụng component | UC-LOC-02 | Developer | Dùng chung cho form missing, found, và Filament HandoverPointResource |

#### Bảng mới 5: Gợi ý điểm tiếp nhận

| Use Case | Mã | Actor | Mô tả |
|----------|-----|-------|-------|
| Gợi ý điểm tiếp nhận theo found_children | UC-HANDOVER-01 | Hệ thống | Dùng toạ độ trẻ được tìm thấy (found_children) để tìm 3 điểm bàn giao gần nhất |
| Xem tất cả điểm tiếp nhận | UC-HANDOVER-02 | Người dùng | Trang `/diem-ban-giao` hiển thị toàn bộ danh bạ, sắp theo khoảng cách khi có toạ độ |

#### Bảng mới 6: Audit Log

| Use Case | Mã | Actor | Mô tả |
|----------|-----|-------|-------|
| Ghi log tự động khi đổi status | UC-AUDIT-01 | System | Observer bắt `updated` trên MissingChild/FoundChild khi `status` thay đổi → sinh audit_logs |
| Xem log timeline | UC-AUDIT-02 | Admin | Filament resource read-only hiển thị timeline: thời gian, actor, action, target, lý do |

#### Bảng mới 7: Cam kết dữ liệu

| Use Case | Mã | Actor | Mô tả |
|----------|-----|-------|-------|
| Đồng ý chính sách dữ liệu | UC-DATA-01 | Người dùng | Checkbox bắt buộc khi đăng tin: "Tôi đồng ý dữ liệu chỉ dùng cho mục đích tìm kiếm trẻ thất lạc" |

---

## 🔴 PHẦN 3: CẬP NHẬT CƠ SỞ DỮ LIỆU (5.2)

### 3.1 — Sửa Bảng `users` (Bảng 5.2)

**TÌM:** Bảng `users` trong mục 5.2

**THÊM các cột sau (nếu chưa có):**

| Tên cột | Kiểu dữ liệu | Mô tả |
|-----------|-------------|-------|
| `kyc_status` | ENUM('NONE','PENDING','VERIFIED','REJECTED') | Trạng thái xác thực danh tính |
| `ban_reason` | TEXT | Lý do khóa tài khoản |
| `notify_lat` | DECIMAL(10,7) | Vĩ độ cho geo-push notification |
| `notify_lng` | DECIMAL(10,7) | Kinh độ cho geo-push notification |
| `notify_radius_km` | TINYINT | Bán kính thông báo (km) |
| `otp_code` | VARCHAR(255) | Bcrypt hash OTP xác thực email |
| `otp_expires_at` | TIMESTAMP | Thời hạn OTP |
| `last_active_at` | TIMESTAMP | Lần cuối online (dùng cho admin online indicator) |
| `theme` | VARCHAR(10) | Dark/light mode preference |

### 3.2 — Sửa Bảng `missing_children` (Bảng 5.4)

**TÌM:** Bảng `missing_children` trong mục 5.2

**SỬA ENUM `status` — thêm các giá trị:**
```
'HANDED_OVER'     → Đã bàn giao cho cơ quan chức năng
'REUNITED'        → Đã đoàn tụ thành công
'UNREACHABLE_FAMILY' → Không liên lạc được gia đình
'MATCHED'         → Đã có kết quả AI khớp (đã có nhưng cần ghi rõ)
'ARCHIVED'        → Đã lưu trữ (hết hạn 2 năm)
```

**THÊM các cột sau:**

| Tên cột | Kiểu dữ liệu | Mô tả |
|-----------|-------------|-------|
| `case_code` | VARCHAR(20) UNIQUE | Mã hồ sơ định dạng MC-YYYY-NNNNN |
| `review_level` | ENUM('STANDARD','ENHANCED') | Mức độ kiểm duyệt |
| `handover_point_id` | BIGINT FK | Điểm bàn giao đã xác nhận |
| `handover_confirmed_at` | TIMESTAMP | Thời điểm xác nhận bàn giao |
| `handover_confirmed_by` | BIGINT FK | Admin xác nhận bàn giao |
| `reunited_confirmed_at` | TIMESTAMP | Thời điểm xác nhận đoàn tụ |
| `reunited_confirmed_by` | BIGINT FK | Người xác nhận đoàn tụ |
| `reunited_verification_method` | ENUM('FAMILY_SELF','ADMIN_PHONE_CALL') | Phương thức xác minh đoàn tụ |
| `expires_at` | TIMESTAMP | Hạn 2 năm từ created_at |
| `archived_at` | TIMESTAMP | Thời điểm lưu trữ |
| `status_before_archive` | ENUM | Lưu status trước khi archive |
| `slug` | VARCHAR | SEO-friendly URL |
| `meta_title` | VARCHAR(500) | SEO title |
| `meta_description` | VARCHAR(500) | SEO description |
| `og_image_url` | VARCHAR(500) | OG image cho share social |
| `qr_code_path` | VARCHAR(500) | Đường dẫn QR code |

### 3.3 — Sửa Bảng `found_children` (Bảng 5.5)

**THÊM các cột:**

| Tên cột | Kiểu dữ liệu | Mô tả |
|-----------|-------------|-------|
| `handover_point_id` | BIGINT FK | Điểm bàn giao liên quan |
| `handover_confirmed_at` | TIMESTAMP | Thời điểm xác nhận bàn giao |
| `handover_confirmed_by` | BIGINT FK | Người xác nhận |
| `ai_processed_at` | TIMESTAMP | Thời điểm AI xử lý xong |

### 3.4 — Sửa Bảng `photos` (Bảng 5.6)

**THÊM các cột:**

| Tên cột | Kiểu dữ liệu | Mô tả |
|-----------|-------------|-------|
| `path_display` | VARCHAR(500) | Ảnh resize ~1600px, quality 80% |
| `path_display_webp` | VARCHAR(500) | WebP version của display |
| `path_thumb_webp` | VARCHAR(500) | WebP thumbnail |
| `face_bounding_box` | JSON | {x, y, w, h} từ AI detect-face |
| `face_crop_path` | VARCHAR(500) | Đường dẫn ảnh crop khuôn mặt |
| `detected_gender` | ENUM('MALE','FEMALE','UNKNOWN') | Giới tính AI phát hiện |
| `ai_status` | VARCHAR(20) | 'pending','processing','done','failed' |
| `file_size_display` | INT | Kích thước file display (bytes) |
| `file_size` | INT | Kích thước file original (bytes) |

### 3.5 — Sửa Bảng `face_match_results` (Bảng 5.7)

**THÊM các cột:**

| Tên cột | Kiểu dữ liệu | Mô tả |
|-----------|-------------|-------|
| `confidence_level` | ENUM('HIGH','MEDIUM','LOW') | HIGH ≥85%, MEDIUM ≥70%, LOW <70% |
| `model_used` | VARCHAR(50) | 'ArcFace', 'FaceNet', ... |

### 3.6 — Sửa Bảng `clues` (Bảng 5.9)

**THÊM các cột:**

| Tên cột | Kiểu dữ liệu | Mô tả |
|-----------|-------------|-------|
| `is_hidden` | TINYINT | Admin ẩn manh mối |
| `verified_at` | TIMESTAMP | Thời điểm admin xác minh |
| `parent_id` | BIGINT | Self-reference cho reply |
| `is_anonymous` | TINYINT | Ẩn danh |
| `gps_shared_at` | TIMESTAMP | Thời điểm chia sẻ GPS |
| `location_accuracy_m` | SMALLINT | Độ chính xác GPS (mét) |

### 3.7 — THÊM 14 BẢNG MỚI

**Chèn sau Bảng 5.9 (clues), thêm các bảng sau:**

#### Bảng mới: `audit_logs`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | UUID PK | Primary key |
| `actor_id` | BIGINT FK | ID người thực hiện |
| `actor_role` | ENUM('ADMIN','SYSTEM','FAMILY') | Vai trò actor |
| `action` | VARCHAR(100) | Ví dụ: `missing_children.status_updated` |
| `target_type` | VARCHAR(50) | Model type |
| `target_id` | BIGINT | ID target |
| `old_value` | JSON | Giá trị cũ |
| `new_value` | JSON | Giá trị mới |
| `reason` | TEXT | Lý do (bắt buộc cho admin override) |
| `ip_address` | VARCHAR(45) | IP address |
| `user_agent` | VARCHAR(500) | User agent |
| `created_at` | TIMESTAMP | Thời gian tạo |

#### Bảng mới: `kyc_verifications`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `user_id` | BIGINT FK | |
| `id_number` | VARCHAR(50) ENCRYPTED | Số CCCD |
| `full_name` | VARCHAR(100) | Họ tên trên CCCD |
| `dob` | DATE | Ngày sinh |
| `gender` | ENUM | Giới tính |
| `front_image_path` | VARCHAR(500) | Ảnh mặt trước |
| `back_image_path` | VARCHAR(500) | Ảnh mặt sau |
| `selfie_path` | VARCHAR(500) | Ảnh selfie |
| `ocr_raw_response` | JSON | Raw response từ OCR |
| `ocr_provider` | VARCHAR(20) | 'fpt_ai','vnpt','tesseract' |
| `face_match_score` | DECIMAL(5,4) | Điểm so khớp CCCD vs selfie |
| `status` | ENUM | 'PENDING','VERIFIED','REJECTED' |
| `review_level` | ENUM | 'STANDARD','ENHANCED' |
| `reviewed_by` | BIGINT FK | Admin review |
| `reviewed_at` | TIMESTAMP | Thời gian review |
| `created_at` | TIMESTAMP | |

#### Bảng mới: `user_consents`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `user_id` | BIGINT FK | |
| `consent_type` | ENUM | 'TERMS_OF_SERVICE','PRIVACY_POLICY','DATA_USE_LIMITATION' |
| `ip_address` | VARCHAR(45) | |
| `user_agent` | VARCHAR(500) | |
| `consented_at` | TIMESTAMP | |

#### Bảng mới: `idempotency_keys`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `key` | VARCHAR(64) UNIQUE | |
| `user_id` | BIGINT FK | |
| `action` | VARCHAR(50) | |
| `payload_hash` | VARCHAR(64) | |
| `response_status` | SMALLINT | |
| `response_body` | JSON | |
| `expires_at` | TIMESTAMP | |

#### Bảng mới: `volunteer_assignments`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `missing_child_id` | BIGINT FK | |
| `user_id` | BIGINT FK | Volunteer |
| `assigned_by` | BIGINT FK | Admin assign |
| `status` | ENUM('ASSIGNED','COMPLETED','CANCELLED') | |
| `assigned_at` | TIMESTAMP | |
| `completed_at` | TIMESTAMP | |

#### Bảng mới: `handover_points`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `name` | VARCHAR(200) | Tên điểm |
| `type` | ENUM | 'POLICE_STATION','HOTLINE_111','HOSPITAL','COMMUNE' |
| `address` | VARCHAR(500) | Địa chỉ |
| `lat` | DECIMAL(10,7) | |
| `lng` | DECIMAL(10,7) | |
| `province_id` | TINYINT UNSIGNED FK | Trỏ `provinces.id` (34 đơn vị cấp tỉnh sau sáp nhập) |
| `hotline` | VARCHAR(20) | Số điện thoại |
| `email` | VARCHAR(100) | |
| `responsible_org_name` | VARCHAR(200) | Tên đơn vị phụ trách |
| `legal_authority_note` | TEXT | Ghi chú thẩm quyền pháp lý |
| `verification_document_path` | VARCHAR(500) | Ảnh/quyết định thành lập |
| `priority_order` | INT | Thứ tự ưu tiên hiển thị |
| `is_active` | TINYINT | |
| `created_at` | TIMESTAMP | |

#### Bảng mới: `third_party_verifier_profiles`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `user_id` | BIGINT FK | |
| `handover_point_id` | BIGINT FK | |
| `verified_at` | TIMESTAMP | |
| `note` | TEXT | |

> **GHI CHÚ:** Bảng này đang ở trạng thái **deprecated** sau khi xóa role THIRD_PARTY_VERIFIER, nhưng vẫn giữ dữ liệu cũ.

#### Bảng mới: `notification_events`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `event_type` | VARCHAR(50) | |
| `subject_type` | VARCHAR(100) | |
| `subject_id` | BIGINT | |
| `payload` | JSON | |
| `occurred_at` | TIMESTAMP | |

#### Bảng mới: `notification_recipients`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `event_id` | BIGINT FK | |
| `user_id` | BIGINT FK | |
| `read_at` | TIMESTAMP | |
| `delivered_at` | TIMESTAMP | |

#### Bảng mới: `escalation_queue`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `notification_recipient_id` | BIGINT FK | |
| `due_at` | TIMESTAMP | |
| `status` | ENUM('PENDING','PROCESSED','CANCELLED') | |
| `processed_at` | TIMESTAMP | |

#### Bảng mới: `support_tickets`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `user_id` | BIGINT FK | |
| `subject` | VARCHAR(200) | |
| `message` | TEXT | |
| `status` | ENUM('OPEN','IN_PROGRESS','RESOLVED','CLOSED') | |
| `priority` | ENUM('LOW','MEDIUM','HIGH','CRITICAL') | |
| `assigned_to` | BIGINT FK | |
| `resolved_at` | TIMESTAMP | |

#### Bảng mới: `support_ticket_replies`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `ticket_id` | BIGINT FK | |
| `user_id` | BIGINT FK | Người trả lời |
| `message` | TEXT | |
| `is_internal` | TINYINT | Chỉ admin xem |
| `created_at` | TIMESTAMP | |

#### Bảng mới: `admin_chat_assignments`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `user_id` | BIGINT FK | Người dùng cần chat |
| `admin_id` | BIGINT FK | Admin được gán |
| `assigned_at` | TIMESTAMP | |
| `last_message_at` | TIMESTAMP | |
| `is_active` | TINYINT | |

#### Bảng mới: `articles`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | BIGINT PK | |
| `title` | VARCHAR(200) | |
| `slug` | VARCHAR(200) | |
| `excerpt` | VARCHAR(500) | |
| `content` | TEXT | |
| `cover_image_path` | VARCHAR(500) | |
| `is_published` | TINYINT | |
| `published_at` | TIMESTAMP | |
| `created_at` | TIMESTAMP | |

---

## 🟠 PHẦN 4: CẬP NHẬT GIAO DIỆN (5.3)

### 4.1 — THÊM mục 5.3.2.XX: Trang tìm kiếm bằng ảnh

**CHÈN sau mục 5.3.2.13 (KYC step 3) hoặc cuối mục 5.3.2:**

```
5.3.2.14 — Trang tìm kiếm bằng ảnh (AI Photo Search)

Giao diện công khai, không cần đăng nhập:
- Khối trung tâm: nút lớn "Chụp ảnh / Tải ảnh lên", input file accept="image/*" capture="environment"
- Loading: skeleton shimmer + dòng chữ động "Đang quét toàn bộ hồ sơ..."
- Kết quả: carousel ngang, mỗi thẻ gần trọn chiều rộng mobile (scroll-snap-type: x mandatory)
- Mỗi thẻ: ảnh đại diện lớn, badge % khớp, tên + tuổi, 2 nút "Xem chi tiết" + "Đây đúng là bé này — Báo tin ngay"
- Không có kết quả: icon trung tính + hướng dẫn + nút dẫn found-children.create
- Dòng cảnh báo cố định: "Kết quả do AI gợi ý, có thể không chính xác 100% — vui lòng xác minh qua kênh chính thức"
```

### 4.2 — THÊM mục 5.3.2.XX: Khối "Việc cần làm"

```
5.3.2.15 — Khối "Việc cần làm" 5 bước (Next Steps Card)

Hiển thị khi status = HANDED_OVER, chỉ cho chủ tài khoản:
1. Xem địa chỉ + hotline điểm tiếp nhận (nút tel:)
2. Chuẩn bị giấy tờ (CCCD, giấy khai sinh, ảnh trước khi mất tích)
3. Liên hệ công an phường/xã nơi cư trú, cung cấp case_code
4. Gọi hotline xác nhận thời gian đến nhận
5. Bấm "Xác nhận đã nhận lại con" (chỉ xuất hiện ở bước này)
```

### 4.3 — THÊM mục 5.3.2.XX: Thanh trạng thái 6 bước

```
5.3.2.16 — Thanh trạng thái 6 bước (Status Timeline)

6 bước: Đã đăng tin → AI so khớp → Nghi vấn khớp → Admin xác nhận → Đã bàn giao công an → Đã đoàn tụ
- Bước hiện tại: tô --color-primary (có .pulse-dot)
- Bước đã qua: tô --color-success
- Bước chưa tới: giữ màu slate
- Mobile 375px: flex + overflow-x-auto + .no-scrollbar
```

### 4.4 — THÊM mục 5.3.2.XX: Location Picker Component

```
5.3.2.17 — Location Picker Component (V-01)

Component Blade dùng chung cho form missing/found:
- Bản đồ Leaflet nhúng sẵn
- Nút "Lấy vị trí hiện tại" (GPS)
- Click trực tiếp vào bản đồ để ghim
- Không còn input text tự do cho địa chỉ — chỉ ghi lat/lng vào 2 field ẩn
- Tái sử dụng được ở form missing, found, và Filament HandoverPointResource
```

---

## 🟡 PHẦN 5: CẬP NHẬT MÔ TẢ CÔNG NGHỆ CHI TIẾT

### 5.1 — Sửa mục 4.3 "Kiến trúc hệ thống" (nếu có)

**TÌM:** Sơ đồ kiến trúc hoặc mô tả các layer

**THÊM:**
```
┌─────────────────────────────────────────────────────────────┐
│                    CLIENT LAYER                              │
│  Browser (Blade + Livewire + Tailwind CSS + Vite)           │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTPS
┌───────────────────────────▼─────────────────────────────────┐
│                   LARAVEL APPLICATION                        │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────────┐ │
│  │ Controllers │ │   Services   │ │     Jobs / Events       │ │
│  │ + Livewire  │ │ (Business)  │ │ (Async queue + Realtime) │ │
│  └─────────────┘ └─────────────┘ └─────────────────────────┘ │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────────┐ │
│  │ Repositories│ │   Models    │ │       Policies          │ │
│  │ (Data Access)│ │  (Eloquent) │ │    (Authorization)      │ │
│  └─────────────┘ └─────────────┘ └─────────────────────────┘ │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTP (Internal Key)
┌───────────────────────────▼─────────────────────────────────┐
│              AI MICROSERVICE (Python FastAPI)                 │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────────┐ │
│  │   /encode    │ │ /detect-face │ │       /compare          │ │
│  │ ArcFace vec │ │  Bounding    │ │  So khớp 2 vector       │ │
│  └─────────────┘ └─────────────┘ └─────────────────────────┘ │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────────┐ │
│  │   /verify    │ │   /health    │ │        /ready           │ │
│  │ CCCD+Selfie │ │   Status     │ │   Model readiness       │ │
│  └─────────────┘ └─────────────┘ └─────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│                    DATA LAYER                                │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────────────────┐ │
│  │   MySQL 8   │ │    Redis    │ │   Laravel Horizon       │ │
│  │ (28 tables) │ │ (Cache/Queue)│ │   (Queue Dashboard)     │ │
│  └─────────────┘ └─────────────┘ └─────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 — Thêm mô tả AI Pipeline (nếu chưa có)

```
AI Pipeline (Python FastAPI - port 8001):
- Model: ArcFace (DeepFace wrapper)
- Detector: MTCNN / OpenCV (fallback)
- Endpoints:
  + POST /encode → Vector embedding (512-d)
  + POST /detect-face → Bounding box + face_crop + gender
  + POST /compare → So khớp 2 vector, trả score + is_matched
  + POST /verify → So khớp CCCD + Selfie (cho KYC)
- Concurrency: asyncio.Semaphore(MAX_CONCURRENT_INFERENCES)
- Auth: X-Internal-Key header
```

---

## 📌 CHECKLIST CẬP NHẬT

- [ ] **4.4/4.5** Xóa Bootstrap/Google Maps, thêm Tailwind/FastAPI/DeepFace/Reverb/Horizon
- [ ] **4.2** Xóa bảng Third-Party Verifier
- [ ] **4.2** Thêm 7 bảng Use Case mới (Photo Search, Reunion, Geo, Location Picker, Handover, Audit, Data Consent)
- [ ] **5.2** Sửa bảng `users` (thêm 8 cột)
- [ ] **5.2** Sửa bảng `missing_children` (thêm 14 cột + mở rộng ENUM)
- [ ] **5.2** Sửa bảng `found_children` (thêm 4 cột)
- [ ] **5.2** Sửa bảng `photos` (thêm 8 cột)
- [ ] **5.2** Sửa bảng `face_match_results` (thêm 2 cột)
- [ ] **5.2** Sửa bảng `clues` (thêm 6 cột)
- [ ] **5.2** Thêm 14 bảng mới (audit_logs, kyc_verifications, user_consents, idempotency_keys, volunteer_assignments, handover_points, third_party_verifier_profiles, notification_events, notification_recipients, escalation_queue, support_tickets, support_ticket_replies, admin_chat_assignments, articles)
- [ ] **5.3** Thêm mô tả 4 màn hình mới (photo search, next-steps, status timeline, location picker)
- [ ] **5.3** Thêm sơ đồ kiến trúc đầy đủ
- [ ] **Chương 6-7** Viết kết quả, screenshots, hướng phát triển

---

## ⚠️ LƯU Ý QUAN TRỌNG

1. **Giữ nguyên định dạng**: Khi sửa, giữ nguyên style (font, size, màu) của đoạn văn bảng đang sửa
2. **Không xóa nội dung cũ** nếu không chắc chắn — nên ghi chú `[DEPRECATED]` thay vì xóa
3. **Bảng Third-Party Verifier**: Có thể giữ lại nhưng ghi chú `[Đã xóa khỏi hệ thống - G-01]` thay vì xóa hoàn toàn, để phòng khi cần tham chiếu
4. **Chương 6-7**: Nếu chưa có, cần viết mới hoàn toàn dựa trên kết quả thực tế đã triển khai

---

*Generated from BACKLOG_Task_Update.md + codebase audit — 2026-08-26*
