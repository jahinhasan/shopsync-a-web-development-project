<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        // On update, the category's own name and slug must not fail the
        // unique checks — otherwise editing without renaming is impossible.
        $category = $this->route('category');

        $nameRule = Rule::unique('categories', 'name');
        $slugRule = Rule::unique('categories', 'slug');

        if ($category) {
            $nameRule->ignore($category);
            $slugRule->ignore($category);
        }

        return [
            'name' => ['required', 'string', 'max:100', $nameRule],
            'slug' => ['nullable', 'alpha_dash', 'max:255', $slugRule],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
