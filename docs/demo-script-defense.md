# Kịch bản Demo — TìmTrẻ.vn

10 phút slide (đã có) + 35 phút demo thực tế + 15 phút hỏi đáp  
4 người, mỗi người lên 1 lần rồi nhường chỗ. Không quay lại.

---

## Timeline

| Phút | Người | Làm gì |
|------|-------|--------|
| 0–3 | Khang | Mở đầu, báo tin, tìm kiếm |
| 3–7 | Linh | AI tìm kiếm bằng ảnh, bản đồ GPS |
| 7–12 | Thanh | Chatbot, KYC, realtime |
| 12–17 | Nhân | Admin panel, duyệt case, KYC, AI match, **audit log**, chat |
| 17–22 | Khang | Đoàn tụ, bàn giao an toàn, **thông báo đa kênh**, thống kê |
| 22–27 | Linh | Tin tìm thấy, manh mối, tình nguyện |
| 27–32 | Thanh | Chat realtime, escalation, PDF |
| 32–35 | Nhân | Thống kê, SEO, responsive |

---

## Phần 1 — Khang: Mở đầu (3 phút)

Ngồi trước máy, tay trên bàn phím.

0. **Khung luồng nghiệp vụ (30 giây đầu)** — *giúp giám khảo theo dõi cả 35 phút*
   - Chiếu sơ đồ 7 bước (file `TimTre_UseCase_Updated.svg` / `.drawio`): **Báo tin → Duyệt → AI gợi ý → Admin xác nhận → Đoàn tụ → Bàn giao an toàn → Thông báo & Log**.
   - "35 phút tới, 4 bạn sẽ đi hết luồng này. Tôi bắt đầu ở bước 'Báo tin'."

1. **Trang chủ** (`/`)
   - "Đây là giao diện người dùng cuối — thiết kế tối giản, khoảng trắng rộng, chuẩn châu âu. Một bà mẹ mất con, hoảng loạn, vẫn nhìn vào đây và biết phải bấm vào đâu."

2. **Bấm "Báo tin tìm con" — demo định vị auto fill**
   - Điền nhanh: Bé An, 4 tuổi, chọn ảnh mẫu trong `storage/demo-assets/`.
   - Tại trường vị trí: bấm **"Lấy vị trí hiện tại"** (nếu phòng thi chặn GPS → chạm hoặc kéo thẳng marker trên bản đồ, vẫn ghim được điểm).
   - Map ghim → 2 ô **Tỉnh/Thành phố** và **Xã/Phường tự động điền, bị khóa (disabled)** — không ai gõ tay được.
   - "Hệ thống reverse-geocode **100% nội bộ**: point-in-polygon trên GeoJSON, **không gọi Google/Nominatim**. Vừa nhanh, vừa chống giả mạo hành chính. Thử ghim ra ngoài lãnh thổ VN → form báo đỏ, chặn gửi hồ sơ."
   - "Form đủ thông tin nhưng không rối — người dùng đang căng thẳng vẫn biết bấm đâu."
   - Submit → "Đã gửi, đang chờ duyệt."

3. **Tìm kiếm**
   - Gõ "An" → kết quả hiện ngay. Lọc theo tỉnh, giới tính, độ tuổi.
   - "Trẻ em thất lạc thường mất ở nơi quen. Cho nên người dùng cần tìm theo khu vực, không chỉ tên."

4. **Mở case chi tiết → QR → PDF**
   - "Mỗi hồ sơ có mã QR. In ra dán ở bệnh viện, trường học, chợ — ai quét là xem thông tin ngay."
   - Bấm xuất PDF. "In ra đóng hồ sơ, công an xử lý nhanh."

5. Đẩy ghế cho Linh.

---

## Phần 2 — Linh: AI & Bản đồ (4 phút)

Ngồi vào.

1. **Trước khi AI chạy**
   - "Khi người dùng upload ảnh, hệ thống kiểm tra định dạng, rồi chạy phát hiện khuôn mặt. Nếu không thấy mặt → báo ngay cho người dùng chụp lại. Sau đó mới encode."
   - Mở tab ảnh của case vừa tạo: "Đây là ảnh gốc, đây là khuôn mặt đã phát hiện, đây là vector 512 chiều đã mã hóa lưu trong database."
   - "Vector này không ai lấy được ra ngoài nếu không có key."

