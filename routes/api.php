<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Middleware\ApiAuthenticate;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/users', [UserController::class, 'register']);
Route::post('/auth/login', [UserController::class, 'login']);

Route::middleware(ApiAuthenticate::class)->group(function () {
    Route::get('/users/current', [UserController::class, 'get']);
    Route::put('/users/current', [UserController::class, 'update']);
    Route::patch('/users/current/password', [UserController::class, 'updatePassword']);
    Route::delete('/auth/logout', [UserController::class, 'logout']);
});
