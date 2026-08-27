<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * MissingChildResource - Transform MissingChild model to JSON API response
 * 
 * Security:
 * - contact_phone: Only visible to authenticated users
 * - path_original: Only visible to authenticated users
 * - Other sensitive info managed per field
 */
class MissingChildResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authenticated = Auth::check();

        return [
            'id' => $this->id,
            'case_code' => $this->case_code,
            'full_name' => $this->full_name,
            'age_estimate' => $this->age_estimate,
            'gender' => $this->gender,
            'skin_tone' => $this->skin_tone,
            'height_cm' => $this->height_cm,
            'weight_kg' => $this->weight_kg,
            'province' => $this->whenLoaded('province', function () {
                return [
                    'id' => $this->province->id,
                    'name' => $this->province->name,
                    'code' => $this->province->code,
                ];
            }),
            'ward' => $this->ward,
            'address_detail' => $this->address_detail,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'missing_date' => $this->missing_date?->format('Y-m-d'),
            'missing_time' => $this->missing_time,
            'clothes_desc' => $this->clothes_desc,
            'clothes_color' => $this->clothes_color,
            'special_marks' => $this->special_marks,
            'circumstances' => $this->circumstances,
            'reward_info' => $this->reward_info,

            // Contact info - only visible to authenticated users
            'contact_name' => $this->when(
                $authenticated,
                $this->contact_name
            ),
            'contact_phone' => $this->when(
                $authenticated,
                $this->contact_phone
            ),
            'contact_relation' => $this->when(
                $authenticated,
                $this->contact_relation
            ),

            // Status & moderation
            'status' => $this->status,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'found_at' => $this->found_at?->toIso8601String(),

            // Photo
            'primary_photo' => $this->whenLoaded('primaryPhoto', function () {
                return new PhotoResource($this->primaryPhoto);
            }),

            'photos' => $this->whenLoaded('photos', function () {
                return PhotoResource::collection($this->photos);
            }),

            // Stats
            'view_count' => $this->view_count,
            'share_count' => $this->share_count,

            // QR Code
            'qr_code_url' => $this->when(
                true,
                fn() => $this->qr_code_url
            ),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
