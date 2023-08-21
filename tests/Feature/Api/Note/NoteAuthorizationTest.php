<?php

namespace Tests\Feature\Api\Note;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class NoteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function testCreate(): void
    {
        $users = User::factory(2)->create();

        $users->each(function (User $user) {
            $payload = [
                'title' => fake()->sentence(3),
                'tags' => fake()->words(2),
                'body' => fake()->paragraph(),
            ];

            $login = $this->login($user);

            $response = $this
                ->withHeader('Authorization', 'Bearer' . $login['access_token'])
                ->post('/api/notes', $payload);

            $response
                ->assertCreated()
                ->assertHeader('Content-Type', 'application/json; charset=utf-8');

            $this->assertDatabaseHas('notes', [
                'id' => $response['data']['noteId'],
                'user_id' => $login['user']->id,
            ]);
        });
    }

    public function testGetAll(): void
    {
        $users = User::factory(2)->create();

        for ($i = 0; $i < 5; $i++) {
            Note::factory()->for($users->get($i % 2))->create();
        }

        $users->each(function (User $user) {
            $login = $this->login($user);

            $notes = $user->notes;

            $response = $this
                ->withHeader('Authorization', 'Bearer' . $login['access_token'])
                ->get('/api/notes');

            $response
                ->assertOk()
                ->assertHeader('Content-Type', 'application/json; charset=utf-8')
                ->assertJson(fn (AssertableJson $json) => $json
                    ->has('data.notes', $notes->count())
                    ->etc()
                );
        });
    }

    public function testGetDetailCrossOwner(): void
    {
        $users = User::factory(2)->has(Note::factory())->create();

        $userALogin = $this->login($users->get(0));

        // send get request to note owned by user B
        $response = $this
            ->withHeader('Authorization', 'Bearer' . $userALogin['access_token'])
            ->get('/api/notes/' . $users->get(1)->notes->first()->id);

        $response
            ->assertForbidden()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'fail')
                ->has('message')
            );
    }

    public function testUpdateCrossOwner(): void
    {
        $users = User::factory(2)->has(Note::factory())->create();

        $userALogin = $this->login($users->get(0));

        $userBNote = $users->get(1)->notes->first();

        // send update request to note owned by user B
        $response = $this
            ->withHeader('Authorization', 'Bearer' . $userALogin['access_token'])
            ->put('/api/notes/' . $userBNote->id, [
                'title' => $userBNote->title,
                'tags' => $userBNote->tags,
                'body' => fake()->paragraph(),
            ]);

        $response
            ->assertForbidden()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'fail')
                ->has('message')
            );
    }

    public function testDeleteCrossOwner(): void
    {
        $users = User::factory(2)->has(Note::factory())->create();

        $userALogin = $this->login($users->get(0));

        // send delete request to note owned by user B
        $response = $this
            ->withHeader('Authorization', 'Bearer' . $userALogin['access_token'])
            ->delete('/api/notes/' . $users->get(1)->notes->first()->id);

        $response
            ->assertForbidden()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'fail')
                ->has('message')
            );
    }

    private function login(User $user): array
    {
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
