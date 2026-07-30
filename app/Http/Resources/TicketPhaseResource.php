<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketPhaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'phase_name'       => $this->phase_name,
            'phase_number'     => $this->phase_number,
            'description'      => $this->description,
            'technician_notes' => $this->technician_notes,
            'manager_notes'    => $this->manager_notes,
            'status'           => $this->status,
            'started_at'       => $this->started_at?->toIso8601String(),
            'completed_at'     => $this->completed_at?->toIso8601String(),
            'attachments'      => PhaseAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
