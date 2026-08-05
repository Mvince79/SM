# D-03 — End-to-End Manual Test Checklist toàn site

Người test: Linh
Ngày test: __________
Domain deploy thật đang test: __________ (KHÔNG dùng localhost)
Commit/build: __________

> Cách dùng file này: tick ✅/❌ vào từng dòng, cột "Ghi chú" ghi rõ hiện tượng lỗi nếu Fail.
> Mọi lỗi Blocker → tạo GitHub Issue **ngay trong ngày** phát hiện (không dồn cuối sprint), dán link issue vào cột Ghi chú.

---

## 0. Chuẩn bị trước khi test

- [ ] Đã có bản deploy thật (staging/production), không phải `php artisan serve` local
- [ ] Đã tạo/seed đủ tài khoản test cho từng role (4 role bắt buộc):

| Role | Email/username test | Mật khẩu | Ghi chú |
|---|---|---|---|
| USER | | | tài khoản gia đình báo tin |
| VOLUNTEER | | | |
| THIRD_PARTY_VERIFIER | | | cần gán đúng tỉnh phụ trách (I-03) |
| ADMIN | | | vào được Filament `/admin` |

- [ ] Chuẩn bị sẵn 2 bộ ảnh giấy tờ demo: 1 bộ CMND (9 số), 1 bộ CCCD (12 số) + ảnh selfie tương ứng
- [ ] Chuẩn bị 2 trình duyệt/2 thiết bị khác nhau để test realtime (chat, notification, GPS) — 1 mình không tự trigger được các sự kiện 2 chiều
- [ ] Mở sẵn GitHub Issues tab để log bug ngay khi thấy, kèm severity: **Blocker / Major / Minor**

---

## 1. Luồng Auth (đăng ký / đăng nhập / OTP / Google / quên mật khẩu)

| # | Route | Kịch bản | Pass/Fail | Ghi chú |
|---|---|---|---|---|
| 1.1 | `auth.register` (`/auth/register`) | Đăng ký user mới, số điện thoại/email hợp lệ | | |
| 1.2 | `auth.otp.form` / `auth.otp.verify` | Nhập đúng OTP → vào được hệ thống | | |
| 1.3 | `auth.otp.resend` | Bấm gửi lại OTP, không spam được liên tục | | |
| 1.4 | `auth.otp.verify` — sai OTP | Nhập sai OTP nhiều lần → thông báo lỗi rõ ràng, không crash | | |
| 1.5 | `auth.login` | Đăng nhập đúng tài khoản mỗi role (USER/VOLUNTEER/VERIFIER/ADMIN) | | |
| 1.6 | `auth.login` — sai mật khẩu | Thông báo lỗi, không lộ thông tin tài khoản có tồn tại hay không | | |
| 1.7 | `auth.google` / `auth.google.callback` | Đăng nhập bằng Google OAuth thật (không mock) | | |
| 1.8 | `password.request` → `password.email` → `password.reset` → `password.update` | Luồng quên mật khẩu đầy đủ, link reset còn hạn dùng được | | |
| 1.9 | `auth.logout` | Đăng xuất, session/cookie bị huỷ thật (thử back trình duyệt) | | |
| 1.10 | Middleware `email.verified` | User chưa xác thực email bị chặn đúng chỗ, không vào lụi được | | |

---

## 2. KYC — bắt buộc test **cả 2 luồng** (không suy diễn từ 1 luồng)

File nguồn: `app/Http/Requests/SubmitKycRequest.php` — `id_type` = `CMND_9` (9 số) hoặc `CCCD_12` (12 số).

