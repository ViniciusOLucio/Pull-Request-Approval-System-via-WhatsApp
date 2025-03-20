@foreach($pullRequests as $pr)
    <div>
        <h3>{{ $pr->title }}</h3>
        <p>Por: {{ $pr->user }}</p>
        <a href="{{ $pr->url }}" target="_blank">Ver no GitHub</a>

        <!-- Botões para merge ou fechar -->
        <form action="{{ route('pr.action', $pr->id) }}" method="POST">
            @csrf
            <button type="submit" name="action" value="merge">Aceitar e Fazer Merge</button>
            <button type="submit" name="action" value="close">Recusar e Fechar</button>
        </form>
    </div>
@endforeach
