# BACKLOG — Đặc Tả Cập Nhật TimTre.vn

> **Nguồn:** `D:\Dowloads\DacTa_Task_TimTre_Update.docx`
> **Ngày tạo:** 2026-08-25
> **Phạm vi:** Nhóm V · G · H · S · D · F · C · U · R

---

## 📋 Tổng quan các nhóm

| Nhóm | Tên | Số task | Ưu tiên tổng |
|------|-----|---------|--------------|
| **V** | Vị trí & Bản đồ định vị | 4 | 🔴 Cao |
| **G** | Danh bạ điểm tiếp nhận | 2 | 🔴/🟠 |
| **H** | Bàn giao & Đoàn tụ | 5 | 🔴/🟠 |
| **S** | Tìm kiếm bằng hình ảnh (AI) | 4 | 🔴/🟠 |
| **D** | Audit Log | 2 | 🔴/🟡 |
| **F** | Sửa lỗi kỹ thuật | 2 | 🔴 |
| **C** | Cam kết dữ liệu | 1 | 🟠 |
| **U** | UI/UX Form & Thành phần | 3 | 🔴/🟢 |
| **R** | Dọn route | 2 | 🔴 |

**Tổng:** 25 task | Ước lượng ~15–17 ngày

---

## 🗺️ V — Vị trí & Bản đồ định vị

**Mục tiêu:** Gộp toàn bộ xác định vị trí về 1 nguồn sự thật duy nhất (GPS + bản đồ), loại bỏ định vị IP và ô địa chỉ tự do.

### V-01 — Component Location Picker hợp nhất
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 1.5 ngày
- **Phụ thuộc:** Không có
- **File:** `resources/views/components/location-picker.blade.php`, `resources/js/location-picker.js`, `resources/css/map-widgets.css`

**Mô tả:**
- Component Blade dùng chung cho form missing/found: bản đồ Leaflet nhúng sẵn, có nút "Lấy vị trí hiện tại"
- Không còn input text tự do cho địa chỉ — chỉ ghi `lat/lng` vào 2 field ẩn
- Bỏ hoàn toàn IP-geo

**DoD:**
- ☐ Không còn input text địa chỉ trong form missing/found
- ☐ Tắt GPS vẫn thao tác được bằng chạm/kéo trên bản đồ
- ☐ Tái sử dụng được ở form missing, found, và Filament HandoverPointResource

---

### V-02 — Reverse-geocode Tỉnh/Huyện/Xã từ lat/lng
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 1 ngày
- **Phụ thuộc:** V-01
- **File:** `app/Services/GeoBoundaryService.php`, `storage/app/geo/vn-provinces-wards.geojson`, `app/Http/Controllers/Api/GeoLookupController.php`

**Mô tả:**
- Endpoint `POST /api/geo/lookup` nhận `{lat, lng}`, trả `{province, district, ward}` hoặc `NOT_FOUND`
- Dùng point-in-polygon với GeoJSON Việt Nam tĩnh (không gọi API ngoài)
- Cache theo lat/lng làm tròn 4 chữ số (~11m)

**DoD:**
- ☐ Chọn điểm trong VN → hiển thị đúng Tỉnh/Huyện/Xã
- ☐ Chọn điểm ngoài lãnh thổ → chặn submit với thông báo rõ
- ☐ 100% xử lý nội bộ, không gọi API bên ngoài

---

### V-03 — Sửa Service tính khoảng cách dùng found_children
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 0.5 ngày
- **Phụ thuộc:** V-02
- **File:** `app/Services/HandoverPointService.php`

**Mô tả:**
- Sửa `nearestTo()` đang lấy toạ độ từ `missing_children` → lấy từ `found_children`
- Vị trí mất tích chỉ giữ lại để hiển thị lịch sử

**DoD:**
- ☐ Test: missing tỉnh A / found tỉnh B → gợi ý điểm tiếp nhận ở tỉnh B
- ☐ Không còn nơi nào gọi `nearestTo()` bằng toạ độ missing_children

---

