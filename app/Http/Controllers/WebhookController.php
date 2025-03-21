<?php

namespace App\Http\Controllers;

use App\Models\PullRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Processa o webhook enviado pelo GitHub e salva/atualiza o PR no banco de dados.
     */
    public function handleWebhook(Request $request)
    {
        // Log do evento recebido
        $event = $request->header('X-GitHub-Event');
        Log::info("Evento GitHub: " . $event);

        $payload = json_decode($request->getContent(), true);

        if ($event == 'pull_request') {
            $action = $payload['action'];
            $prData = $payload['pull_request'];

            if (in_array($action, ['opened', 'reopened', 'synchronize'])) {
                Log::info('PR aberto: ' . $prData['title']);

                // Salva ou atualiza o PR usando "pr_number" como chave única.
                PullRequest::updateOrCreate(
                    ['pr_number' => $prData['number']],
                    [
                        'title' => $prData['title'],
                        'user'  => $prData['user']['login'],
                        'url'   => $prData['html_url'], // Usando o campo "url"
                        'state' => 'open'
                    ]
                );
            } elseif ($action == 'closed') {
                Log::info('PR fechado: ' . $prData['title']);

                // Atualiza o status do PR para "closed"
                PullRequest::where('pr_number', $prData['number'])
                    ->update(['state' => 'closed']);
            }
            return response()->json(['message' => 'Evento processado']);
        } elseif ($event == 'issue_comment') {
            Log::info('Comentário no PR #' . $payload['issue']['number'] . ': ' . $payload['comment']['body']);
            return response()->json(['message' => 'Evento processado']);
        } else {
            Log::info('Evento ignorado: ' . $event);
            return response()->json(['message' => 'Evento ignorado'], 400);
        }
    }

    /**
     * Adiciona um comentário a um PR via API do GitHub.
     */
    public function addComment($id, Request $request)
    {
        $pr = PullRequest::findOrFail($id);
        $comment = $request->input('comment');
        if (!$comment) {
            return redirect()->back()->with('error', 'Comentário não fornecido.');
        }

        $repoOwner = 'ViniciusOLucio';
        $repoName = 'Pull-Request-Approval-System-via-WhatsApp';
        $prNumber = $pr->pr_number;
        $token = env('GITHUB_TOKEN');

        // Endpoint para adicionar comentário (usando o endpoint de issues)
        $url = "https://api.github.com/repos/{$repoOwner}/{$repoName}/issues/{$prNumber}/comments";

        $response = Http::withToken($token)->post($url, [
            'body' => $comment
        ]);

        Log::info("Resposta da API GitHub (comentário): " . $response->body());
        Log::info("Status da resposta (comentário): " . $response->status());

        if ($response->successful()) {
            Log::info("Comentário adicionado ao PR #{$prNumber}: {$comment}");
            return redirect()->back()->with('success', 'Comentário adicionado com sucesso!');
        } else {
            Log::error("Erro ao adicionar comentário no PR #{$prNumber}: " . $response->body());
            Log::error("Erro HTTP (comentário): " . $response->status());
            return redirect()->back()->with('error', 'Erro ao adicionar o comentário.');
        }
    }

    /**
     * Realiza ações (merge ou fechar) no PR via API do GitHub.
     */
    public function handlePRAction($id, Request $request)
    {
        $pr = PullRequest::findOrFail($id);
        $action = $request->input('action');

        Log::info("PR encontrado: " . $pr->id . " - Número do PR: " . $pr->pr_number . " - Ação: " . $action);

        $repoOwner = env('GITHUB_REPO_OWNER');
        $repoName = env('GITHUB_REPO_NAME');
        $pullNumber = $pr->pr_number;
        $token = env('GITHUB_TOKEN');

        if ($action === 'merge') {
            Log::info("Iniciando o merge do PR #{$pullNumber}");
            $response = Http::withToken($token)
                ->post("https://api.github.com/repos/{$repoOwner}/{$repoName}/pulls/{$pullNumber}/merge", [
                    'commit_title' => 'Merge via sistema',
                    'commit_message' => 'Merge automático realizado via sistema',
                    'merge_method' => 'merge'
                ]);
        } elseif ($action === 'close') {
            Log::info("Fechando o PR #{$pullNumber}");
            $response = Http::withToken($token)
                ->patch("https://api.github.com/repos/{$repoOwner}/{$repoName}/pulls/{$pullNumber}", [
                    'state' => 'closed'
                ]);
        } else {
            return redirect()->back()->with('error', 'Ação inválida.');
        }

        Log::info("Resposta da API GitHub (ação): " . $response->body());
        if ($response->successful()) {
            $pr->state = ($action === 'merge') ? 'merged' : 'closed';
            $pr->save();
            return redirect()->back()->with('success', 'Ação realizada com sucesso!');
        } else {
            Log::error("Erro ao realizar a ação no PR #{$pullNumber}: " . $response->body());
            return redirect()->back()->with('error', 'Erro ao realizar a ação.');
        }
    }

    /**
     * Exibe os dados dos PRs armazenados.
     */
    public function showWebhookData()
    {
        $pullRequests = PullRequest::all();
        return view('components.webhook', compact('pullRequests'));
    }
}
