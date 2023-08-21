<?php

namespace Tests\Feature\Api\Note;

use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Util\Note as NoteTestUtil;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class NoteCollaborationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $collaborator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->has(Note::factory())->create();
        $this->collaborator = User::factory()->create();
    }

    public function testAddNoteCollaboratorAsOwner(): void
    {
        $ownerLogin = $this->login($this->owner);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $ownerLogin['access_token'])
            ->post('/api/collaborations', [
                'noteId' => $this->owner->notes->first()->id,
                'userId' => $this->collaborator->id,
            ]);

        $response
            ->assertCreated()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->has('data.collaborationId')
            );

        $this->assertDatabaseHas('collaborations', [
            'note_id' => $this->owner->notes->first()->id,
            'user_id' => $this->collaborator->id,
        ]);
    }

    public static function addNoteCollaboratorBadPayloadProvider(): array
    {
        return [
            'the user is owner' => [
                fn(NoteCollaborationTest $testCase) => [
                    'noteId' => $testCase->owner->notes->first()->id,
                    'userId' => $testCase->owner->id,
                ],
                'Gagal menambahkan karena user adalah pemilik catatan'
            ],
            'the user is already a collaborator' => [
                function (NoteCollaborationTest $testCase) {
                    $ownerNote = $testCase->owner->notes()->first();
                    $ownerNote->collaborators()->save($testCase->collaborator);

                    return [
                        'noteId' => $ownerNote->id,
                        'userId' => $testCase->collaborator->id,
                    ];
                },
                'User sudah ditambahkan sebelumnya'
            ],
            'the user is not exists' => [
                fn(NoteCollaborationTest $testCase) => [
                    'noteId' => $testCase->owner->notes->first()->id,
                    'userId' => Str::ulid(),
                ],
                'User tidak ada'
            ],
            'the note is not exists' => [
                fn(NoteCollaborationTest $testCase) => [
                    'noteId' => Str::ulid(),
                    'userId' => $testCase->collaborator->id,
                ],
                'Catatan tidak ada'
            ],
        ];
    }

    #[TestDox('add note collaborator with bad payload should fail')]
    #[DataProvider('addNoteCollaboratorBadPayloadProvider')]
    public function testAddNoteCollaboratorWithBadPayload(callable $ceremony, string $responseMessageExpectation)
    {
        $payload = $ceremony($this);

        $ownerLogin = $this->login($this->owner);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $ownerLogin['access_token'])
            ->post('/api/collaborations', $payload);

        $response
            ->assertBadRequest()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'fail')
                ->where('message', $responseMessageExpectation)
            );
    }

    public function testGetNotesAsCollaborator(): void
    {
        $ownerNote = $this->owner->notes()->first();
        $ownerNote->collaborators()->save($this->collaborator);

        $collaboratorLogin = $this->login($this->collaborator);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $collaboratorLogin['access_token'])
            ->get('/api/notes');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->has('data.notes', 1)
                ->etc()
            );
    }

    public function testGetNoteDetailAsCollaborator(): void
    {
        $ownerNote = $this->owner->notes()->first();
        $ownerNote->collaborators()->save($this->collaborator);

        $collaboratorLogin = $this->login($this->collaborator);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $collaboratorLogin['access_token'])
            ->get('/api/notes/' . $ownerNote->id);

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->has('data.note', NoteTestUtil::noteJsonAsserter($ownerNote->load('user')->toArray()))
                ->etc()
            );
    }

    public function testUpdateNoteAsCollaborator(): void
    {
        $ownerNote = $this->owner->notes()->first();
        $ownerNote->collaborators()->save($this->collaborator);

        $collaboratorLogin = $this->login($this->collaborator);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $collaboratorLogin['access_token'])
            ->put('/api/notes/' . $ownerNote->id, [
                'title' => $ownerNote->title,
                'body' => fake()->paragraph(),
                'tags' => $ownerNote->tags,
            ]);

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8');
    }

    #[TestDox('delete note as collaborator should forbidden')]
    public function testDeleteNoteAsCollaborator(): void
    {
        $ownerNote = $this->owner->notes()->first();
        $ownerNote->collaborators()->save($this->collaborator);

        $collaboratorLogin = $this->login($this->collaborator);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $collaboratorLogin['access_token'])
            ->delete('/api/notes/' . $ownerNote->id);

        $response
            ->assertForbidden()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8');
    }

    public function testDeleteNoteCollaborationAsOwner(): void
    {
        $ownerNote = $this->owner->notes()->first();
        $ownerNote->collaborators()->save($this->collaborator);

        $ownerLogin = $this->login($this->owner);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $ownerLogin['access_token'])
            ->delete('/api/collaborations', [
                'noteId' => $ownerNote->id,
                'userId' => $this->collaborator->id,
            ]);

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->has('message')
            );

        $this->assertDatabaseMissing('collaborations', [
            'note_id' => $ownerNote->id,
            'user_id' => $this->collaborator->id,
        ]);
    }

    public function testDeleteNoteCollaborationAsOwnerNotFound(): void
    {
        $ownerLogin = $this->login($this->owner);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $ownerLogin['access_token'])
            ->delete('/api/collaborations', [
                'noteId' => $this->owner->notes->first()->id,
                'userId' => $this->collaborator->id,
            ]);

        $response
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'fail')
                ->where('message', 'Kolaborasi tidak ditemukan')
            );
    }

    #[TestDox('add note collaborator as collaborator should forbidden')]
    public function testAddNoteCollaboratorAsCollaborator()
    {
        $collaboratorLogin = $this->login($this->collaborator);

        $otherUser = User::factory()->create();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $collaboratorLogin['access_token'])
            ->post('/api/collaborations', [
                'noteId' => $this->owner->notes->first()->id,
                'userId' => $otherUser->id,
            ]);

        $response
            ->assertForbidden()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'fail')
                ->has('message')
            );

        $this->assertDatabaseMissing('collaborations', [
            'note_id' => $this->owner->notes->first()->id,
            'user_id' => $this->collaborator->id,
        ]);
    }

    #[TestDox('add note collaborator as collaborator should forbidden')]
    public function testDeleteNoteCollaboratorAsCollaborator()
    {
        $ownerNote = $this->owner->notes()->first();
        $ownerNote->collaborators()->save($this->collaborator);

        $collaboratorLogin = $this->login($this->collaborator);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $collaboratorLogin['access_token'])
            ->delete('/api/collaborations', [
                'noteId' => $ownerNote->id,
                'userId' => $this->collaborator->id,
            ]);

        $response
            ->assertForbidden()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'fail')
                ->has('message')
            );

        $this->assertDatabaseHas('collaborations', [
            'note_id' => $ownerNote->id,
            'user_id' => $this->collaborator->id,
        ]);
    }

    #[TestDox('get note detail as not collaborator should forbidden')]
    public function testGetNoteDetailAsNotCollaborator(): void
    {
        $collaboratorLogin = $this->login($this->collaborator);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $collaboratorLogin['access_token'])
            ->get('/api/notes/' . $this->owner->notes()->first()->id);

        $response
            ->assertForbidden()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8');
    }

    #[TestDox('update note as not collaborator user should forbidden')]
    public function testUpdateNoteAsNotNotCollaborator(): void
    {
        $ownerNote = $this->owner->notes()->first();

        $collaboratorLogin = $this->login($this->collaborator);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $collaboratorLogin['access_token'])
            ->put('/api/notes/' . $ownerNote->id, [
                'title' => $ownerNote->title,
                'body' => fake()->paragraph(),
                'tags' => $ownerNote->tags,
            ]);

        $response
            ->assertForbidden()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8');
    }

    private function login(User $user): array
    {
        $response = $this->post('/api/authentications', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        return [
            'access_token' => $response['data']['accessToken'],
            'refresh_token' => $response['data']['refreshToken'],
        ];
    }
}