### V-04 — Endpoint top-3 điểm gần nhất + trang "Xem tất cả"
- **Ưu tiên:** 🟠 Trung bình
- **Ước lượng:** 0.75 ngày
- **Phụ thuộc:** V-03, G-02
- **File:** `app/Http/Controllers/HandoverPointPublicController.php`, `routes/web.php`

**Mô tả:**
- `GET /diem-ban-giao/nearest?lat=&lng=` trả JSON 3 điểm gần nhất kèm hotline, distance_km
- Trang `/diem-ban-giao` giữ nguyên vai trò danh sách đầy đủ

**DoD:**
- ☐ `/diem-ban-giao/nearest` trả đúng 3 bản ghi sắp theo khoảng cách tăng dần
- ☐ Nút "Xem tất cả" dẫn đúng `/diem-ban-giao` không kèm filter

---

## 📇 G — Danh bạ điểm tiếp nhận (thay thế role Third-Party Verifier)

**Mục tiêu:** Gỡ bỏ role THIRD_PARTY_VERIFIER, chuyển sang mô hình danh bạ công khai kèm bằng chứng ảnh.

### G-01 — Gỡ bỏ hoàn toàn role THIRD_PARTY_VERIFIER
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 1 ngày
- **Phụ thuộc:** Không có
- **File:** `routes/web.php`, `app/Http/Controllers/Verifier/HandoverConfirmationController.php`, `resources/views/verifier/`, migration xóa role

**Mô tả:**
- Xóa nhóm route `middleware('role:THIRD_PARTY_VERIFIER')`
- Xóa controller `HandoverConfirmationController` và view `verifier/dashboard.blade.php`
- Migration ALTER bỏ giá trị THIRD_PARTY_VERIFIER khỏi ENUM role
- Giữ nguyên bảng `third_party_verifier_profiles` ở trạng thái deprecated (không xóa dữ liệu)

**DoD:**
- ☐ `php artisan route:list` không còn route `verifier.*`
- ☐ `grep -r THIRD_PARTY_VERIFIER app/ resources/ routes/` không còn kết quả hoạt động
- ☐ `php artisan test` chạy xanh

---

### G-02 — Tăng cường trang public `/diem-ban-giao` theo vị trí
- **Ưu tiên:** 🟠 Trung bình
- **Ước lượng:** 0.5 ngày
- **Phụ thuộc:** V-04, G-01
- **File:** `app/Http/Controllers/HandoverPointPublicController.php`, `resources/views/handover-points/index.blade.php`

**Mô tả:**
- Khi truy cập kèm `?lat=&lng=`, sắp danh sách theo khoảng cách tăng dần, bôi nổi 3 điểm đầu
- Không có query string → sắp theo `priority_order` như hiện tại
- Không thêm chức năng đăng nhập/xác nhận giao dịch nào

**DoD:**
- ☐ `/diem-ban-giao` không kèm toạ độ vẫn hoạt động như cũ
- ☐ Kèm toạ độ hiển thị đúng thứ tự gần → xa

---

## 🤝 H — Bàn giao & Xác nhận đoàn tụ

**Mục tiêu:** Tách rõ "đã bàn giao cho cơ quan chức năng" và "đã đoàn tụ thành công" thành 2 mốc khác nhau.

### H-01 — Mở rộng ENUM trạng thái + cột xác nhận đoàn tụ
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 0.5 ngày
- **Phụ thuộc:** Không có
- **File:** Migration `..._add_reunion_fields_to_missing_children_table.php`, `app/Models/MissingChild.php`

**Mô tả:**
- Thêm cột: `reunited_confirmed_at`, `reunited_confirmed_by`, `reunited_verification_method` (ENUM: `FAMILY_SELF`, `ADMIN_PHONE_CALL`)
- Thêm status: `HANDED_OVER`, `REUNITED`, `UNREACHABLE_FAMILY`
- Dùng các cột `handover_point_id`, `handover_confirmed_at`, `handover_confirmed_by` đã có cho mốc "đã bàn giao"

**DoD:**
- ☐ `php artisan migrate` chạy sạch
- ☐ `MissingChild::STATUS_*` cập nhật đủ 3 giá trị mới

---

