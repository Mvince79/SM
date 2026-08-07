<?php

namespace Tests\Unit;

use Tests\TestCase;

class FilamentUploadSecurityTest extends TestCase
{
    public function test_all_filament_upload_fields_use_content_validation_and_safe_names(): void
    {
        $imageUploadFiles = [
            'app/Filament/Admin/Resources/MissingChildren/RelationManagers/PhotosRelationManager.php',
            'app/Filament/Admin/Resources/FoundChildResource.php',
            'app/Filament/Admin/Resources/FoundChildResource/RelationManagers/PhotosRelationManager.php',
            'app/Filament/Admin/Resources/Clues/Schemas/ClueForm.php',
            'app/Filament/Admin/Resources/Articles/Schemas/ArticleForm.php',
        ];

        foreach ($imageUploadFiles as $file) {
            $source = file_get_contents(base_path($file));

            $this->assertIsString($source);
            $this->assertStringContainsString('new SecureImage', $source, $file);
            $this->assertStringContainsString('SecureUploadFilename::for($file)', $source, $file);
        }

        $documentForm = file_get_contents(base_path(
            'app/Filament/Admin/Resources/HandoverPoints/Schemas/HandoverPointForm.php',
        ));

        $this->assertIsString($documentForm);
        $this->assertStringContainsString('new SecureDocument', $documentForm);
        $this->assertStringContainsString('SecureUploadFilename::for($file)', $documentForm);
    }
}
