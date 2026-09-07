{{-- Botões de alternar causa raiz / remover causa --}}
<span class="text-nowrap">
    <form method="POST" action="{{ route('causas.update', [$causa->investigacao_id, $causa]) }}" class="d-inline">
        @csrf
        @method('PUT')
        <input type="hidden" name="categoria" value="{{ $causa->categoria->value }}">
        <input type="hidden" name="descricao" value="{{ $causa->descricao }}">
        <input type="hidden" name="ordem" value="{{ $causa->ordem }}">
        <input type="hidden" name="eh_causa_raiz" value="{{ $causa->eh_causa_raiz ? 0 : 1 }}">
        <button class="btn btn-link btn-sm p-0 ml-1" title="{{ $causa->eh_causa_raiz ? 'Desmarcar causa raiz' : 'Marcar como causa raiz' }}">
            <i class="{{ $causa->eh_causa_raiz ? 'fas' : 'far' }} fa-star"></i>
        </button>
    </form>
    <form method="POST" action="{{ route('causas.destroy', [$causa->investigacao_id, $causa]) }}" class="d-inline"
          onsubmit="return confirm('Remover esta causa?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-link btn-sm text-danger p-0 ml-1" title="Remover causa">
            <i class="fas fa-times"></i>
        </button>
    </form>
</span>
