<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'phone'              => ['required', 'string', 'max:20'],
            'alternate_phone'    => ['nullable', 'string', 'max:20'],
            'email'              => ['nullable', 'email', 'max:255'],
            'source'             => ['required', Rule::in(Lead::SOURCES)],
            'status'             => ['nullable', Rule::in(Lead::STATUSES)],
            'priority'           => ['nullable', Rule::in(Lead::PRIORITIES)],
            'property_type'      => ['nullable', Rule::in(Lead::PROPERTY_TYPES)],
            'budget_min'         => ['nullable', 'integer', 'min:0'],
            'budget_max'         => ['nullable', 'integer', 'min:0', 'gte:budget_min'],
            'preferred_location' => ['nullable', 'string', 'max:255'],
            'project_interest'   => ['nullable', 'string', 'max:255'],
            'bhk_preference'     => ['nullable', 'string', 'max:50'],
            'score'              => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes'              => ['nullable', 'string'],
            'tags'               => ['nullable', 'array'],
            'tags.*'             => ['string', 'max:50'],
            'assigned_to'        => ['nullable', 'exists:users,id'],
        ];
    }
}
