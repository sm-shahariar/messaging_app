<?php

use App\Livewire\Chat;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;


Route::middleware('auth')->group(function () {
    Route::get('/chat', \App\Livewire\ChatComponent::class)->name('chat');
    Route::post('/messages', [ChatController::class, 'sendMessage']);
});


Route::view('/', 'welcome');
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
