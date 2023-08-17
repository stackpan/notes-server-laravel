<?php

namespace App\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;

class HttpResponseNotFoundException extends HttpResponseException
{
    public function __construct() {
        parent::__construct(response()->json([
            'errors' => [
                'message' => 'Resource not found.'
            ]
        ], 404));
    }
}
