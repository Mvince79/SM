<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lớp giao tiếp với Gemini 2.0 Flash qua REST API + Function Calling.
 *
 * Luồng: người dùng hỏi → gửi system prompt + tools → nếu Gemini cần dữ liệu
 * thật, nó trả về functionCall → Laravel chạy hàm (ChatbotTools) query DB →
 * gửi kết quả về Gemini → Gemini tổng hợp câu trả lời cuối bằng tiếng Việt.
 *
 * Trả về null khi: chưa cấu hình key, lỗi mạng, timeout, hết quota (429),
 * hoặc bị chặn an toàn — để ChatbotController fallback về intent resolver cũ.
 */
class ChatbotGeminiService
{
    public function __construct(
        private ChatbotTools $tools,
    ) {
    }

    /**
     * @param  array{message: string, lat: ?float, lng: ?float, history?: list<array{role: string, content: string}>}  $input
     * @return array{intent: string, message: string, handover_points?: array}|null  null => fallback.
     */
    public function reply(array $input): ?array
    {
        $apiKey = (string) config('gemini.api_key');
        if ($apiKey === '' || $apiKey === '0') {
            Log::debug('ChatbotGemini: no API key configured, fallback to intent resolver.');

            return null; // Chưa cấu hình key → fallback.
        }

        $model = (string) config('gemini.model', 'gemini-3.6-flash');
        $base = rtrim((string) config('gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $url = $base.'/models/'.$model.':generateContent?key='.urlencode($apiKey);
        $timeout = (int) config('gemini.timeout', 10);

        $functionDeclarations = $this->functionDeclarations();
        $contents = $this->buildContents($input);
        $handoverPoints = [];
        $maxTurns = 5;

        for ($turn = 0; $turn < $maxTurns; $turn++) {
            $payload = [
                'systemInstruction' => ['parts' => [['text' => $this->systemPrompt()]]],
                'contents' => $contents,
                'tools' => [['functionDeclarations' => $functionDeclarations]],
                'toolConfig' => ['functionCallingConfig' => ['mode' => 'AUTO']],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 800,
                    'candidateCount' => 1,
                ],
            ];

            try {
                $response = Http::timeout($timeout)->post($url, $payload);
            } catch (\Throwable $e) {
                Log::warning('ChatbotGemini: request failed (network/timeout)', [
                    'exception' => $e->getMessage(),
                    'model' => $model,
                ]);

                return null; // Lỗi mạng / timeout → fallback.
            }

            if (! $response->successful()) {
                // KHÔNG log $url (chứa API key). Body từ Gemini không chứa key.
                Log::warning('ChatbotGemini: upstream non-success response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $model,
                ]);

                return null; // 4xx (hết quota 429) / 5xx → fallback.
            }

            $body = $response->json();
            $candidate = $body['candidates'][0] ?? null;
            if (! is_array($candidate)) {
                Log::warning('ChatbotGemini: malformed/empty candidates in response', [
                    'body' => $response->body(),
                    'model' => $model,
                ]);

                return null;
            }

            $parts = $candidate['content']['parts'] ?? [];
            $functionCall = null;
            $textParts = [];

            $functionCallPart = null;
            foreach ($parts as $part) {
                if (isset($part['functionCall']['name'])) {
                    $functionCall = $part['functionCall'];
                    $functionCallPart = $part; // Giữ nguyên cả part (có thoughtSignature)
                }
                if (isset($part['text'])) {
                    $textParts[] = $part['text'];
                }
            }

            // Gemini muốn gọi hàm để lấy dữ liệu thật → thực thi rồi gọi lại.
            if ($functionCall !== null) {
                $fnName = (string) $functionCall['name'];
                $fnArgs = is_array($functionCall['args'] ?? null) ? $functionCall['args'] : [];

                $result = $this->tools->call($fnName, $fnArgs, $input['lat'] ?? null, $input['lng'] ?? null);
                if (isset($result['handover_points']) && is_array($result['handover_points'])) {
                    $handoverPoints = $result['handover_points'];
                }

                // Echo lại TOÀN BỘ part functionCall NGUYÊN BẢN (giữ nguyên vị trí
                // thoughtSignature — có thể nằm ở cấp Part, không phải trong functionCall).
                // Gemini 3.6 bắt buộc giữ thoughtSignature khi gọi lại, nếu không 400.
                // Echo cả part thay vì tự tái tạo ['name','args'] (tránh mất thoughtSignature
                // và tránh biến args rỗng thành [] sai kiểu Struct).
                $contents[] = [
                    'role' => 'model',
                    'parts' => [$functionCallPart],
                ];
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['functionResponse' => ['name' => $fnName, 'response' => ['result' => $result]]]],
                ];

                continue;
            }

            $text = implode('', $textParts);
            if (trim($text) === '') {
                Log::warning('ChatbotGemini: empty or safety-blocked text response', [
                    'model' => $model,
                ]);

                return null; // Bị chặn an toàn / rỗng → fallback.
            }

            $output = [
                'intent' => 'GEMINI',
                'message' => trim($text),
            ];
            if ($handoverPoints !== []) {
                $output['handover_points'] = $handoverPoints;
            }

