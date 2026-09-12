<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTmsTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->attributes->get('tmsUser')?->isRole('GM', 'AM') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', Rule::exists('tms_task_categories', 'id')->where('is_active', true)],
            'priority' => ['required', Rule::in(['LOW', 'MEDIUM', 'HIGH', 'URGENT'])],
            'business_unit_id' => ['required', Rule::exists('tms_business_units', 'id')->where('is_active', true)],
            'hospital_id' => ['nullable', 'exists:tms_hospitals,id'],
            'assignee_id' => ['required', 'exists:tms_users,id'],
            'planned_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:planned_at'],
            'verification_required' => ['nullable', 'boolean'],
            'expected_outcome' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
