# Pull Request Approval System via WhatsApp

Este sistema integra webhooks do GitHub com um aplicativo Laravel para gerenciar Pull Requests. Ele permite visualizar os PRs, realizar ações (como merge e fechamento) e adicionar comentários aos PRs diretamente do seu site. O sistema usa o Ultrahook para expor o ambiente local à internet, possibilitando a recepção de eventos do GitHub.

## Tabela de Conteúdos

- [Visão Geral](#visão-geral)
- [Funcionalidades](#funcionalidades)
- [Instalação](#instalação)
- [Configuração](#configuração)
    - [Variáveis de Ambiente](#variáveis-de-ambiente)
    - [CSRF Middleware](#csrf-middleware)
- [Rotas e Endpoints](#rotas-e-endpoints)
- [Funcionamento do Webhook](#funcionamento-do-webhook)
- [Integração com a API do GitHub](#integração-com-a-api-do-github)
- [Uso e Testes](#uso-e-testes)
- [Logs e Depuração](#logs-e-depuração)
- [Contribuição](#contribuição)
- [Licença](#licença)

## Visão Geral

Este sistema foi desenvolvido em Laravel para gerenciar Pull Requests (PRs) de um repositório GitHub. Através de webhooks, ele capta eventos do GitHub (como PR aberto, fechado e comentários) e os processa, salvando informações no banco de dados. Além disso, o sistema permite que o usuário realize ações, como merge, fechar um PR e adicionar comentários via API do GitHub.

## Funcionalidades

- **Recepção de Webhooks**:  
  O sistema recebe eventos do GitHub (ex.: `pull_request`, `issue_comment`) através de um endpoint exposto com Ultrahook.

- **Persistência de Dados**:  
  Os dados dos PRs são salvos ou atualizados no banco de dados.

- **Ações nos Pull Requests**:  
  Possibilidade de realizar merge ou fechar um PR através de chamadas à API do GitHub.

- **Adição de Comentários**:  
  Permite que comentários sejam adicionados aos PRs via API do GitHub.

- **Interface de Visualização**:  
  Exibe os PRs registrados e oferece botões para executar as ações mencionadas.

## Instalação

1. Clone o repositório:
   ```bash
   git clone https://github.com/SeuUsuario/Pull-Request-Approval-System-via-WhatsApp.git
   cd Pull-Request-Approval-System-via-WhatsApp
   ```

2. Instale as dependências via Composer:
   ```bash
   composer install
   ```

3. Configure o arquivo `.env` (veja a seção [Configuração](#configuração)).

4. Execute as migrations para criar as tabelas necessárias:
   ```bash
   php artisan migrate
   ```

5. Inicie o servidor local:
   ```bash
   php artisan serve
   ```

## Configuração

### Variáveis de Ambiente

No arquivo `.env`, adicione ou ajuste as seguintes variáveis:

```env
# Token de acesso pessoal do GitHub com escopo "repo"
GITHUB_TOKEN=seu_token_do_github_aqui

# Informações do repositório
GITHUB_REPO_OWNER=SeuUsuario
GITHUB_REPO_NAME=Pull-Request-Approval-System-via-WhatsApp
```

Esses valores são usados para autenticar e direcionar as chamadas à API do GitHub.

### CSRF Middleware

Para que o GitHub possa enviar requisições para o seu webhook sem ser bloqueado pela proteção CSRF do Laravel, adicione as seguintes rotas ao array `$except` no arquivo `\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken.php`.

## **IMPORTANTE**
O **Ultrahook** foi utilizado como exemplo neste projeto para expor o ambiente local, mas isso não é definitivo para o funcionamento do webhook. Caso esteja utilizando outro sistema para expor seu ambiente ou configurar hooks, é necessário adicionar a URL gerada, especificamente o caminho do webhook (ex.: `127.0.0.1:8000/webhook`), no arquivo `VerifyCsrfToken.php`.

```php
protected $except = [
    'webhook',
    'webhook-data',
    'webhook-data-show'
];
```

## Rotas e Endpoints

No arquivo `web.php` você encontrará as seguintes rotas:

- **Webhook**: Recebe os eventos do GitHub.
  ```php
  Route::match(['get', 'post'], '/webhook', [WebhookController::class, 'handleWebhook']);
  ```

- **Exibição de Dados**: Exibe os PRs registrados no banco de dados.
  ```php
  Route::get('/webhook-data-show', [WebhookController::class, 'showWebhookData']);
  ```

- **Ação no PR (Merge/Close)**: Processa ações de merge ou fechamento.
  ```php
  Route::post('/pr/action/{id}', [WebhookController::class, 'handlePRAction'])->name('pr.action');
  ```

- **Adicionar Comentário**: Permite adicionar um comentário ao PR.
  ```php
  Route::post('/pr/{id}/comment', [WebhookController::class, 'addComment'])->name('pr.addComment');
  ```

## Funcionamento do Webhook

O endpoint `/webhook` é configurado para receber requisições do GitHub. Para expor seu servidor local, você utiliza Ultrahook. Um exemplo de comando Ultrahook:

```bash
ultrahook github http://127.0.0.1:8000/webhook -k sua_chave_ultrahook
```

Este comando gera uma URL pública (ex.: `https://seu-nome-github.ultrahook.com`) que redireciona para seu endpoint local. Quando um evento (como a abertura ou fechamento de um PR) ocorre, o GitHub envia o payload para esse endpoint e o sistema processa o evento.

## Integração com a API do GitHub

O sistema utiliza a API do GitHub para:

- **Salvar e atualizar dados dos PRs**:
  ```php
  PullRequest::updateOrCreate(
      ['pr_number' => $prData['number']],
      [
          'title' => $prData['title'],
          'user'  => $prData['user']['login'],
          'url'   => $prData['html_url'],
          'state' => 'open'
      ]
  );
  ```

- **Adicionar comentários**:
  ```php
  $url = "https://api.github.com/repos/{$repoOwner}/{$repoName}/issues/{$prNumber}/comments";
  $response = Http::withToken($token)->post($url, ['body' => $comment]);
  ```

- **Realizar ações (merge/close)**:
  ```php
  // Merge
  $response = Http::withToken($token)
      ->post("https://api.github.com/repos/{$repoOwner}/{$repoName}/pulls/{$pullNumber}/merge", [...]);

  // Fechar
  $response = Http::withToken($token)
      ->patch("https://api.github.com/repos/{$repoOwner}/{$repoName}/pulls/{$pullNumber}", ['state' => 'closed']);
  ```

## Uso e Testes

1. **Configuração do Ultrahook**:  
   Execute o comando Ultrahook para expor seu servidor local:
   ```bash
   ultrahook github http://127.0.0.1:8000/webhook -k sua_chave_ultrahook
   ```

2. **Criação de um Pull Request**:  
   Ao criar um PR no repositório configurado, o GitHub enviará o payload para o endpoint `/webhook` e os dados serão salvos no banco.

3. **Visualização dos PRs**:  
   Acesse a rota `/webhook-data-show` para visualizar os PRs registrados.

4. **Realizar Ações e Adicionar Comentários**:  
   Na interface, use os botões para fazer merge, fechar ou adicionar comentários aos PRs. Essas ações são encaminhadas para os endpoints configurados, que fazem chamadas à API do GitHub.

## Logs e Depuração

- **Logs do Laravel**:  
  Verifique os arquivos em `storage/logs/laravel.log` para acompanhar o fluxo dos eventos, respostas da API do GitHub e identificar possíveis erros.

- **Mensagens de Erro**:  
  Se ocorrerem erros (como "Bad credentials" ou problemas de inserção no banco), os logs ajudarão a identificar e corrigir a causa.

## Contribuição

Se você deseja contribuir para este projeto, sinta-se à vontade para abrir issues ou enviar pull requests.


