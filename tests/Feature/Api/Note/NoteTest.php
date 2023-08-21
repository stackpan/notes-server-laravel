<?php

namespace Api\Note;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

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

    public function testCreateSuccess(): void
    {
        $login = $this->login();
        $payload = [
            'title' => fake()->sentence(3),
            'tags' => fake()->words(2),
            'body' => fake()->paragraph(),
        ];

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->post('/api/notes', $payload);

        $response
            ->assertCreated()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->has('message')
                ->has('data.noteId')
            );

        $this->assertDatabaseHas('notes', [
            'id' => $response['data']['noteId'],
            'user_id' => $login['user']->id,
        ]);
    }

    public static function badPayloadsProvider(): array
    {
        return [
            [["tags" => ["Android", "Web"], "body" => "Isi dari catatan A"]],
            [["title" => 1, "tags" => ["Android", "Web"], "body" => "Isi dari catatan A"]],
            [["title" => "Catatan A", "body" => "Isi dari catatan A"]],
            [["title" => "Catatan A", "tags" => [1, "2"], "body" => "Isi dari catatan A"]],
            [["title" => "Catatan A", "tags" => ["Android", "Web"]]],
            [["title" => "Catatan A", "tags" => ["Android", "Web"], "body" => true]]
        ];
    }

    #[DataProvider('badPayloadsProvider')]
    public function testCreateWithBadPayloads(array $payload)
    {
        $login = $this->login();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->post('/api/notes', $payload);

        $response
            ->assertBadRequest()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'fail')
                ->has('message')
                ->etc(),
            );
    }

    public function testCreateFailed(): void
    {
        $this->markTestIncomplete('There is no 500 response configuration ATM.');

        $login = $this->login();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->post('/api/notes', [
                'title' => fake()->sentence(3),
                'tags' => fake()->words(2),
                'body' => fake()->paragraph(),
            ]);

        $response
            ->assertStatus(500)
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'error')
                ->has('message')
            );
    }

    public function testGetSuccess(): void
    {
        $login = $this->login();

        $notes = Note::factory()
            ->count(4)
            ->for($login['user'])
            ->create();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->get('/api/notes/');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->whereType('data.notes', 'array')
                ->has('data.notes', 4, self::noteJsonAsserter($notes->load('user')->first()->toArray()))
            );
    }

    public function testGetSuccessEmpty(): void
    {
        $login = $this->login();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->get('/api/notes/');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'notes' => []
                ]
            ]);
    }

    public function testGetUnauthorized(): void
    {
        $response = $this->get('/api/notes/');

        $response
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8');
    }

    public function testGetDetailSuccess(): void
    {
        $login = $this->login();

        $note = Note::factory()
            ->for($login['user'])
            ->create();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->get('/api/notes/' . $note->id);

        $response
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->has('data.note', self::noteJsonAsserter($note->load('user')->toArray()))
            );
    }

    public function testGetDetailNotFound(): void
    {
        $login = $this->login();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->get('/api/notes/' . Str::ulid());

        $response
            ->assertNotFound()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'fail')
                ->whereType('message', 'string')
            );
    }

    public function testUpdateSuccess(): void
    {
        $login = $this->login();

        $note = Note::factory()
            ->for($login['user'])
            ->create();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->put('/api/notes/' . $note->id, [
                'title' => $note->title,
                'body' => fake()->paragraph(),
                'tags' => $note->tags,
            ]);

        $response
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->whereType('message', 'string')
            );

        $updatedNote = Note::find($note->id);
        $this->assertNotEquals($note->toArray(), $updatedNote->toArray());
    }

    #[DataProvider('badPayloadsProvider')]
    public function testUpdateWithBadPayloads(array $payload): void
    {
        $login = $this->login();

        $note = Note::factory()
            ->for($login['user'])
            ->create();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->put('/api/notes/' . $note->id, $payload);

        $response
            ->assertBadRequest()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'fail')
                ->has('message')
                ->etc(),
            );
    }

    public function testUpdateNotFound(): void
    {
        $login = $this->login();

        $note = Note::factory()
            ->for($login['user'])
            ->create();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->put('/api/notes/' . Str::ulid(), [
                'title' => $note->id,
                'body' => fake()->paragraph(),
                'tags' => $note->tags,
            ]);

        $response
            ->assertNotFound()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'fail')
                ->whereType('message', 'string')
            );

        $updatedNote = Note::find($note->id);
        $this->assertEquals($note->toArray(), $updatedNote->toArray());
    }

    public function testDeleteSuccess(): void
    {
        $login = $this->login();

        $note = Note::factory()
            ->for($login['user'])
            ->create();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->delete('/api/notes/' . $note->id);

        $response
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->whereType('message', 'string')
            );

        $this->assertModelMissing($note);
    }

    public function testDeleteNotFound(): void
    {
        $login = $this->login();

        $note = Note::factory()
            ->for($login['user'])
            ->create();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $login['access_token']
            ])
            ->delete('/api/notes/' . Str::ulid());

        $response
            ->assertNotFound()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'fail')
                ->whereType('message', 'string')
            );

        $this->assertModelExists($note);
    }
    public static function noteJsonAsserter(array $note): callable
    {
        return fn(AssertableJson $json) => $json
            ->whereAll([
                'id' => $note['id'],
                'title' => $note['title'],
                'body' => $note['body'],
                'createdAt' => $note['created_at'],
                'updatedAt' => $note['updated_at'],
                'tags' => $note['tags'],
                'username' => $note['user']['username']
            ]);
    }
}
