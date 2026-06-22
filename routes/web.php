<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/events')->name('home');

Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/data', [EventController::class, 'data'])->middleware('throttle:60,1')->name('events.data');
Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
Route::post('events/{event}/register', [EventController::class, 'register'])->middleware('throttle:20,1')->name('events.register');

Route::get('events-visual-1', [EventController::class, 'visualOne'])->name('events.visual1');
Route::get('events-visual-2', [EventController::class, 'visualTwo'])->name('events.visual2');

Route::inertia('dashboard', 'Dashboard')->name('dashboard');

require __DIR__.'/settings.php';
