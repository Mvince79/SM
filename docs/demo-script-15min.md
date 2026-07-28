# Kịch bản Demo TìmTrẻ.vn — 15 phút (Sprint 4)

> Người trình bày: Khang (điều phối) — mỗi thành viên demo đúng phần mình phụ trách.
> Môi trường: chạy `php artisan serve` + `npm run dev`, đã `migrate:fresh --seed`.
> Tài khoản demo: 1 Admin, 2 Third-Party Verifier (O-02), case mẫu đã seed sẵn.

---

## Phần 1 — Mở đầu: Báo tin & Tìm kiếm (2.5 phút) _(Sprint 3.5)_

1. Vào trang chủ `/` — giới thiệu banner AI 97.8%, thống kê realtime.
2. Vào `/missing` — lọc theo tỉnh/giới tính/độ tuổi, mở 1 hồ sơ chi tiết.
3. Bấm "+ Báo tin tìm con" (`missing.create`) — điền nhanh 1 case demo.

## Phần 2 — AI Match & Bản đồ GPS (2.5 phút) _(Sprint 3.5 + PB-09b/c)_

1. Vào `/map` — chỉ heatmap mật độ case (PB-09c), toggle mượt không giật.
2. Demo "Tôi đang gặp trẻ lạc" trên 1 hồ sơ — marker cam xuất hiện, di chuyển theo GPS thực (PB-09b).
3. Vào 1 case đã có AI match CONFIRMED — chỉ khung gợi ý AI + card gợi ý điểm bàn giao (H-05).

## Phần 3 — 🆕 Bàn giao an toàn (1 phút) _(Sprint 4 — Nhóm H)_

1. Từ card gợi ý điểm bàn giao trên hồ sơ case — bấm xem chi tiết.
2. Vào `/diem-ban-giao` — chỉ danh sách công khai các điểm bàn giao (công an, UBND, NGO, Tổng đài 111).
3. Mở `/quy-trinh-ban-giao-an-toan` — trang hướng dẫn quy trình an toàn, nhấn mạnh không tự ý giao nhận trẻ giữa 2 cá nhân.

## Phần 4 — 🆕 Verifier xác nhận bàn giao (1 phút) _(Sprint 4 — Nhóm I)_

1. Đăng nhập tài khoản demo Third-Party Verifier (O-02 đã tạo sẵn).
2. Vào `verifier.dashboard` — chỉ dashboard chỉ hiện đúng case trong tỉnh phụ trách (I-03).
3. Bấm xác nhận đã bàn giao 1 case — verify trạng thái case cập nhật `handover_confirmed_at`.
4. (Admin) đổi role user demo → verify token cũ bị thu hồi ngay (I-06), user phải đăng nhập lại.

## Phần 5 — 🆕 Trung tâm thông báo động theo role (1 phút) _(Sprint 4 — Nhóm J)_

1. Đăng nhập lại bằng Admin — mở dropdown chuông thông báo.
2. Vào `notifications.center` — chỉ danh sách thông báo, trạng thái đã đọc/chưa đọc realtime.
3. So sánh nhanh: cùng 1 sự kiện AI match CONFIRMED, nội dung thông báo khác nhau giữa Admin và gia đình (J-03 Strategy Pattern).

## Phần 6 — 🆕 Escalation khẩn cấp (1 phút) _(Sprint 4 — J-05/J-06)_

1. (Chuẩn bị trước) 1 thông báo khẩn cấp chưa đọc quá ngưỡng thời gian cấu hình.
2. Vào Admin Panel `/admin` — chỉ widget "Cần hỗ trợ khẩn cấp" (J-06), hiện đúng case bị escalate.
3. Giải thích nhanh: `EscalationQueue` + Scheduled Command quét mỗi 5 phút, ngưỡng đổi được qua config không cần sửa code.

## Phần 7 — KYC, Support Ticket, SEO & Polish (3 phút) _(Sprint 4 — K, M, L, N)_

1. `/profile/kyc` — demo wizard KYC 3 bước (K-05), upload CCCD, đối chiếu selfie.
2. `support.create` → gửi 1 ticket — chuyển qua Filament Admin chỉ ticket vừa nhận, trả lời — quay lại `support.index` chỉ lịch sử ticket cập nhật.
3. Mở 2 trình duyệt (2 admin khác nhau) — cùng mở chat với 2 user khác nhau → chỉ round-robin gán đúng, không đụng nhau (M-04).
4. `/articles` → mở 1 bài viết — `Ctrl+U` chỉ nhanh `<script type="application/ld+json">` (L-05), và `/sitemap.xml` liệt kê đủ URL.
5. Resize trình duyệt xuống 375px — chỉ responsive không vỡ layout, toggle dark mode không flash trắng (PB-UI-08/10).

## Phần 8 — Thống kê tổng & Q&A (1 phút)

1. Vào `stats.index` — chỉ số liệu tổng: tổng ca tiếp nhận, đã tìm thấy, tỷ lệ đoàn tụ.
2. Kết luận: tổng số tính năng Sprint 4 đã triển khai đầy đủ H → O, mở phần hỏi đáp.

---

## Checklist chạy thử trước ngày báo cáo chính thức

- [ ] Toàn bộ 8 phần trên chạy thử liên tục đúng 15 phút (± 30s)
- [ ] `php artisan test` xanh hết toàn bộ Sprint 4 (H → O)
- [ ] 2 tài khoản Verifier demo (O-02) đăng nhập được, đúng phân quyền theo tỉnh
- [ ] Dữ liệu escalation demo (O-03) đã seed sẵn, không cần chờ 5 phút thật khi live demo
- [ ] KYC demo data (O-06) đã seed đủ case pending/verified để demo nhanh không cần upload tay
