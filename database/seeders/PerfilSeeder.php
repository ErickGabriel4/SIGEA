<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Perfis (roles) e permissões do RBAC (Seção 3 / 5.1).
 */
class PerfilSeeder extends Seeder
{
    /** Permissões do sistema: slug => descrição. */
    public const PERMISSOES = [
        'evento.criar' => 'Criar notificações de evento adverso',
        'evento.ver_proprios' => 'Ver as próprias notificações',
        'evento.ver_todos' => 'Ver todos os eventos de todos os setores',
        'evento.editar' => 'Editar eventos',
        'evento.excluir' => 'Excluir eventos',
        'evento.triar' => 'Triar eventos notificados',
        'evento.classificar' => 'Classificar eventos (dano, risco, evitabilidade)',
        'evento.concluir' => 'Concluir eventos',
        'evento.exportar' => 'Exportar dados de eventos',
        'investigacao.gerenciar' => 'Gerenciar investigações de causa raiz',
        'acao.gerenciar' => 'Gerenciar ações corretivas',
        'acao.executar' => 'Executar as próprias ações corretivas',
        'dashboard.ver' => 'Visualizar o painel de indicadores',
        'relatorio.ver' => 'Visualizar e emitir relatórios',
        'usuario.gerenciar' => 'Gerenciar usuários e perfis',
        'setor.gerenciar' => 'Gerenciar setores',
        'categoria.gerenciar' => 'Gerenciar categorias de evento',
        'configuracao.gerenciar' => 'Gerenciar parâmetros do sistema',
        'auditoria.ver' => 'Consultar a trilha de auditoria',
        'notivisa.gerenciar' => 'Gerenciar notificações à ANVISA/NOTIVISA',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSOES as $slug => $descricao) {
            Permission::findOrCreate($slug, 'web');
        }

        // Perfil: notificador — registra e acompanha as próprias notificações
        $notificador = Role::findOrCreate('notificador', 'web');
        $notificador->syncPermissions([
            'evento.criar',
            'evento.ver_proprios',
            'acao.executar',
        ]);

        // Perfil: gestor de segurança — NSP, tratativa completa dos eventos
        $gestor = Role::findOrCreate('gestor_seguranca', 'web');
        $gestor->syncPermissions([
            'evento.criar',
            'evento.ver_proprios',
            'evento.ver_todos',
            'evento.editar',
            'evento.triar',
            'evento.classificar',
            'evento.concluir',
            'evento.exportar',
            'investigacao.gerenciar',
            'acao.gerenciar',
            'acao.executar',
            'dashboard.ver',
            'relatorio.ver',
            'notivisa.gerenciar',
        ]);

        // Perfil: administrador — tudo
        $administrador = Role::findOrCreate('administrador', 'web');
        $administrador->syncPermissions(array_keys(self::PERMISSOES));
    }
}
