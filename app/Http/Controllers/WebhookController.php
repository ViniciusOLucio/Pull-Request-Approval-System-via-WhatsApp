<?php

namespace App\Http\Controllers;

use App\Models\PullRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // IMPORTANTE!
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
            $prNumber = $data['pull_request']['number'] ?? null;
            // Log para depuração
            Log::info("Novo PR recebido: $prTitle por $prUser. Link: $prUrl");

            // Armazenar no banco de dados (se aplicável)
            PullRequest::create([
                'title' => $prTitle,
                'user' => $prUser,
                'url' => $prUrl,
                'state' => $prState,
                'pr_number' => $prNumber,
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

    public function handlePRAction($id, Request $request)
    {
        $pr = PullRequest::findOrFail($id);
        $action = $request->input('action');

        $repoOwner = 'ViniciusOLucio';
        $repoName = 'Pull-Request-Approval-System-via-WhatsApp';
        $pullNumber = $pr->pr_number;

        $token = env('GITHUB_TOKEN');
        if ($action === 'merge') {
            // Chamada para fazer merge do PR
            $response = Http::withToken($token)
                ->post("https://api.github.com/repos/{$repoOwner}/{$repoName}/pulls/{$pullNumber}/merge", [
                    'commit_title' => 'Merge via sistema',
                    'commit_message' => 'Merge automático realizado via sistema',
                    'merge_method' => 'merge' // ou 'squash' / 'rebase'
                ]);
        } elseif ($action === 'close') {
            // Chamada para fechar o PR sem merge
            $response = Http::withToken($token)
                ->patch("https://api.github.com/repos/{$repoOwner}/{$repoName}/pulls/{$pullNumber}", [
                    'state' => 'closed'
                ]);
        }

        if ($response->successful()) {
            $pr->state = ($action === 'merge') ? 'merged' : 'closed';
            $pr->save();

            return redirect()->back()->with('success', 'Ação realizada com sucesso!');
        } else {
            return redirect()->back()->with('error', 'Erro ao realizar a ação.');
        }
    }


}
