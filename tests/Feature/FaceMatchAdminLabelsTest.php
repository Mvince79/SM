<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\FaceMatchResults\FaceMatchResultResource;
use Tests\TestCase;

class FaceMatchAdminLabelsTest extends TestCase
{
    public function test_face_match_resource_uses_vietnamese_labels(): void
    {
        $this->assertSame('Đối chiếu khuôn mặt AI', FaceMatchResultResource::getNavigationLabel());
        $this->assertSame('kết quả đối chiếu khuôn mặt', FaceMatchResultResource::getModelLabel());
        $this->assertSame('kết quả đối chiếu khuôn mặt', FaceMatchResultResource::getPluralModelLabel());
    }
}
