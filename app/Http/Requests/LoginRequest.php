<?php

namespace App\Http\Requests;

use App\Traits\HasValidationMessages;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use HasValidationMessages;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return $this->getAuthMessages();
    }
}
