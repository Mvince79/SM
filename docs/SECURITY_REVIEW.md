# Security Review - B-01 (OWASP Basic)

## 1. SQL Injection: PASS
Không có DB::raw() nối chuỗi với input người dùng.

## 2. XSS: PASS
{!! !!} chỉ dùng cho JSON-LD (bắt buộc) và article content 
(chỉ Admin ghi qua Filament RichEditor).

## 3. CSRF: PASS
14 file có <form>, tất cả đã có @csrf hoặc không cần 
(Livewire/GET/display-only).

## 4. Broken Access Control: PASS
- Manual test: user thường không vào được /admin, không xem/sửa 
  được dữ liệu user khác qua đổi ID URL.
- Mass Assignment: không có chỗ dùng $request->all() để ghi Model.
- Đã bổ sung $guarded cho User/MissingChild/KycVerification 
  làm lớp phòng thủ thứ 2.