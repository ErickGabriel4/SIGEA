# SIGEA — Sistema Integrado de Gestão de Eventos Adversos

Plataforma web para registro, classificação, investigação e análise de eventos adversos
hospitalares — ferramenta de apoio à segurança do paciente.

> Artefato tecnológico do TCC **"Sistema Web para Gestão de Eventos Adversos: Ferramenta de
> Apoio à Segurança do Paciente"** — Tecnologia em Análise e Desenvolvimento de Sistemas,
> IFSP Câmpus Campinas. Autor: Érick Gabriel Pereira Batista · Orientador: Prof. Ms. Emilio
> Carlos Rodrigues.
>
> ⚠️ **O sistema opera exclusivamente com dados simulados.** Nenhum dado real de paciente é
> armazenado; identificadores são pseudonimizados (ver `docs/adr/0001-pseudonimizacao.md`).

---

## 🔑 Usuários de acesso (demonstração)

| Perfil | E-mail | Senha | O que enxerga |
|---|---|---|---|
| **Administrador** | `admin@sigea.local` | `senha123` | Tudo + Administração (usuários, setores, categorias, configurações, auditoria) |
| **Gestor de Segurança (NSP)** | `gestor@sigea.local` | `senha123` | Painel, eventos, fila de triagem, investigações, ações corretivas, relatórios |
| **Notificador** | `notificador@sigea.local` | `senha123` | Notificar evento, minhas notificações, minhas ações |

Além dos três usuários fixos, o seeder cria mais 27 usuários simulados (23 notificadores,
3 gestores e 1 administrador), todos com a senha `senha123`.

---

## Requisitos de ambiente

| Ferramenta | Versão mínima | Observação |
|---|---|---|
| PHP | 8.2 | com extensões `pdo_mysql`, `mbstring`, `gd`, `zip`, `xml` |
| Composer | 2.x | |
| Node.js / npm | 18+ | apenas para compilar os assets (Vite) |
| MySQL | 8.x | engine InnoDB (já forçada pelo projeto) |

> **WAMP:** o projeto funciona com o MySQL do WAMP (`wampmysqld64`). Garanta que o serviço
> esteja iniciado antes dos passos abaixo.

## 🚀 Execução passo a passo

### 1. Instalar as dependências

```bash
composer install
npm install && npm run build
```

### 2. Configurar o ambiente

```bash
# Windows (PowerShell/CMD):
copy .env.example .env
# Linux/macOS:
# cp .env.example .env

php artisan key:generate
```

Confira no `.env` a conexão com o banco (padrão do projeto):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigea
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Criar o banco de dados

No MySQL (ex.: console do WAMP ou linha de comando):

```sql
CREATE DATABASE sigea CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Exemplo com o cliente do WAMP:

```powershell
& "C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS sigea CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Migrar e popular com a massa simulada

```bash
php artisan migrate --seed
```

Isso cria as 20 tabelas e semeia: 3 perfis RBAC com 20 permissões, 12 setores,
57 categorias da taxonomia ICPS, 7 parâmetros de configuração, 30 usuários e
**600 eventos adversos simulados** nos últimos 24 meses (com investigações, ações
corretivas e histórico completos).

### 5. Subir a aplicação

```bash
php artisan serve
```

Acesse **http://localhost:8000** e entre com um dos usuários da tabela acima.

### 6. (Opcional) Agendador de rotinas automáticas

Para ativar as rotinas de prazo (ações atrasadas às 06h, alertas às 07h/07h30 e resumo
semanal às segundas 08h), mantenha o agendador rodando em outro terminal:

```bash
php artisan schedule:work
```

## 🧪 Testes automatizados

A suíte roda contra um banco MySQL dedicado chamado **`sigea_teste`** (mesmo dialeto da
produção). Crie-o uma única vez:

```sql
CREATE DATABASE sigea_teste CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Depois execute:

```bash
php artisan test        # 110 testes / 384 asserções
```

## Comandos úteis

```bash
php artisan migrate:fresh --seed           # recria o banco do zero com a massa simulada
php artisan sigea:marcar-acoes-atrasadas   # marca ações vencidas como atrasadas
php artisan sigea:alertar-prazos           # alertas de prazo (ações e eventos)
php artisan sigea:resumo-semanal           # resumo de indicadores aos gestores
npm run dev                                # Vite em modo de desenvolvimento (hot reload)
```

## Solução de problemas

| Sintoma | Causa / solução |
|---|---|
| `Specified key was too long (1000 bytes)` ao migrar | MySQL com engine padrão MyISAM (comum no WAMP). O projeto já força InnoDB em `config/database.php`; atualize o código ou confira essa configuração. |
| Gráficos não aparecem no painel | Assets não compilados — rode `npm install && npm run build`. |
| E-mails não chegam | Em desenvolvimento `MAIL_MAILER=log` (mensagens vão para `storage/logs/laravel.log`). Em produção configure SMTP no `.env`. |
| Testes falham com "Unknown database" | Crie o banco `sigea_teste` (passo da seção de testes). |

## Stack

| Camada | Tecnologia |
|---|---|
| Linguagem / Framework | PHP 8.2+ · Laravel 12 (MVC) |
| Banco de dados | MySQL 8 (InnoDB, utf8mb4) · Eloquent + Migrations |
| Interface | Blade · AdminLTE 3 · Chart.js 4 · Vite |
| Autenticação / RBAC | Laravel Breeze (Blade) · spatie/laravel-permission |
| Auditoria | spatie/laravel-activitylog |
| Exportação | barryvdh/laravel-dompdf (PDF) · maatwebsite/excel (CSV/XLSX) |
| Testes | PHPUnit (110 testes / 384 asserções) |

## Documentação

Toda a documentação técnica está em [`docs/`](docs/):

- [`MANUAL_USUARIO.md`](docs/MANUAL_USUARIO.md) — guia de uso por perfil
- [`MODELO_DADOS.md`](docs/MODELO_DADOS.md) — dicionário de dados + DER (Mermaid)
- [`CASOS_DE_USO.md`](docs/CASOS_DE_USO.md) — casos de uso e user stories
- [`REQUISITOS.md`](docs/REQUISITOS.md) — RF/RNF rastreados aos objetivos do TCC
- [`ARQUITETURA.md`](docs/ARQUITETURA.md) — camadas MVC, classes e fluxos
- [`SEGURANCA.md`](docs/SEGURANCA.md) — checklist de segurança com evidências
- [`AVALIACAO_HEURISTICA.md`](docs/AVALIACAO_HEURISTICA.md) — usabilidade (Nielsen)
- [`adr/`](docs/adr/) — decisões arquiteturais registradas

## Licença

Projeto acadêmico de código aberto (MIT), desenvolvido para fins de pesquisa e ensino.
