<?php

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SecureUploadFilename
{
    /** @var array<string, string> */
    private const EXTENSIONS_BY_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function for(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        $mime = is_string($path) && is_readable($path)
            ? (new \finfo(FILEINFO_MIME_TYPE))->file($path)
            : false;
        $extension = is_string($mime) ? (self::EXTENSIONS_BY_MIME[$mime] ?? null) : null;

        if ($extension === null) {
            throw new RuntimeException('Cannot generate a storage name for an unsupported upload type.');
        }

        return Str::uuid().'.'.$extension;
    }
}
