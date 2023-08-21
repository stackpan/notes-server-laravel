<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Mockery\Matcher\Not;

class NotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Note $note): bool
    {
        $isOwner = $note->user->is($user);
        $isCollaborator = $note->collaborators()->where('user_id', $user->id)->first();

        if (!$isOwner && !$isCollaborator) {
            throw new HttpResponseException(response()
                ->json([
                    'status' => 'fail',
                    'message' => 'Anda tidak berhak mengakses resource ini'
                ], 403)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                ])
            );
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Note $note): bool
    {
        $isOwner = $note->user->is($user);
        $isCollaborator = $note->collaborators()->where('user_id', $user->id)->first();

        if (!$isOwner && !$isCollaborator) {
            throw new HttpResponseException(response()
                ->json([
                    'status' => 'fail',
                    'message' => 'Anda tidak berhak mengakses resource ini'
                ], 403)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                ])
            );
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Note $note): bool
    {
        if (!$note->user->is($user)) {
            throw new HttpResponseException(response()
                ->json([
                    'status' => 'fail',
                    'message' => 'Anda tidak berhak mengakses resource ini'
                ], 403)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                ])
            );
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Note $note): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Note $note): bool
    {
        return true;
    }

    public function addCollaborator(User $user, Note $note): bool
    {
        if (!$note->user->is($user)) {
            throw new HttpResponseException(response()
                ->json([
                    'status' => 'fail',
                    'message' => 'Anda tidak berhak mengakses resource ini'
                ], 403)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                ])
            );
        }

        return true;
    }

    public function removeCollaborator(User $user, Note $note): bool
    {
        if (!$note->user->is($user)) {
            throw new HttpResponseException(response()
                ->json([
                    'status' => 'fail',
                    'message' => 'Anda tidak berhak mengakses resource ini'
                ], 403)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                ])
            );
        }

        return true;
    }
}
