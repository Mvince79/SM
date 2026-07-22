<?php
namespace App\Http\Controllers\Verifier;

use App\Http\Controllers\Controller;
use App\Models\MissingChild;
use App\Models\FoundChild;
use App\Models\NotificationEvent;
use App\Services\Notification\NotificationDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class HandoverConfirmationController extends Controller
{
    public function confirm(int $handoverable, string $type, NotificationDeliveryService $delivery): RedirectResponse
    {
        $model = $type === 'missing'
            ? MissingChild::findOrFail($handoverable)
            : FoundChild::findOrFail($handoverable);

        Gate::authorize('confirm-handover', $model); // dùng lại đúng Policy đã có sẵn, không viết lại validate

        $model->update([
            'handover_confirmed_at' => now(),
            'handover_confirmed_by' => Auth::id(),
        ]);

        $event = NotificationEvent::create([
            'event_type' => NotificationEvent::TYPE_HANDOVER_READY,
            'subject_type' => $model->getMorphClass(),
            'subject_id' => $model->id,
            'payload' => ['case_id' => $model->id, 'case_code' => $model->case_code],
            'triggered_by_user_id' => Auth::id(),
            'occurred_at' => now(),
        ]);
        $event->setRelation('subject', $model);

        $delivery->deliver($event, [$model->user]);

        return redirect()->route('verifier.dashboard')->with('success', 'Đã xác nhận tiếp nhận trẻ thành công.');
    }
}