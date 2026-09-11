<?php

use App\Http\Controllers\CustomerSpendingController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/customer-spending', [CustomerSpendingController::class, 'index'])->name('customer-spending.index');
