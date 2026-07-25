# O-06 — Demo KYC CMND & CCCD

Toàn bộ ảnh trong thư mục này là dữ liệu tổng hợp dành riêng cho kiểm thử.
Các thẻ đều ghi rõ `DEMO`, không mô phỏng quốc huy, mã bảo an hoặc giấy tờ do
cơ quan nhà nước phát hành và không có giá trị xác minh danh tính.

## Tài khoản

| Luồng | Email | Mật khẩu | Số giấy tờ |
|---|---|---|---|
| CMND 9 số | `demo.kyc.cmnd@timtre.vn` | `Demo@123456` | `012345678` |
| CCCD 12 số | `demo.kyc.cccd@timtre.vn` | `Demo@123456` | `079092000001` |

## Chuẩn bị trước khi demo UI

```bash
php artisan demo:verify-kyc --prepare-only
```

Lệnh trên:

1. Tạo hoặc reset hai tài khoản về `kyc_status = NONE`.
2. Xóa hồ sơ KYC demo cũ.
3. Chép hai bộ ảnh vào private storage.

Sau đó đăng nhập từng tài khoản, mở `/profile/kyc` và tải đúng ba ảnh trong
thư mục `cmnd/` hoặc `cccd/`:

- `front.jpg`
- `back.jpg`
- `selfie.jpg`

Nếu queue không dùng kết nối `sync`, chạy worker trước khi submit:

```bash
php artisan queue:work --queue=kyc
```

## Rehearsal tự động

Trước buổi trình bày, chạy:

```bash
php artisan demo:verify-kyc
```

Lệnh chạy đồng bộ OCR và ArcFace cho cả hai bộ ảnh. Kết quả đạt yêu cầu khi:

- OCR nhận đúng `CMND_9` và `CCCD_12`.
- Số giấy tờ và họ tên khớp dữ liệu tài khoản.
- Điểm khuôn mặt đạt ngưỡng `FACE_MATCH_HIGH_CONFIDENCE_THRESHOLD`.
- Cả user và hồ sơ KYC chuyển sang `VERIFIED`.

Sau rehearsal, chạy lại lệnh `--prepare-only` để reset trước khi demo UI.

## Dịch vụ bắt buộc

- Face Match service chạy tại `FACE_MATCH_SERVICE_URL`.
- Một OCR provider hợp lệ:
  - `EKYC_PROVIDER=fpt_ai` cùng `FPT_AI_API_KEY`; hoặc
  - `EKYC_PROVIDER=vnpt` cùng token VNPT; hoặc
  - `EKYC_PROVIDER=tesseract` với binary Tesseract và language pack
    `vie+eng`.

Nếu thiếu provider, lệnh rehearsal dừng với lỗi rõ ràng thay vì seed sẵn kết
quả giả.

## Nguồn ảnh

Hai chân dung là người hoàn toàn hư cấu, được tạo bằng công cụ sinh ảnh cho
fixture O-06. Ảnh thẻ, watermark và phần chữ được dựng cục bộ theo cách xác
định để OCR có thể đọc ổn định. Không sử dụng ảnh hoặc thông tin của người thật.
