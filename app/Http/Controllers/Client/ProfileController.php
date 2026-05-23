<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $user = User::with(['province', 'missingChildren', 'clues'])
            ->findOrFail(Auth::id());
        $provinces = Province::where('is_active', 1)->orderBy('name')->get();

        return view('profile.index', compact('user', 'provinces'));
    }
}
