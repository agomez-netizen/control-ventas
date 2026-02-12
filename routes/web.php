<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\Ventas\TalonarioController;
use App\Http\Controllers\Ventas\LiquidacionController;

/*
|--------------------------------------------------------------------------
| Ruta raíz
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return session()->has('user')
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Rutas públicas (solo invitados)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas (sesión + no cache)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.custom', 'nocache'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/scatter', [DashboardController::class, 'scatter'])->name('dashboard.scatter');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Ventas
    |--------------------------------------------------------------------------
    */
    Route::prefix('ventas')->name('ventas.')->group(function () {

        // Ventas (tu flujo actual es "crear", no hay index)
        Route::get('/create', [VentaController::class, 'create'])->name('create');
        Route::post('/', [VentaController::class, 'store'])->name('store');

        // Talonarios
        Route::get('/talonarios', [TalonarioController::class, 'index'])->name('talonarios.index');

        Route::get('/talonarios/estacion/{id_estacion}', [TalonarioController::class, 'estacion'])
            ->whereNumber('id_estacion')
            ->name('talonarios.estacion');

        // Anular talonario (admin)
        Route::get('/talonarios/{id}/anular', [TalonarioController::class, 'anularForm'])
            ->whereNumber('id')
            ->name('talonarios.anular.form');

        Route::post('/talonarios/{id}/anular', [TalonarioController::class, 'anularStore'])
            ->whereNumber('id')
            ->name('talonarios.anular.store');

        // Liquidaciones
        Route::get('/liquidaciones', [LiquidacionController::class, 'index'])->name('liquidaciones.index');
        Route::get('/liquidaciones/create', [LiquidacionController::class, 'create'])->name('liquidaciones.create');
        Route::post('/liquidaciones', [LiquidacionController::class, 'store'])->name('liquidaciones.store');

        Route::get('/liquidaciones/{id}', [LiquidacionController::class, 'show'])
            ->whereNumber('id')
            ->name('liquidaciones.show');

        // Talonarios disponibles por estación (tu endpoint existente)
        Route::get('/estaciones/{id_estacion}/talonarios-disponibles', [LiquidacionController::class, 'talonariosDisponibles'])
            ->whereNumber('id_estacion')
            ->name('estaciones.talonarios_disponibles');

        /*
        |--------------------------------------------------------------------------
        | Opción B: Liquidar por número
        |--------------------------------------------------------------------------
        */

        // Talonarios para el selector "por números" (AJAX)
        Route::get('/liquidaciones/talonarios', [LiquidacionController::class, 'talonariosByEstacion'])
            ->name('liquidaciones.talonarios');

        // Números DISPONIBLES de un talonario (AJAX)
        Route::get('/liquidaciones/talonarios/{talonarioId}/numeros', [LiquidacionController::class, 'numeros'])
            ->whereNumber('talonarioId')
            ->name('liquidaciones.numeros');

            Route::get('/estaciones/{id_estacion}/talonarios-disponibles', [LiquidacionController::class, 'talonariosDisponibles'])
  ->whereNumber('id_estacion')
  ->name('estaciones.talonarios_disponibles');

Route::get('/liquidaciones/talonarios/{talonarioId}/numeros', [LiquidacionController::class, 'numeros'])
  ->whereNumber('talonarioId')
  ->name('liquidaciones.numeros'); // (si no la usas por name, igual sirve por URL)

    });
});
