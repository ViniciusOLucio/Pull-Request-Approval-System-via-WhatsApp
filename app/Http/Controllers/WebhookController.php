<?php

namespace App\Http\Controllers;

use App\Models\PullRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Verifica se é uma requisição GET para validar o webhook
        if ($request->isMethod('get')) {
            return response()->json(['message' => 'Webhook ativo'], 200);
        }

        // Caso contrário, processa o evento POST
        if ($request->header('X-GitHub-Event') === 'pull_request') {
            $data = $request->all();

            // Extrai as informações do PR
            $prTitle = $data['pull_request']['title'] ?? 'Título não disponível';
            $prUser = $data['pull_request']['user']['login'] ?? 'Usuário desconhecido';
            $prUrl = $data['pull_request']['html_url'] ?? '';
            $prState = $data['pull_request']['state'] ?? 'open';

            // Log para depuração
            Log::info("Novo PR recebido: $prTitle por $prUser. Link: $prUrl");

            // Armazenar no banco de dados (se aplicável)
            PullRequest::create([
                'title' => $prTitle,
                'user' => $prUser,
                'url' => $prUrl,
                'state' => $prState
            ]);

            return response()->json(['message' => 'Pull Request processado']);
        }

        return response()->json(['message' => 'Evento ignorado'], 200);
    }


    public function showWebhookData()
    {
        // Recupera todos os Pull Requests armazenados
        $pullRequests = PullRequest::all();

        // Passa os dados para a view
        return view('components.webhook', compact('pullRequests'));
    }

}
