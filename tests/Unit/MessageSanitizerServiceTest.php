<?php

namespace Tests\Unit;

use App\Models\MissingChild;
use App\Services\MessageSanitizerService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MessageSanitizerServiceTest extends TestCase
{
    private MessageSanitizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MessageSanitizerService;
    }

    #[DataProvider('openCaseStatuses')]
    public function test_it_masks_vietnamese_mobile_numbers_for_open_cases(string $status): void
    {
        $case = new MissingChild(['status' => $status]);

        $result = $this->service->sanitize('Gọi 0905 123 456 hoặc 037-123-4567 nhé', $case);

        $this->assertSame(
            'Gọi '.MessageSanitizerService::MASKED_PHONE_MESSAGE.' hoặc '.MessageSanitizerService::MASKED_PHONE_MESSAGE.' nhé',
            $result
        );
    }

    public static function openCaseStatuses(): array
    {
        return [['PENDING'], ['APPROVED'], ['MATCHED']];
    }

    #[DataProvider('closedCaseStatuses')]
    public function test_it_preserves_numbers_for_closed_cases(string $status): void
    {
        $case = new MissingChild(['status' => $status]);
        $body = 'Liên hệ 0905123456';

        $this->assertSame($body, $this->service->sanitize($body, $case));
    }

    public static function closedCaseStatuses(): array
    {
        return [['FOUND'], ['CLOSED']];
    }

    public function test_it_does_not_mask_non_mobile_digit_sequences(): void
    {
        $case = new MissingChild(['status' => 'APPROVED']);
        $body = 'Mã hồ sơ 0123456789, hotline 111';

        $this->assertSame($body, $this->service->sanitize($body, $case));
    }
}
