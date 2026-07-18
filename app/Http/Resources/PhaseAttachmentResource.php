<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhaseAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attachmentPath = route('mobile.v1.phases.attachments.show', [
            'attachment' => $this->id,
            'v' => $this->updated_at?->timestamp,
        ], false);

        return [
            'id'             => $this->id,
            'file_name'      => $this->file_name,
            'mime_type'      => $this->mime_type,
            'file_size'      => $this->file_size,
            'attachment_type'=> $this->attachment_type,
            'url'            => $request->getSchemeAndHttpHost().$attachmentPath,
            'uploader'       => UserResource::make($this->whenLoaded('uploader')),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
