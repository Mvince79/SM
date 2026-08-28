<?php

/*
 * Cấu hình Gemini (chatbot AI — Bước nâng cấp trợ lý ảo).
 *
 * Dùng Gemini 2.0 Flash (free tier) qua REST API + Function Calling.
 * Điền GEMINI_API_KEY thật vào file .env (KHÔNG commit key thật).
 */

return [
    // Lấy từ Google AI Studio: https://aistudio.google.com/apikey
    'api_key' => env('GEMINI_API_KEY'),

    // Gemini 3.6 Flash — model được cấp cho key dự án này (đã xác nhận gọi được
    // generateContent + function calling, trả về thoughtSignature). GIỮ NGUYÊN trừ khi đổi key/account.
    // LƯU Ý: một số model khác (vd gemini-2.5-flash) báo "no longer available to new users"
    // với key này, nên không tự ý đổi về 2.5/2.0. Nếu cần model mới hơn, thử gemini-3.5-flash.
    'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),

    // Endpoint chuẩn của Generative Language API (v1beta hỗ trợ function calling).
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

    // Bước 5 — timeout (giây). Gemini 3.6 Flash có "thinking" + có thể cần 2 lượt
    // (gọi hàm → tổng hợp), cộng truy vấn DB, dễ vượt 10s. Đặt 25s để tránh cURL
    // timeout (mà không làm người dùng đợi quá lâu). Quá thời gian sẽ fallback resolver cũ.
    'timeout' => (int) env('GEMINI_TIMEOUT', 25),
];
