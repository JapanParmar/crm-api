<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteVisitResource extends JsonResource
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
            'attended_by'  => $this->when(
                $this->relationLoaded('attendedBy'),
                fn() => $this->attendedBy ? [
                    'id'   => $this->attendedBy->id,
                    'name' => $this->attendedBy->name,
                ] : null
            ),
            'project_name' => $this->project_name,
            'location'     => $this->location,
            'status'       => $this->status,
            'scheduled_at' => $this->scheduled_at,
            'completed_at' => $this->completed_at,
            'notes'        => $this->notes,
            'feedback'     => $this->feedback,
            'interested'   => $this->interested,
            'created_at'   => $this->created_at,
        ];
    }
}
