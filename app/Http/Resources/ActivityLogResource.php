<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'lead_id'      => $this->lead_id,
            'type'         => $this->type,
            'description'  => $this->description,
            'metadata'     => $this->metadata,
            'performed_by' => $this->when(
                $this->relationLoaded('performedBy'),
                fn() => $this->performedBy ? [
                    'id'   => $this->performedBy->id,
                    'name' => $this->performedBy->name,
                ] : null
            ),
            'created_at'   => $this->created_at,
        ];
    }
}
