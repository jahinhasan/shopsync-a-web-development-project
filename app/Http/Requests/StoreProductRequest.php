<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role ==='business_owner';
    }

  
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer','exists:categories,id'],
            'name'=>['required','string','max:100'],
            'slug'=>['required','alpha_dash','max:255','unique:products,slug'],
            'description'=>['nullable','string','max:10000'],
            'price'=>['required','numeric','min:0.01','max:99999999999.99'],
            'stock'=>['required','integer','min:0'],
            'image'=>['nullable','file', 'max:2048'],
        ];
    }
}
