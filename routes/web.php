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
        $role = auth()->user()->role ?? 'none';
        
        $allowedRoles = ['admin', 'mod', 'user', 'none'];
        if (!in_array($role, $allowedRoles)) {
            $role = 'none';
        }
        
        return view("Backend.{$role}.dashboard");
    })->name('dashboard');
});
