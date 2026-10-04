<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Livewire\Cashier\CloseShift;
use App\Livewire\Cashier\OpenShift;
use App\Livewire\Cashier\Payment;
use App\Livewire\Cashier\PosCheckout;
use App\Livewire\Cashier\Receipt;
use App\Livewire\Manager\Dashboard as ManagerDashboard;
use App\Livewire\Owner\Dashboard as OwnerDashboard;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/auth/login');

Route::get('/auth/login', [LoginController::class, 'create'])->name('auth.login');
Route::post('/auth/login', [LoginController::class, 'store'])->name('auth.login.store');
Route::post('/auth/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('auth.logout');
Route::get('/cashier/open-shift', OpenShift::class)->middleware('auth')->name('cashier.open-shift');
Route::redirect('/cashier', '/cashier/pos')->middleware('auth')->name('cashier.workspace');
Route::get('/cashier/pos', PosCheckout::class)->middleware('auth')->name('cashier.pos');
Route::get('/cashier/pos-checkout', PosCheckout::class)->middleware('auth')->name('cashier.pos.legacy');
Route::get('/cashier/payment-page', Payment::class)->middleware('auth')->name('cashier.payment');
Route::get('/cashier/receipt-page', Receipt::class)->middleware('auth')->name('cashier.receipt');
Route::get('/cashier/close-shift', CloseShift::class)->middleware('auth')->name('cashier.close-shift');
Route::get('/manager', ManagerDashboard::class)->middleware('auth')->name('manager.dashboard');
Route::get('/owner', OwnerDashboard::class)->middleware('auth')->name('owner.dashboard');
Route::get('/auth/register', [RegistrationController::class, 'create'])->middleware('guest')->name('auth.register');
Route::post('/auth/register', [RegistrationController::class, 'store'])->middleware('guest')->name('auth.register.store');
Route::get('/auth/forgot-password', [PasswordResetController::class, 'requestForm'])->middleware('guest')->name('password.request');
Route::post('/auth/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('guest')->name('password.email');
Route::get('/auth/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->middleware('guest')->name('password.reset');
Route::post('/auth/reset-password', [PasswordResetController::class, 'reset'])->middleware('guest')->name('password.update');
