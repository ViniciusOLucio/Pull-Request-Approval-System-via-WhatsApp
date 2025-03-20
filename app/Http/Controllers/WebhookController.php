<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Log dos dados recebidos
        Log::info('Webhook recebido:', $request->all());

        // Exibir na tela (pode ser salvo no banco também)
        return view('webhook', ['data' => $request->all()]);
    }
}