### H-02 — Trang "Việc cần làm" cho gia đình khi `status = HANDED_OVER`
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 1.5 ngày
- **Phụ thuộc:** H-01, G-02
- **File:** `resources/views/missing/partials/next-steps-card.blade.php`, `app/Http/Controllers/Client/MissingController.php`

**Mô tả:**
- Khi `status = HANDED_OVER`, trang chi tiết hiện khối "Việc cần làm" 5 bước:
  1. Xem địa chỉ + hotline điểm tiếp nhận (nút `tel:`)
  2. Chuẩn bị giấy tờ (CCCD, giấy khai sinh, ảnh trước khi mất tích)
  3. Liên hệ công an phường/xã nơi cư trú, cung cấp `case_code`
  4. Gọi hotline xác nhận thời gian đến nhận
  5. Bấm "Xác nhận đã nhận lại con" (chỉ xuất hiện ở bước này)

**DoD:**
- ☐ Chủ hồ sơ thấy đủ 5 bước, có số điện thoại bấm gọi được
- ☐ Tài khoản khác không thấy khối này
- ☐ Không có câu mơ hồ không kèm hành động cụ thể

---

### H-03 — Route & Controller xác nhận đoàn tụ (chỉ chủ tài khoản)
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 0.75 ngày
- **Phụ thuộc:** H-01, H-02
- **File:** `routes/web.php`, `app/Http/Controllers/Client/MissingController.php@confirmReunited`

**Mô tả:**
- `POST /missing/{missingChild}/confirm-reunited` — middleware `auth` + `email.verified`
- Policy: chỉ `owner_id === auth()->id()` mới gọi được
- Khi thành công: `status = REUNITED`, `reunited_confirmed_at = now()`, `reunited_confirmed_by = auth()->id()`, `reunited_verification_method = 'FAMILY_SELF'`
- Idempotent: trả 409 nếu đã REUNITED
- Tự động gỡ khỏi UrgentCasesGrid trang chủ và map/data

**DoD:**
- ☐ Tài khoản không phải chủ → 403
- ☐ Chủ tài khoản → status REUNITED, biến mất khỏi trang chủ/bản đồ ngay
- ☐ Gọi lần 2 → 409, không ghi đè thời gian cũ

---

### H-04 — Nhắc tự động + Admin liên hệ xác minh khi gia đình không phản hồi
- **Ưu tiên:** 🟠 Trung bình
- **Ước lượng:** 1.5 ngày
- **Phụ thuộc:** H-01, H-03
- **File:** `app/Console/Commands/RemindFamilyReunionConfirmation.php`, `app/Jobs/EscalateUnconfirmedReunionJob.php`, Filament Page `ConfirmReunionManually`

**Mô tả:**
- Scheduled command hằng ngày: `HANDED_OVER` quá 3 ngày → gửi nhắc notification; quá 7 ngày → đẩy vào danh sách admin
- Filament action "Xác nhận hộ (đã liên hệ gia đình)" — bắt buộc nhập reason (min 20 ký tự)
- Admin xác nhận: `status = REUNITED`, `reunited_verification_method = 'ADMIN_PHONE_CALL'`, ghi audit log
- Liên hệ 3 lần/14 ngày không được → `status = UNREACHABLE_FAMILY`

**DoD:**
- ☐ `HANDED_OVER` quá 3 ngày → có `notification_events` mới
- ☐ Admin không nhập reason (hoặc < 20 ký tự) → bị chặn
- ☐ Sau admin xác nhận → status REUNITED, audit log thể hiện `actor_role = admin`

---

### H-05 — Thanh trạng thái 5 bước hiển thị trên trang chi tiết
- **Ưu tiên:** 🟠 Trung bình
- **Ước lượng:** 1 ngày
- **Phụ thuộc:** H-01
- **File:** `resources/views/components/status-timeline.blade.php`, `resources/css/app.css`

