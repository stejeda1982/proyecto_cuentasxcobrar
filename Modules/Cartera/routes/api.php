<?php

use Illuminate\Support\Facades\Route;
use Modules\Cartera\Http\Controllers\CarteraController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('carteras', CarteraController::class)->names('cartera');
});
