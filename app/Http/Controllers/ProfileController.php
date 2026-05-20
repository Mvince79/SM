<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $provinces = \App\Models\Province::orderBy('name')->get();

        return view('profile.index', compact('user', 'provinces'));
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            try {
                // Delete old avatar if it is stored in the public disk
                $old = $user->avatar_url;
                if ($old) {
                    // If URL contains /storage/, map to public disk path
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
