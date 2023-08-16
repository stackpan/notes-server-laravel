<?php

namespace App\Http\Requests;

use App\Traits\HasFailedValidation;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class UserRegisterRequest extends FormRequest
{
    use HasFailedValidation;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'max:100', 'unique:users'],
            'password' => ['required', 'max:100', Password::defaults()],
            'email' => ['required', 'max:200', 'email:rfc'],
            'firstName' => ['required', 'max:100'],
            'lastName' => ['nullable', 'max:100']
        ];
    }
}
