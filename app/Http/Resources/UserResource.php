<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photoUrl = null;

        if ($this->profile_photo_path) {
            $photoPath = route('mobile.v1.profile.photo.show', [
                'user' => $this->id,
                'v' => $this->updated_at?->timestamp,
            ], false);

            $photoUrl = $request->getSchemeAndHttpHost().$photoPath;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
            'profile_photo_url' => $photoUrl,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
