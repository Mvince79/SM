# D-04 — Cross-browser & Responsive Final Check

Người test: Linh
Ngày test: \***\*\_\_\*\***
Build/commit: \***\*\_\_\*\***

## Ma trận test

| #   | Trình duyệt                              | Cách test                               | Bắt buộc                                 |
| --- | ---------------------------------------- | --------------------------------------- | ---------------------------------------- |
| 1   | Chrome Desktop                           | Chạy trực tiếp                          | ✅                                       |
| 2   | Chrome DevTools (mô phỏng mobile)        | F12 → Toggle device toolbar             | ✅                                       |
| 3   | Firefox Desktop                          | Chạy trực tiếp                          | ✅                                       |
| 4   | Safari Desktop (macOS) hoặc BrowserStack | BrowserStack free tier nếu không có Mac | ✅                                       |
| 5   | Safari iOS thật                          | Điện thoại iPhone thật                  | ✅ (bắt buộc cho phần realtime + camera) |
| 6   | Chrome Android thật                      | Điện thoại Android thật                 | ✅ (bắt buộc cho phần camera)            |

**3 breakpoint chuẩn:** 375px (mobile) · 768px (tablet) · 1280px (desktop)

Cách set breakpoint trong Chrome DevTools: F12 → Ctrl+Shift+M (toggle device toolbar) → chọn "Responsive" → gõ tay đúng width 375 / 768 / 1280 (không dùng preset "iPhone 12" vì có thể lệch vài px).

---

## Phần 1 — Layout theo trang × breakpoint × browser

Tick ✅ nếu không vỡ layout (không tràn ngang, không chữ đè nhau, không nút bị che, không ảnh vỡ tỷ lệ). Ghi chú lỗi cụ thể nếu có.

| Trang                      | URL                      | 375px<br>Chrome | 375px<br>Safari | 768px<br>Chrome | 768px<br>Safari | 1280px<br>Chrome | 1280px<br>Firefox |
| -------------------------- | ------------------------ | --------------- | --------------- | --------------- | --------------- | ---------------- | ----------------- |
| Trang chủ                  | `/`                      |                 |                 |                 |                 |                  |                   |
| Danh sách tin tìm          | `/missing`               |                 |                 |                 |                 |                  |                   |
| Chi tiết tin tìm           | `/missing/{slug}`        |                 |                 |                 |                 |                  |                   |
| Đăng tin tìm               | `/missing/create`        |                 |                 |                 |                 |                  |                   |
| Bản đồ                     | `/map`                   |                 |                 |                 |                 |                  |                   |
| Danh sách tin tìm thấy     | `/found-children`        |                 |                 |                 |                 |                  |                   |
| Chi tiết tin tìm thấy      | `/found-children/{id}`   |                 |                 |                 |                 |                  |                   |
| Đăng tin tìm thấy          | `/found-children/create` |                 |                 |                 |                 |                  |                   |
| KYC                        | `/kyc`                   |                 |                 |                 |                 |                  |                   |
| Hồ sơ cá nhân              | `/profile`               |                 |                 |                 |                 |                  |                   |
| Thông báo                  | `/notifications`         |                 |                 |                 |                 |                  |                   |
| Điểm bàn giao              | `/handover-points`       |                 |                 |                 |                 |                  |                   |
| Dashboard tình nguyện viên | `/volunteer/dashboard`   |                 |                 |                 |                 |                  |                   |
| Admin panel (Filament)     | `/admin`                 |                 |                 |                 |                 |                  |                   |
| Admin chat                 | (trong admin)            |                 |                 |                 |                 |                  |                   |
| Điều khoản / Chính sách    | `/terms`, `/privacy`     |                 |                 |                 |                 |                  |                   |

**Ưu tiên kỹ hơn cho các trang Sprint 4 mới** (map, KYC, admin chat, handover-points, volunteer dashboard) — đây là các trang chưa qua vòng test responsive nào trước đó.

### Các lỗi responsive thường gặp cần đặc biệt chú ý

