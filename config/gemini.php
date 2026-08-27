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

    // Gemini 3.6 Flash (free tier, hỗ trợ function calling).
    // LƯU Ý: gemini-2.0-flash đã bị Google gỡ bỏ (HTTP 404) từ 2026 — không dùng lại.
    'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),

    // Endpoint chuẩn của Generative Language API (v1beta hỗ trợ function calling).
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

    // Bước 5 — timeout 10s, quá thời gian sẽ fallback về intent resolver cũ.
    'timeout' => (int) env('GEMINI_TIMEOUT', 10),
];
