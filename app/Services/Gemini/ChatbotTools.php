<?php

namespace App\Services\Gemini;

use App\Models\HandoverPoint;
use App\Models\MissingChild;
use App\Services\HandoverPointFinderService;

/**
 * Các "hàm" (tools) Gemini được phép gọi khi cần dữ liệu thật.
 *
 * QUY TẮC QUYỀN RIÊNG TƯ: chỉ trả dữ liệu CÔNG KHAI.
 * TUYỆT ĐỐI không trả về số điện thoại người thân, địa chỉ nhà riêng,
 * tên đầy đủ gia đình ngoài tên trẻ (đã cho phép), hay bất kỳ dữ liệu KYC.
 */
class ChatbotTools
{
    public function __construct(
        private HandoverPointFinderService $finder,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function call(string $name, array $args, ?float $lat, ?float $lng): array
    {
        return match ($name) {
            'searchMissingChildren' => $this->searchMissingChildren($args),
            'getCaseByCode' => $this->getCaseByCode($args),
            'getProjectStats' => $this->getProjectStats(),
            'getNearestHandoverPoints' => $this->getNearestHandoverPoints($args, $lat, $lng),
            'getSafeHandoverProcess' => $this->getSafeHandoverProcess(),
            default => ['error' => 'Hàm không được hỗ trợ: '.$name],
        };
    }

    /**
     * Tìm hồ sơ trẻ mất tích (chỉ công khai).
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function searchMissingChildren(array $args): array
    {
        $query = MissingChild::query()->where('status', MissingChild::STATUS_APPROVED);

        if (! empty($args['keyword'])) {
            $keyword = (string) $args['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('full_name', 'like', '%'.$keyword.'%')
                    ->orWhere('case_code', 'like', '%'.$keyword.'%');
            });
        }

        if (! empty($args['province'])) {
            $province = (string) $args['province'];
            $query->whereHas('province', fn ($p) => $p->where('name', 'like', '%'.$province.'%'));
        }

        if (isset($args['age_min']) && is_numeric($args['age_min'])) {
            $query->whereNotNull('age_estimate')->where('age_estimate', '>=', (int) $args['age_min']);
        }

        if (isset($args['age_max']) && is_numeric($args['age_max'])) {
            $query->whereNotNull('age_estimate')->where('age_estimate', '<=', (int) $args['age_max']);
        }

        $rows = $query->with('province')
            ->orderByDesc('missing_date')
            ->limit(10)
            ->get();

        return [
            'count' => $rows->count(),
            'results' => $rows->map(fn (MissingChild $c) => [
                'case_code' => $c->case_code,
                'name' => $c->full_name,
                'age' => $c->age,
                'province' => $c->province?->name,
                'missing_date' => $c->missing_date?->toDateString(),
                'status' => $c->status,
                'status_label' => $this->statusLabel($c->status),
            ])->all(),
        ];
    }

    /**
     * Tra cứu 1 hồ sơ theo mã (chỉ công khai, không rò rỉ SĐT/địa chỉ nhà).
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function getCaseByCode(array $args): array
    {
        $code = trim((string) ($args['case_code'] ?? ''));
        if ($code === '') {
            return ['found' => false, 'error' => 'Thiếu mã hồ sơ.'];
        }

        $case = MissingChild::query()->where('case_code', $code)->with('province')->first();

        if ($case === null) {
            return ['found' => false, 'case_code' => $code, 'message' => 'Không tìm thấy hồ sơ với mã này.'];
        }

        return [
            'found' => true,
            'case_code' => $case->case_code,
            'name' => $case->full_name,
            'age' => $case->age,
            'gender' => $case->gender,
            'province' => $case->province?->name,
            'missing_date' => $case->missing_date?->toDateString(),
            'status' => $case->status,
            'status_label' => $this->statusLabel($case->status),
            'circumstances' => $case->circumstances,
            'public_url' => url('/missing/'.$case->case_code),
        ];
    }

    /**
     * Số liệu tổng quan thời gian thực.
     *
     * @return array<string, mixed>
     */
    private function getProjectStats(): array
    {
        return [
            'total_missing_approved' => MissingChild::where('status', MissingChild::STATUS_APPROVED)->count(),
            'total_found_or_reunited' => MissingChild::whereIn('status', [
                MissingChild::STATUS_FOUND,
                MissingChild::STATUS_REUNITED,
                MissingChild::STATUS_HANDED_OVER,
            ])->count(),
            'provinces_with_cases' => MissingChild::whereNotNull('province_id')
                ->distinct()->count('province_id'),
            'total_handover_points' => HandoverPoint::count(),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Tái dùng V-04: 3 điểm tiếp nhận gần nhất.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function getNearestHandoverPoints(array $args, ?float $lat, ?float $lng): array
    {
        $lat = isset($args['lat']) ? (float) $args['lat'] : $lat;
        $lng = isset($args['lng']) ? (float) $args['lng'] : $lng;

        if ($lat === null || $lng === null) {
            return ['error' => 'Cần cung cấp vị trí (kinh độ, vĩ độ) để tìm điểm gần nhất.'];
        }

        $points = $this->finder->findNearest($lat, $lng, 3);

        return [
            'count' => $points->count(),
            'handover_points' => $points->map(function (HandoverPoint $p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'type' => $p->type,
                    'address' => $p->address,
                    'distance_km' => round((float) ($p->distance ?? 0), 2),
                    'hotline_phone' => $p->hotline_phone,
                    'lat' => (float) $p->lat,
                    'lng' => (float) $p->lng,
                    'directions_url' => 'https://www.google.com/maps?q='.$p->lat.','.$p->lng,
                ];
            })->values()->all(),
        ];
    }

    /**
     * Tóm tắt quy trình bàn giao an toàn (theo trang /quy-trinh-ban-giao-an-toan).
     *
     * @return array<string, mixed>
     */
    private function getSafeHandoverProcess(): array
    {
        return [
            'summary' => "Quy trình bàn giao trẻ an toàn (TìmTrẻ.vn):\n"
                ."1. Đưa trẻ tới nơi an toàn, không tự di chuyển xa nếu không cần thiết; gọi cơ quan chức năng nếu khẩn cấp.\n"
                ."2. Liên hệ công an, UBND địa phương, Tổng đài 111 hoặc điểm tiếp nhận được công bố trên hệ thống.\n"
                ."3. Cung cấp địa điểm, thời gian, tình trạng trẻ; không đăng công khai dữ liệu riêng tư của trẻ.\n"
                ."4. Chỉ bàn giao khi đại diện đơn vị có thẩm quyền đã có mặt và xác nhận; không giao cho cá nhân chỉ qua ảnh/tin nhắn/lời nói.\n"
                ."5. Lưu lại tên đơn vị, địa điểm, thời gian bàn giao để hỗ trợ xác minh sau này.\n"
                ."Lưu ý: KHÔNG tự ý bàn giao trẻ trực tiếp cho cá nhân tự xưng là người thân khi chưa được cơ quan có thẩm quyền xác minh.",
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            MissingChild::STATUS_APPROVED => 'Đang tìm kiếm',
            MissingChild::STATUS_FOUND => 'Đã tìm thấy',
            MissingChild::STATUS_REUNITED => 'Đã đoàn tụ',
            MissingChild::STATUS_HANDED_OVER => 'Đã bàn giao',
            MissingChild::STATUS_MATCHED => 'Đã ghép đôi gợi ý',
            MissingChild::STATUS_PENDING => 'Chờ duyệt',
            MissingChild::STATUS_CLOSED => 'Đã đóng',
            MissingChild::STATUS_ARCHIVED => 'Đã lưu trữ',
            default => $status,
        };
    }
}
