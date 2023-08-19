<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateSuccess(): void
    {
        $user = User::factory()->make();

        $response = $this->post('/api/users', [
            'username' => $user->username,
            'password' => 'password',
            'fullname' => $user->fullname,
        ]);

        $response
            ->assertCreated()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->has('message')
                ->where('data.userId', $user->id)
            );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    public function testCreateUserExists(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/api/users', [
            'username' => $user->username,
            'password' => 'password',
            'fullname' => $user->fullname,
        ]);

        $response
            ->assertBadRequest()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson([
                'status' => 'fail',
                'message' => 'Gagal menambahkan user. Username sudah digunakan.',
            ]);
    }

    public static function badPayloadsProvider(): array
    {
        return [
            [['password' => 'secret', 'fullname' => 'John Doe']],
            [['username' => 1, 'password' => 'secret', 'fullname' => 'John Doe']],
            [['username' => 'johndoe', 'fullname' => 'John Doe']],
            [['username' => 'johndoe', 'password' => true, 'fullname' => 'John Doe']],
            [['username' => 'johndoe', 'password' => 'secret']],
            [['username' => 'johndoe', 'password' => 'secret', 'fullname' => 0]],
        ];
    }

    #[DataProvider('badPayloadsProvider')]
    public function testCreateWithBadPayload(array $payload): void
    {
        $response = $this->post('/api/users', $payload);

        $response
            ->assertBadRequest()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'fail')
                ->whereType('message', 'object')
            );
    }

    public function testGetSuccess(): void
    {
        $user = User::factory()->create();

        $response = $this->get('/api/users/' . $user->id);

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->where('data.user', [
                    'id' => $user->id,
                    'username' => $user->username,
                    'fullname' => $user->fullname,
                ])
            );
    }

    public function testGetNotFound(): void
    {
        $user = User::factory()->make();

        $response = $this->get('/api/users/' . $user->id);

        $response
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson([
                'status' => 'fail',
                'message' => 'User tidak ditemukan'
            ]);
    }
}
