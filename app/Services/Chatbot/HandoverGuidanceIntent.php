<?php

namespace App\Services\Chatbot;

use App\Models\HandoverPoint;
use App\Services\Chatbot\Contracts\ChatbotIntent;
use App\Services\HandoverPointFinderService;
use Illuminate\Support\Str;

class HandoverGuidanceIntent implements ChatbotIntent
{
    public const NAME = 'HANDOVER_GUIDANCE';

    private const KEYWORDS = [
        'dua be den dau',
        'dua tre den dau',
        'giao o dau',
        'ban giao',
        'diem ban giao',
        'diem tiep nhan',
        'noi tiep nhan tre',
    ];

    public function __construct(
        private HandoverPointFinderService $handoverPointFinder,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function matches(string $message): bool
    {
        $normalized = Str::lower(Str::ascii($message));
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        return Str::contains($normalized, self::KEYWORDS);
    }

    public function handle(array $context): array
    {
        $points = $this->handoverPointFinder
            ->findNearest((float) $context['lat'], (float) $context['lng'], 3)
            ->map(fn (HandoverPoint $point): array => [
                'id' => $point->id,
                'name' => $point->name,
                'type' => $point->type,
                'address' => $point->address,
                'distance_km' => round((float) $point->distance, 2),
                'hotline_phone' => $point->hotline_phone,
                'lat' => (float) $point->lat,
                'lng' => (float) $point->lng,
                'directions_url' => sprintf(
                    'https://www.google.com/maps?q=%s,%s',
                    $point->lat,
                    $point->lng,
                ),
            ])
            ->values();

        return [
            'message' => $points->isEmpty()
                ? 'Chưa tìm thấy điểm tiếp nhận gần vị trí này. Vui lòng gọi Tổng đài 111 để được hướng dẫn ngay.'
                : 'Vui lòng đưa trẻ đến một trong các điểm tiếp nhận có thẩm quyền gần nhất dưới đây. Không tự bàn giao trực tiếp cho cá nhân khác.',
            'handover_points' => $points->all(),
            'hotline' => [
                'name' => 'Tổng đài Quốc gia Bảo vệ Trẻ em',
                'phone' => '111',
            ],
        ];
    }
}
