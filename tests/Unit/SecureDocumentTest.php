<?php

namespace Tests\Unit;

use App\Rules\SecureDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecureDocumentTest extends TestCase
{
    public function test_valid_pdf_content_is_accepted(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'authority.pdf',
            "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF",
        );

        $validator = Validator::make(['document' => $file], [
            'document' => [new SecureDocument],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_pdf_with_embedded_executable_payload_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'authority.pdf',
            "%PDF-1.4\n<?php echo 'owned';\n%%EOF",
        );

        $validator = Validator::make(['document' => $file], [
            'document' => [new SecureDocument],
        ]);

        $this->assertTrue($validator->fails());
    }
}