| # | Kịch bản | Pass/Fail | Ghi chú |
|---|---|---|---|
| 2.1 | Vào `profile.kyc` (`/profile/kyc`) — hiện wizard 3 bước | | |
| 2.2a | **Luồng CMND**: chọn CMND_9, nhập đúng 9 số, upload ảnh mặt trước/sau + selfie, submit thành công | | |
| 2.2b | **Luồng CMND**: nhập sai định dạng (8 hoặc 10 số) → báo lỗi "CMND phải gồm đúng 9 chữ số" | | |
| 2.3a | **Luồng CCCD**: chọn CCCD_12, nhập đúng 12 số, upload đủ ảnh, submit thành công | | |
| 2.3b | **Luồng CCCD**: nhập sai định dạng (11 hoặc 13 số) → báo lỗi đúng | | |
| 2.4 | Ảnh mặt trước/sau/selfie thiếu 1 trong 3 → chặn submit, báo rõ thiếu ảnh nào | | |
| 2.5 | Sau khi submit, `id_number` không bị lộ ra ngoài (kiểm tra Network tab response JSON không chứa số CCCD/CMND plaintext) | | |
| 2.6 | Admin vào Filament **KycVerification** resource, mở đúng ca vừa nộp, xem được ảnh (route `admin.kyc-verifications.image`) cho cả 3 side: front/back/selfie | | |
| 2.7 | Admin duyệt (verify) hồ sơ KYC CMND → trạng thái user cập nhật đúng | | |
| 2.8 | Admin duyệt hồ sơ KYC CCCD → trạng thái user cập nhật đúng | | |
| 2.9 | Admin từ chối 1 hồ sơ KYC → user nhận được thông báo/lý do từ chối | | |
| 2.10 | User cố truy cập ảnh KYC của người khác qua URL trực tiếp → phải 403/404 (test bảo mật nhanh) | | |

---

## 3. Kịch bản chính Sprint 6: đăng ký → đăng tin → duyệt → AI match → GPS → notification → chat → resolved

Đây là luồng **quan trọng nhất**, chạy liên tục 1 lượt không ngắt quãng, đúng như sẽ demo thật.

| # | Bước | Route/Chức năng | Pass/Fail | Ghi chú |
|---|---|---|---|---|
| 3.1 | Đăng ký + KYC 1 tài khoản USER mới (dùng lại luồng ở mục 1–2) | | | |
| 3.2 | Đăng tin tìm trẻ | `missing.create` → `missing.store` | | case tạo ở trạng thái `PENDING` |
| 3.3 | Kiểm tra tin **chưa** hiện công khai khi còn PENDING | `missing.index`, `missing.by_slug` | | |
| 3.4 | Admin duyệt tin trong Filament **MissingChildResource** → status chuyển `APPROVED` | | | |
| 3.5 | Tin hiện công khai đúng, đúng slug/case_code, QR code load được (`missing.qr`) | | | |
| 3.6 | Upload/đăng 1 tin "tìm thấy trẻ" (Found Child) khớp mặt | `found-children.create/store` | | |
| 3.7 | AI service chạy match (gọi `/encode`, `/compare` hoặc `/verify` của ai-service) → kết quả xuất hiện trong Filament **FaceMatchResultResource** | | | kiểm tra AI service `/health` sống trước khi test bước này |
| 3.8 | Admin xác nhận match CONFIRMED | | | |
| 3.9 | Card gợi ý AI + gợi ý điểm bàn giao hiện trên trang chi tiết case | `/missing/{slug}` | | |
| 3.10 | Bấm "Tôi đang gặp trẻ lạc" → marker GPS cam xuất hiện, cập nhật realtime trên `/map` | event `ClueLocationUpdated` | | test bằng 2 thiết bị: 1 gửi GPS, 1 xem map |
| 3.11 | Notification bắn ra đúng nội dung theo từng role khi AI match CONFIRMED (Strategy Pattern J-03) — so sánh nội dung Admin nhận vs gia đình nhận | `notifications.center`, `notifications.dropdown` | | |
| 3.12 | Badge chuông thông báo cập nhật realtime không cần F5 | event `NotificationCountUpdated` | | |
| 3.13 | Gia đình chat với Admin | Livewire `Chat/ChatBox`, `Chat/ChatContainer` | | test đính kèm ảnh qua `admin-chat.attachment` |
| 3.14 | Chat realtime 2 chiều, round-robin gán đúng admin phụ trách | event `AdminChatMessageSent`, `ChatAssignmentService` | | dùng 2 tab admin khác nhau |
| 3.15 | Verifier xác nhận bàn giao | `verifier.dashboard` → `verifier.handover.confirm` | | login bằng tài khoản THIRD_PARTY_VERIFIER |
| 3.16 | Case chuyển trạng thái `FOUND`/`CLOSED`, hiện đúng ở trang `missing.found` | | | |
| 3.17 | `stats.index` cập nhật số liệu tổng (tổng ca, đã tìm thấy, tỷ lệ đoàn tụ) phản ánh đúng case vừa resolved | | | |

