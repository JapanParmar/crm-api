<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'lead_id'      => $this->lead_id,
            'lead'         => $this->when(
                $this->relationLoaded('lead'),
                fn() => $this->lead ? [
                    'id'    => $this->lead->id,
                    'name'  => $this->lead->name,
                    'phone' => $this->lead->phone,
                ] : null
            ),
            'assigned_to'  => $this->when(
                $this->relationLoaded('assignedTo'),
                fn() => $this->assignedTo ? [
                    'id'   => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                ] : null
            ),
            'type'         => $this->type,
            'status'       => $this->status,
            'scheduled_at' => $this->scheduled_at,
            'completed_at' => $this->completed_at,
            'notes'        => $this->notes,
            'outcome'      => $this->outcome,
            'created_at'   => $this->created_at,
        ];
    }
}
