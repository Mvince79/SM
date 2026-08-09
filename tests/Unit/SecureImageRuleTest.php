<?php

namespace Tests\Unit;

use App\Rules\SecureImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecureImageRuleTest extends TestCase
{
    public function test_valid_image_content_is_accepted(): void
    {
        $validator = Validator::make(
            ['photo' => UploadedFile::fake()->image('portrait.jpg', 800, 600)],
            ['photo' => ['required', new SecureImage]],
        );

        $this->assertFalse($validator->fails());
    }

    public function test_image_with_embedded_php_payload_is_rejected(): void
    {
        $photo = UploadedFile::fake()->image('portrait.jpg', 800, 600);
        file_put_contents($photo->getRealPath(), '<?php echo "owned";', FILE_APPEND);

        $validator = Validator::make(
            ['photo' => $photo],
            ['photo' => ['required', new SecureImage]],
        );

        $this->assertTrue($validator->fails());
    }
}
