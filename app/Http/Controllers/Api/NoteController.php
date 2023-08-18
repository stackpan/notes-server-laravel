<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteCreateRequest;
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

        $note = Note::create([
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
        ]);

        if (isset($validated['tags'])) {
            foreach ($validated['tags'] as $tag) {
                $note->tags()->create(['body' => $tag]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan berhasil ditambahkan',
            'data' => [
                'noteId' => $note->id,
            ]
        ], 201);
    }

    public function get(): NoteCollection
    {
        return (new NoteCollection(Note::all()))
            ->additional([
                'status' => 'success',
            ]);
    }

    public function getDetail(string $id): JsonResponse
    {
        $note = Note::find($id);

        if (!$note) {
            throw new HttpResponseException(response()->json([
                'status' => 'fail',
                'message' => 'Catatan tidak ditemukan'
            ], 404));
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'note' => new NoteResource($note)
            ]
        ]);
    }

}