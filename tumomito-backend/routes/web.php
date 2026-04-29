<?php

use App\Http\Controllers\Web\StoreController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BiController;
use App\Http\Controllers\Web\ErpWebController;
use App\Http\Controllers\Web\ErpUsuariosController;
use App\Http\Controllers\Web\WebCartController;
use App\Http\Controllers\Web\WebCheckoutController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return 'pong';
});

// Catálogo (lectura amplia: sin throttle estricto aquí)
Route::get('/', [StoreController::class, 'index'])->name('store.index');

Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login.form');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::get('/registro', [AuthController::class, 'showRegister'])->name('auth.register.form');
Route::post('/registro', [AuthController::class, 'register'])->name('auth.register');
Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::middleware('tumomito.auth')->group(function () {
    Route::get('/carrito', [WebCartController::class, 'index'])->name('cart.index');

    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/carrito/add', [WebCartController::class, 'store'])->name('cart.store');
        Route::post('/carrito/remove/{id}', [WebCartController::class, 'destroy'])->name('cart.destroy');
        Route::post('/checkout', [WebCheckoutController::class, 'process'])->name('checkout.process');
    });

    Route::prefix('erp')->middleware('tumomito.admin')->name('erp.')->group(function () {
        Route::get('/dashboard', [ErpWebController::class, 'dashboard'])->name('dashboard');
        Route::get('/compras', [ErpWebController::class, 'compras'])->name('compras');
        Route::post('/compras', [ErpWebController::class, 'registrarCompra'])->name('compras.store');
        Route::post('/proveedores', [ErpWebController::class, 'crearProveedor'])->name('proveedores.store');
        Route::get('/inventario', [ErpWebController::class, 'inventario'])->name('inventario');
        Route::get('/stock', [ErpWebController::class, 'stock'])->name('stock');
        Route::get('/stock-bajo', [ErpWebController::class, 'stockBajo'])->name('stock_bajo');
        Route::get('/marketing', [ErpWebController::class, 'marketing'])->name('marketing');
        Route::post('/marketing', [ErpWebController::class, 'guardarMarketing'])->name('marketing.store');

        Route::prefix('usuarios')->name('usuarios.')->group(function () {
            Route::get('/', [ErpUsuariosController::class, 'index'])->name('index');
            Route::get('/crear', [ErpUsuariosController::class, 'create'])->name('create');
            Route::post('/', [ErpUsuariosController::class, 'store'])->name('store');
            Route::get('/{id}/editar', [ErpUsuariosController::class, 'edit'])->name('edit');
            Route::post('/{id}', [ErpUsuariosController::class, 'update'])->name('update');
        });

        Route::prefix('bi')->name('bi.')->group(function () {
            Route::get('/ventas', [BiController::class, 'ventas'])->name('ventas');
            Route::get('/productos', [BiController::class, 'productos'])->name('productos');
        });
    });
});
