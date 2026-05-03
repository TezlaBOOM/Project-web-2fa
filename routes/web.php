<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\RegisterController;
use App\Http\Controllers\Frontend\LoginController;

Route::get('/', function () {
    return view('Frontend.login');
})->name('login');

Route::get('/register', function () {
    return view('Frontend.register');
})->name('register');

Route::post('/register', [RegisterController::class, 'store']);
Route::post('/login', [LoginController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('Backend.dashboard');
    })->name('dashboard');
});
