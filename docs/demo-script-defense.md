# Kịch bản Demo — TìmTrẻ.vn

10 phút slide (đã có) + **25 phút demo thực tế** + hỏi đáp riêng  
4 người, mỗi người lên 1 lần rồi nhường chỗ. Không quay lại.

> **6 tính năng nổi bật sẽ được demo**:
>
> 1. **AI** local 100% (face matching, image search, chatbot, KYC)
> 2. **Luồng nghiệp vụ** end-to-end (báo tin → AI → duyệt → đoàn tụ → bàn giao)
> 3. **Thông báo đa kênh** (chuông + email + push, rule-based escalation)
> 4. **Giao diện cao điểm** (design system chung, Lighthouse ≥ 90 A11Y/SEO)
> 5. **Ticket hỗ trợ** (tạo – admin xử lý – user thấy cập nhật realtime)
> 6. **Log kiểm toán** (who / when / from IP)

---

## Timeline

| Phút  | Người     | Làm gì                                                                                                   |
| ----- | --------- | -------------------------------------------------------------------------------------------------------- |
| 0–6   | **Khang** | Mở đầu, luồng 7 bước, trang chủ (AI banner + UI), báo tin, tìm kiếm, QR/PDF, bàn giao, thống kê nhanh    |
| 6–14  | **Linh**  | AI pipeline (DeepFace/ArcFace), tìm kiếm bằng ảnh, bản đồ heatmap + GPS, 34 tỉnh, tin tìm thấy, manh mối |
| 14–19 | **Thanh** | Chatbot (Gemini), KYC (OCR + face match), realtime chat, escalation                                      |
| 19–25 | **Nhân**  | Admin panel, duyệt case, AI match xác nhận, KYC duyệt, **audit log**, **thông báo đa kênh**, **ticket**  |

---

## Phần 1 — Khang: Trang chủ → Báo tin → Tìm kiếm → Bàn giao → UI/Stats (6 phút)

0. **Khung luồng nghiệp vụ (30 giây)** — _giúp giám khảo theo dõi cả 25 phút_
    - Chiếu sơ đồ 7 bước (`TimTre_UseCase_Updated.svg`):
      **Báo tin → Duyệt → AI gợi ý → Admin xác nhận → Đoàn tụ → Bàn giao an toàn → Thông báo & Log**.
    - "25 phút tới, 4 bạn sẽ đi hết luồng này. Tôi bắt đầu ở bước 'Báo tin'."

1. **Trang chủ** (`/`)
    - "Giao diện người dùng cuối — thiết kế tối giản, khoảng trắng rộng, chuẩn châu âu. Một bà mẹ mất con, hoảng loạn, vẫn nhìn vào đây và biết phải bấm vào đâu."
    - Nhấn vào **bảng số thống kê** ở banner: "7.892 hồ sơ đang tìm, 3.147 đã tìm thấy, 1.842 đã đoàn tụ."
    - "Số liệu realtime — AI đã so khớp **97.8%** trên LFW. Mỗi số đều được cập nhật real-time từ hệ thống."
    - Highlight nhanh **design system**: "Mọi nút, badge, form đều dùng chung một design system — màu nhã, không rườm rà. Đạt Lighthouse A11Y **92**, SEO **92** (xem `docs/LIGHTHOUSE_REPORT.md`)."

2. **Báo tin tìm con** (`/missing/create`)
    - Điền nhanh: Bé An, 4 tuổi, chọn ảnh mẫu trong `storage/demo-assets/`.
    - Tại trường vị trí: bấm **"Lấy vị trí hiện tại"** (nếu phòng thi chặn GPS → chạm/kéo marker trên bản đồ, vẫn ghim được điểm).
    - Map ghim → 2 ô **Tỉnh/Thành phố** và **Xã/Phường tự động điền, bị khóa (disabled)**.
    - "Hệ thống reverse-geocode **100% nội bộ**: point-in-polygon trên GeoJSON, **không gọi Google/Nominatim**. Thử ghim ra ngoài lãnh thổ VN → form báo đỏ, chặn gửi hồ sơ."
    - Submit → "Đã gửi, đang chờ duyệt."

3. **Tìm kiếm** (`/missing`)
    - Gõ "An" → kết quả hiện ngay. Lọc theo tỉnh, giới tính, độ tuổi.
    - "Trẻ em thất lạc thường mất ở nơi quen. Cho nên người dùng cần tìm theo khu vực, không chỉ tên."

