<?php

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

Route::post('/webhook/github', function (Request $request) {
    // Log para ver os dados recebidos
    Log::info('Webhook recebido do GitHub:', $request->all());

    // Verifica se o evento é um Pull Request
    if ($request->header('X-GitHub-Event') === 'pull_request') {
        $action = $request->input('action'); // opened, closed, etc.
        $prTitle = $request->input('pull_request.title');
        $prUrl = $request->input('pull_request.html_url');

        if ($action === 'opened') {
            Log::info("Novo Pull Request: $prTitle ($prUrl)");
        }
    }

    return response()->json(['status' => 'ok']);
});

require __DIR__.'/auth.php';
