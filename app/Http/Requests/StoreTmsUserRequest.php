<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTmsUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->attributes->get('tmsUser')?->isRole('GM') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('tms_users')->ignore($this->route('tmsUser'))],
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('tms_users')->ignore($this->route('tmsUser'))],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['GM', 'AM', 'BDE'])],
            'direct_manager_id' => ['nullable', 'exists:tms_users,id'],
            'default_business_unit_id' => ['nullable', 'exists:tms_business_units,id'],
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'min:8', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
