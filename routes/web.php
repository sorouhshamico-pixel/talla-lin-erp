<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