2. **Tìm kiếm bằng ảnh** (`/tim-kiem-bang-anh`)
   - "Nếu gia đình có ảnh cũ của con, họ upload lên đây. Hệ thống lấy vector 512 chiều, so với toàn bộ vector đang lưu."
   - Upload ảnh mẫu → vài giây → hiện top 5 kết quả, kèm tỷ lệ phần trăm.

3. **Pipeline AI**
   - "Upload → Validate → Phát hiện khuôn mặt (MTCNN) → Cắt 112×112 → Encode ArcFace (512-d) → Mã hóa → Lưu DB → So khớp cosine similarity → Trả về top-K."
   - "Chúng em dùng **DeepFace** — thư viện Python, mô hình **ArcFace**. Tất cả chạy **local** trên máy chủ, không gửi ảnh ra ngoài."
   - Ngưỡng: ≥85% gợi ý tự động, 70–85% cần người xác nhận, dưới 70% bỏ qua. "AI chỉ gợi ý, admin mới quyết định."

4. **Bản đồ** (`/map`)
   - Heatmap màu đỏ = nhiều ca mất tích. "Dữ liệu thực, mỗi điểm là một ca thật."
   - Zoom TP.HCM, bấm marker → thông tin case hiện ra.

5. **GPS + 34 tỉnh**
   - "Khi người dùng báo tin, lấy GPS thực tế từ điện thoại. Lưu latitude, longitude."
   - "Chúng em có **34 tỉnh thành** đầy đủ, ranh giới dạng **GeoJSON**. Khi chọn tỉnh, bản đồ tự zoom đến phạm vi đó. Tình nguyện viên đăng ký theo vùng — chỉ thấy manh mối trong tỉnh mình phụ trách."
   - "Tất cả chạy local, không gọi Google Maps hay API bản đồ bên ngoài."

6. Đẩy ghế cho Thanh.

---

## Phần 3 — Thanh: Chatbot + KYC + Realtime (5 phút)

Ngồi vào.

1. **Chatbot**
   - Gõ: *"Quy trình bàn giao con an toàn như thế nào?"*
   - Chatbot trả lời ngay, có link hướng dẫn.
   - "Chatbot này dùng **Gemini 3.5 Flash** của Google — bản free. Nếu câu hỏi phức tạp, AI trả lời tự nhiên. Nếu AI timeout 10 giây, hệ thống rơi về rule cũ. Người dùng không bao giờ thấy báo lỗi."

2. **Hỏi thử câu khó:** *"Tôi muốn làm tình nguyện viên"*
   - Chatbot hướng dẫn đăng ký, yêu cầu KYC.

3. **KYC** (`/profile/kyc`)
   - Wizard 3 bước: thông tin cá nhân → upload 2 mặt CCCD → chụp selfie đối chiếu.
   - "OCR đọc CCCD bằng **Tesseract** — chạy local, không gửi ảnh đi đâu. Sau đó AI so khớp mặt selfie với mặt trên CCCD. Nếu điểm >85% + thông tin trùng → tự động duyệt."

4. **Về AI local**
   - "Phần AI nhận diện mặt chạy hoàn toàn **local**. Microservice Python FastAPI + DeepFace, cổng nội bộ. Backend Laravel gọi qua HTTP có key bảo vệ."
   - "Chúng em không dùng Face++ hay AWS Rekognition. Vì ảnh trẻ em không được gửi ra ngoài."

5. Đẩy ghế cho Nhân.

---

## Phần 4 — Nhân: Admin Panel (5 phút)

Ngồi vào.

1. **Admin** (`/admin`)
   - Filament v5. Dashboard hiện số ca mới, KYC chờ duyệt, AI match cần xác nhận.
   - "Đây là giao diện người quản lý — công an xã, nhân viên NGO. Họ thấy ngay việc cần làm hôm nay."

2. **Duyệt case**
   - Mở case chờ duyệt → xem ảnh, mô tả → phê duyệt.
   - "Case được duyệt mới hiện công khai. Nếu nghi ngờ, admin từ chối hoặc gửi escalation."

