<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportNotesRequest;
use App\Jobs\ProcessNotesExport;
use App\Mail\NotesExport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ExportController extends Controller
{
    public function exportNotes(ExportNotesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        ProcessNotesExport::dispatch(auth()->user(), $validated['targetEmail']);

        return response()
            ->json([
                'status' => 'success',
                'message' => 'Permintaan Anda dalam antrean'
            ], 201)
            ->header('Content-Type', 'application/json; charset=utf-8');
    }
}
