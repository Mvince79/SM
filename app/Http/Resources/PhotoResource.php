<?php

namespace App\Http\Resources;

use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Photo $photo */
        $photo = $this->resource;
        $user = $request->user();
        $selectedPath = $photo->path_blurred;
        $tier = 'guest_blur';

        if ($user && $user->can('viewOriginal', $photo) && $photo->path_original) {
            $selectedPath = $photo->path_original;
            $tier = 'original';
            $this->logOriginalAccess($photo, $user);
        } elseif ($user && $user->can('viewThumbnail', $photo) && $photo->path_thumb) {
            $selectedPath = $photo->path_thumb;
            $tier = 'thumbnail';
        }

        return [
            'id' => $photo->id,
            'is_primary' => (bool) $photo->is_primary,
            'access_tier' => $tier,
            'url' => $selectedPath ? '/storage/' . $selectedPath : null,
            'url_original' => $tier === 'original' ? '/storage/' . $photo->path_original : null,
            'url_thumb' => $photo->path_thumb ? '/storage/' . $photo->path_thumb : null,
            'url_blurred' => $photo->path_blurred ? '/storage/' . $photo->path_blurred : null,
        ];
    }

    private function logOriginalAccess(Photo $photo, $user): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('photo_access')
            ->causedBy($user)
            ->performedOn($photo)
            ->withProperties([
                'photo_id' => $photo->id,
                'path_type' => 'path_original',
            ])
            ->log('Original photo accessed');
    }
}