3. **Duyệt KYC**
   - Mở tab KYC pending → xem ảnh CCCD, selfie, kết quả OCR, điểm AI.
   - "Hệ thống đã bôi đen thông tin nhạy cảm. Admin chỉ cần xác nhận hoặc bác bỏ."

4. **Xác nhận AI match**
   - "AI gợi ý cặp trẻ mất tích ↔ trẻ tìm thấy. Admin xem 2 ảnh, thấy 87% → xác nhận. Nếu AI sai → bác bỏ. Hệ thống ghi nhận để cải thiện."

5. **Nhật ký kiểm toán (Audit Logs)** — *tính năng log, điểm nhấn bảo mật*
   - Mở menu **Nhật ký kiểm toán** → bảng liệt kê mọi đổi trạng thái: **ai làm** (Quản trị viên / Hệ thống / Gia đình — badge màu), **lúc nào**, **đổi từ đâu sang đâu**.
   - Filter "Vai trò = Gia đình" hoặc "Hệ thống" để lọc nhanh đống log.
   - Bấm **"Xem chi tiết"** một dòng → modal hiện rõ **IP, User-Agent, lý do đổi trạng thái**.
   - "Mọi duyệt / từ chối / đoàn tụ đều ghi lại *ai-làm-lúc-nào-từ-IP-nào*. Đây là bằng chứng trách nhiệm giải trình, giúp phát hiện báo cáo giả. Trên từng hồ sơ cũng có tab **Hoạt động** ghi log tương tự."
   - "Giao diện modal này theo design system chung — badge có ngữ nghĩa, màu nhã, nhất quán với chuông thông báo và dashboard."

6. **Chat realtime**
   - Mở chat, thấy tin nhắn người dùng đang chờ → trả lời.
   - "Phân công tự động: admin nào rảnh nhất nhận. Không bị ùn ứ."

7. Đẩy ghế cho Khang.

---

## Phần 5 — Khang: Hoàn tất (3 phút)

Ngồi vào.

1. **Xác nhận đoàn tụ**
   - Mở case vừa tạo → "Xác nhận đã đoàn tụ" → trạng thái đổi.
   - "Gia đình tìm được con, xác nhận trên hệ thống. Admin nhận thông báo, tiến hành bàn giao an toàn."

2. **Điểm bàn giao** (`/diem-ban-giao`)
   - Danh sách công an, UBND, Tổng đài 111.
   - "Không để hai người lạ gặp ở chỗ vắng. Bắt buộc bàn giao tại điểm có người chứng kiến."

3. **Thông báo — pipeline đa kênh, đa vai trò**
   - Mở dropdown chuông → thông báo vừa sinh ra hiện ngay (realtime, **không reload** trang).
   - "Cùng một sự kiện `AI_MATCH_CONFIRMED`: gia đình nhận 'có kết quả khớp', admin nhận 'cần xác nhận' — khác nội dung, khác mức độ ưu tiên."
   - Chỉ vào email vừa đến (mở sẵn tab hộp thư demo): "Ngoài chuông (database), hệ thống gửi thêm **mail** và **push FCM** tuỳ loại. Quy tắc nằm trong config `notification-policies.php` — chuông luôn bật, mail/push theo từng event và vai trò, **không hard-code**."
   - "Event khẩn cấp (đoàn tụ chưa xác nhận): hệ thống **tự động nhắc** gia đình → nếu vẫn không phản hồi → **leo thang admin** → **tự động đóng hồ sơ**. Toàn bộ luồng này có test riêng (`EscalationAutoResolveOnReunionTest`)."

4. **Thống kê & giao diện** (`/stats`)
   - Tổng ca mất tích, đã tìm thấy, tỷ lệ đoàn tụ. Minh bạch.
   - "Giao diện chuông thông báo, modal kiểm toán, dashboard đều theo **một design system chung** — màu nhã, badge có ngữ nghĩa, khoảng trắng nhất quán, chuẩn tối giản châu âu chứ không rườm rà."
   - Mở `/sitemap.xml` để thấy cấu trúc SEO; resize xuống điện thoại → responsive.
   - "Đã chạy **Lighthouse thực tế** (có `docs/LIGHTHOUSE_REPORT.md`): Accessibility **92**, SEO **92**, Performance mobile **71** — đạt Definition of Done. Không phải tự nhận."

