<?php

use Illuminate\Support\Facades\Route;
use Modules\Integracion\Http\Controllers\IntegracionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('integracions', IntegracionController::class)->names('integracion');
});