**Mô tả:**
- 5 bước (ánh xạ theo status thật lưu trong DB + FaceMatchResult, KHÔNG dùng trạng thái ồn ào):
  1. **Đã đăng tin** — `status >= APPROVED` (đã duyệt, công khai)
  2. **Nghi vấn khớp** — có `FaceMatchResult` đạt ngưỡng điểm (`score >= config('face_match.score_threshold')`)
  3. **Admin xác nhận** — có `FaceMatchResult` có `status = CONFIRMED`
  4. **Đã bàn giao** — `status = HANDED_OVER` (đã gửi thông tin điểm tiếp nhận cho gia đình; không ghi cứng "công an" vì điểm tiếp nhận có thể là y tế/trung tâm bảo trợ…)
  5. **Đã đoàn tụ** — `status = REUNITED` (cả `FAMILY_SELF` lẫn `ADMIN_PHONE_CALL`)
- "AI so khớp" KHÔNG có ô riêng — chỉ là quá trình xử lý ngầm; khi hồ sơ đã đăng tin mà chưa có khớp nghi vấn, bước 2 được bôi pulse kèm dòng "đang xử lý…".
- Bước hiện tại tô `--color-primary` (có `.pulse-dot`), bước đã qua tô `--color-success`, chưa tới giữ màu slate
- Mobile 375px: flex + `overflow-x-auto` + `.no-scrollbar`

**DoD:**
- ☐ Hiển thị đúng bước hiện tại trên mọi trạng thái (PENDING → APPROVED → MATCHED → HANDED_OVER → REUNITED / UNREACHABLE_FAMILY)
- ☐ Bước 2,3 phản ánh đúng sự tồn tại FaceMatchResult (ngưỡng điểm / CONFIRMED)
- ☐ Mobile 375px không vỡ layout, cuộn ngang mượt

---

## 🔍 S — Tìm kiếm bằng hình ảnh (AI Photo Search, công khai)

**Mục tiêu:** Người qua đường chụp/tải ảnh lên → AI so khớp với toàn bộ hồ sơ trẻ mất tích — không cần tài khoản.

### S-01 — Trang tìm kiếm bằng ảnh — công khai
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 1 ngày
- **Phụ thuộc:** Không có
- **File:** `routes/web.php`, `app/Http/Controllers/Client/PhotoSearchController.php`, `resources/views/photo-search/index.blade.php`

**Mô tả:**
- `GET /tim-kiem-bang-anh` — public, không middleware auth
- Giao diện 1 khối trung tâm: nút lớn "Chụp ảnh / Tải ảnh lên", `input file accept="image/*" capture="environment"`
- Chú thích: "Ảnh chỉ dùng để so khớp, không được lưu lại nếu bạn không báo tin"

**DoD:**
- ☐ Truy cập `/tim-kiem-bang-anh` không cần đăng nhập
- ☐ Hoạt động bằng camera thật trên điện thoại + chọn ảnh có sẵn trên desktop
- ☐ Không có trường nhập liệu khác ngoài ảnh trước khi tìm kiếm

---

### S-02 — API xử lý so khớp ảnh với toàn bộ hồ sơ
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 1.5 ngày
- **Phụ thuộc:** S-01, F-01
- **File:** `app/Http/Controllers/Client/PhotoSearchController.php@search`, `app/Services/FaceMatchingService.php`

**Mô tả:**
- `POST /api/photo-search` — `throttle:5,60` (5 lần/giờ/IP)
- Gọi AI `/encode` lấy vector, `/compare` với toàn bộ `missing_children` chưa REUNITED
- Trả tối đa 5 kết quả `score ≥ 0.5`, sắp theo score giảm dần
- File ảnh xóa khỏi tmp ngay sau khi xử lý (finally block)
- Response mỗi kết quả: `case_code`, tên, tuổi ước tính, ảnh đại diện, score (%) — không trả địa chỉ/SĐT

**DoD:**
- ☐ Lần thứ 6 trong 1 giờ → bị chặn
- ☐ Sau xử lý, thư mục tmp không còn file ảnh
- ☐ Response không chứa địa chỉ/SĐT gia đình

---

### S-03 — Giao diện kết quả dạng thẻ cuộn (TikTok/Facebook)
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 1.5 ngày
- **Phụ thuộc:** S-02
- **File:** `resources/views/photo-search/results.blade.php`, `resources/css/app.css`