4. **Chi tiết case → QR → PDF**
    - Mở case vừa tạo → thanh bên hiện **mã QR** và nút **xuất PDF**.
    - "Mỗi hồ sơ có mã QR. In ra dán ở bệnh viện, trường học, chợ — ai quét là xem thông tin ngay."
    - Bấm xuất PDF: "In ra đóng hồ sơ, công an xử lý nhanh."
    - Nhấn vào thẻ **AI gợi ý** bên trong case: "Hệ thống AI đã so khớp 87% với một tin tìm thấy. Điểm số realtime từ cosine similarity — chưa xác nhận, chờ admin."

5. **Điểm bàn giao** (`/diem-ban-giao`)
    - "Không để hai người lạ gặp ở chỗ vắng. Bắt buộc bàn giao tại điểm có người chứng kiến."
    - Liệt kê công an, UBND, Tổng đài 111. Bấm một marker → thông tin liên hệ hiện ra.

6. **Thống kê & UI — nhanh** (`/stats`)
    - Resize trình duyệt xuống 375px → responsive không vỡ. "Mọi giao diện — banner, form, modal — đều một design system, màu nhã, badge có ngữ nghĩa."

---

## Phần 2 — Linh: AI & Bản đồ (8 phút)

1. **Pipeline AI — toàn bộ local** (1.5 phút)
    - Mở tab ảnh của case vừa tạo: "Đây là ảnh gốc, đây là khuôn mặt đã phát hiện, đây là vector 512 chiều đã mã hóa."
    - "Pipeline: upload → validate → phát hiện khuôn mặt (MTCNN) → cắt 112×112 → encode ArcFace (512-d) → **mã hóa** → lưu DB → so khớp cosine similarity → trả về top-K."
    - "Dùng **DeepFace** (Python, ArcFace), chạy trên **microservice FastAPI + Uvicorn, cổng nội bộ**. Backend Laravel gọi qua HTTP có key bảo vệ."
    - "Ngưỡng: ≥85% gợi ý tự động, 70–85% cần người xác nhận, dưới 70% bỏ qua. AI chỉ gợi ý, admin mới quyết định."

2. **Tìm kiếm bằng ảnh** (`/tim-kiem-bang-anh`) (1.5 phút)
    - "Nếu gia đình có ảnh cũ, họ upload. Hệ thống lấy vector 512 chiều, so với toàn bộ vector trong DB."
    - Upload ảnh mẫu → vài giây → hiện top 5 kết quả, kèm tỷ lệ % và dấu hiệu "AI gợi ý — chờ xác nhận admin."
    - "Chạy **100% local** trên máy chủ. Không dùng Face++, không AWS Rekognition. Vì ảnh trẻ em không được gửi ra ngoài."

3. **Bản đồ — heatmap + GPS + 34 tỉnh** (2 phút)
    - Mở `/map` → heatmap màu đỏ = nhiều ca. "Dữ liệu thực, mỗi điểm là một ca thật."
    - Zoom TP.HCM → bấm marker → thông tin case hiện ra.
    - "Khi người dùng báo tin, lấy **GPS thực tế** từ điện thoại, lưu lat/lng. Reverse-geocode 100% nội bộ."
    - "34 tỉnh thành seed sẵn, ranh giới GeoJSON. Chọn tỉnh → bản đề zoom đến phạm vi. Tình nguyện viên đăng ký theo vùng — chỉ thấy manh mối trong tỉnh mình."

4. **Tin tìm thấy + Manh mối** (2 phút)
    - Mở `/tin-tim-thay` → danh sách tin tìm thấy trẻ đã được duyệt.
    - Bấm một tin → thấy thông tin, ảnh (phiên bản mờ cho công khai), và nút **"Gửi manh mối"**.
    - Điền manh mối → submit → "Manh mối đã gửi, đang chờ xác minh."
    - "Hệ thống gửi thông báo cho gia đình và admin: có manh mối mới. Admin xác minh → cập nhật case."

---

## Phần 3 — Thanh: Chatbot, KYC & Realtime (5 phút)

1. **Chatbot** (1 phút)
    - Gõ: _"Quy trình bàn giao con an toàn như thế nài?"_
    - Chatbot trả lời ngay, có link hướng dẫn.
    - "Chatbot dùng **Gemini 3.5 Flash** (free tier). Nếu câu phức tạp, AI trả lời tự nhiên. Nếu timeout 10s, rơi về rule layer. Người dùng không bao giờ thấy lỗi."

