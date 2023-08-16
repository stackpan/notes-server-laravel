<?php

namespace App\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait HasFailedValidation {

    protected function failedValidation(Validator $validator): HttpResponseException
    {
        throw new HttpResponseException(response([
            'errors' => $validator->getMessageBag(), 
        ], 400));
    }
}
