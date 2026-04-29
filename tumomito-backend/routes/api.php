<?php

use App\Http\Controllers\Api\CarritoController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ErpController;
use App\Http\Controllers\Api\MarketingController;
use App\Http\Controllers\Api\ProductoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('throttle:120,1')->group(function () {
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/carrito', [CarritoController::class, 'index']);
});

Route::middleware('throttle:40,1')->group(function () {
    Route::post('/carrito/add', [CarritoController::class, 'store']);
    Route::post('/checkout', [CheckoutController::class, 'process']);
});

Route::prefix('erp')->middleware('throttle:60,1')->group(function () {
    Route::get('/dashboard', [ErpController::class, 'dashboard']);
    Route::get('/proveedores', [ErpController::class, 'proveedores']);
    Route::post('/proveedores', [ErpController::class, 'crearProveedor']);
    Route::get('/compras', [ErpController::class, 'compras']);
    Route::post('/compras', [ErpController::class, 'registrarCompra']);
    Route::get('/inventario/movimientos', [ErpController::class, 'movimientosInventario']);
});

Route::prefix('marketing')->middleware('throttle:60,1')->group(function () {
    Route::get('/kpis', [MarketingController::class, 'index']);
    Route::post('/kpis', [MarketingController::class, 'upsert']);
    Route::get('/resumen', [MarketingController::class, 'resumen']);
});
