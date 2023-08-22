<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CollaborationController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\RefreshTokenMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});


Route::post('/users', [UserController::class, 'create'])->name('api.users.create');
Route::get('/users/', [UserController::class, 'search'])->name('api.users.search');
Route::get('/users/{id}', [UserController::class, 'get'])->name('api.users.get');

Route::post('/authentications', [AuthController::class, 'login'])->name('api.auth.login');

Route::middleware(RefreshTokenMiddleware::class)->group(function () {
    Route::put('/authentications', [AuthController::class, 'refresh'])->name('api.auth.refresh');
    Route::delete('/authentications', [AuthController::class, 'logout'])->name('api.auth.logout');
});

Route::middleware('auth:api')->group(function () {
    Route::post('/notes', [NoteController::class, 'create'])->name('api.notes.create');
    Route::get('/notes', [NoteController::class, 'get'])->name('api.notes.get');
    Route::get('/notes/{id}', [NoteController::class, 'getDetail'])->name('api.notes.get_detail');
    Route::put('/notes/{id}', [NoteController::class, 'update'])->name('api.notes.update');
    Route::delete('/notes/{id}', [NoteController::class, 'delete'])->name('api.notes.delete');

    Route::post('/collaborations', [CollaborationController::class, 'create'])->name('api.collaborations.create');
    Route::delete('/collaborations', [CollaborationController::class, 'delete'])->name('api.collaborations.delete');
});
