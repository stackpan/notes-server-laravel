<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            $response = response(status: 401)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8'
                ]);

            throw new HttpResponseException($response);
        }

        return null;
    }
}
