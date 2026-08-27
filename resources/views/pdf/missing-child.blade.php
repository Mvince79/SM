<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Hồ sơ tìm trẻ — {{ $case->full_name }}</title>
    <style>
        /* DomPDF không hỗ trợ tốt flexbox/grid → toàn bộ layout dùng table + float.
           default_font đã set 'DejaVu Sans' ở config/dompdf.php (font bundled sẵn
           trong dompdf, hỗ trợ đầy đủ ký tự có dấu tiếng Việt). Khai báo lại ở đây
           để chắc chắn không bị override bởi @page hoặc theme khác. */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.5;
        }

        @page {
            margin: 0;
        }

        .page {
            padding: 28px 32px 24px 32px;
        }

        /* ── Header thương hiệu ─────────────────────────────────────────── */
        table.header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid #ff3b30;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
        }

        .brand span {
            color: #ff3b30;
        }

        .brand-tagline {
            font-size: 9px;
            color: #64748b;
            font-weight: bold;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .doc-label {
            text-align: right;
            font-size: 10px;
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .case-code {
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            color: #ff3b30;
            margin-top: 2px;
        }

        /* ── Banner trạng thái ──────────────────────────────────────────── */
        .status-banner {
            width: 100%;
            background-color: #fff0ef;
            border: 1px solid #ffcfc9;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 12px;
            font-weight: bold;
            color: #e02b20;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Khối chính: ảnh trái, thông tin phải ──────────────────────── */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        td.photo-col {
            width: 170px;
            vertical-align: top;
            padding-right: 16px;
        }

        .photo-frame {
            width: 160px;
            height: 200px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            text-align: center;
            overflow: hidden;
        }

        .photo-frame img {
            width: 160px;
            height: 200px;
        }

        .photo-placeholder {
            width: 160px;
            height: 200px;
            border: 1px dashed #cbd5e1;
            text-align: center;
            color: #94a3b8;
            font-size: 9px;
            padding-top: 90px;
        }

        .full-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 8px;
        }

        td.info-col {
            vertical-align: top;
        }

        table.fact-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.fact-table td {
            padding: 3px 0;
            font-size: 10.5px;
            vertical-align: top;
        }

        table.fact-table td.fact-label {
            width: 100px;
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
        }

        /* ── Section block ──────────────────────────────────────────────── */
        .section {
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #ff3b30;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .section-body {
            font-size: 10.5px;
            color: #334155;
            text-align: justify;
        }

        /* ── Footer: QR + liên hệ + cảnh báo ───────────────────────────── */
        table.footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            border-top: 2px solid #e2e8f0;
            padding-top: 12px;
        }

        td.qr-col {
            width: 100px;
            vertical-align: top;
        }

        td.qr-col img {
            width: 90px;
            height: 90px;
            border: 1px solid #cbd5e1;
        }

        td.contact-col {
            vertical-align: top;
            padding-left: 14px;
            font-size: 9.5px;
            color: #475569;
        }

        .contact-title {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .privacy-note {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 8px;
            margin-top: 6px;
            font-size: 9px;
            color: #64748b;
            font-style: italic;
        }

        .hotline {
            font-size: 12px;
            font-weight: bold;
            color: #ff3b30;
        }

        .print-meta {
            margin-top: 10px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- ── Header ── --}}
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <div class="brand">TìmTrẻ<span>.vn</span></div>
                    <div class="brand-tagline">Vì con xứng đáng được về nhà</div>
                </td>
                <td style="width: 40%;">
                    <div class="doc-label">Hồ sơ tìm kiếm trẻ mất tích</div>
                    <div class="case-code">{{ $case->case_code ?: '#'.$case->id }}</div>
                </td>
            </tr>
        </table>

        {{-- ── Banner trạng thái ── --}}
        @php
            $statusText = match ($case->status) {
                'APPROVED' => 'ĐANG TÌM KIẾM — VUI LÒNG GIÚP LAN TỎA THÔNG TIN',
                'FOUND', 'CLOSED' => 'HỒ SƠ ĐÃ TÌM THẤY / ĐÃ ĐOÀN TỤ',
                default => strtoupper($case->status),
            };
        @endphp
        <div class="status-banner">{{ $statusText }}</div>

        {{-- ── Ảnh + thông tin chính ── --}}
        <table class="main-table">
            <tr>
                <td class="photo-col">
                    @if ($photoDataUri)
                        <div class="photo-frame">
                            <img src="{{ $photoDataUri }}" alt="Ảnh {{ $case->full_name }}">
                        </div>
                    @else
                        <div class="photo-placeholder">Chưa có ảnh</div>
                    @endif
                </td>
                <td class="info-col">
                    <div class="full-name">{{ $case->full_name }}</div>

                    <table class="fact-table">
                        <tr>
                            <td class="fact-label">Giới tính</td>
                            <td>
                                @if ($case->gender === 'MALE')
                                    Nam
                                @elseif($case->gender === 'FEMALE')
                                    Nữ
                                @else
                                    Chưa rõ
                                @endif
                            </td>
                            <td class="fact-label">Tuổi ước tính</td>
                            <td>{{ $case->age_estimate ? $case->age_estimate . ' tuổi' : 'Chưa rõ' }}</td>
                        </tr>
                        <tr>
                            <td class="fact-label">Chiều cao</td>
                            <td>{{ $case->height_cm ? $case->height_cm . ' cm' : '—' }}</td>
                            <td class="fact-label">Cân nặng</td>
                            <td>{{ $case->weight_kg ? $case->weight_kg . ' kg' : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fact-label">Nước da</td>
                            <td colspan="3">
                                @php
                                    $skinLabel = match ($case->skin_tone) {
                                        'LIGHT' => 'Trắng, sáng màu',
                                        'MEDIUM' => 'Ngăm vừa',
                                        'DARK' => 'Ngăm đen',
                                        default => 'Chưa rõ',
                                    };
                                @endphp
                                {{ $skinLabel }}
                            </td>
                        </tr>
                        <tr>
                            <td class="fact-label">Ngày mất tích</td>
                            <td colspan="3">
                                {{ $case->missing_date ? \Carbon\Carbon::parse($case->missing_date)->format('d/m/Y') : '—' }}
                                @if ($case->missing_time)
                                    lúc {{ $case->missing_time }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fact-label">Nơi mất tích</td>
                            <td colspan="3">
                                @php
                                    $address = collect([
                                        $case->address_detail,
                                        $case->ward,
                                        $case->province?->name,
                                    ])
                                        ->filter()
                                        ->implode(', ');
                                @endphp
                                {{ $address ?: 'Chưa cập nhật' }}
                            </td>
                        </tr>
                        @if ($case->clothes_desc || $case->clothes_color)
                            <tr>
                                <td class="fact-label">Trang phục</td>
                                <td colspan="3">
                                    {{ trim(($case->clothes_color ? $case->clothes_color . ' — ' : '') . $case->clothes_desc) }}
                                </td>
                            </tr>
                        @endif
                        @if ($case->special_marks)
                            <tr>
                                <td class="fact-label">Đặc điểm</td>
                                <td colspan="3">{{ $case->special_marks }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        {{-- ── Hoàn cảnh mất tích ── --}}
        @if ($case->circumstances)
            <div class="section">
                <div class="section-title">Hoàn cảnh mất tích</div>
                <div class="section-body">{{ $case->circumstances }}</div>
            </div>
        @endif

        {{-- ── Tiền thưởng (nếu có) ── --}}
        @if ($case->reward_info)
            <div class="section">
                <div class="section-title">Thông tin tiền thưởng</div>
                <div class="section-body">{{ $case->reward_info }}</div>
            </div>
        @endif

        {{-- ── Footer: QR + liên hệ (KHÔNG có contact_phone) ── --}}
        <table class="footer-table">
            <tr>
                <td class="qr-col">
                    <img src="{{ $qrDataUri }}" alt="QR hồ sơ">
                </td>
                <td class="contact-col">
                    <div class="contact-title">Quét mã QR để xem hồ sơ đầy đủ & cập nhật mới nhất trực tuyến</div>
                    <div>{{ $detailUrl }}</div>

                    @if ($case->contact_name || $case->contact_relation)
                        <div style="margin-top: 8px;">
                            <strong>Người báo tin:</strong>
                            {{ $case->contact_name ?: 'Ẩn danh' }}
                            @if ($case->contact_relation)
                                ({{ $case->contact_relation }})
                            @endif
                        </div>
                    @endif

                    <div style="margin-top: 4px;">
                        Mọi thông tin/manh mối xin liên hệ Tổng đài quốc gia bảo vệ trẻ em
                        <span class="hotline">111</span> hoặc cơ quan công an gần nhất.
                    </div>

                    <div class="privacy-note">
                        Vì lý do bảo mật thông tin cá nhân, số điện thoại liên hệ trực tiếp
                        không được in trên bản PDF này — vui lòng quét mã QR và đăng nhập trên
                        TìmTrẻ.vn để xem đầy đủ thông tin liên hệ, hoặc gọi hotline 111.
                    </div>
                </td>
            </tr>
        </table>

        <div class="print-meta">
            Bản in tạo lúc {{ $generatedAt->format('H:i d/m/Y') }} từ TìmTrẻ.vn —
            Vui lòng KHÔNG tự ý giao nhận trẻ giữa 2 cá nhân, luôn thực hiện qua điểm bàn giao an toàn.
        </div>
    </div>
</body>

</html>