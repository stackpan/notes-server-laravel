<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\NoteController;
use App\Models\Note;
use http\Env\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

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
                ->has('data.notes', 4, fn (AssertableJson $json) => $json
                    ->whereAll([
                        'id' => $firstNote['id'],
                        'title' => $firstNote['title'],
                        'body' => $firstNote['body'],
                        'createdAt' => $firstNote['created_at'],
                        'updatedAt' => $firstNote['updated_at'],
                        'tags' => collect($firstNote['tags'])
                            ->map(fn (array $item, int $key) => $item['body'])
                            ->toArray(),
                    ]),
                )
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
}
