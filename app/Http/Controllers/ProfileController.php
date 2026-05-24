<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        /** @var User $user */
        $user = Auth::user();
        $provinces = \App\Models\Province::orderBy('name')->get();

        return view('profile.index', compact('user', 'provinces'));
    }

    public function update(ProfileUpdateRequest $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            try {
                $old = $user->avatar_url;
                if ($old) {
                    $parsed = parse_url($old, PHP_URL_PATH) ?: $old;
                    if (str_contains($parsed, '/storage/')) {
                        $publicPath = ltrim(str_replace('/storage/', '', $parsed), '/');
                        Storage::disk('public')->delete($publicPath);
                    } elseif (str_starts_with($parsed, 'avatars/')) {
                        Storage::disk('public')->delete($parsed);
                    }
                }

                $path = $request->file('avatar')->store('avatars', 'public');
                $data['avatar_url'] = Storage::url($path);
            } catch (\Exception $e) {
                return redirect()->route('profile.index')->with('error', 'Không thể tải ảnh lên: ' . $e->getMessage());
            }
        }

        $user->fill($data);
        $user->save();

        return redirect()->route('profile.index')->with('success', 'Cập nhật hồ sơ thành công.');
    }
}