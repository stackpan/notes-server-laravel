<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteCreateRequest;
use App\Http\Requests\NoteUpdateRequest;
use App\Http\Resources\NoteCollection;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function create(NoteCreateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = auth()->user();
        $note = $user->notes()->create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'tags' => $validated['tags'],
        ]);

        return response()
            ->json([
                'status' => 'success',
                'message' => 'Catatan berhasil ditambahkan',
                'data' => [
                    'noteId' => $note->id,
                ]
            ], 201)
            ->withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
            ]);
    }

    public function get(): JsonResponse
    {
        $user = auth()->user();

        $ownedNotes = $user->notes;
        $collaborationNotes = $user->collaborationNotes;

        $notes = $ownedNotes->concat($collaborationNotes);

        return (new NoteCollection($notes))
            ->additional([
                'status' => 'success',
            ])
            ->response()
            ->withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
            ]);
    }

    public function getDetail(string $id): JsonResponse
    {
        if (!$note = Note::find($id)) {
            throw new HttpResponseException(response()->json([
                'status' => 'fail',
                'message' => 'Catatan tidak ditemukan'
            ], 404));
        }

        $this->authorize('view', $note);
        return response()
            ->json([
                'status' => 'success',
                'data' => [
                    'note' => new NoteResource($note)
                ]
            ])
            ->withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
            ]);
    }

    public function update(NoteUpdateRequest $request, string $id): JsonResponse
    {
        if (!$note = Note::find($id)) {
            throw new HttpResponseException(response()->json([
                'status' => 'fail',
                'message' => 'Gagal memperbarui catatan. Id catatan tidak ditemukan'
            ], 404));
        }

        $this->authorize('update', $note);

        $validated = $request->validated();

        $note
            ->fill([
                'title' => $validated['title'],
                'body' => $validated['body'],
                'tags' => $validated['tags']
            ])
            ->save();

        return response()
            ->json([
                'status' => 'success',
                'message' => 'Catatan berhasil diperbarui',
            ])
            ->withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
            ]);
    }

    public function delete(string $id): JsonResponse
    {
        if (!$note = Note::find($id)) {
            throw new HttpResponseException(response()->json([
                'status' => 'fail',
                'message' => 'Catatan gagal dihapus. Id catatan tidak ditemukan'
            ], 404));
        }

        $this->authorize('delete', $note);

        $note->delete();
        return response()
            ->json([
                'status' => 'success',
                'message' => 'Catatan berhasil dihapus',
            ])
            ->withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
            ]);
    }

}