5. Kết thúc: "Chúng em đã chạy hết luồng thực tế — từ báo tin, định vị auto-fill, AI hỗ trợ, admin xử lý, đến đoàn tụ, bàn giao an toàn, kèm thông báo và nhật ký kiểm toán minh bạch."

---

## Câu hỏi dự kiến

### 1. AI so khớp khuôn mặt: làm sao, chính xác bao nhiêu, dùng thư viện gì, có dùng API bên ngoài không?

**Trả lời (Khang hoặc Thanh):**
- "Toàn bộ pipeline chúng em xây từ đầu: upload → kiểm tra ảnh → phát hiện khuôn mặt (MTCNN) → cắt 112×112 → encode ArcFace ra 512 vector → mã hóa → lưu DB → so khớp cosine similarity."
- "Thư viện là **DeepFace** — Python, mô hình **ArcFace**. Chạy trên microservice **FastAPI + Uvicorn**, cổng nội bộ. Backend Laravel gọi qua HTTP có key bảo vệ."
- "AI chạy **hoàn toàn local** trên máy chủ. Không gửi ảnh ra ngoài, không dùng Face++ hay AWS Rekognition."
- "Ngưỡng: ≥85% gợi ý tự động, 70–85% cần người xác nhận, dưới 70% bỏ qua. Trong lab đạt 97.8% trên dataset LFW. Nhưng ảnh trẻ em Việt Nam thực tế khó hơn — góc chụp, ánh sáng — nên luôn giữ quyền quyết định cho con người."

---

### 2. 34 tỉnh thành, GPS, ranh giới bản đồ: lấy ở đâu, có dữ liệu ranh giới không?

**Trả lời (Linh hoặc Nhân):**
- "34 tỉnh thành seed sẵn trong database. Ranh giới hành chính dùng **GeoJSON** — mỗi tỉnh có polygon riêng."
- "Khi người dùng báo tin, lấy **GPS thực tế** từ điện thoại, lưu latitude + longitude. Bản đồ Leaflet hiện marker đúng vị trí đó. Heatmap cũng dựa trên tọa độ thực."
- "Tình nguyện viên đăng ký theo vùng — hệ thống chỉ hiển thị manh mối trong tỉnh họ phụ trách. Tất cả chạy local, không gọi API bản đồ bên ngoài."

---

### 3. Tích hợp AI có quá dễ không? Hay chỉ cần gọi API bên ngoài?

**Trả lời (Thanh):**
- "Thực ra không hề dễ. DeepFace chạy nặng — ArcFace cần 2GB RAM, inference CPU mất 1–2 giây/ảnh."
- "Nếu gọi API công ngoài thì 5 phút xong. Nhưng chúng em chạy **local** để bảo vệ dữ liệu trẻ em — ảnh không rời khỏi máy chủ."
- "Khó khăn thật sự: (1) Cân bằng tải — nhiều người upload cùng lúc, AI xử lý tuần tự, phải cấu hình queue; (2) Xử lý lỗi — ảnh mờ, góc xấu, DeepFace báo lỗi phải catch và báo người dùng chụp lại; (3) Bảo mật — `face_encoding` mã hóa khi lưu, internal key bảo vệ, không cho truy cập ngoài."
- "Quan trọng nhất: AI chỉ là **công cụ gợi ý**. Con người mới quyết định cuối cùng."

---

### 4. Chatbot dùng của bên nào?

**Trả lời (Khang hoặc Thanh):**
- "Dùng **Gemini 3.5 Flash** của Google — bản free tier."
- "Nhưng không phụ thuộc 100%. Hệ thống có 2 lớp: (1) intent resolver local — xử lý câu hỏi thông thường bằng rule; (2) câu phức tạp mới chuyển sang Gemini."
- "Nếu Gemini timeout 10 giây hoặc lỗi, tự động rơi về layer 1. Người dùng không bao giờ thấy báo lỗi."