**Mô tả:**
- Loading: `.skeleton-shimmer` + dòng chữ động (không dùng `.ai-scanner-overlay` đã bị loại bỏ)
- Kết quả: carousel ngang, mỗi thẻ gần trọn chiều rộng mobile, `scroll-snap-type: x mandatory`
- Mỗi thẻ: ảnh đại diện lớn, badge % khớp, tên + tuổi, 2 nút: "Xem chi tiết" + "Đây đúng là bé này — Báo tin ngay"
- Không có kết quả: thẻ duy nhất icon trung tính + hướng dẫn + nút dẫn `found-children.create`
- Màu sắc chỉ dùng 3 token: Carmine/Navy/Forest

**DoD:**
- ☐ Mobile 375px: vuốt ngang mượt, không giật/vỡ layout
- ☐ "Báo tin ngay" → mở `found-children.create` với liên kết missing_children match được điền sẵn
- ☐ Không có kết quả → hiển thị hướng dẫn rõ, không màn hình trống

---

### S-04 — Cảnh báo độ tin cậy & giới hạn sử dụng
- **Ưu tiên:** 🟠 Trung bình
- **Ước lượng:** 0.25 ngày
- **Phụ thuộc:** S-03
- **File:** `resources/views/photo-search/results.blade.php`

**Mô tả:**
- Dòng cố định hiển thị trên khối kết quả: "Kết quả do AI gợi ý, có thể không chính xác 100% — vui lòng xác minh qua kênh chính thức trước khi hành động"
- Không cho phép ẩn dòng này
- Không dùng ngôn từ khẳng định chắc chắn ("đây chính là bé")

**DoD:**
- ☐ Dòng cảnh báo xuất hiện ở mọi trường hợp có kết quả, không thể tắt/ẩn vĩnh viễn

---

## 🔐 D — Audit Log

### D-01 — Bảng audit_logs + Observer tự động ghi log
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 1 ngày
- **Phụ thuộc:** H-01
- **File:** `database/migrations/..._create_audit_logs_table.php`, `app/Models/AuditLog.php`, `app/Observers/StatusChangeAuditObserver.php`

**Mô tả:**
- Cột: `id` (UUID), `actor_id`, `actor_role` (ENUM: ADMIN, SYSTEM, FAMILY), `action` (ví dụ: `missing_children.status_updated`), `target_type`, `target_id`, `old_value` (JSON), `new_value` (JSON), `reason` (TEXT, NULL), `ip_address`, `user_agent`, `created_at`
- Observer bắt `updated` trên `MissingChild`/`FoundChild` khi `status` thay đổi → tự ghi audit_logs
- `reason` bắt buộc (min 20 ký tự) khi `actor_role = ADMIN` và action thuộc nhóm override thủ công
- Không cấp quyền UPDATE/DELETE audit_logs cho bất kỳ role nào

**DoD:**
- ☐ Mọi lần đổi status của missing_children/found_children đều sinh đúng 1 dòng audit_logs
- ☐ Không tồn tại route/action nào cho phép sửa/xóa audit_logs

---

### D-02 — Giao diện xem log dạng timeline cho Admin
- **Ưu tiên:** 🟡 Thấp-trung bình
- **Ước lượng:** 0.75 ngày
- **Phụ thuộc:** D-01
- **File:** `app/Filament/Admin/Resources/AuditLogs/AuditLogResource.php`

**Mô tả:**
- Filament Resource read-only, hiển thị dạng timeline: "[thời gian] — [actor] đã [action] hồ sơ [target] — Lý do: [reason]"
- Bộ lọc: `target_type`, `actor_role`, khoảng thời gian
- `canCreate()` và `canEdit()` trả về false cứng

**DoD:**
- ☐ Không có nút Tạo/Sửa nào xuất hiện dù admin cấp cao nhất
- ☐ Lọc `target_type = missing_children` trả đúng danh sách log liên quan

---

## 🔧 F — Sửa lỗi kỹ thuật đã phát hiện

