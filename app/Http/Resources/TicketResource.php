<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            'ticket_no' => $this->ticket_no,
            'title' => $this->title,
            'description' => $this->description,
            'unit' => $this->unit,
            'status' => $this->status,
            'priority' => $this->priority,
            'etd' => $this->etd?->toIso8601String(),
            'estimated_cost' => $this->estimated_cost !== null ? (string) $this->estimated_cost : null,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'requires_additional_cost' => (bool) $this->requires_additional_cost,
            'property' => PropertyResource::make($this->whenLoaded('property')),
            'category' => MaintenanceCategoryResource::make($this->whenLoaded('category')),
            'reporter' => UserResource::make($this->whenLoaded('reporter')),
            'technician' => UserResource::make($this->whenLoaded('technician')),
            'cost_requests' => CostRequestResource::collection($this->whenLoaded('costRequests')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