2. **KYC** (`/profile/kyc`) (1.5 phút)
    - Wizard 3 bước: thông tin cá nhân → upload CCCD (2 mặt) → chụp selfie đối chiếu.
    - "OCR bằng **Tesseract** — chạy local. AI so khớp mặt selfie với mặt CCCD. Nếu >85% + thông tin trùng → tự động duyệt."
    - Chỉ kết quả: "Xác thực thành công — vai trình đã được bảo vệ."

3. **Realtime chat** (1 phút)
    - Mở chat → gửi tin nhắn. "Phân công tự động: admin rảnh nhất nhận. Round-robin, không bị ùn ứ."
    - Thể hiện tin nhắn online ngay (Reverb websocket).

4. **Escalation** (30s)
    - "Event khẩn cấp: nếu gia đình chưa xác nhận đoàn tụ sau N ngày được cấu hình → hệ thống tự động nhắc → leo thang admin → tự động đóng hồ sơ."
    - "Cả pipeline này có test riêng (`EscalationAutoResolveOnReunionTest`)."

---

## Phần 4 — Nhân: Admin Panel → Audit Log → Thông báo → Ticket (6 phút)

Mở tab admin.

1. **Admin panel** (`/admin`) (1 phút)
    - Dashboard Filament v5: số ca mới, KYC chờ duyệt, AI match cần xác nhận.
    - "Giao diện người quản lý — công an xã, nhân viên NGO. Họ thấy ngay việc cần làm hôm nay."
    - Mở **Found Children** → bấm **Duyệt** một case → xem ảnh, mô tả → phê duyệt.
    - Mở **KYC Verifications** → bấm **Duyệt** một hồ sơ → thấy ảnh CCCD (mờ), kết quả OCR, điểm AI face match.

2. **Xác nhận AI match** (30s)
    - Mở **FaceMatchResults** → thấy gợi ý AI 87% → xác nhận. "AI gợi ý, admin quyết định."

3. **Audit Log — Nhật ký kiểm toán** (1.5 phút)
    - Mở menu **Nhật ký kiểm toán** (AuditLogsResource, nhóm "Giám sát").
    - Bảng liệt kê mọi đổi trạng thái: **actor** (badge màu: Quản trị viên / Hệ thống / Gia đình), **lúc nào**, **từ đâu → đến đâu**.
    - Filter "Vai trò = Gia đình" → thấy log gia đình xác nhận đoàn tụ.
    - Bấm **"Xem chi tiết"** → modal hiện **IP, User-Agent** — "Mọi duyệt / từ chối / đoàn tụ đều ghi lại _ai-làm-lúc-nào-từ-IP-nào_. Đây là bằng chứng trách nhiệm giải trình."
    - "AuditLogResource **chỉ đọc** — không cho tạo/sửa/xóa, bảo vệ toàn hành. Trên từng hồ sớ có tab **Hoạt động** ghi log tương tự (Spatie activitylog)."

4. **Thông báo — pipeline đa kênh, đa vai trò** (1 phút)
    - Mở dropdown chuông → thông báo vừa sinh ra hiện ngay (**realtime, không reload**).
    - Cùng event `TICKET_CREATED`: admin nhận "có yêu cầu hỗ trợ mới", user nhận "đã nhận yêu cầu — đang xử lý".
    - Chỉ vào tab hộp thư demo: "Ngoài chuông (database), hệ thống gửi thêm **email** và **push FCM**. Quy tắc trong config `notification-policies.php` — **không hard-code**."
    - "Chuông luôn bật; email/push theo event + role. Admin nhận email cho việc CẦN HÀNH ĐỘNG, tránh bỏ sót."

5. **Ticket hỗ trợ — luồng end-to-end** (1.5 phút)
    - Chuyển sang tab user → mở `/support` → bấm **"Gửi yêu cầu mới"** (`/support/create`).
    - Chọn loại: **Nghi ngờ lừa đảo**, mức độ **Cao**, gõ mô tả ngắn → submit.
    - "Ticket đã gửi" — quay lại `/support` thấy thẻ mới hiện ngay, status **Đang mở**.
    - Chuyển sang tab admin → bảng **Yêu cầu hỗ trợ** (Filament, có **badge số** trên sidebar).
    - Mở ticket vừa tạo → thấy chi tiết: người gửi, loại, mức độ, mô tả.
    - Nhấn **"Trả lời"** → gõ phản hồi → lưu.
    - Quay lại tab user → **thông báo realtime** hiện trên chuông → mở **Trung tâm thông báo** → thấy dòng mới.
    - Mở `/support` → ticket cập nhật status **Đã giải quyết**, có phản hồi hiển thị.
    - "Ticket cũng được ghi vào **Audit Log** — mọi thay đổi trạng thái đều có trace."

