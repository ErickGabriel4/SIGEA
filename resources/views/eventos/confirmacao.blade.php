{{-- Confirmação de envio com protocolo em destaque (UC02, passo 9) --}}
@extends('layouts.app')

@section('title', 'Notificação Enviada · SIGEA')
@section('titulo', 'Notificação enviada com sucesso')
@section('breadcrumb')
    <li class="breadcrumb-item active">Confirmação</li>
@endsection

@section('conteudo')
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card card-success">
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle text-success fa-4x mb-3" aria-hidden="true"></i>
                    <h2 class="h4">Obrigado por contribuir com a segurança do paciente!</h2>
                    <p class="text-muted">Sua notificação foi registrada e encaminhada ao Núcleo de Segurança do Paciente.</p>

                    <p class="mb-1">Número de protocolo:</p>
                    <p class="display-4 font-weight-bold" id="protocolo-gerado">{{ $evento->protocolo }}</p>

                    <button type="button" class="btn btn-outline-primary" id="botao-copiar">
                        <i class="far fa-copy mr-1"></i> Copiar protocolo
                    </button>

                    @if ($anonima)
                        <div class="alert alert-warning mt-4 mb-0 text-left" role="alert">
                            <i class="fas fa-user-secret mr-1"></i>
                            <strong>Notificação anônima:</strong> guarde este protocolo — ele é a única forma de
                            referenciar esta notificação, pois ela não aparecerá na sua lista e não haverá devolutiva.
                        </div>
                    @else
                        <p class="text-muted mt-4 mb-0">
                            Acompanhe o andamento e a devolutiva do NSP em
                            <a href="{{ route('eventos.meus') }}">Minhas notificações</a>.
                        </p>
                    @endif
                </div>
                <div class="card-footer text-center">
                    @unless ($anonima)
                        <a href="{{ route('eventos.show', $evento) }}" class="btn btn-primary">Ver notificação</a>
                    @endunless
                    <a href="{{ route('eventos.create') }}" class="btn btn-outline-secondary">Notificar outro evento</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('botao-copiar').addEventListener('click', function () {
        navigator.clipboard.writeText(document.getElementById('protocolo-gerado').textContent.trim()).then(() => {
            this.innerHTML = '<i class="fas fa-check mr-1"></i> Copiado!';
        });
    });
});
</script>
@endpush
