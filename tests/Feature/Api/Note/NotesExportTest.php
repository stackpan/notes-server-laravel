<?php

namespace Tests\Feature\Api\Note;

use App\Http\Resources\NoteCollection;
use App\Jobs\ProcessNotesExport;
use App\Mail\NotesExport;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\MailFake;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use function PHPUnit\TestFixture\func;

class NotesExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->has(Note::factory())
            ->create();

        Note::factory()
            ->for(User::factory())
            ->create()
            ->collaborators()
            ->save($this->user);
    }

    public function testSuccess()
    {
        $login = $this->login($this->user);

        Queue::fake();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $login['access_token'])
            ->post('/api/export/notes', [
                'targetEmail' => fake()->email(),
            ]);

        $response
            ->assertCreated()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson([
                'status' => 'success',
                'message' => 'Permintaan Anda dalam antrean'
            ]);

        Queue::assertPushed(ProcessNotesExport::class);
    }

    public function testNotesExportMailable()
    {
        $this->freezeTime(function (Carbon $time) {
            $targetEmail = fake()->email();

            Mail::fake();

            $notes = (new NoteCollection(Note::all()))->toJson();

            Mail::to($targetEmail)->send(new NotesExport($notes, $this->user));

            $filename = 'notes_user-' . $this->user->id . '_' . $time->nowWithSameTz() . '.json';

            Mail::assertSent(NotesExport::class, fn(NotesExport $mail) =>
                $mail->hasTo($targetEmail) &&
                $mail->hasSubject('Notes Export') &&
                $mail->hasAttachment(Attachment::fromData(fn() => $notes, $filename))
            );
        });

    }

    public static function badPayloadProvider(): array
    {
        return [
            'targetEmail is boolean' => [['targetEmail' => true]],
            'targetEmail is number' => [['targetEmail' => 0]],
            'targetEmail is blank string' => [['targetEmail' => '']],
            'targetEmail is not valid email 1' => [['targetEmail' => 'John']],
            'targetEmail is not valid email 2' => [['targetEmail' => 'qwert123']],
        ];
    }

    #[DataProvider('badPayloadProvider')]
    public function testWithBadPayload(array $payload)
    {
        $login = $this->login($this->user);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $login['access_token'])
            ->post('/api/export/notes', $payload);

        $response
            ->assertBadRequest()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
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
            'access_token' => $response['data']['accessToken'],
            'refresh_token' => $response['data']['refreshToken'],
        ];
    }
}
