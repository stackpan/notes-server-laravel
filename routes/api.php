<?php

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

Route::post('/notes', [\App\Http\Controllers\Api\NoteController::class, 'create'])->name('api.notes.create');
Route::get('/notes', [\App\Http\Controllers\Api\NoteController::class, 'get'])->name('api.notes.get');
Route::get('/notes/{id}', [\App\Http\Controllers\Api\NoteController::class, 'getDetail'])->name('api.notes.get_detail');
Route::put('/notes/{id}', [\App\Http\Controllers\Api\NoteController::class, 'update'])->name('api.notes.update');
Route::delete('/notes/{id}', [\App\Http\Controllers\Api\NoteController::class, 'delete'])->name('api.notes.delete');

Route::post('/users', [\App\Http\Controllers\Api\UserController::class, 'create'])->name('api.users.create');
Route::get('/users/{id}', [\App\Http\Controllers\Api\UserController::class, 'get'])->name('api.users.get');
