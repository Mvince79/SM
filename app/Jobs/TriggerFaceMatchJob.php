<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TriggerFaceMatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $foundChildId, public ?int $photoId = null)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Log::info('Đang kích hoạt face match.', [
            'found_child_id' => $this->foundChildId,
            'photo_id' => $this->photoId,
        ]);

        // Sprint sau sẽ gọi service AI / so khớp khuôn mặt thực tế ở đây.
    }
}
