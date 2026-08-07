<?php

namespace Tests\Unit;

use App\Support\SecureUploadFilename;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SecureUploadFilenameTest extends TestCase
{
    public function test_storage_name_uses_uuid_and_detected_mime_instead_of_user_extension(): void
    {
        $file = UploadedFile::fake()->image('web-shell.php', 40, 40);

        $filename = SecureUploadFilename::for($file);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\.jpg$/',
            $filename,
        );
    }
}
