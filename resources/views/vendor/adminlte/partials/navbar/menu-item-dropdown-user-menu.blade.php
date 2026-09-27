{{-- Override do AdminLTE: no lugar do dropdown, exibe o nome do usuário com um ícone de acesso ao perfil. O "Sair" fica no fim da sidebar (config/adminlte.php). --}}
<li class="nav-item">
    <a href="{{ route('perfil.edit') }}" class="nav-link" title="Meu perfil">
        <i class="fas fa-fw fa-user-circle"></i>
        <span class="d-none d-md-inline ml-1">{{ Auth::user()->name }}</span>
    </a>
</li>
