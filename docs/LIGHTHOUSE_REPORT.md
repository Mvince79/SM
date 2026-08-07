# Lighthouse / PageSpeed Insights Audit Report

**Task:** F-03 — SEO On-page Final Check + Lighthouse Audit
**Người phụ trách:** Linh
**Công cụ:** Google PageSpeed Insights (dựa trên Lighthouse engine)
**Ngày audit:** 07/08/2026
**Bản deploy:** https://timtre.duckdns.org/ (production)

---

## 1. Tổng hợp điểm số — TRƯỚC khi sửa

| Trang | URL | Device | Performance | Accessibility | Best Practices | SEO |
|---|---|---|---|---|---|---|
| Trang chủ | `/` | Mobile | 71 🟡 | 92 🟢 | 96 🟢 | 92 🟢 |
| Trang chủ | `/` | Desktop | 90 🟢 | 92 🟢 | 96 🟢 | 92 🟢 |
| Danh sách hồ sơ | `/missing` | Mobile | 64 🟠 | 88 🟡 | 100 🟢 | 92 🟢 |
| Danh sách hồ sơ | `/missing` | Desktop | 92 🟢 | 88 🟡 | 100 🟢 | 92 🟢 |
| Chi tiết hồ sơ | `/missing/detail/1` | Mobile | 77 🟡 | 88 🟡 | 96 🟢 | 66 🔴 |
| Chi tiết hồ sơ | `/missing/detail/1` | Desktop | 94 🟢 | 87 🟡 | 96 🟢 | 66 🔴 |

**Chú giải:** 🟢 ≥90 (Tốt) · 🟡 50–89 (Cần cải thiện) · 🟠 <70 (Yếu) · 🔴 Lỗi nghiêm trọng

### Kiểm tra Definition of Done (bắt buộc: Trang chủ, Mobile)
- [x] Performance ≥ 70 → **71** ✅ Đạt
- [x] Accessibility ≥ 90 → **92** ✅ Đạt
- [x] SEO ≥ 90 → **92** ✅ Đạt

> DoD bắt buộc của task đã đạt ngay từ lần audit đầu. Các mục sửa lỗi bên dưới nhằm nâng chất lượng tổng thể cho cả 3 loại trang, không chỉ riêng trang chủ.

---

## 2. Danh sách lỗi phát hiện & mức độ ưu tiên

### 🔴 Nghiêm trọng — cần escalate

| # | Lỗi | Trang bị ảnh hưởng | Ghi chú |
|---|---|---|---|
| 1 | **Page is blocked from indexing** — trang không thể xuất hiện trên Google Search | `/missing/<slug>` (Mobile + Desktop) | Đã kiểm tra `robots.txt` — **không** phải nguyên nhân (chỉ chặn `/missing/create`, `/missing/store`, không chặn `/missing/<slug>`). Nghi ngờ do thẻ `<meta name="robots" content="noindex">` bị set cứng trong template trang chi tiết. **Cần dev kiểm tra source code.** |
| 2 | **Sitemap trỏ về localhost** — `Sitemap: http://127.0.0.1:8000/sitemap.xml` trong `robots.txt` | Toàn site | Cấu hình sót lại từ môi trường dev, Google không thể truy cập sitemap này từ production. Cần đổi thành `https://timtre.duckdns.org/sitemap.xml`. |

> **Đây là 2 lỗi nằm ngoài phạm vi DoD gốc của F-03** (không thuộc danh sách "alt text / contrast / meta description"), nhưng có mức độ ảnh hưởng cao vì đây là nền tảng cộng đồng tìm trẻ mất tích — nếu Google không index được hồ sơ, mục tiêu cốt lõi của sản phẩm bị ảnh hưởng trực tiếp. **Đề xuất tạo task riêng, ưu tiên cao hơn 🟢 THẤP, giao cho backend/DevOps xử lý.**

### 🟡 Cần sửa — trong phạm vi DoD (dễ sửa, ảnh hưởng điểm tốt)

