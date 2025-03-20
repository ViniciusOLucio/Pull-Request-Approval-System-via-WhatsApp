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



//rota para view do webhook
Route::get('/webhook-data-show  ', [WebhookController::class, 'showWebhookData']);


//rota para puxar os dados em webhook
Route::match(['get', 'post'], '/webhook', [WebhookController::class, 'handleWebhook']);


// Rota para marge/close ao PR
Route::post('/pr/action/{id}', [WebhookController::class, 'handlePRAction'])->name('pr.action');

// Rota para adicionar o comentário ao PR
Route::post('/pr/{id}/comment', [WebhookController::class, 'addComment'])->name('pr.addComment');



require __DIR__.'/auth.php';
