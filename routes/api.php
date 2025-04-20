<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\FriendController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('projects.tasks', TaskController::class);

    Route::prefix('projects/{project}')->group(function () {
        Route::post('/share', [ProjectController::class, 'share']);
        Route::delete('/unshare', [ProjectController::class, 'unshare']);
        Route::get('/shared-users', [ProjectController::class, 'sharedUsers']);
    });

    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    Route::prefix('friends')->group(function () {
        Route::post('send', [FriendController::class, 'sendRequest']);
        Route::post('respond', [FriendController::class, 'respondRequest']);
        Route::get('list', [FriendController::class, 'listFriends']);
        Route::get('pending', [FriendController::class, 'pendingRequests']);
    });
});
