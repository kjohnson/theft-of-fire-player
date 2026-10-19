<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\TextController;
use Illuminate\Support\Facades\Route;

Route::get('/', PlayerController::class)->name('player');

Route::middleware('throttle:audio')
    ->whereIn('book', array_keys(config('audiobook.books')))
    ->whereNumber('chapter')
    ->group(function () {
        Route::get('/audio/{book}/{chapter}', [AudioController::class, 'stream'])->middleware('signed')->name('audio');
        Route::get('/audio/{book}/{chapter}/url', [AudioController::class, 'refresh'])->name('audio.refresh');
        Route::get('/text/{book}/{chapter}', [TextController::class, 'show'])->name('text');
    });
