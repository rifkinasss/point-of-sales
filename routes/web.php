<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Livewire\Cashier\OpenShift;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/auth/login');

Route::get('/auth/login', [LoginController::class, 'create'])->name('auth.login');
Route::post('/auth/login', [LoginController::class, 'store'])->name('auth.login.store');
Route::post('/auth/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('auth.logout');
Route::get('/cashier/open-shift', OpenShift::class)->middleware('auth')->name('cashier.open-shift');
Route::get('/auth/register', [RegistrationController::class, 'create'])->middleware('guest')->name('auth.register');
Route::post('/auth/register', [RegistrationController::class, 'store'])->middleware('guest')->name('auth.register.store');
Route::get('/auth/forgot-password', [PasswordResetController::class, 'requestForm'])->middleware('guest')->name('password.request');
Route::post('/auth/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('guest')->name('password.email');
Route::get('/auth/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->middleware('guest')->name('password.reset');
Route::post('/auth/reset-password', [PasswordResetController::class, 'reset'])->middleware('guest')->name('password.update');