### F-01 — Sửa K-03: endpoint AI sai + nhầm field review_level/kyc_status
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 0.5 ngày
- **Phụ thuộc:** Không có
- **File:** `app/Services/FaceMatchingService.php`, `app/Http/Controllers/Admin/KycVerificationController.php`

**Mô tả:**
- K-03 gọi endpoint `/verify` không tồn tại trên AI service → sửa thành `/compare`
- K-03 gán nhầm giá trị vào `review_level` (thuộc missing_children) khi xử lý KYC → sửa đúng field `kyc_status`

**DoD:**
- ☐ Flow so khớp khuôn mặt end-to-end không còn lỗi 404
- ☐ `kyc_status` được cập nhật đúng, `review_level` không bị ghi đè

---

### F-02 — Sửa lỗi query T-34: orWhere phá vỡ điều kiện khóa ngoại
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 0.25 ngày
- **Phụ thuộc:** Không có
- **File:** `app/Http/Controllers/Client/MissingController.php` (hoặc nơi chứa query T-34)

**Mô tả:**
- Query hiện tại: `$missing->faceMatchResults()->where('status','CONFIRMED')->orWhere('score','>=',0.7)`
- `orWhere` phá vỡ điều kiện `missing_id = X` ngầm định → có nguy cơ trả về kết quả của mọi hồ sơ khác
- Sửa thành: `$missing->faceMatchResults()->where(function ($q) { $q->where('status','CONFIRMED')->orWhere('score','>=',0.7); })`

**DoD:**
- ☐ Test: 2 hồ sơ missing khác nhau, mỗi hồ sơ có FaceMatchResult riêng, score ≥0.7 — gọi query trên hồ sơ A không được trả về kết quả của hồ sơ B

---

## 🔒 C — Cam kết dữ liệu

### C-01 — Checkbox bắt buộc đồng ý chính sách dữ liệu khi đăng tin
- **Ưu tiên:** 🟠 Trung bình
- **Ước lượng:** 0.5 ngày
- **Phụ thuộc:** V-01
- **File:** `resources/views/missing/create.blade.php`, `resources/views/found-children/create.blade.php`, `app/Http/Requests/StoreMissingChildRequest.php`

**Mô tả:**
- Checkbox bắt buộc ở cuối form: "Tôi đồng ý dữ liệu chỉ được dùng cho mục đích tìm kiếm trẻ thất lạc, không chia sẻ cho bên thứ ba ngoài phạm vi hệ thống" — kèm link `legal.child-data`
- Thêm `DATA_USE_LIMITATION` vào ENUM `consent_type` của `user_consents`
- Validation: `'data_use_consent' => 'accepted'`
- Khi submit thành công: ghi 1 bản ghi `UserConsent` với `consent_type = DATA_USE_LIMITATION`

**DoD:**
- ☐ Submit khi chưa tick checkbox → bị chặn
- ☐ Sau khi đăng tin thành công, DB có đúng 1 bản ghi `user_consents` mới

---

## 🎨 U — UI/UX Form & Thành phần liên quan

**Nguyên tắc:** Dựa trên token thiết kế hiện có (Carmine × Deep Navy × Forest, rounded-3xl, shadow-premium, không gradient nhiều lớp).

### U-01 — Cấu trúc lại form đăng tin theo 4 nhóm rõ ràng
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** 1.5 ngày
- **Phụ thuộc:** V-01, C-01
- **File:** `resources/views/missing/create.blade.php`, `resources/views/found-children/create.blade.php`

**Mô tả:**
- **Nhóm 1 — Thông tin trẻ:** `.form-field` + `.form-label`, ảnh preview khung tỷ lệ giống `.case-card-img`
- **Nhóm 2 — Vị trí:** Component location-picker (V-01), nút GPS style `.btn-oauth`, reverse-geocode hiển thị `.form-input` disabled
- **Nhóm 3 — Xác minh (KYC):** Giữ nguyên luồng Sprint 4
- **Nhóm 4 — Cam kết & Gửi:** Checkbox C-01 ngay phía trên nút `.btn-primary`

