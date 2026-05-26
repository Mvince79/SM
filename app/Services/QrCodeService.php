<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Generate a PNG QR code for a given URL and save to public disk under qrcodes/{case_code}.png
     * Returns the relative storage path (relative to storage/app/public)
     */
    public function generate(string $url, string $caseCode): string
    {
        // Try PNG first (may require imagick). If it fails, fallback to SVG.
        $dir = 'qrcodes';

        if (!Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }

        try {
            $fileName = "{$dir}/{$caseCode}.png";
            $png = QrCode::format('png')->size(300)->generate($url);
            Storage::disk('public')->put($fileName, $png);
            return $fileName;
        } catch (\Throwable $e) {
            // Fallback to SVG which doesn't require imagick
            $fileName = "{$dir}/{$caseCode}.svg";
            $svg = QrCode::format('svg')->size(300)->generate($url);
            Storage::disk('public')->put($fileName, $svg);
            return $fileName;
        }
    }
}