6. Kết thúc: "Chúng em đã chạm hết luồng thực tế — từ báo tin, định vị, AI hỗ trợ, chatbot, KYC, admin xử lý, ticket hỗ trợ, đến bàn giao an toàn; kèm thông báo đa kênh, audit log minh bạch và giao diện đạt chuẩn Lighthouse."

---

## Câu hỏi dự kiến

### 1. AI so khớp khuôn mặt: làm sao, chính xác bao nhiêu, dùng thư viện gì, có dùng API bên ngoài không?

**Trả lời (Khang hoặc Thanh):**

- "Toàn bộ pipeline xây từ đầu: upload → kiểm tra ảnh → phát hiện khuôn mặt (MTCNN) → cắt 112×112 → encode ArcFace ra 512 vector → mã hóa → lưu DB → so khớp cosine similarity."
- "Thư viện **DeepFace** — Python, mô hình **ArcFace**. Chạy trên **microservice FastAPI + Uvicorn**, cổng nội bộ. Backend Laravel gọi qua HTTP có key bảo vệ."
- "AI chạy **hoàn toàn local** trên máy chủ. Không gửi ảnh ra ngoài, không dùng Face++ hay AWS Rekognition."
- "Ngưỡng: ≥85% gợi ý tự động, 70–85% cần người xác nhận, dưới 70% bỏ qua. Lab đạt 97.8% trên LFW. Ảnh trẻ em VN thực tế khó hơn — luôn giữ quyền quyết định cho con người."

---

### 2. 34 tỉnh thành, GPS, ranh giới bản đồ: lấy ở đâu, có dữ liệu ranh giới không?

**Trả lời (Linh hoặc Nhân):**

- "34 tỉnh thành seed sẵn trong database. Ranh giới hành chính dùng **GeoJSON** — mỗi tỉnh có polygon riêng."
- "Khi người dùng báo tin, lấy **GPS thực tế** từ điện thoại, lưu lat + lng. Bản đồ Leaflet hiện marker đúng vị trí. Heatmap dựa trên tọa độ thực."
- "Tình nguyện viên đăng ký theo vùng — chỉ hiển thị manh mối trong tỉnh phụ trách. Tất cả chạy local, không gọi API bản đồ bên ngoài."

---

### 3. Tích hợp AI có quá dễ không? Hay chỉ cần gọi API bên ngoài?

**Trả lời (Thanh):**

- "Thực ra không hề dễ. DeepFace chạy nặng — ArcFace cần 2GB RAM, inference CPU mất 1–2s/ảnh."
- "Nếu gọi API công ngoài thì 5 phút xong. Nhưng chúng chạy **local** để bảo vệ dữ liệu trẻ em."
- "Khó khăn: (1) Queue xử lý khi nhiều người upload cùng lúc; (2) Xử lý lỗi ảnh mờ — catch và báo chụp lại; (3) Bảo mật — `face_encoding` mã hóa, internal key, không cho truy cập ngoài."
- "AI chỉ là **công cụ gợi ý**. Con người mới quyết định cuối cùng."

---

### 4. Chatbot dùng của bên nào?

**Trả lời (Khang hoặc Thanh):**

- "Dùng **Gemini 3.5 Flash** của Google — free tier."
- "Không phụ thuộc 100%. Hệ thống 2 lớp: (1) intent resolver local rule xử lý câu thông thường; (2) câu phức tạp mới chuyển Gemini."
- "Nếu Gemini timeout 10s hoặc lỗi, tự động rơi về layer 1. Người dùng không bao giờ thấy lỗi."

---

### 5. Ticket hỗ trợ hoạt động như thế nào? Có liên kết với thông báo không?

**Trả lời (Nhân):**

- "Ticket là hệ thống yêu cầu hỗ trợ — người dùng tạo tại `/support/create` (category: BUG/COMPLAINT/FEEDBACK/FRAUD_SUSPECT, priority: LOW/MEDIUM/HIGH)."
- "Mỗi loại thay đổi trạng thái (tạo / trả lời / giải quyết / đóng) đều sinh **NotificationEvent** — admin nhận chuông + email, user nhận chuông + email theo rule trong `notification-policies.php`."
- "Quản lý trong Filament Admin: bảng có **badge số** phiếu mở, filter theo category/priority/status, admin trả lời qua RelationManager Replies."
- "Ticket cũng được log vào **AuditLog** — mọi thay đổi status đều có trace (ai / lúc nào / IP)."

