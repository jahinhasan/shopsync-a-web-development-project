<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'slug' => ['required', 'alpha_dash', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
