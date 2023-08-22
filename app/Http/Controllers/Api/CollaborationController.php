<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CollaborationCreateRequest;
use App\Http\Requests\CollaborationDeleteRequest;
use App\Models\Collaboration;
use App\Models\Note;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollaborationController extends Controller
{
    public function create(CollaborationCreateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $request->checkUserIdIsNotOwnerId();

        $this->authorize('addCollaborator', Note::find($validated['noteId']));

        $collaboration = Collaboration::create([
            'note_id' => $validated['noteId'],
            'user_id' => $validated['userId'],
        ]);

        return response()
            ->json([
                'status' => 'success',
                'data' => [
                    'collaborationId' => $collaboration->id,
                ],
            ], 201)
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function delete(CollaborationDeleteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->authorize('removeCollaborator', Note::find($validated['noteId']));

        $collaboration = Collaboration::where('note_id', $validated['noteId'])
            ->where('user_id', $validated['userId'])
            ->first();

        if (!$collaboration) {
            throw new HttpResponseException(response()
                ->json([
                    'status' => 'fail',
                    'message' => 'Kolaborasi tidak ditemukan'
                ], 404)
                ->header('Content-Type', 'application/json; charset=utf-8'));
        }

        $collaboration->delete();

        return response()
            ->json([
                'status' => 'success',
                'message' => 'Kolaborasi berhasil dihapus',
            ])
            ->header('Content-Type', 'application/json; charset=utf-8');
    }
}