---

### 6. Tại sao chọn đề tài này?

**Trả lời (Khang):**

- "Thứ nhất, **đây là vấn đề có thật**. Hàng nghìn trẻ em thất lạc mỗi năm. Làm sản phẩm giải quyết vấn đề thật, khác với quản lý thư viện để lấy điểm."
- "Thứ hai, **độ khó vừa phải nhưng đủ sâu**. Cần AI, bản đồ 34 tỉnh + GeoJSON + GPS, realtime chat, quản trị đa vai trò, ticket, log kiểm toán. Đủ thể hiện toàn bộ năng lực."
- "Thứ ba, **ý nghĩa xã hội**. Nếu đi vào vận hành, trực tiếp giúp gia đình và công an tìm kiếm hiệu quả."

---

### 7. Điểm gì làm nhóm khác với các nhóm khác?

**Trá lời (Khang hoặc Linh):**

- "Thứ nhất, **bảo vệ trẻ em từ thiết kế**, không phải thêm sau. Ảnh khuôn mặt mã hóa khi lưu, ảnh công khai có phiên bản mờ, có bàn giao an toàn, escalation khẩn cấp, activity log phát hiện báo cáo giả."
- "Thứ hai, **AI chạy local hoàn toàn** — không phụ thuộc API bên ngoài. Kiến trúc microservice, tách riêng để scale."
- "Thứ ba, **có test đầy đủ** — 64 file test, xanh hết. Nhiều nhóm khác không có test."
- "Thứ tư, **dữ liệu thực** — 34 tỉnh, GeoJSON, GPS, heatmap thực. Không mock data."

---

### 8. Chịu được bao nhiêu người dùng, chi phí?

**Trả lời (Nhân hoặc Khang):**

- "VPS 8GB RAM, 4 CPU → 100–200 người đồng thời. Đủ cho quy mô tỉnh nhỏ hoặc NGO."
- "AI service hiện tại **0 đồng** vì chạy local. Nếu cần scale, lên GPU server hoặc chuyển API thương mại."

---

### 9. Bảo mật dữ liệu trẻ em?

**Trả lời (Khang):**

- "5 lớp: (1) ảnh nhạy cảm lưu disk riêng, không public; (2) `face_encoding` mã hóa trong DB; (3) URL ảnh KYC cần quyền admin; (4) ảnh công khai có phiên bản mờ; (5) activity log — ai xem/sửa/duyệt đều ghi lại."
- "Có trang chính sách dữ liệu trẻ em, yêu cầu đồng ý trước khi báo tin — tránh báo cáo giả."

---

## Checklist

- [ ] `composer dev` chạy hết (serve + queue + Vite + Reverb + AI service)
- [ ] Database `migrate:fresh --seed` có dữ liệu (provinces, handover points, articles)
- [ ] Tài khoản demo: Admin (`admin@timkiemtre.vn` / `Admin@123456`), User, Verifier
- [ ] `storage/demo-assets/` có ảnh mẫu (kyc-samples, missing children)
- [ ] Mở sẵn 3 tab: trang chủ (user), admin panel, support (user) — không cần tab stats (Khang mở trực tiếp)
- [ ] Admin: mở sẵn menu **Nhật ký kiểm toán** (có dữ liệu để lọc theo vai trò)
- [ ] Admin: mở sẵn menu **Yêu cầu hỗ trợ** (có badge số, có thể có ticket demo seed sẵn)
- [ ] Mở sẵn tab hộp thư demo để khoe thông báo mail song song với chuông
- [ ] `TimTre_UseCase_Updated.svg` sẵn sàng chiếu khung luồng 7 bước
- [ ] Demo auto-fill: nếu trình duyệt chặn GPS → dùng chạm/kéo marker (có fallback)
- [ ] Tắt thông báo điện thoại
- [ ] Remote + pin sạc đầy

---

## Lưu ý

- Không rush. 25 phút là đủ — Khang 6', Linh 8', Thanh 5', Nhân 6'.
- Nếu lỗi → người đang demo nhận nhẹ nhàng, không hoảng. Có dự phòng screenshot.
- Trả lời ngắn, có ví dụ cụ thể. Không biết thì thừa nhận — hơn là bịa.
- Với doanh nghiệp: nhấn mạnh **khả thi vận hành** và **ý nghĩa xã hội**.
