<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roles = $this->whenLoaded('roles', fn() => $this->getRoleNames());

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'is_active'     => $this->is_active,
            'profile_image' => $this->profile_image,
            'roles'         => $roles,
            'created_at'    => $this->created_at,
        ];
    }
}
