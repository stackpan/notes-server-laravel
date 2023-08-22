<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthLogoutRequest;
use App\Http\Requests\AuthRefreshRequest;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(AuthLoginRequest $request)
    {
        $credentials = $request->validated();

        if (!$refreshToken = auth()->attempt($credentials)) {
            throw new HttpResponseException(response()
                ->json([
                    'status' => 'fail',
                    'message' => 'Kredensial yang Anda berikan salah'
                ], 401)
                ->header('Content-Type', 'application/json; charset=utf-8'));
        }

        $user = auth()->user();
        $user->refresh_token = $refreshToken;
        $user->save();

        $accessToken = auth()->tokenById($user->id);
        auth()->setToken($accessToken)->user();

        return response()
            ->json([
                'status' => 'success',
                'message' => 'Authentication berhasil ditambahkan',
                'data' => [
                    'accessToken' => $accessToken,
                    'refreshToken' => $refreshToken,
                ]
            ], 201)
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function refresh(Request $request)
    {
        $accessToken = auth()->refresh();

        return response()
            ->json([
                'status' => 'success',
                'message' => 'Access Token berhasil diperbarui',
                'data' => [
                    'accessToken' => $accessToken,
                ]
            ])
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        $user->refresh_token = null;
        $user->save();

        auth()->invalidate();

        return response()
            ->json([
                'status' => 'success',
                'message' => 'Refresh token berhasil dihapus',
            ])
            ->header('Content-Type', 'application/json; charset=utf-8');
    }
}
