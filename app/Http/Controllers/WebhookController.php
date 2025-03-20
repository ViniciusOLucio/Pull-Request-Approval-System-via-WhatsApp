<?php

namespace App\Http\Controllers;

use App\Models\PullRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    // Método para adicionar um comentário ao PR
    public function addComment($id, Request $request)
    {
        // Recuperar o PR com base no ID
        $pr = PullRequest::findOrFail($id);

        // Recuperar o comentário enviado pelo formulário
        $comment = $request->input('comment');
        if (!$comment) {
            return redirect()->back()->with('error', 'Comentário não fornecido.');
        }

        $repoOwner = 'ViniciusOLucio'; // Nome do proprietário do repositório
        $repoName = 'Pull-Request-Approval-System-via-WhatsApp'; // Nome do repositório
        $prNumber = $pr->pr_number; // Número do PR que será comentado
        $token = env('GITHUB_TOKEN'); // Token da API do GitHub

        // URL da API para adicionar um comentário a um PR
        $url = "https://api.github.com/repos/{$repoOwner}/{$repoName}/issues/{$prNumber}/comments";

        // Enviar o comentário para a API do GitHub
        $response = Http::withToken($token)->post($url, [
            'body' => $comment
        ]);

        // Log para verificar detalhes da resposta
        Log::info("Resposta da API GitHub: " . $response->body());
        Log::info("Status da resposta: " . $response->status());

        // Verificar o status da resposta
        if ($response->successful()) {
            Log::info("Comentário adicionado ao PR #{$prNumber}: {$comment}");
            return redirect()->back()->with('success', 'Comentário adicionado com sucesso!');
        } else {
            // Mostrar o erro se não for bem-sucedido
            Log::error("Erro ao adicionar comentário no PR #{$prNumber}: " . $response->body());
            Log::error("Erro HTTP: " . $response->status());
            return redirect()->back()->with('error', 'Erro ao adicionar o comentário.');
        }
    }

    // Método para processar o webhook (evento do GitHub)
    public function handleWebhook(Request $request)
    {
        // Log para verificar o evento
        Log::info("Evento GitHub: " . $request->header('X-GitHub-Event'));

        $event = $request->header('X-GitHub-Event');
        $payload = json_decode($request->getContent(), true);

        // Tratar PR aberto e fechado
        if ($event == 'pull_request') {
            if ($payload['action'] == 'opened') {
                Log::info('PR aberto: ' . $payload['pull_request']['title']);
            } elseif ($payload['action'] == 'closed') {
                Log::info('PR fechado: ' . $payload['pull_request']['title']);
            }
        }

        // Tratar comentário de issue ou PR
        elseif ($event == 'issue_comment') {
            Log::info('Comentário no PR #'.$payload['issue']['number'].': ' . $payload['comment']['body']);
        }

        // Para outros eventos, pode registrar ou tratar como necessário
        else {
            Log::info('Evento ignorado: ' . $event);
        }

        return response()->json(['message' => 'Evento processado']);
    }


    // Método para exibir os dados dos PRs (para visualização)
    public function showWebhookData()
    {
        // Recupera todos os Pull Requests armazenados
        $pullRequests = PullRequest::all();

        // Passa os dados para a view
        return view('components.webhook', compact('pullRequests'));
    }
}
