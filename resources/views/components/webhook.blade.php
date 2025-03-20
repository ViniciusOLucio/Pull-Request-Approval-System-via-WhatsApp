<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dados do Webhook</title>
</head>
<body>
<h1>Dados dos Pull Requests</h1>

@if($pullRequests->isEmpty())
    <p>Nenhum Pull Request encontrado.</p>
@else
    <ul>
        @foreach($pullRequests as $pr)
            <li>
                <strong>{{ $pr->title }}</strong> - Por {{ $pr->user }}<br>
                <a href="{{ $pr->url }}" target="_blank">Ver no GitHub</a><br>
                <strong>Status:</strong> {{ $pr->state }}<br>
            </li>
        @endforeach
    </ul>
@endif
</body>
</html>