- [ ] Bảng dữ liệu (KYC list, admin tables) tràn ngang ở 375px — cần scroll ngang hoặc chuyển sang card layout
- [ ] Modal/dialog bị cắt hoặc che hết màn hình ở mobile
- [ ] Sidebar admin (Filament) có bị che nội dung ở tablet 768px không
- [ ] Bản đồ Leaflet ở `/map`: control zoom, filter panel có đè lên nhau ở mobile không
- [ ] Form nhiều bước (multi-step create tin) — nút "Tiếp theo/Quay lại" có bị tràn khỏi màn hình 375px
- [ ] Grid card ảnh (case-card, found-children list) — số cột đúng theo breakpoint (1 cột mobile, 2 cột tablet, 3 cột desktop)

---

## Phần 2 — Safari iOS: Realtime (WebSocket/Reverb)

**Bắt buộc test trên điện thoại iPhone thật**, không dùng simulator/DevTools — Safari iOS xử lý WebSocket reconnect khi app chuyển nền (background) khác hẳn Chrome.

Các tính năng dùng Reverb/Echo cần test:

- Notification badge (số thông báo chưa đọc) — event `NotificationCountUpdated`
- Admin chat realtime — event `AdminChatMessageSent`, `MessageSent`
- Comment mới xuất hiện không cần reload — `resources/views/components/missing/comment-section.blade.php`
- Clue GPS cập nhật realtime trên bản đồ — `ClueLocationUpdated`

### Kịch bản test

1. Mở Safari trên iPhone, đăng nhập, vào trang có badge thông báo
2. Từ 1 thiết bị/tab khác, trigger 1 sự kiện (vd: admin gửi tin nhắn chat, hoặc duyệt 1 comment)
3. Badge/chat trên iPhone phải cập nhật **ngay lập tức không cần F5**
4. **Test case quan trọng nhất**: bấm nút Home để đưa Safari xuống nền (background) trong ~30 giây, rồi mở lại → kiểm tra:
    - [ ] WebSocket có tự reconnect không, hay bị đứng im mãi mãi cho tới khi F5 tay?
    - [ ] Badge có bị "đếm sai" (miss event trong lúc offline) không?
5. Test thêm với mạng chuyển đổi: bắt đầu bằng WiFi, chuyển sang 4G giữa chừng → connection có tự phục hồi không

☐ **DoD**: Chat/notification realtime hoạt động đúng trên Safari iOS (test tay bằng điện thoại thật)

---

## Phần 3 — Upload ảnh KYC bằng camera (thiết bị thật)

**Không test qua DevTools giả lập** — input `<input type="file" capture>` mở camera thật khác nhau hoàn toàn giữa iOS Safari và Android Chrome.

### Android (Chrome)

1. Vào `/kyc` trên điện thoại Android thật
2. Bấm nút upload ảnh CCCD → xác nhận mở được lựa chọn "Chụp ảnh" (không chỉ "Chọn từ thư viện")
3. Chụp ảnh → xác nhận ảnh hiển thị preview đúng, đúng chiều (không bị xoay 90°)
4. Submit form → xác nhận ảnh lên server không bị lỗi orientation (EXIF rotation)

### iOS (Safari)

1. Lặp lại 4 bước trên với Safari iOS
2. Chú ý riêng: iOS đôi khi nén ảnh HEIC thay vì JPG khi chụp trực tiếp qua input file — kiểm tra server có nhận và xử lý được định dạng này không, hay bị lỗi upload

☐ **DoD**: Upload ảnh KYC từ camera điện thoại thật hoạt động bình thường trên cả Android và iOS

---

## Tổng kết lỗi tìm được

| #   | Trang                | Breakpoint/Browser | Mô tả lỗi                      | Mức độ              | Trạng thái |
| --- | -------------------- | ------------------ | ------------------------------ | ------------------- | ---------- |
|     | /found-children/{id} | Androind           | Giao diện tràng viền           | Blocker/Major/Minor | Open/Fixed |
|     | /profile/kyc         | Androind           | Không thể chụp ảnh bằng camera | Blocker/Major/Minor | Open/Fixed |
|     | /missing/create      | Androind           | Không thể chụp ảnh bằng camera | Blocker/Major/Minor | Open/Fixed |

---

## Kết luận

☐ Không có lỗi vỡ layout ở 3 breakpoint chuẩn trên Chrome + Safari
☐ Chat/notification realtime hoạt động đúng trên Safari iOS (test tay bằng điện thoại thật)
☐ Upload ảnh KYC từ camera điện thoại thật hoạt động bình thường trên cả Android và iOS
