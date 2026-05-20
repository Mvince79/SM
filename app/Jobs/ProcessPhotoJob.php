<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPhotoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $photoId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Log::info('Đang xử lý ảnh.', [
            'photo_id' => $this->photoId,
        ]);

        // Sprint sau sẽ thêm OCR/blur/face detect thật ở đây.
    }
}
