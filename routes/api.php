<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\NotificationController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('projects.tasks', TaskController::class);

    Route::prefix('projects/{project}')->controller(ProjectController::class)->group(function () {
        Route::post('/share', 'share');
        Route::delete('/unshare', 'unshare');
        Route::get('/shared-users', 'sharedUsers');
    });

    Route::controller(TaskController::class)->group(function () {
        Route::post('/tasks', 'store');
        Route::get('/projects/{project}/tasks', 'index');
        Route::put('/tasks/{task}', 'update');
        Route::delete('/tasks/{task}', 'destroy');
    });

    Route::prefix('friends')->controller(FriendController::class)->group(function () {
        Route::post('send', 'sendRequest');
        Route::post('respond', 'respondRequest');
        Route::get('list', 'listFriends');
        Route::get('pending', 'pendingRequests');
    });

    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('', 'getNotifications');
        Route::post('/{notification}/mark-as-read', 'markNotificationAsRead');
        Route::post('/mark-all-as-read', 'markAllNotificationsAsRead');
        Route::post('/{notification}/mark-as-unread', 'markNotificationAsUnread');
        Route::delete('/{notification}', 'destroy');
        Route::delete('/', 'destroyMultiple');
    });
});