---

### 5. Tại sao chọn đề tài này?

**Trả lời (Khang):**
- "Thứ nhất, **đây là vấn đề có thật**. Mỗi năm có hàng nghìn trẻ em thất lạc. Làm sản phẩm giải quyết vấn đề thật, khác với làm quản lý thư viện để lấy điểm."
- "Thứ hai, **độ khó vừa phải nhưng đủ sâu**. Cần AI, cần bản đồ với 34 tỉnh + GeoJSON + GPS thực, cần realtime chat, cần quản trị đa vai trò. Đủ để thể hiện năng lực tổng thể."
- "Thứ ba, **có ý nghĩa xã hội**. Nếu đi vào vận hành, nó trực tiếp giúp gia đình và công an tìm kiếm hiệu quả hơn."

---

### 6. Điểm gì làm nhóm em khác với các nhóm khác?

**Trả lời (Khang hoặc Linh):**
- "Thứ nhất, **bảo vệ trẻ em từ thiết kế**, không phải thêm vào sau. Ảnh khuôn mặt mã hóa khi lưu, có ảnh blur riêng cho công khai, có bàn giao an toàn, có escalation khẩn cấp, có theo dõi hoạt động để phát hiện báo cáo giả."
- "Thứ hai, **AI chạy local hoàn toàn** — không phụ thuộc API bên ngoài. Kiến trúc microservice, tách riêng để scale sau này."
- "Thứ ba, **có test đầy đủ** — 64 file test, chạy xanh toàn bộ trước khi báo cáo. Nhiều nhóm khác không có test."
- "Thứ tư, **dữ liệu thực tế** — 34 tỉnh, GeoJSON, GPS thực, heatmap thực. Không phải mock data."

---

### 7. Chịu được bao nhiêu người dùng, chi phí thế nào?

**Trả lời (Nhân hoặc Khang):**
- "VPS 8GB RAM, 4 CPU → chịu được 100–200 người đồng thời. Đủ cho quy mô tỉnh nhỏ hoặc NGO."
- "AI service hiện tại **0 đồng** vì chạy local. Nếu cần scale lớn, có thể đưa lên GPU server hoặc chuyển sang API thương mại."

---

### 8. Bảo mật dữ liệu trẻ em?

**Trả lời (Khang):**
- "5 lớp: (1) Ảnh nhạy cảm lưu disk riêng, không public; (2) `face_encoding` mã hóa trong DB; (3) URL xem ảnh KYC cần quyền admin; (4) ảnh công khai có phiên bản làm mờ; (5) activity log — ai xem, sửa, duyệt đều ghi lại."
- "Có trang chính sách dữ liệu trẻ em, yêu cầu đồng ý điều khoản trước khi báo tin — tránh báo cáo giả."

---

## Checklist

- [ ] `composer dev` chạy hết (serve + queue + Vite + Reverb + AI service)
- [ ] Database `migrate:fresh --seed` có dữ liệu mẫu
- [ ] 3 tài khoản demo sẵn: Admin, User, Verifier
- [ ] `storage/demo-assets/` có ảnh mẫu
- [ ] Mở sẵn 2–3 tab: công khai, admin, user
- [ ] Admin: mở sẵn menu **Nhật ký kiểm toán** (có dữ liệu seed để lọc theo vai trò)
- [ ] Chuẩn bị sẵn 1 hộp thư demo (hoặc tab mail test) để khoe thông báo mail song song với chuông
- [ ] Demo auto-fill: nếu trình duyệt chặn GPS → dùng chạm/kéo marker (đã có fallback, vẫn ghim được)
- [ ] `TimTre_UseCase_Updated.svg` sẵn sàng chiếu khung luồng 7 bước
- [ ] Tắt notification điện thoại
- [ ] Remote + pin sạc đầy

---

## Lưu ý

- Không rush. 35 phút là đủ.
- Nếu lỗi → Khang nhận nhẹ nhàng, không hoảng.
- Trả lời ngắn, có ví dụ cụ thể. Không biết thì thừa nhận — hơn là bịa.
- Với doanh nghiệp: nhấn mạnh **khả thi vận hành** và **ý nghĩa xã hội**.
