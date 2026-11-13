<?php

use Illuminate\Support\Facades\Route;
use Modules\Seguridad\Http\Controllers\SeguridadController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('seguridads', SeguridadController::class)->names('seguridad');
});
