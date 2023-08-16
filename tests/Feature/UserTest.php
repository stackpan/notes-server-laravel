<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

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

    /**
     * @dataProvider registerSuccessProvider
     */
    public function testRegisterSuccess(UserFactory $userFactory, callable $getPayload, callable $getExpectedDbHas): void
    {
        $user = $userFactory->make();

        $response = $this
            ->post('/api/users', $getPayload($user));
            
        $response
            ->assertStatus(201)
            ->assertJson(fn (AssertableJson $json) => $json
                    ->has('data')
                    ->has('data.id')
                );
        
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', $getExpectedDbHas($user));
    }

    public function testRegisterFailed(): void
    {
        $response = $this
            ->post('/api/users', [
                'username' => '',
                'password' => '',
                'email' => '',
                'firstName' => '',
            ]);

        $response
            ->assertStatus(400)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('errors', fn (AssertableJson $json) => $json
                        ->whereType('username', 'array')    
                        ->whereType('password', 'array')    
                        ->whereType('email', 'array')    
                        ->whereType('firstName', 'array')    
                    ));
    }

    public function testRegisterUserAlreadyExists(): void
    {
        $user = User::factory()->unhashedPassword()->make();

        $this
            ->post('/api/users', [
                'username' => $user->username,
                'password' => $user->password,
                'email' => $user->email,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
            ]);

        $response = $this
            ->post('/api/users', [
                'username' => $user->username,
                'password' => $user->password,
                'email' => $user->email,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
            ]);

        $response
            ->assertStatus(400)
            ->assertJson([
                'errors' => [
                    'username' => [
                        'The username has already been taken.'
                    ]
                ]
            ]);
    }

    public function testLoginSuccess(): void
    {
        $user = User::factory()->unhashedPassword()->make();
        $userUnhashedPassword = $user->password;

        $user->password = Hash::make($user->password);
        $user->save();
        
        $response = $this
            ->post('/api/auth/login', [
                'username' => $user->username,
                'password' => $userUnhashedPassword,
            ]);
        
        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                    ->has('data')
                    ->has('data.token')
                    ->has('errors'));
    }

    public function testLoginUserNotFound(): void
    {
        $response = $this
            ->post('/api/auth/login', [
                'username' => 'johndoe',
                'password' => 'Secret123!',
            ]);
        
        $response
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'Username or password is wrong.'
                    ]
                ]
            ]);
    }

    public function testLoginPasswordWrong(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->post('/api/auth/login', [
                'username' => $user->username,
                'password' => 'wrongpassword',
            ]);
        
        $response
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => [
                        'Username or password is wrong.'
                    ]
                ]
            ]);
    }

    public function testGetCurrentSuccess(): void
    {
        $user = User::factory()->withToken()->create();

        $response = $this
            ->withHeaders([
                'Authorization' => $user->token,
            ])
            ->get('/api/users/current');

        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data', fn (AssertableJson $json) => $json
                    ->has('id')
                    ->where('username', $user->username)
                    ->where('email', $user->email)
                    ->where('firstName', $user->first_name)
                    ->where('lastName', $user->last_name)
                )
                ->has('errors'));
    }

    public function testGetCurrentUnauthorized(): void
    {
        $user = User::factory()->withToken()->create();

        $response = $this->get('/api/users/current');
        
        $response
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => 'Unauthorized.'
                ]
            ]);
    }

    public function testGetCurrentInvalidToken(): void
    {
        $user = User::factory()->withToken()->create();

        $response = $this
            ->withHeaders([
                'Authorization' => 'wrongtoken',
            ])
            ->get('/api/users/current');
        
        $response
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => 'Unauthorized.'
                ]
            ]);
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

    /**
     * @dataProvider updateSuccessProvider
     */
    public function testUpdateSuccess(
        UserFactory $userFactory,
        callable $getPayload,
        callable $getExpectedDbMissing,
        callable $getExpectedDbHas
        ): void
    {
        $user = $userFactory->create();

        $response = $this
            ->withHeaders([
                'Authorization' => $user->token,
            ])
            ->put('/api/users/current', $getPayload($user));
        
        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data')
                ->has('data.id')
                ->has('errors')
            );
        
        $this->assertDatabaseMissing('users', $getExpectedDbMissing($user));
        $this->assertDatabaseHas('users', $getExpectedDbHas($user));
    }

    public function testUpdateUnauthorized(): void
    {
        $user = User::factory()->withToken()->create();

        $response = $this
            ->put('/api/users/current', [
                'email' => 'updated' . $user->email,
                'lastName' => ''
            ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => 'Unauthorized.'
                ]
            ]);
    }

    public function testUpdateFailed(): void
    {
        $user = User::factory()->withToken()->create();

        $response = $this
            ->withHeaders([
                'Authorization' => $user->token,
            ])
            ->put('/api/users/current', [
                'email' => 'invalidemail',
            ]);

        $response
            ->assertStatus(400)
            ->assertJson([
                'errors' => [
                    'email' => [
                        'The email field must be a valid email address.'
                    ]
                ]
            ]);
    }

    public function testUpdatePasswordSuccess(): void
    {
        $user = User::factory()->withToken()->create();

        $response = $this
            ->withHeaders([
                'Authorization' => $user->token,
            ])
            ->patch('/api/users/current/password', [
                'password' => 'NewSecret123!',
            ]);
        
        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data')
                ->has('data.id')
                ->has('errors')
            );

        $this->assertDatabaseMissing('users', [
            'password' => $user->password,
        ]);
    }

    public function testUpdatePasswordInvalid(): void
    {
        $user = User::factory()->withToken()->create();

        $response = $this
            ->withHeaders([
                'Authorization' => $user->token,
            ])
            ->patch('/api/users/current/password', [
                'password' => 'pass',
            ]);

        $response
            ->assertStatus(400)
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('errors')
                ->whereType('errors.password', 'array')
            );
    }

    public function testUpdatePasswordUnauthenticated(): void
    {
        $user = User::factory()->withToken()->create();

        $response = $this
            ->patch('/api/users/current/password', [
                'password' => 'pass',
            ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => 'Unauthorized.'
                ]
            ]);
    }

    public function testLogoutSuccess(): void
    {
        $user = User::factory()->withToken()->create();

        $response = $this
            ->withHeaders([
                'Authorization' => $user->token,
            ])
            ->delete('/api/auth/logout');

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => true,
                'errors' => []
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'token' => $user->token,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'token' => null,
        ]);
    }

    public function testLogoutFailed(): void
    {
        $user = User::factory()->withToken()->create();

        $response = $this
            ->delete('/api/auth/logout');

        $response
            ->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => 'Unauthorized.'
                ]
            ]);
    }

}