**DoD:**
- ☐ Mobile 375px: 4 nhóm hiển thị tuần tự, khoảng cách rõ ràng
- ☐ Không còn field địa chỉ tự do nào sót lại

---

### U-02 — Khối gợi ý điểm tiếp nhận gần nhất (top 3 + Xem tất cả)
- **Ưu tiên:** 🟠 Trung bình
- **Ước lượng:** 0.75 ngày
- **Phụ thuộc:** V-04, G-02
- **File:** `resources/views/components/nearest-handover-points.blade.php`

**Mô tả:**
- Bố cục style `.found-card` (nền success-light, bo góc, shadow nhẹ)
- Mỗi thẻ: tên điểm, khoảng cách (km), nút `tel:` hotline
- Link "Xem tất cả điểm tiếp nhận →" dùng `.section-link` → `/diem-ban-giao`
- Gọi API V-04 bằng fetch khi trang load, ẩn khối nếu không có kết quả trong bán kính hợp lý

**DoD:**
- ☐ Trang chi tiết found_children hiển thị 3 điểm gần nhất kèm khoảng cách
- ☐ Bấm hotline mở được ứng dụng gọi điện trên di động

---

### U-03 — Badge trạng thái mới dùng đúng token màu sẵn có
- **Ưu tiên:** 🟢 Thấp
- **Ước lượng:** 0.25 ngày
- **Phụ thuộc:** H-01
- **File:** `resources/views/components/*.blade.php`

**Mô tả:**
- `HANDED_OVER` → `.badge-brand` (navy)
- `REUNITED`, `REUNITED_ADMIN_VERIFIED` → `.badge-found` (forest green)
- `UNREACHABLE_FAMILY` → `.badge-urgent` (carmine)

**DoD:**
- ☐ Toàn bộ badge trạng thái mới chỉ dùng 3 class `.badge-*` đã có sẵn

---

## 🧹 R — Dọn route không còn sử dụng

### R-01 — Xóa route/controller/view thuộc role Third-Party Verifier
- **Ưu tiên:** 🔴 Cao
- **Ước lượng:** Gộp với G-01
- **Phụ thuộc:** G-01
- **File:** `routes/web.php`

**Mô tả:**
- Xóa import `use App\Http\Controllers\Verifier\HandoverConfirmationController;`
- Xóa toàn bộ khối route `role:THIRD_PARTY_VERIFIER`

**DoD:**
- ☐ `php artisan route:list` không còn route nào có tên bắt đầu bằng `verifier.`

---

### R-02 — Route cần thêm mới

| Method | Route | Nhóm | Mô tả |
|--------|-------|------|-------|
| GET | `/tin-tim-thay/{foundChild}` | U | Trang public chi tiết found_children |
| POST | `/missing/{missingChild}/confirm-reunited` | H | Xác nhận đoàn tụ (chỉ chủ) |
| GET | `/diem-ban-giao/nearest?lat=&lng=` | V | API top-3 điểm gần nhất |
| POST | `/api/geo/lookup` | V | Reverse-geocode lat/lng → Tỉnh/Huyện/Xã |
| GET | `/tim-kiem-bang-anh` | S | Trang tìm kiếm bằng ảnh công khai |
| POST | `/api/photo-search` | S | API so khớp ảnh (throttle:5,60) |

> **Lưu ý:** `AuditLogResource` (D-02) đăng ký như Filament Resource — không cần khai báo thủ công trong `web.php`.

---

## 📌 Tổng hợp routes cần xóa

```
XÓA:
verifier.dashboard
verifier.handover.confirm
verifier.* (toàn bộ prefix)
```

## 📌 Tổng hợp routes cần thêm

```
THÊM:
GET  /tin-tim-thay/{foundChild}
POST /missing/{missingChild}/confirm-reunited
GET  /diem-ban-giao/nearest
POST /api/geo/lookup
GET  /tim-kiem-bang-anh
POST /api/photo-search
```

---

## 🔗 Phụ thuộc giữa các nhóm

