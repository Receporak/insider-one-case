<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Notification\NotificationTemplateController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    Route::prefix('notification')->group(function () {
        Route::get('/',                          [NotificationController::class, 'list']);
        Route::post('/',                         [NotificationController::class, 'insert']);
        Route::get('/findByBatchId/{batchId}',   [NotificationController::class, 'findByBatchId']);
        Route::get('/{id}',                      [NotificationController::class, 'findById']);
        Route::put('/{id}',                      [NotificationController::class, 'update']);
        Route::put('/updateByBatchId/{batchId}', [NotificationController::class, 'updateByBatchId']);
        Route::delete('/{id}',                   [NotificationController::class, 'delete']);
    });

    Route::prefix('notification-template')->group(function () {
        Route::post('/',      [NotificationTemplateController::class, 'create']);
        Route::get('/{id}',   [NotificationTemplateController::class, 'findById']);
        Route::put('/{id}',   [NotificationTemplateController::class, 'update']);
        Route::delete('/{id}',[NotificationTemplateController::class, 'delete']);
    });

    Route::prefix('metrics')->group(function () {
        Route::get('/notifications', [MetricController::class, 'getNotificationMetric']);
    });

    Route::get('/health', [HealthController::class, 'check']);
});

