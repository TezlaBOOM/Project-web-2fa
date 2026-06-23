<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\RegisterController;
use App\Http\Controllers\Frontend\LoginController;
use App\Http\Controllers\Backend\SettingsController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\DepartmentController;
use App\Http\Controllers\Backend\PModulController;
use App\Http\Controllers\Backend\POperacjeController;
use App\Http\Controllers\Backend\PAccessController;
use App\Http\Controllers\Backend\DocumentController;
use App\Http\Controllers\Frontend\PasswordResetController;

Route::get('/', function () {
    return view('Frontend.login');
})->name('login');

Route::get('/register', function () {
    return view('Frontend.register');
})->name('register');

Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');
Route::post('/login', [LoginController::class, 'authenticate'])->middleware('throttle:10,1')->name('login.post');
Route::get('/login/2fa', [LoginController::class, 'show2faForm'])->name('login.2fa');
Route::post('/login/2fa', [LoginController::class, 'verify2fa'])->middleware('throttle:5,1')->name('login.2fa.verify');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Resetowanie hasła
Route::get('/forgot-password',  [PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:3,5')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $role = $user->role ?? 'none';
        
        $allowedRoles = ['admin', 'mod', 'user', 'none'];
        if (!in_array($role, $allowedRoles)) {
            $role = 'none';
        }
        
        $usersCount = 0;
        $activities = null;
        $accesses = null;
        $search = request('search');

        if ($role === 'admin') {
            $usersCount = \App\Models\User::count();
            $query = \App\Models\UserActivity::with('user');
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%")
                      ->orWhereHas('user', function($uq) use ($search) {
                          $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }
            $activities = $query->latest()->paginate(10)->withQueryString();
        } elseif ($role === 'mod') {
            $departmentIds = $user->departments->pluck('ID_Departament');
            if ($departmentIds->isNotEmpty()) {
                $usersCount = \App\Models\User::whereHas('departments', function ($q) use ($departmentIds) {
                    $q->whereIn('Departament.ID_Departament', $departmentIds);
                })->count();
            }
        } elseif ($role === 'user') {
            $accesses = \App\Models\PAccess::with(['modul', 'operacja'])->where('user_id', $user->id)->get();
        }
        
        return view("Backend.{$role}.dashboard", compact('usersCount', 'activities', 'accesses', 'search'));
    })->name('dashboard');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/2fa', [SettingsController::class, 'toggle2fa'])->name('settings.2fa.toggle');
    Route::get('/settings/logon', [SettingsController::class, 'logon'])->name('settings.logon');
    Route::post('/settings/logon', [SettingsController::class, 'updateLogon'])->name('settings.logon.update');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{user}/permissions', [UserController::class, 'showPermissions'])->name('users.permissions');

    // Departments (Wydziały)
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

    // Uprawnienia (Permissions)
    Route::resource('/permissions/modules', PModulController::class)->except(['show']);
    Route::resource('/permissions/operations', POperacjeController::class)->except(['show']);
    Route::resource('/permissions/access', PAccessController::class)->except(['show']);

    // Dokumenty (Documents)
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
});
