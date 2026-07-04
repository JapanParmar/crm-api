<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'lead_number'       => $this->lead_number,
            'name'              => $this->name,
            'phone'             => $this->phone,
            'alternate_phone'   => $this->alternate_phone,
            'email'             => $this->email,

            'source'            => $this->source,
            'status'            => $this->status,
            'priority'          => $this->priority,

            'property_type'     => $this->property_type,
            'budget_min'        => $this->budget_min,
            'budget_max'        => $this->budget_max,
            'preferred_location'=> $this->preferred_location,
            'project_interest'  => $this->project_interest,
            'bhk_preference'    => $this->bhk_preference,

            'score'             => $this->score,
            'notes'             => $this->notes,
            'tags'              => $this->tags ?? [],

            'assigned_to'       => $this->when(
                $this->relationLoaded('assignedTo'),
                fn() => $this->assignedTo ? [
                    'id'    => $this->assignedTo->id,
                    'name'  => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                ] : null
            ),

            'is_duplicate'      => $this->is_duplicate,
            'follow_up_count'   => $this->follow_up_count,
            'site_visit_count'  => $this->site_visit_count,

            'last_contacted_at' => $this->last_contacted_at,
            'next_follow_up_at' => $this->next_follow_up_at,
            'assigned_at'       => $this->assigned_at,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
