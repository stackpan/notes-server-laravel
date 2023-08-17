<?php

namespace App\Http\Requests;

use App\Traits\MustAuthenticated;
use App\Traits\HasFailedValidation;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdatePasswordRequest extends FormRequest
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
            'password' => ['required', 'max:100', Password::defaults()],
        ];
    }
}
