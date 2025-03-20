<?php

namespace App\Http\Controllers;

use App\Models\PullRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// IMPORTANTE!

class WebhookController extends Controller
{

    public function handlePRAction($id, Request $request)
    {
        $pr = PullRequest::findOrFail($id);
        $action = $request->input('action');

        Log::info("PR encontrado: " . $pr->id . " - Número do PR: " . $pr->pr_number . " - Ação: " . $action);

        $repoOwner = 'ViniciusOLucio';
        $repoName = 'Pull-Request-Approval-System-via-WhatsApp';
        $pullNumber = $pr->pr_number;  // Certifique-se de que isso está correto

        $token = env('GITHUB_TOKEN');

        if ($action === 'merge') {
            // Chamada para fazer merge do PR
            Log::info("Iniciando o merge do PR #{$pullNumber}");
            $response = Http::withToken($token)
                ->post("https://api.github.com/repos/{$repoOwner}/{$repoName}/pulls/{$pullNumber}/merge", [
                    'commit_title' => 'Merge via sistema',
                    'commit_message' => 'Merge automático realizado via sistema',
                    'merge_method' => 'merge' // ou 'squash' / 'rebase'
                ]);
        } elseif ($action === 'close') {
            // Chamada para fechar o PR sem merge
            Log::info("Fechando o PR #{$pullNumber}");
            $response = Http::withToken($token)
                ->patch("https://api.github.com/repos/{$repoOwner}/{$repoName}/pulls/{$pullNumber}", [
                    'state' => 'closed'
                ]);
        }

        // Adicionando um log para ver a resposta
        Log::info("Resposta da API GitHub: " . $response->body());

        if ($response->successful()) {
            // Atualiza o estado do PR no banco de dados
            $pr->state = ($action === 'merge') ? 'merged' : 'closed';
            $pr->save();

            return redirect()->back()->with('success', 'Ação realizada com sucesso!');
        } else {
            // Mostrar o erro se não for bem-sucedido
            Log::error("Erro ao tentar realizar a ação: " . $response->body());

            return redirect()->back()->with('error', 'Erro ao realizar a ação.');
        }
    }

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




    public function handleWebhook(Request $request)
    {
        if ($request->header('X-GitHub-Event') === 'pull_request') {
            $data = $request->all();

            // Pega o número do PR e o título
            $prNumber = $data['pull_request']['number'];
            $prTitle = $data['pull_request']['title'];

            // Agora, vamos adicionar um comentário dizendo algo como "PR aberto!"
            $comment = "PR aberto: {$prTitle}. Aguardando revisão.";

            // Adiciona o comentário ao PR
            $this->addCommentToPR($prNumber, $comment);

            return response()->json(['message' => 'Webhook processado']);
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
