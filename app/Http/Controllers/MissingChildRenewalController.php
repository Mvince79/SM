<?php

namespace App\Http\Controllers;

use App\Models\MissingChild;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class MissingChildRenewalController extends Controller
{
    public function show(Request $request, MissingChild $missingChild): View
    {
        $this->authorizeOwner($request, $missingChild);
        $this->ensureRenewable($missingChild);

        return view('missing.renew', [
            'missingChild' => $missingChild,
            'renewalAction' => URL::temporarySignedRoute(
                'missing.renew.store',
                now()->addMinutes(30),
                ['missingChild' => $missingChild->getKey()],
            ),
        ]);
    }

    public function store(Request $request, MissingChild $missingChild): RedirectResponse
    {
        $this->authorizeOwner($request, $missingChild);
        $this->ensureRenewable($missingChild);

        $missingChild->renewExpiration();

        return redirect()
            ->route('missing.detail', ['id' => $missingChild->getKey()])
            ->with('success', 'Hồ sơ đã được gia hạn thêm 2 năm.');
    }

    private function authorizeOwner(Request $request, MissingChild $missingChild): void
    {
        abort_unless((int) $missingChild->user_id === (int) $request->user()->getKey(), 403);
    }

    private function ensureRenewable(MissingChild $missingChild): void
    {
        abort_unless($missingChild->canBeRenewed(), 410, 'Hồ sơ không còn trong thời hạn được phép gia hạn.');
    }
}
