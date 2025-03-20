@foreach($pullRequests as $pr)
    <div>
        <h3>{{ $pr->title }}</h3>
        <p>Por: {{ $pr->user }}</p>
        <a href="{{ $pr->url }}" target="_blank">Ver no GitHub</a>

        <!-- Formulário para adicionar comentário -->
        <form action="{{ route('pr.addComment', $pr->id) }}" method="POST">
            @csrf
            <textarea name="comment" placeholder="Adicione um comentário..." required></textarea>
            <button type="submit">Adicionar Comentário</button>
        </form>
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
@endforeach
