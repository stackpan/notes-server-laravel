<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserCreateRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function create(UserCreateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'fullname' => $validated['fullname'],
        ]);

        return response()
            ->json([
                'status' => 'success',
                'message' => 'User berhasil ditambahkan',
                'data' => [
                    'userId' => $user->id,
                ]
            ], 201)
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function search(Request $request): JsonResponse
    {
        $query = User::query();

        if ($username = $request->input('username')) {
            $query->where('username', 'like', '%' . $username . '%');
        }

        $users = $query->get();

        return response()
            ->json([
                'status' => 'success',
                'data' => new UserCollection($users),
            ])
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function get(string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            throw new HttpResponseException(response()
                ->json([
                    'status' => 'fail',
                    'message' => 'User tidak ditemukan'
                ], 404)
                ->header('Content-Type', 'application/json; charset=utf-8'),
            );
        }

        return response()
            ->json([
                'status' => 'success',
                'data' => [
                    'user' => new UserResource($user),
                ]
            ])
            ->header('Content-Type', 'application/json; charset=utf-8');
    }
}
