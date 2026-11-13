<?php

use Illuminate\Support\Facades\Route;
use Modules\Integracion\Http\Controllers\IntegracionController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('integracions', IntegracionController::class)->names('integracion');
});
