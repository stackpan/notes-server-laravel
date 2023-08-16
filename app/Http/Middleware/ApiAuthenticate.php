<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');
        $user = User::where('token', $token)->first();

        if (!$user) {
            return response()->json([
                'errors' => [
                    'message' => 'Unauthorized.'
                ]
            ], 401);
        }

        Auth::login($user);
        return $next($request);
    }
}
