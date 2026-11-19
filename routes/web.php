<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BasicController;
use App\Http\Controllers\AuthController;



// ========================================
// АВТОРИЗАЦИЯ
// ========================================

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.post');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

// ========================================
// ПУБЛИЧНАЯ СТАРТОВАЯ СТРАНИЦА
// ========================================

//Route::get('/', [BasicController::class, 'index'])
//    ->name('home');


// ========================================
// ЗАЩИЩЁННЫЕ СТРАНИЦЫ
// ========================================

Route::middleware('auth')->group(function () {

    // ========================================
    // УПРАВЛЕНИЕ ПОЛЬЗОВАТЕЛЯМИ
    // ========================================

    Route::get('/users', [AuthController::class, 'index'])
        ->name('users.index');

    Route::get('/users/create', [AuthController::class, 'create'])
        ->name('users.create');

    Route::post('/users', [AuthController::class, 'store'])
        ->name('users.store');

    Route::get('/users/{id}/edit', [AuthController::class, 'edit'])
        ->whereNumber('id')
        ->name('users.edit');

    Route::put('/users/{id}', [AuthController::class, 'update'])
        ->whereNumber('id')
        ->name('users.update');

    Route::delete('/users/{id}', [AuthController::class, 'destroy'])
        ->whereNumber('id')
        ->name('users.destroy');


    // ========================================
    // ГЛАВНАЯ СТРАНИЦА
    // ========================================

    Route::get('/', function () {
        return view('static.home');
    })->name('home');

    // ========================================
    // АКТИВНОЕ ИМУЩЕСТВО
    // ========================================

    Route::get('/mayno', [BasicController::class, 'mayno'])
        ->name('mayno');

    Route::get('/mayno/{code}/edit', [BasicController::class, 'editMayno'])
        ->whereNumber('code')
        ->name('mayno.edit');

    Route::put('/mayno/{code}', [BasicController::class, 'updateMayno'])
        ->whereNumber('code')
        ->name('mayno.update');


    // ========================================
    // СПИСАННОЕ ИМУЩЕСТВО
    // ========================================

    Route::get('/maynoSpisane', [BasicController::class, 'maynoSpisane'])
        ->name('maynoSpisane');

    Route::get('/maynoSpisane/{code}/edit', [BasicController::class, 'editMaynoSpisane'])
        ->whereNumber('code')
        ->name('maynoSpisane.edit');


    // ========================================
    // О ПРОГРАММЕ
    // ========================================

    Route::get('/About', [BasicController::class, 'about'])
        ->name('about');

});