<?php

use Illuminate\Support\Facades\Route;
use Modules\Cartera\Http\Controllers\CarteraController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('carteras', CarteraController::class)->names('cartera');
});
