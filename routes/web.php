<?php

use App\Http\Controllers\ClientManagementController;
use App\Http\Controllers\TestRunnerController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/readme', function () {
    $md = @file_get_contents(base_path('README.md')) ?: '# README not found';

    return view('readme', [
        'html' => Str::markdown($md),
    ]);
})->name('readme');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::group(['prefix' => 'admin', 'middleware' => 'auth'], function () {
    Route::get('/client-management', [ClientManagementController::class, 'index'])->name('client-management');
    Route::get('/client-management/export/all', [ClientManagementController::class, 'exportAllCsv'])->name('client-management.export.all');
    Route::post('/client-management/import', [ClientManagementController::class, 'import'])->name('client-management.import');
    Route::get('/client-management/import/review/{token}', [ClientManagementController::class, 'importReview'])->name('client-management.import.review');
    Route::post('/client-management/import/confirm/{token}', [ClientManagementController::class, 'importConfirm'])->name('client-management.import.confirm');
    Route::post('/client-management/import/cancel/{token}', [ClientManagementController::class, 'importCancel'])->name('client-management.import.cancel');
    Route::get('/client-management/{client}/edit', [ClientManagementController::class, 'edit'])->name('client-management.edit');
    Route::put('/client-management/{client}', [ClientManagementController::class, 'update'])->name('client-management.update');
    Route::delete('/client-management/{client}', [ClientManagementController::class, 'destroy'])->name('client-management.destroy');
    Route::get('readme', function () {
        return view('admin.readme');
    })->name('admin.readme');

    Route::get('test', function () {
        return app(TestRunnerController::class)->index();
    })->name('admin.test');

    Route::post('test/run-feature', [TestRunnerController::class, 'runFeature'])->name('admin.test.runFeature');
    Route::post('test/run-unit', [TestRunnerController::class, 'runUnit'])->name('admin.test.runUnit');
});
