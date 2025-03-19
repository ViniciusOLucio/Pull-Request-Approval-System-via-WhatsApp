# Pull-Request-Approval-System-via-WhatsApp

Sistema de Aprovação de Pull Requests via WhatsApp
Descrição: Quando alguém abre um Pull Request, o administrador recebe uma mensagem no WhatsApp perguntando se quer aprovar. Ele pode responder "Aprovar" ou "Rejeitar", e o sistema interage com a API do GitHub para aceitar ou fechar o PR.

Eventos do GitHub: pull_request

Tecnologias: Laravel, WhatsApp Cloud API, GitHub API

Passos Principais:

[ ] Criar um Webhook que escute eventos pull_request
[ ] Quando um PR for criado, enviar uma mensagem no WhatsApp
[ ] Se o admin responder "Aprovar", chamar a API do GitHub para fazer merge
[ ] Se responder "Rejeitar", fechar o PR automaticamente