            return $output;
        }

        return null;
    }

    /**
     * @param  array{message: string, lat: ?float, lng: ?float, history?: list<array{role: string, content: string}>}  $input
     * @return list<array{role: string, parts: list<array{text: string}>}>
     */
    private function buildContents(array $input): array
    {
        $contents = [];

        foreach ($input['history'] ?? [] as $message) {
            $role = $message['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => (string) $message['content']]],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => (string) $input['message']]],
        ];

        return $contents;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function functionDeclarations(): array
    {
        return [
            [
                'name' => 'searchMissingChildren',
                'description' => 'Tìm kiếm hồ sơ trẻ mất tích theo tên/mã, tỉnh thành hoặc độ tuổi. Chỉ trả thông tin công khai.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'keyword' => ['type' => 'STRING', 'description' => 'Từ khóa tên hoặc mã hồ sơ (vd: MC-2026-00001, Nguyễn).'],
                        'province' => ['type' => 'STRING', 'description' => 'Tên tỉnh/thành phố (vd: Cần Thơ, Hà Nội, TP. Hồ Chí Minh).'],
                        'age_min' => ['type' => 'INTEGER', 'description' => 'Tuổi tối thiểu.'],
                        'age_max' => ['type' => 'INTEGER', 'description' => 'Tuổi tối đa.'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'getCaseByCode',
                'description' => 'Tra cứu một hồ sơ cụ thể theo mã case_code và trả về thông tin công khai + trạng thái hiện tại.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'case_code' => ['type' => 'STRING', 'description' => 'Mã hồ sơ dạng MC-YYYY-XXXXX.'],
                    ],
                    'required' => ['case_code'],
                ],
            ],
            [
                'name' => 'getProjectStats',
                'description' => 'Lấy số liệu tổng quan thời gian thực: tổng hồ sơ, số đã đoàn tụ, số tỉnh có hồ sơ, số điểm tiếp nhận.',
            ],
            [
                'name' => 'getNearestHandoverPoints',
                'description' => 'Tìm 3 điểm tiếp nhận/bàn giao an toàn gần nhất với một toạ độ. Dùng khi hỏi đưa bé đến đâu, bàn giao ở đâu.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'lat' => ['type' => 'NUMBER', 'description' => 'Vĩ độ (latitude).'],
                        'lng' => ['type' => 'NUMBER', 'description' => 'Kinh độ (longitude).'],
                    ],
                    'required' => ['lat', 'lng'],
                ],
            ],
            [
                'name' => 'getSafeHandoverProcess',
                'description' => 'Trả tóm tắt quy trình bàn giao trẻ an toàn qua cơ quan có thẩm quyền. Dùng khi hỏi về bàn giao, an toàn, khẩn cấp.',
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
Bạn là "Trợ lý TìmTrẻ.vn", trợ lý AI của hệ thống TìmTrẻ.vn — nền tảng phi lợi nhuận hỗ trợ tìm kiếm trẻ em mất tích tại Việt Nam.

Tính năng chính của TìmTrẻ.vn:
- Đăng và duyệt hồ sơ trẻ mất tích (có mã case_code, ảnh, thông tin công khai).
- Tìm kiếm bằng ảnh (AI đối chiếu khuôn mặt) để gợi ý trẻ có thể trùng khớp.
- Bản đồ định vị và mạng lưới điểm tiếp nhận/bàn giao an toàn (công an, UBND, tổ chức bảo trợ).
- Quy trình bàn giao an toàn qua cơ quan có thẩm quyền.
- Thống kê và hỗ trợ người báo tin.

NGUYÊN TẮC BẮT BUỘC:
1. Ưu tiên gọi các hàm (function) để lấy dữ liệu THẬT từ hệ thống thay vì đoán hoặc bịa số liệu, tình trạng hồ sơ, vị trí điểm bàn giao.
2. TUYỆT ĐỐI KHÔNG tự bịa số điện thoại, địa chỉ nhà riêng, tên đầy đủ gia đình, hay bất kỳ dữ liệu KYC/nhạy cảm nào. Chỉ dùng thông tin do hàm trả về.
3. Với câu hỏi khẩn cấp, pháp lý hoặc an toàn: hãy dẫn người dùng gọi Tổng đài Quốc gia Bảo vệ Trẻ em 111 hoặc Cảnh sát 113, và có thể gợi ý gọi hàm getSafeHandoverProcess() để giải thích quy trình bàn giao an toàn.
4. Không bao giờ khẳng định kết quả AI đối chiếu khuôn mặt là chắc chắn 100%. Luôn dùng từ "gợi ý", "có thể", "khả năng trùng khớp".
5. Nếu không có hàm phù hợp, cứ trò chuyện tự nhiên như một AI hội thoại bình thường — không bị giới hạn chỉ trả lời về dự án, không nói "tôi không hiểu".
6. Giọng văn ngắn gọn, tiếng Việt, dễ hiểu mọi lứa tuổi.

Khi người dùng hỏi về hồ sơ cụ thể, số liệu, điểm bàn giao gần nhất, hoặc quy trình bàn giao — hãy gọi hàm tương ứng rồi tổng hợp câu trả lời tự nhiên bằng tiếng Việt.
PROMPT;
    }
}
