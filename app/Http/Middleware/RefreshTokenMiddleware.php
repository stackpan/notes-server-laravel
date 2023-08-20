<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class RefreshTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Validator::make($request->all(), [
            'refreshToken' => ['required', 'string'],
        ])->validate();

        if (!$user = User::where('refresh_token', $request->refreshToken)->first()) {
            throw new HttpResponseException(
                response()
                    ->json([
                        'status' => 'fail',
                        'message' => 'Refresh token tidak valid'
                    ], 400)
                    ->withHeaders([
                        'Content-Type' => 'application/json; charset=utf-8',
                    ])
            );
        }

        auth()->login($user);

        return $next($request);
    }
}
