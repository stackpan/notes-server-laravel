<?php

namespace App\Http\Requests;

use App\Traits\MustAuthenticated;
use App\Traits\HasFailedValidation;
use Illuminate\Foundation\Http\FormRequest;

class ContactUpdateRequest extends FormRequest
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
            'firstName' => ['required', 'max:100'],
            'lastName' => ['nullable', 'max:100'],
            'email' => ['nullable', 'max:200', 'email'],
            'phone' => ['nullable', 'max:20'],
        ];
    }
}
