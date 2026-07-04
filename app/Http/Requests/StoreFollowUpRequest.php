<?php

namespace App\Http\Requests;

use App\Models\FollowUp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'         => ['required', Rule::in(FollowUp::TYPES)],
            'scheduled_at' => ['required', 'date'],
            'notes'        => ['nullable', 'string'],
            'assigned_to'  => ['nullable', 'exists:users,id'],
        ];
    }
}
