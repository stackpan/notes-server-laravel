<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\IdResource;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserLoginRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserUpdatePasswordRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserController extends Controller
{
    public function register(UserRegisterRequest $request): IdResource
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'email' => $validated['email'],
            'first_name' => $validated['firstName'],
            'last_name' => $validated['lastName'] ?? null,
        ]);

        return new IdResource($user);
    }

    public function login(UserLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('username', $validated['username'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'Username or password is wrong.'
                    ]
                ]
            ], 401));
        }

        $user->token = (string) Str::ulid();
        $user->save();

        return response()->json([
            "data" => [
                "token" => $user->token,
            ],
            "errors" => []
        ]);
    }

    public function get(Request $request): UserResource
    {
        return new UserResource(auth()->user());
    }

    public function update(UserUpdateRequest $request): IdResource
    {
        $validated = $request->validated();
        $user = auth()->user();

        $user->fill([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'first_name' => $validated['firstName'],
            'last_name' => $validated['lastName'],
        ])->save();

        return (new IdResource($user))
            ->additional([
                'errors' => []
            ]);
    }

    public function updatePassword(UserUpdatePasswordRequest $request): IdResource
    {
        $validated = $request->validated();
        $user = auth()->user();

        $user->fill([
            'password' => Hash::make($validated['password']),
        ])->save();
        return (new IdResource($user))
            ->additional([
                'errors' => []
            ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = auth()->user();
        $user->token = null;

        $user->save();
        return response()->json([
            'data' => true,
            'errors' => []
        ], 200);
    }
}
