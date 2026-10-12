<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'business_owner';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array

    {
        $product = $this->route('product');
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('products', 'slug')->ignore($product)],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
