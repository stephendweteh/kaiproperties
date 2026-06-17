<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CostRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'amount' => (string) $this->amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'reviewer_comment' => $this->reviewer_comment,
            'requested_by' => UserResource::make($this->whenLoaded('requester')),
            'reviewed_by' => UserResource::make($this->whenLoaded('reviewer')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