---

## 4. Toàn bộ route client (≥18 route) — test độc lập từng trang

| # | Route name | URL mẫu | Yêu cầu đăng nhập? | Pass/Fail | Ghi chú |
|---|---|---|---|---|---|
| 1 | `home` | `/` | Không | | banner AI, thống kê realtime |
| 2 | `handover-points.index` | `/diem-ban-giao` | Không | | |
| 3 | `safe-handover-process` | `/quy-trinh-ban-giao-an-toan` | Không | | |
| 4 | `sitemap` | `/sitemap.xml` | Không | | liệt kê đủ URL, XML hợp lệ |
| 5 | `missing.index` | `/missing` | Không | | filter tỉnh/giới tính/độ tuổi |
| 6 | `missing.by_slug` / `by_case_code` | `/missing/{slug}` | Không | | cả 2 kiểu URL đều load đúng |
| 7 | `missing.found` | `/missing/found/{id}` | Không | | |
| 8 | `missing.qr` | `/missing/detail/{id}/qr.svg` | Không | | |
| 9 | `missing.create` | `/missing/create` | **Có** | | |
| 10 | `map.index` | `/map` | Không | | heatmap + marker GPS |
| 11 | `map.heatmap-data` | `/map/heatmap-data` | Không | | trả JSON đúng |
| 12 | `stats.index` | `/stats` | Không | | |
| 13 | `articles.index` / `articles.show` | `/articles`, `/articles/{slug}` | Không | | JSON-LD schema (`Ctrl+U` kiểm tra) |
| 14 | `legal.terms` / `legal.privacy` / `legal.child-data` | `/legal/...` | Không | | |
| 15 | `profile.index` / `profile.update` | `/profile` | **Có** | | |
| 16 | `profile.kyc` | `/profile/kyc` | **Có** | | |
| 17 | `support.index` / `support.create` / `support.store` | `/support` | **Có** | | |
| 18 | `notifications.center` / `.dropdown` / `.show` / `.read` | `/notifications` | **Có** | | |
| 19 | `found-children.*` (resource CRUD đủ 7 route) | `/found-children` | **Có** | | |
| 20 | `found-children.photos.store` | POST | **Có** | | |
| 21 | `admin-chat.attachment` | `/admin-chat/attachment/{filename}` | **Có** | | chỉ xem được ảnh chat của chính mình |
| 22 | `admin.kyc-verifications.image` | | **Có, chỉ Admin** | | user thường phải bị 403 |
| 23 | `volunteer.dashboard` | `/volunteer/dashboard` | **Có, role VOLUNTEER** | | Gate `access-volunteer-features` |
| 24 | `verifier.dashboard` | `/verifier/dashboard` | **Có, role THIRD_PARTY_VERIFIER** | | chỉ hiện case đúng tỉnh phụ trách |
| 25 | `verifier.handover.confirm` | POST | **Có, role THIRD_PARTY_VERIFIER** | | test cả type=missing và type=found |
| 26 | `watch-area.store` / `.destroy` | POST/DELETE | **Có** | | |

☐ Tổng cộng đã test **26 route/nhóm route** ở trên — vượt yêu cầu tối thiểu 18 route.

---

## 5. Filament Admin — test đủ toàn bộ Resource + Dashboard

