<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SecureDocument implements ValidationRule
{
    /**
     * @param  list<string>  $allowedMimeTypes
     */
    public function __construct(
        private readonly array $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Tệp tải lên không hợp lệ.');

            return;
        }

        $path = $value->getRealPath();

        if (! is_string($path) || ! is_readable($path)) {
            $fail('Không thể đọc nội dung tệp.');

            return;
        }

        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);

        if (! is_string($detectedMime) || ! in_array($detectedMime, $this->allowedMimeTypes, true)) {
            $fail('Chỉ chấp nhận tệp PDF, JPG hoặc PNG có nội dung hợp lệ.');

            return;
        }

        if (str_starts_with($detectedMime, 'image/')) {
            (new SecureImage(array_values(array_filter(
                $this->allowedMimeTypes,
                fn (string $mime): bool => str_starts_with($mime, 'image/'),
            ))))->validate($attribute, $value, $fail);

            return;
        }

        $contents = file_get_contents($path);

        if (! is_string($contents) || ! str_starts_with($contents, '%PDF-')) {
            $fail('Nội dung tệp PDF không hợp lệ.');

            return;
        }

        $normalized = strtolower($contents);

        if (
            str_contains($normalized, '<?php')
            || str_contains($normalized, '<?=')
            || str_contains($normalized, '<script')
        ) {
            $fail('Tệp tải lên chứa nội dung không an toàn.');
        }
    }
}
