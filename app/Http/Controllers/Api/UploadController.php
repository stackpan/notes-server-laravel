<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function uploadImage(UploadImageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $file = $validated['data'];

        $filename = Str::ulid()->toRfc4122() . '.' . $file->getClientOriginalExtension();

        $file->storePubliclyAs('public', $filename);

        $path = config('filesystems.disks.public.url') . '/' . $filename;

        return response()
            ->json([
                'status' => 'success',
                'data' => [
                    'fileLocation' => $path,
                ]
            ], 201)
            ->header('Content-Type', 'application/json; charset=utf-8');
    }
}
