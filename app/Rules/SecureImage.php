<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SecureImage implements ValidationRule
{
    /**
     * @param  list<string>  $allowedMimeTypes
     */
    public function __construct(
        private readonly array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'],
        private readonly int $maximumPixels = 40_000_000,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Tệp ảnh tải lên không hợp lệ.');

            return;
        }

        $path = $value->getRealPath();

        if (! is_string($path) || ! is_readable($path)) {
            $fail('Không thể đọc nội dung tệp ảnh.');

            return;
        }

        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $imageInfo = @getimagesize($path);
        $imageMime = is_array($imageInfo) ? ($imageInfo['mime'] ?? null) : null;

        if (
            ! is_string($detectedMime)
            || ! in_array($detectedMime, $this->allowedMimeTypes, true)
            || ! is_string($imageMime)
            || ! hash_equals($detectedMime, $imageMime)
        ) {
            $fail('Chỉ chấp nhận tệp ảnh JPG, PNG hoặc WebP có nội dung hợp lệ.');

            return;
        }

        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);

        if ($width < 1 || $height < 1 || $width * $height > $this->maximumPixels) {
            $fail('Kích thước điểm ảnh không hợp lệ hoặc quá lớn.');

            return;
        }

        if ($this->containsExecutablePayload($path)) {
            $fail('Tệp ảnh chứa nội dung không an toàn.');
        }
    }

    private function containsExecutablePayload(string $path): bool
    {
        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            return true;
        }

        $normalized = strtolower($contents);

        return str_contains($normalized, '<?php')
            || str_contains($normalized, '<?=')
            || str_contains($normalized, '<script');
    }
}
