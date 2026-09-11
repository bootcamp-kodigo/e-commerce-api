<?php

namespace App\Http\Requests;

use App\Traits\HasValidationMessages;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    use HasValidationMessages;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'sku' => ['sometimes', 'string', 'max:50', 'unique:products,sku,' . $productId],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'numeric', 'min:0.01', 'max:999999.99'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return $this->getProductMessages();
    }
}
