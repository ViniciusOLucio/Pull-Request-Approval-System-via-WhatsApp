<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});
Route::get('/webhook-data-show  ', [WebhookController::class, 'showWebhookData']);

Route::match(['get', 'post'], '/webhook', [WebhookController::class, 'handleWebhook']);


// Rota para adicionar o comentário ao PR
Route::post('/pr/{id}/comment', [WebhookController::class, 'addComment'])->name('pr.addComment');




require __DIR__.'/auth.php';
