<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\Depends;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function testLoginSuccess(): void
    {
        $response = $this->post('/api/authentications', [
            'username' => $this->user->username,
            'password' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->has('message')
                ->has('data.accessToken')
                ->has('data.refreshToken')
            );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'refresh_token' => $response['data']['refreshToken']
        ]);
    }

    public function testLoginWithWrongCredentials(): void
    {
        $response = $this->post('/api/authentications', [
            'username' => fake()->userName,
            'password' => 'password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'fail')
                ->has('message')
            );
    }

    public function testRefreshSuccess(): void
    {
        $loginResponse = $this->post('/api/authentications', [
            'username' => $this->user->username,
            'password' => 'password',
        ]);

        $refreshResponse = $this->put('/api/authentications', [
            'refreshToken' => $loginResponse['data']['refreshToken'],
        ]);

        $refreshResponse
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->has('message')
                ->has('data.accessToken')
            );

        $this->assertNotEquals($loginResponse['data']['accessToken'], $refreshResponse['data']['accessToken']);
    }

    public function testRefreshWithInvalidToken(): void
    {
        $response = $this->put('/api/authentications', [
            'refreshToken' => 'invalidrefreshtoken',
        ]);

        $response
            ->assertBadRequest()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson([
                'status' => 'fail',
                'message' => 'Refresh token tidak valid',
            ]);
    }

    public function testLogoutSuccess(): void
    {
        $loginResponse = $this->post('/api/authentications', [
            'username' => $this->user->username,
            'password' => 'password',
        ]);

        $logoutResponse = $this->delete('/api/authentications', [
            'refreshToken' => $loginResponse['data']['refreshToken'],
        ]);

        $logoutResponse
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->has('message')
            );

        $this->assertDatabaseMissing('users', [
            'id' => $this->user->id,
            'refresh_token' => $loginResponse['data']['refreshToken'],
        ]);
    }

    public function testLogoutWithInvalidToken(): void
    {
        $response = $this->delete('/api/authentications', [
            'refreshToken' => 'invalidrefreshtoken'
        ]);

        $response
            ->assertBadRequest()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson([
                'status' => 'fail',
                'message' => 'Refresh token tidak valid',
            ]);
    }

}
