<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Traits\MustAuthenticated;
use App\Traits\HasFailedValidation;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    use HasFailedValidation, MustAuthenticated;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'max:100', Rule::unique('users')->ignore($this->user())],
            'email' => ['required', 'max:200', 'email:rfc'],
            'firstName' => ['required', 'max:100'],
            'lastName' => ['nullable', 'max:100']
        ];
    }
}
