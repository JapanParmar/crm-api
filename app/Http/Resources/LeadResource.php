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
            'lead_date'         => $this->lead_date ? $this->lead_date->format('Y-m-d') : null,

            'source'            => $this->source,
            'service_type'      => $this->service_type,
            'status'            => $this->status,
            'priority'          => $this->priority,

            'property_type'     => $this->property_type,
            'project_id'        => $this->project_id,
            'budget_min'        => $this->budget_min,
            'budget_max'        => $this->budget_max,
            'preferred_location'=> $this->preferred_location,
            'city'              => $this->city,
            'locality'          => $this->locality,
            'project_interest'  => $this->project_interest,
            'bhk_preference'    => $this->bhk_preference,

            'score'             => $this->score,
            'notes'             => $this->notes,
            'listing_id'        => $this->listing_id,
            'lead_provider_ref' => $this->lead_provider_ref,
            'tags'              => $this->tags ?? [],

            'project'           => $this->when(
                $this->relationLoaded('project'),
                fn() => $this->project ? [
                    'id'              => $this->project->id,
                    'name'            => $this->project->name,
                    'code'            => $this->project->code,
                    'type'            => $this->project->type,
                    'status'          => $this->project->status,
                    'city'            => $this->project->city,
                    'developer'       => $this->project->developer,
                    'price_min'       => $this->project->price_min,
                    'price_max'       => $this->project->price_max,
                    'available_units' => $this->project->available_units,
                ] : null
            ),

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
