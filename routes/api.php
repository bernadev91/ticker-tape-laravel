<?php

use App\Http\Controllers\CalculationController;
use Illuminate\Support\Facades\Route;

Route::get('/calculations', [CalculationController::class, 'index']);

Route::middleware('throttle:30,1')->group(function (): void {
    Route::post('/calculations', [CalculationController::class, 'store']);
    Route::delete('/calculations', [CalculationController::class, 'destroyAll']);
    Route::delete('/calculations/{calculation}', [CalculationController::class, 'destroy']);
});

