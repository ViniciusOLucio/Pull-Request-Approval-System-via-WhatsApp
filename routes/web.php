<?php
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

Route::post('/webhook', function (Request $request) {
    // Verifica se o evento GitHub foi enviado
    $githubEvent = $request->header('X-GitHub-Event');

    // Se o evento for de Pull Request
    if ($githubEvent === 'pull_request') {
        // Ação (opened, closed, etc.)
        $action = $request->input('action');
        // Título do PR
        $prTitle = $request->input('pull_request.title');
        // URL do PR
        $prUrl = $request->input('pull_request.html_url');

        // Verifica se a ação é 'opened' (PR aberto)
        if ($action === 'opened') {
            // Registra no log
            Log::info("Novo Pull Request aberto: $prTitle ($prUrl)");
        }

        // Adicionar mais condições para outros tipos de ação, caso necessário (ex: 'closed', 'reopened')
        if ($action === 'closed') {
            Log::info("Pull Request fechado: $prTitle ($prUrl)");
        }
    } else {
        // Caso o evento não seja de pull_request, pode logar como erro
        Log::warning('Evento recebido não é um Pull Request', ['event' => $githubEvent]);
    }

    // Responde com sucesso
    return response()->json(['status' => 'ok']);
});

require __DIR__.'/auth.php';
