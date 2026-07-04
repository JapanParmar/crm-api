<?php

namespace App\Http\Requests;

use App\Models\SiteVisit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_name' => ['required', 'string', 'max:255'],
            'location'     => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
            'attended_by'  => ['nullable', 'exists:users,id'],
            'notes'        => ['nullable', 'string'],
        ];
    }
}
