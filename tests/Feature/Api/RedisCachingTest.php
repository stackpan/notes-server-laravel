<?php

namespace Tests\Feature\Api;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RedisCachingTest extends TestCase
{

    use RefreshDatabase;

    public function testGetUserNotesCache()
    {
        $login = $this->login();

        $user = $login['user'];

        Note::factory()
            ->count(4)
            ->for($user)
            ->create();

        $this->withHeader('Authorization', 'Bearer ' . $login['access_token'])
            ->get('/api/notes');

        $this->assertTrue(Cache::has('notes:user:' . $user->id));
    }

    public function testGetUserNoteCacheExpire()
    {
        $login = $this->login();

        $user = $login['user'];

        Note::factory()
            ->count(4)
            ->for($user)
            ->create();

        $this->withHeader('Authorization', 'Bearer ' . $login['access_token'])
            ->get('/api/notes');

        $this->travel(6)->minutes();

        $this->assertFalse(Cache::has('notes:user:' . $user->id));
    }

    public static function userNotesCacheDeletionProvider(): array
    {
        return [
            'user create notes action' => [
                function (RedisCachingTest $testCase, ?string $noteId) {
                    $testCase->post('/api/notes', [
                        'title' => fake()->sentence(3),
                        'body' => fake()->paragraph(),
                        'tags' => fake()->words(3),
                    ]);
                }
            ],
            'user edit notes action' => [
                function (RedisCachingTest $testCase, ?string $noteId) {
                    $testCase->put('/api/notes/' . $noteId, [
                        'title' => fake()->sentence(3),
                        'body' => fake()->paragraph(),
                        'tags' => fake()->words(3),
                    ]);
                }
            ],
            'user delete notes action' => [
                function (RedisCachingTest $testCase, ?string $noteId) {
                    $testCase->delete('/api/notes/' . $noteId);
                }
            ],
        ];
    }

    #[DataProvider('userNotesCacheDeletionProvider')]
    public function testUserNotesCacheDeletion(callable $userRequest)
    {
        $login = $this->login();

        $user = $login['user'];

        Note::factory()
            ->count(4)
            ->for($user)
            ->create();

        $getResponse = $this->withHeader('Authorization', 'Bearer ' . $login['access_token'])
            ->get('/api/notes');

        $userRequest(
            $this->withHeader('Authorization', 'Bearer ' . $login['access_token']),
            $getResponse['data']['notes'][0]['id']
        );

        $this->assertFalse(Cache::has('notes:user:' . $user->id));
    }

    private function login(): array
    {
        $user = User::factory()->create();

        $response = $this->post('/api/authentications', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        return [
            'user' => $user,
            'access_token' => $response['data']['accessToken'],
            'refresh_token' => $response['data']['refreshToken'],
        ];
    }

}
