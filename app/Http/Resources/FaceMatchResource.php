<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FaceMatchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'score' => $this->score,
            'score_pct' => round($this->score * 100, 2),
            'status' => $this->status,
            'found_id' => $this->found_id,
            'found_child' => [
                'province' => $this->whenLoaded('foundChild', fn() => $this->foundChild->province?->name),
                'found_date' => $this->whenLoaded('foundChild', fn() => $this->foundChild->found_date?->format('d/m/Y')),
            ],
        ];
    }
}