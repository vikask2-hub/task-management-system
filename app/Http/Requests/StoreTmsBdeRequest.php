<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTmsBdeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => filled($this->input('phone')) ? trim((string) $this->input('phone')) : null,
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->attributes->get('tmsUser')?->isRole('AM') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $manager = $this->attributes->get('tmsUser');
        $businessUnitIds = $manager?->businessUnits()
            ->where('is_active', true)
            ->pluck('tms_business_units.id') ?? collect();

        if ($manager?->default_business_unit_id) {
            $businessUnitIds->push($manager->default_business_unit_id);
        }

        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('tms_users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'business_unit_id' => ['required', 'integer', Rule::in($businessUnitIds->unique()->all())],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_unit_id.in' => 'Choose one of your assigned business units.',
        ];
    }
}
