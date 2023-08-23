<?php

namespace App\Jobs;

use App\Http\Resources\NoteCollection;
use App\Mail\NotesExport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProcessNotesExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly User $user,
        private readonly string $targetEmail,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $notes = $this->user->notes()
            ->leftJoin('collaborations', 'collaborations.note_id', '=', 'notes.id')
            ->orWhere('collaborations.user_id', $this->user->id)
            ->get([
                'notes.id',
                'notes.user_id',
                'notes.title',
                'notes.tags',
                'notes.body',
                'notes.created_at',
                'notes.updated_at',
            ]);

        Mail::to($this->targetEmail)->send(new NotesExport((new NoteCollection($notes))->toJson(), $this->user));
    }
}
