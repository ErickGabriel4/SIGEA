# SIGEA — Sistema Integrado de Gestão de Eventos Adversos

Plataforma web para registro, classificação, investigação e análise de eventos adversos
hospitalares — ferramenta de apoio à segurança do paciente.

> Artefato tecnológico do TCC **"Sistema Web para Gestão de Eventos Adversos: Ferramenta de
> Apoio à Segurança do Paciente"** — Tecnologia em Análise e Desenvolvimento de Sistemas,
> IFSP Câmpus Campinas. Autor: Érick Gabriel Pereira Batista · Orientador: Prof. Ms. Emilio
> Carlos Rodrigues.
>
> ⚠️ **O sistema opera exclusivamente com dados simulados.** Nenhum dado real de paciente é
> armazenado.

## Requisitos de ambiente

| Ferramenta | Versão mínima | Observação |
|---|---|---|
| PHP | 8.3 | com extensões `pdo_mysql`, `mbstring`, `gd`, `zip`, `xml` |
| Composer | 2.x | |
| Node.js / npm | 18+ | apenas para compilar os assets (Vite) |
| MySQL | 8.x | engine InnoDB (já forçada pelo projeto) |

> **WAMP:** o projeto funciona com o MySQL do WAMP (`wampmysqld64`). Garanta que o serviço
> esteja iniciado antes dos passos abaixo.

## Execução passo a passo

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

### 3. Criar o banco de dados

```sql
CREATE DATABASE sigea CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Migrar o banco

```bash
php artisan migrate
```

### 5. Subir a aplicação

```bash
php artisan serve
```

Acesse **http://localhost:8000**.

## Stack

| Camada | Tecnologia |
|---|---|
| Linguagem / Framework | PHP 8.3+ · Laravel 13 (MVC) |
| Banco de dados | MySQL 8 (InnoDB, utf8mb4) · Eloquent + Migrations |
| Interface | Blade · Vite |
| Testes | PHPUnit |

## Licença

Projeto acadêmico de código aberto (MIT), desenvolvido para fins de pesquisa e ensino.