| # | Lỗi | Trang bị ảnh hưởng | Cách sửa |
|---|---|---|---|
| 3 | Thiếu `alt` text ảnh | Cần rà soát thủ công (avatar dicebear, logo) | Thêm `alt="Ảnh đại diện [Tên]"`; ảnh trang trí dùng `alt=""` |
| 4 | Contrast màu chưa đạt WCAG AA | Cả 3 loại trang, cả Mobile & Desktop | Kiểm tra bằng WebAIM Contrast Checker, ưu tiên nút CTA đỏ/hồng và badge "Đang tìm gấp" |
| 5 | Thiếu `meta description` | `/missing`, `/missing/<slug>` | Trang chủ đã có sẵn. Cần bổ sung description động cho danh sách và từng hồ sơ |
| 6 | Heading không theo thứ tự tuần tự (skip level) | Cả 3 loại trang | Không nhảy từ `h1` sang `h3`, bỏ qua `h2` |
| 7 | Trang chủ thiếu `<main>` landmark | Trang chủ | Bọc nội dung chính trong thẻ `<main>` |
| 8 | Form elements thiếu `<label>` liên kết | Trang chi tiết | Rà soát form tìm kiếm/filter |
| 9 | `button`, `link`, `menuitem` thiếu tên (accessible name) | Trang chi tiết | Thêm `aria-label` cho các nút icon (gọi điện, chia sẻ, gửi manh mối) |
| 10 | Ảnh thiếu `width`/`height` tường minh | Trang chi tiết | Thêm thuộc tính để giảm Cumulative Layout Shift |

### 🟠 Performance — cải thiện nếu còn thời gian trong 2 giờ

| # | Vấn đề | Trang ảnh hưởng nhiều nhất | Est. savings |
|---|---|---|---|
| 11 | Render-blocking requests | `/missing` Mobile | ~2.78s |
| 12 | Cache lifetime chưa tối ưu (thiếu `Cache-Control`) | Toàn site | ~277–404 KiB |
| 13 | Unused JavaScript | Toàn site | ~255–335 KiB |
| 14 | Duplicated JavaScript | Trang chi tiết | ~32 KiB |

> Theo đúng lưu ý của task: **không cố đạt Performance 100 tuyệt đối**. Chỉ cần đưa `/missing` (Mobile, hiện 64) lên ngưỡng an toàn ≥70 là đủ trong phạm vi 2 giờ.

---

## 3. Tổng hợp điểm số — SAU khi sửa

*(Điền lại sau khi hoàn tất sửa lỗi mục 2, đính kèm ảnh chụp màn hình bên dưới)*

| Trang | Device | Performance | Accessibility | Best Practices | SEO |
|---|---|---|---|---|---|
| Trang chủ | Mobile | _TBD_ | _TBD_ | _TBD_ | _TBD_ |
| Trang chủ | Desktop | _TBD_ | _TBD_ | _TBD_ | _TBD_ |
| `/missing` | Mobile | _TBD_ | _TBD_ | _TBD_ | _TBD_ |
| `/missing` | Desktop | _TBD_ | _TBD_ | _TBD_ | _TBD_ |
| Chi tiết hồ sơ | Mobile | _TBD_ | _TBD_ | _TBD_ | _TBD_ |
| Chi tiết hồ sơ | Desktop | _TBD_ | _TBD_ | _TBD_ | _TBD_ |

---

## 4. Ảnh chụp màn hình

### Trước khi sửa
- [ ] Trang chủ — Mobile
- [ ] Trang chủ — Desktop
- [ ] `/missing` — Mobile
- [ ] `/missing` — Desktop
- [ ] Chi tiết hồ sơ — Mobile
- [ ] Chi tiết hồ sơ — Desktop

### Sau khi sửa
- [ ] *(đính kèm sau khi audit lại)*

---

## 5. Kết luận & đề xuất tiếp theo

1. ✅ DoD bắt buộc của F-03 (Trang chủ Mobile: Performance ≥70, Accessibility ≥90, SEO ≥90) **đã đạt**.
2. 🟡 Đã lên danh sách 8 lỗi dễ sửa (mục 3–10) trong phạm vi công sức thấp — thực hiện trong 2 giờ như ước lượng ban đầu.
3. 🔴 Phát hiện 2 lỗi nghiêm trọng ngoài phạm vi DoD gốc (noindex + sitemap localhost) — **đề xuất tạo task riêng ưu tiên cao**, không thuộc trách nhiệm sửa trực tiếp của F-03 trừ khi người phụ trách có quyền truy cập code/server.
4. Khuyến nghị chạy lại Lighthouse cho toàn bộ 6 tổ hợp (3 trang × 2 device) sau khi sửa xong để cập nhật bảng mục 3.
