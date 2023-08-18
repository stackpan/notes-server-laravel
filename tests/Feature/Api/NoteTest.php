<?php

namespace Tests\Feature\Api;

use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    public static function noteJsonAsserter(array $note): callable
    {
        return fn (AssertableJson $json) => $json
            ->whereAll([
                'id' => $note['id'],
                'title' => $note['title'],
                'body' => $note['body'],
                'createdAt' => $note['created_at'],
                'updatedAt' => $note['updated_at'],
                'tags' => self::mapTags($note['tags']),
            ]);
    }

    public static function mapTags(array $tags): array
    {
        return collect($tags)
            ->map(fn (array $item, int $key) => $item['body'])
            ->toArray();
    }

    public static function createSuccessProvider(): array
    {
        return [
            'all field' => [
                [
                    'title' => fake()->sentence(3),
                    'tags' => fake()->words(2),
                    'body' => fake()->paragraph(),
                ],
                function (NoteTest $testCase, array $payload, TestResponse $response): void {
                    $noteId = $response['data']['noteId'];

                    $testCase->assertDatabaseHas('notes', [
                        'id' => $noteId,
                        'title' => $payload['title'],
                        'body' => $payload['body'],
                    ]);

                    foreach ($payload['tags'] as $tag) {
                        $testCase->assertDatabaseHas('tags', [
                            'body' =>  $tag,
                            'taggable_id' => $noteId,
                        ]);
                    }
                }
            ],
            'partially (no body)' => [
                [
                    'title' => fake()->sentence(3),
                    'tags' => fake()->words(2),
                ],
                function (NoteTest $testCase, array $payload, TestResponse $response): void {
                    $noteId = $response['data']['noteId'];

                    $testCase->assertDatabaseHas('notes', [
                        'id' => $noteId,
                        'title' => $payload['title'],
                    ]);

                    foreach ($payload['tags'] as $tag) {
                        $testCase->assertDatabaseHas('tags', [
                            'body' =>  $tag,
                            'taggable_id' => $noteId,
                        ]);
                    }
                }
            ],
            'partially (no tags)' => [
                [
                    'title' => fake()->sentence(3),
                    'body' => fake()->paragraph(),
                ],
                function (NoteTest $testCase, array $payload, TestResponse $response): void {
                    $noteId = $response['data']['noteId'];

                    $testCase->assertDatabaseHas('notes', [
                        'id' => $noteId,
                        'title' => $payload['title'],
                        'body' => $payload['body'],
                    ]);
                }
            ],
            'title only' => [
                [
                    'title' => fake()->sentence(3),
                ],
                function (NoteTest $testCase, array $payload, TestResponse $response): void {
                    $noteId = $response['data']['noteId'];

                    $testCase->assertDatabaseHas('notes', [
                        'id' => $noteId,
                        'title' => $payload['title'],
                    ]);
                }
            ],
        ];
    }

    #[DataProvider('createSuccessProvider')]
    public function testCreateSuccess(array $payload, callable $assertExtra): void
    {
        $response = $this
            ->post('/api/notes', $payload);

        $response
            ->assertStatus(201)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->has('message')
                ->has('data.noteId')
            );

        $assertExtra($this, $payload, $response);
    }

    public function testCreateFailed(): void
    {
        $this->markTestIncomplete('There is no 500 response configuration ATM.');

        $response = $this
            ->post('/api/notes', [
                'title' => fake()->sentence(3),
                'tags' => fake()->words(2),
                'body' => fake()->paragraph(),
            ]);

        $response
            ->assertStatus(500)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'error')
                ->has('message')
            );
    }

    public function testGetSuccess(): void
    {
        $firstNote = Note::factory()
            ->hasTags(3)
            ->count(4)
            ->create()
            ->first()
            ->load('tags')
            ->toArray();

        $response = $this
            ->get('/api/notes/');

        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->whereType('data.notes', 'array')
                ->has('data.notes', 4, self::noteJsonAsserter($firstNote))
            );
    }

    public function testGetSuccessEmpty(): void
    {
        $response = $this
            ->get('/api/notes/');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'notes' => []
                ]
            ]);
    }

    public function testGetDetailSuccess(): void
    {
        $note = Note::factory()
            ->hasTags(3)
            ->create()
            ->load('tags')
            ->toArray();

        $response = $this
            ->get('/api/notes/' . $note['id']);

        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->has('data.note', self::noteJsonAsserter($note))
            );
    }

    public function testGetDetailNotFound(): void
    {
        $response = $this
            ->get('/api/notes/' . Str::ulid());

        $response
            ->assertStatus(404)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'fail')
                ->whereType('message', 'string')
            );
    }

    public function testUpdateSuccess(): void
    {
        $note = Note::factory()
            ->hasTags(3)
            ->create()
            ->load('tags')
            ->toArray();

        $response = $this
            ->put('/api/notes/' . $note['id'], [
                'title' => $note['title'],
                'body' => fake()->paragraph(),
                'tags' => self::mapTags($note['tags']),
            ]);

        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->whereType('message', 'string')
            );

        $updatedNote = Note::find($note['id'])->load('tags')->toArray();
        $this->assertNotSame($note, $updatedNote);
    }

    public function testUpdateNotFound(): void
    {
        $note = Note::factory()
            ->hasTags(3)
            ->create()
            ->load('tags')
            ->toArray();

        $response = $this
            ->put('/api/notes/' . Str::ulid(), [
                'title' => $note['title'],
                'body' => fake()->paragraph(),
                'tags' => self::mapTags($note['tags']),
            ]);

        $response
            ->assertStatus(404)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'fail')
                ->whereType('message', 'string')
            );

        $updatedNote = Note::find($note['id'])->load('tags')->toArray();
        $this->assertSame($note, $updatedNote);
    }

    public function testDeleteSuccess(): void
    {
        $note = Note::factory()
            ->hasTags(3)
            ->create();

        $response = $this
            ->delete('/api/notes/' . $note->id);

        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->whereType('message', 'string')
            );

        $this->assertModelMissing($note);
    }

    public function testDeleteNotFound(): void
    {
        $note = Note::factory()
            ->hasTags(3)
            ->create();

        $response = $this
            ->delete('/api/notes/' . Str::ulid());

        $response
            ->assertStatus(404)
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'fail')
                ->whereType('message', 'string')
            );

        $this->assertModelExists($note);
    }
}
