<?php

namespace Tests\Feature\Api;

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
            'access_token' => $response['data']['accessToken'],
            'refresh_token' => $response['data']['refreshToken'],
        ];
    }

    public function testCreateSuccess(): void
    {
        $token = $this->login();

        $payload = [
            'title' => fake()->sentence(3),
            'tags' => fake()->words(2),
            'body' => fake()->paragraph(),
        ];

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
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

        $noteId = $response['data']['noteId'];

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
        ]);
    }

    public static function barPayloadsProvider(): array
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

    #[DataProvider('barPayloadsProvider')]
    public function testCreateWithBadPayloads(array $payload)
    {
        $token = $this->login();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
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

        $token = $this->login();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
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
        $token = $this->login();

        $firstNote = Note::factory()
            ->count(4)
            ->create()
            ->first()
            ->toArray();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
            ])
            ->get('/api/notes/');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->whereType('data.notes', 'array')
                ->has('data.notes', 4, self::noteJsonAsserter($firstNote))
            );
    }

    public function testGetSuccessEmpty(): void
    {
        $token = $this->login();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
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
        $token = $this->login();

        $note = Note::factory()
            ->create()
            ->toArray();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
            ])
            ->get('/api/notes/' . $note['id']);

        $response
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->has('data.note', self::noteJsonAsserter($note))
            );
    }

    public function testGetDetailNotFound(): void
    {
        $token = $this->login();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
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
        $token = $this->login();

        $note = Note::factory()
            ->create()
            ->toArray();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
            ])
            ->put('/api/notes/' . $note['id'], [
                'title' => $note['title'],
                'body' => fake()->paragraph(),
                'tags' => $note['tags'],
            ]);

        $response
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->whereType('message', 'string')
            );

        $updatedNote = Note::find($note['id'])->toArray();
        $this->assertNotEquals($note, $updatedNote);
    }

    #[DataProvider('barPayloadsProvider')]
    public function testUpdateWithBadPayloads(array $payload): void
    {
        $token = $this->login();

        $note = Note::factory()
            ->create()
            ->toArray();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
            ])
            ->put('/api/notes/' . $note['id'], $payload);

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
        $token = $this->login();

        $note = Note::factory()
            ->create()
            ->toArray();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
            ])
            ->put('/api/notes/' . Str::ulid(), [
                'title' => $note['title'],
                'body' => fake()->paragraph(),
                'tags' => $note['tags'],
            ]);

        $response
            ->assertNotFound()
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'fail')
                ->whereType('message', 'string')
            );

        $updatedNote = Note::find($note['id'])->toArray();
        $this->assertEquals($note, $updatedNote);
    }

    public function testDeleteSuccess(): void
    {
        $token = $this->login();

        $note = Note::factory()
            ->create();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
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
        $token = $this->login();

        $note = Note::factory()
            ->create();

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token['access_token']
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
            ])
            ->has('username');
    }
}
