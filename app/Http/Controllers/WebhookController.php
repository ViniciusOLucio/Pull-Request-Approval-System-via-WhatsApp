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
        // Verifique se a requisição é válida e se contém o tipo de evento esperado
        $event = $request->header('X-GitHub-Event');

        // Aqui você pode adicionar verificações para garantir que o evento seja válido
        // Exemplo: verificar se o evento é de 'pull_request'
        if ($event == 'pull_request') {
            $payload = $request->getContent(); // Conteúdo do webhook
            $data = json_decode($payload, true); // Decodificando o JSON

            // Lógica para processar os dados do webhook, por exemplo, armazenar ou verificar
            // Exemplo: verificar se o PR foi aberto ou fechado
            if ($data['action'] == 'opened') {
                // Ação para quando o PR for aberto
                Log::info('PR aberto: ' . $data['pull_request']['title']);

                // Criar ou atualizar o PR no banco de dados
                PullRequest::updateOrCreate(
                    ['pr_number' => $data['pull_request']['number']],  // Usar o número do PR como chave única
                    [
                        'title' => $data['pull_request']['title'],
                        'user' => $data['pull_request']['user']['login'],
                        'state' => 'opened',
                        'url' => $data['pull_request']['html_url'],
                    ]
                );

                // Exemplo: adicionar um comentário ao PR automaticamente
                $comment = "Obrigado por abrir o PR! Vamos revisar em breve.";
                $this->addComment($data['pull_request']['id'], new Request(['comment' => $comment]));
            } elseif ($data['action'] == 'closed') {
                // Ação para quando o PR for fechado
                Log::info('PR fechado: ' . $data['pull_request']['title']);

                // Atualizar o estado do PR para fechado no banco de dados
                $pr = PullRequest::where('pr_number', $data['pull_request']['number'])->first();
                if ($pr) {
                    $pr->state = 'closed';
                    $pr->save();
                }
            }

            // Retorne uma resposta indicando que o evento foi processado
            return response()->json(['message' => 'Evento processado']);
        }

        // Caso o evento não seja um pull request ou outro evento relevante
        return response()->json(['message' => 'Evento ignorado'], 400);
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
