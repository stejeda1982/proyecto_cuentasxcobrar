<?php

use Illuminate\Support\Facades\Route;
use Modules\Seguridad\Http\Controllers\LoginController;
use Modules\Seguridad\Http\Controllers\PanelController;

// LOG-01 · Inicio de sesión (pública)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // DAS-01 · Panel de operación (inicio por defecto tras login)
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
});

// Ruta de ejemplo de la Fase 0 para validar el middleware de roles (T0.4):
// solo administradores técnicos gestionan usuarios.
Route::middleware(['auth', 'rol:adm_tecnico'])
    ->get('/admin/usuarios', fn () => 'OK: gestión de usuarios (ADM-02)')
    ->name('admin.usuarios');
