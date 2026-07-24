<?php

use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

// start game
Route::get('/', [MainController::class, 'startGame'])->name('start_name');
Route::get('/game', [MainController::class, 'game'])->name('game');
Route::post('/', [MainController::class, 'prepareGame'])->name('prepare-game');

Route::get('/show_data', [MainController::class, 'showData']);