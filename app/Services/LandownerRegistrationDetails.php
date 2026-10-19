<?php

namespace App\Services;

use App\Models\Landowner;
use Illuminate\Validation\Rule;

class LandownerRegistrationDetails
{
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 ()-]{5,28}[0-9]$/'],
            'address' => ['required', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'registered_owner_status' => ['nullable', Rule::in(array_keys(Landowner::registeredOwnerStatusOptions()))],
            'spouse_name' => ['nullable', 'string', 'max:255', 'required_if:registered_owner_status,married'],
        ];
    }

    public static function fromValidated(array $data): array
    {
        return [
            'contact_number' => $data['contact_number'],
            'address' => $data['address'],
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'registered_owner_status' => $data['registered_owner_status'] ?? null,
            'spouse_name' => ($data['registered_owner_status'] ?? null) === Landowner::STATUS_MARRIED
                ? ($data['spouse_name'] ?? null) : null,
        ];
    }
}
