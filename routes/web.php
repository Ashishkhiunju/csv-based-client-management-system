<?php

use App\Http\Controllers\ClientManagementController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::group(['prefix' => 'admin', 'middleware' => 'auth'], function () {
    Route::get('/client-management', [ClientManagementController::class, 'index'])->name('client-management');
    Route::post('/client-management/import', [ClientManagementController::class, 'import'])->name('client-management.import');
    Route::get('/client-management/import/review/{token}', [ClientManagementController::class, 'importReview'])->name('client-management.import.review');
    Route::post('/client-management/import/confirm/{token}', [ClientManagementController::class, 'importConfirm'])->name('client-management.import.confirm');
    Route::post('/client-management/import/cancel/{token}', [ClientManagementController::class, 'importCancel'])->name('client-management.import.cancel');
    Route::get('/client-management/{client}/edit', [ClientManagementController::class, 'edit'])->name('client-management.edit');
    Route::put('/client-management/{client}', [ClientManagementController::class, 'update'])->name('client-management.update');
    Route::delete('/client-management/{client}', [ClientManagementController::class, 'destroy'])->name('client-management.destroy');
});