Vào `/admin`, đăng nhập bằng ADMIN. Với **mỗi Resource**, test đủ 4 thao tác: List → View → Create/Edit → Delete (nếu có), cộng thêm action đặc thù riêng.

| # | Resource | List | View | Create/Edit | Delete | Action đặc thù | Ghi chú |
|---|---|---|---|---|---|---|---|
| 1 | UserResource | | | | | đổi role → token cũ bị thu hồi (I-06) | test đăng nhập song song ở tab khác, đổi role, F5 tab kia phải văng ra |
| 2 | MissingChildResource | | | | | duyệt (APPROVED/REJECTED), xem review level | |
| 3 | FoundChildResource | | | | | | |
| 4 | ClueResource | | | | | | liên quan `NewClueSubmitted` event |
| 5 | FaceMatchResultResource | | | | | confirm/reject match | |
| 6 | KycVerificationResource | | | | | xem ảnh 3 side, duyệt/từ chối | đã test kỹ ở mục 2 |
| 7 | HandoverPointResource | | | | | | hiện đúng ở trang công khai `/diem-ban-giao` |
| 8 | SupportTicketResource | | | | | trả lời ticket → user thấy cập nhật ở `support.index` | |
| 9 | ThirdPartyVerifierProfileResource | | | | | gán tỉnh phụ trách cho verifier | |
| 10 | ArticleResource | | | | | publish → hiện ở `/articles`, JSON-LD đúng | |
| 11 | Admin Dashboard (`Pages/Dashboard.php`) | | | | | tổng hợp đúng | |
| 12 | Widget: StatsOverviewWidget | | | | | số liệu khớp `stats.index` | |
| 13 | Widget: MissingChildStatsWidget | | | | | | |
| 14 | Widget: MissingByProvinceChart | | | | | | |
| 15 | Widget: RecentMissingWidget | | | | | | |
| 16 | Widget: KycPendingStatsWidget | | | | | | |
| 17 | Widget: EscalationQueueWidget | | | | | hiện đúng case bị escalate quá ngưỡng thời gian | seed sẵn data escalation trước khi test |
| 18 | Admin Chat (trong panel) | | | | | round-robin, đính kèm ảnh, realtime | test bằng 2 tab admin khác nhau |

---

## 6. Kiểm tra phân quyền theo role (RBAC) — mỗi role chỉ thấy đúng phần của mình

Đăng nhập lần lượt 4 tài khoản, thử **cùng 1 URL/action** và ghi lại kết quả có đúng như kỳ vọng không.

| Chức năng/URL | USER | VOLUNTEER | THIRD_PARTY_VERIFIER | ADMIN |
|---|---|---|---|---|
| `/admin` (Filament panel) | phải bị chặn | phải bị chặn | phải bị chặn | vào được |
| `/volunteer/dashboard` | phải bị chặn | vào được | phải bị chặn (trừ khi cũng có quyền) | |
| `/verifier/dashboard` | phải bị chặn | phải bị chặn | vào được, đúng case theo tỉnh | |
| `verifier.handover.confirm` | phải bị chặn (403) | phải bị chặn | thực hiện được | |
| Xem ảnh KYC người khác | bị chặn | bị chặn | bị chặn | xem được |
| Nội dung notification khi AI match CONFIRMED | nội dung dành cho gia đình | | | nội dung dành cho admin (khác nhau — J-03) |
| Nút "Đăng tin tìm con" | thấy, dùng được | thấy | thấy | thấy |

☐ Ghi rõ bug nếu 1 role nhìn thấy nút/nội dung không thuộc về mình (leak UI theo role).

---

## 7. Realtime / WebSocket (nên test kèm điện thoại thật, xem thêm CROSS_BROWSER_CHECKLIST.md phần 2)

