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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

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

        Cache::forget('notes:user:' . $user->id);

        return response()
            ->json([
                'status' => 'success',
                'message' => 'Catatan berhasil ditambahkan',
                'data' => [
                    'noteId' => $note->id,
                ]
            ], 201)
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function get(): JsonResponse
    {
        $user = auth()->user();

        $notes = Cache::remember('notes:user:' . $user->id, 300, fn() => $user
                ->notes()
                ->leftJoin('collaborations', 'collaborations.note_id', '=', 'notes.id')
                ->orWhere('collaborations.user_id', $user->id)
                ->get([
                    'notes.id',
                    'notes.user_id',
                    'notes.title',
                    'notes.tags',
                    'notes.body',
                    'notes.created_at',
                    'notes.updated_at',
                ])
            );

        return (new NoteCollection($notes))
            ->additional([
                'status' => 'success',
            ])
            ->response()
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getDetail(string $id): JsonResponse
    {
        if (!$note = Cache::remember('notes:' . $id, 300, fn() => Note::find($id))) {
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
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function update(NoteUpdateRequest $request, string $id): JsonResponse
    {
        if (!$note = Note::find($id)) {
            throw new HttpResponseException(response()
                ->json([
                    'status' => 'fail',
                    'message' => 'Gagal memperbarui catatan. Id catatan tidak ditemukan'
                ], 404)
                ->header('Content-Type', 'application/json; charset=utf-8'));
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

        Cache::put('notes:' . $note->id, $note, 300);
        Cache::forget('notes:user:' . $note->user->id);

        return response()
            ->json([
                'status' => 'success',
                'message' => 'Catatan berhasil diperbarui',
            ])
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function delete(string $id): JsonResponse
    {
        if (!$note = Note::find($id)) {
            throw new HttpResponseException(response()
                ->json([
                    'status' => 'fail',
                    'message' => 'Catatan gagal dihapus. Id catatan tidak ditemukan'
                ], 404)
                ->header('Content-Type', 'application/json; charset=utf-8'));
        }

        $this->authorize('delete', $note);

        $note->delete();

        Cache::forget('notes:' . $note->id);
        Cache::forget('notes:user:' . $note->user->id);

        return response()
            ->json([
                'status' => 'success',
                'message' => 'Catatan berhasil dihapus',
            ])
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

}