```
V-01 ──→ V-02 ──→ V-03 ──→ V-04 ──┐
                                  ├──→ G-02 ──→ H-02 ──→ H-03 ──→ H-04
G-01 ──────────────────────────────┘                          │
                                                               └──→ H-05
S-01 ──→ S-02 ──→ S-03 ──→ S-04
                              ↑
                           F-01 (bắt buộc trước)

D-01 ──→ D-02
  ↑
H-01

C-01 ──→ U-01 (phụ thuộc V-01)
U-02 (phụ thuộc V-04, G-02)
U-03 (phụ thuộc H-01)

R-01 (gộp với G-01)
R-02 (liệt kê route mới)
```

---

## 📊 Ma trận ưu tiên

| Task | Độ phức tạp | Rủi ro | Tổng ưu tiên |
|------|-------------|--------|---------------|
| V-01 | Cao | Cao | 🔴 Làm đầu tiên |
| G-01 | Trung bình | Cao | 🔴 Làm sớm |
| H-01 | Thấp | Thấp | 🔴 Làm đầu nhóm H |
| F-01 | Thấp | Cao | 🔴 Làm sớm (block S) |
| F-02 | Thấp | Trung bình | 🔴 |
| S-01 | Trung bình | Trung bình | 🔴 |
| D-01 | Trung bình | Cao | 🔴 |
| V-02 | Trung bình | Trung bình | 🔴 |
| C-01 | Thấp | Thấp | 🟠 |
| U-01 | Cao | Trung bình | 🔴 |
| H-02 | Cao | Trung bình | 🔴 |
| H-03 | Thấp | Trung bình | 🔴 |
| V-03 | Thấp | Thấp | 🔴 |
| V-04 | Trung bình | Thấp | 🟠 |
| S-02 | Cao | Trung bình | 🔴 |
| G-02 | Thấp | Thấp | 🟠 |
| H-04 | Trung bình | Trung bình | 🟠 |
| H-05 | Trung bình | Thấp | 🟠 |
| S-03 | Cao | Trung bình | 🔴 |
| D-02 | Thấp | Thấp | 🟡 |
| U-02 | Trung bình | Thấp | 🟠 |
| U-03 | Thấp | Thấp | 🟢 |
| R-01 | Thấp | Thấp | 🔴 (gộp G-01) |
| R-02 | — | — | 📋 Liệt kê |
| S-04 | Thấp | Thấp | 🟠 |

---

## ✅ Checklist nhanh theo nhóm

- [ ] **V-01** Location Picker hợp nhất
- [ ] **V-02** Reverse-geocode Tỉnh/Huyện/Xã
- [ ] **V-03** Sửa Service nearestTo dùng found_children
- [ ] **V-04** Endpoint top-3 + trang Xem tất cả
- [ ] **G-01** Gỡ bỏ role THIRD_PARTY_VERIFIER
- [ ] **G-02** Tăng cường /diem-ban-giao theo vị trí
- [ ] **H-01** Mở rộng ENUM + cột đoàn tụ
- [ ] **H-02** Trang "Việc cần làm" 5 bước
- [ ] **H-03** Route xác nhận đoàn tụ
- [ ] **H-04** Nhắc tự động + Admin xác minh
- [ ] **H-05** Thanh trạng thái 5 bước
- [ ] **S-01** Trang tìm kiếm bằng ảnh công khai
- [ ] **S-02** API so khớp ảnh
- [ ] **S-03** Giao diện thẻ cuộn kết quả
- [ ] **S-04** Cảnh báo độ tin cậy
- [ ] **D-01** Bảng audit_logs + Observer
- [ ] **D-02** Giao diện timeline cho Admin
- [ ] **F-01** Sửa endpoint AI + field KYC
- [ ] **F-02** Sửa query T-34 orWhere
- [ ] **C-01** Checkbox cam kết dữ liệu
- [ ] **U-01** Cấu trúc lại form 4 nhóm
- [ ] **U-02** Khối gợi ý điểm tiếp nhận
- [ ] **U-03** Badge trạng thái mới
- [ ] **R-01** Xóa route verifier
- [ ] **R-02** Thêm route mới (liệt kê)

---

*Generated from `DacTa_Task_TimTre_Update.docx` — 25 tasks across 9 groups*