| # | Sự kiện | Kịch bản | Pass/Fail |
|---|---|---|---|
| 7.1 | `NotificationCountUpdated` | Badge chuông tăng ngay khi có thông báo mới, không cần F5 | |
| 7.2 | `AdminChatMessageSent` / `MessageSent` | Tin nhắn chat 2 chiều hiện realtime | |
| 7.3 | `ClueLocationUpdated` | Marker GPS trên `/map` di chuyển realtime khi có clue mới | |
| 7.4 | `NewClueSubmitted` | Admin nhận được thông báo khi có clue mới submit | |
| 7.5 | `PostStatusChanged` | User nhận thông báo khi tin của mình đổi trạng thái (duyệt/từ chối) | |
| 7.6 | `FaceMatchFound` | Notification bắn ra khi AI tìm thấy match mới | |
| 7.7 | `CommentHidden` | Comment bị ẩn biến mất realtime không cần reload | |

---

## 8. 8 route mới của Sprint 4 — verify chạy được trên domain thật, không 404/500

> ⚠️ Danh sách dưới đây tổng hợp từ `docs/demo-script-15min.md` (Sprint 4, nhóm H→O) — **hãy đối chiếu lại với git diff/PR list thực tế của Sprint 4 để chốt đúng 8 route**, vì file demo script không liệt kê tường minh số thứ tự. Lệnh gợi ý để tự chốt danh sách:
> ```
> git log --diff-filter=A --name-only -- routes/web.php | grep -A5 "sprint-4\|Sprint 4"
> git diff <commit-truoc-sprint4>..<commit-cuoi-sprint4> -- routes/web.php
> ```

| # | Route (ứng viên) | Nhóm Sprint 4 | HTTP status kỳ vọng | Pass/Fail |
|---|---|---|---|---|
| 1 | `handover-points.index` (`/diem-ban-giao`) | H | 200 | |
| 2 | `safe-handover-process` (`/quy-trinh-ban-giao-an-toan`) | H | 200 | |
| 3 | `verifier.dashboard` | I | 200 (login đúng role), 403 (role khác) | |
| 4 | `verifier.handover.confirm` | I | 200/302 sau khi confirm | |
| 5 | `notifications.center` / `notifications.dropdown` | J | 200 | |
| 6 | Escalation widget (Admin panel) | J | 200, hiện đúng dữ liệu | |
| 7 | `profile.kyc` (wizard 3 bước) | K | 200 | |
| 8 | `support.create` / `support.store` | M | 200/302 | |

☐ Từng route ở trên **đã chạy thử trực tiếp trên domain thật (không phải localhost)**, không gặp lỗi 404/500.

---

## 9. Tổng kết & bug log

| Mức độ | Số lượng phát hiện | Số đã báo GitHub Issue trong ngày | Ghi chú |
|---|---|---|---|
| 🔴 Blocker | | | |
| 🟠 Major | | | |
| 🟡 Minor | | | |

**Định nghĩa severity (thống nhất dùng chung khi tạo Issue):**
- **Blocker**: chặn hoàn toàn luồng demo Sprint 6 (đăng ký → ... → resolved), crash trang, mất dữ liệu, lỗ hổng bảo mật (leak KYC, bypass role).
- **Major**: 1 tính năng không hoạt động đúng nhưng có thể né qua để demo phần khác, UI vỡ nặng ở breakpoint chính.
- **Minor**: lỗi chính tả, style lệch nhẹ, không ảnh hưởng luồng chính.

**Template tạo GitHub Issue:**
```
Title: [D-03][Severity] Mô tả ngắn gọn
- Route/trang: 
- Role đang test: 
- Bước tái hiện: 1) 2) 3)
- Kết quả thực tế: 
- Kết quả mong đợi: 
- Screenshot/video: 
- Người phụ trách sửa: @
```

---

## Definition of Done (đối chiếu lại trước khi đóng task D-03)

- [ ] `docs/E2E_TEST_CHECKLIST.md` hoàn thành 100% các mục, đánh dấu Pass/Fail rõ ràng cho từng mục
- [ ] Mọi bug mức Blocker được báo ngay trong ngày phát hiện, không dồn tới cuối sprint
- [ ] Toàn bộ 8 route mới của Sprint 4 (mục 8) chạy được trên domain thật, không lỗi 404/500
