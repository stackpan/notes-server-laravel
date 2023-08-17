<?php

namespace Tests\DataProvider;

use App\Models\User;

final class UserDataProvider {

    public static function registerSuccessProvider(): array
    {
        $userFactory = User::factory()->unhashedPassword();

        return [
            "with lastName" => [   
                $userFactory,
                fn (User $user) => [
                    'username' => $user->username,
                    'password' => $user->password,
                    'email' => $user->email,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                ],
                fn (User $user) => [
                    'username' => $user->username,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ]
            ],
            "without lastName" => [
                $userFactory,
                fn (User $user) => [
                    'username' => $user->username,
                    'password' => $user->password,
                    'email' => $user->email,
                    'firstName' => $user->first_name,
                ],
                fn (User $user) => [
                    'username' => $user->username,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => null,
                ]
            ]
        ];
    }

    public static function updateSuccessProvider(): array
    {
        $userFactory = User::factory()->withToken();

        return [
            [
                $userFactory,
                fn (User $user) => [
                    'username' => $user->username,
                    'email' => 'updated' . $user->email,
                    'firstName' => $user->first_name,
                    'lastName' => ''
                ],
                fn (User $user) => [
                    'email' => $user->email,
                    'last_name' => $user->last_name,
                ],
                fn (User $user) => [
                    'email' => 'updated' . $user->email,
                    'last_name' => null,
                ]
            ], 
            [
                $userFactory,
                fn (User $user) => [
                    'username' => 'updated' . $user->username,
                    'email' => $user->email,
                    'firstName' => $user->first_name,
                    'lastName' => 'Updated' . $user->last_name,
                ],
                fn (User $user) =>[
                    'username' => $user->username,
                    'password' => $user->password,
                    'last_name' => $user->last_name,
                ],
                fn (User $user) =>[
                    'username' => 'updated' . $user->username,
                    'last_name' => 'Updated' . $user->last_name,
                ]
            ],
            [
                $userFactory,
                fn (User $user) =>[
                    'username' => $user->username,
                    'email' => $user->email,
                    'firstName' => 'Updated' . $user->first_name,
                    'lastName' => $user->last_name,
                ],
                fn (User $user) =>[
                    'first_name' => $user->first_name,
                ],
                fn (User $user) =>[
                    'first_name' => 'Updated' . $user->first_name,
                    'last_name' => $user->last_name,
                ]
            ],
        ];
    }

}