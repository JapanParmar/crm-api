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
        $rules = [
            'name'               => ['required', 'string', 'max:255'],
            'phone'              => ['required', 'string', 'max:20'],
            'alternate_phone'    => ['nullable', 'string', 'max:20'],
            'email'              => ['nullable', 'email', 'max:255'],
            'lead_date'          => ['nullable', 'date'],
            'source'             => ['required', Rule::in(Lead::SOURCES)],
            'service_type'       => ['nullable', Rule::in(Lead::SERVICE_TYPES)],
            'status'             => ['nullable', Rule::in(Lead::STATUSES)],
            'priority'           => ['nullable', Rule::in(Lead::PRIORITIES)],
            'property_type'      => ['nullable', Rule::in(Lead::PROPERTY_TYPES)],
            'budget_min'         => ['nullable', 'integer', 'min:0'],
            'budget_max'         => ['nullable', 'integer', 'min:0'],
            'preferred_location' => ['nullable', 'string', 'max:255'],
            'city'               => ['nullable', 'string', 'max:100'],
            'locality'           => ['nullable', 'string', 'max:255'],
            'project_interest'   => ['nullable', 'string', 'max:255'],
            'bhk_preference'     => ['nullable', 'string', 'max:50'],
            'listing_id'         => ['nullable', 'string', 'max:100'],
            'lead_provider_ref'  => ['nullable', 'string', 'max:100'],
            'score'              => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes'              => ['nullable', 'string'],
            'tags'               => ['nullable', 'array'],
            'tags.*'             => ['string', 'max:50'],
            'assigned_to'        => ['nullable', 'exists:users,id'],
        ];

        if ($this->filled('budget_min') && $this->filled('budget_max')) {
            $rules['budget_max'][] = 'gte:budget_min';
        }

        return $rules;
    }

    /**
     * Add duplicate lead validations.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $phone = $this->input('phone');
            $alternatePhone = $this->input('alternate_phone');
            $email = $this->input('email');

            // 1. Check phone number (must not exist as phone or alternate_phone)
            if ($phone) {
                $duplicatePhone = Lead::where(function ($query) use ($phone) {
                    $query->where('phone', $phone)
                          ->orWhere('alternate_phone', $phone);
                })->first();

                if ($duplicatePhone) {
                    $validator->errors()->add('phone', "Duplicate detected: Phone number '{$phone}' already belongs to Lead {$duplicatePhone->lead_number} ({$duplicatePhone->name}).");
                }
            }

            // 2. Check alternate phone number
            if ($alternatePhone) {
                $duplicateAltPhone = Lead::where(function ($query) use ($alternatePhone) {
                    $query->where('phone', $alternatePhone)
                          ->orWhere('alternate_phone', $alternatePhone);
                })->first();

                if ($duplicateAltPhone) {
                    $validator->errors()->add('alternate_phone', "Duplicate detected: Alternate phone number '{$alternatePhone}' already belongs to Lead {$duplicateAltPhone->lead_number} ({$duplicateAltPhone->name}).");
                }
            }

            // 3. Check email
            if ($email && trim($email) !== '') {
                $duplicateEmail = Lead::where('email', $email)->first();
                if ($duplicateEmail) {
                    $validator->errors()->add('email', "Duplicate detected: Email '{$email}' already belongs to Lead {$duplicateEmail->lead_number} ({$duplicateEmail->name}).");
                }
            }
        });
    }
}
