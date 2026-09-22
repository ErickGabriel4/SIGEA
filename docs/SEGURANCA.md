# SIGEA — Checklist de Segurança (Seção 5.5 do TCC)

Evidências da implementação dos 12 controles de segurança exigidos.

| # | Controle | Implementação | Evidência (arquivo) |
|---|---|---|---|
| 1 | **SQL Injection** | Exclusivamente Eloquent/Query Builder com bindings; os únicos `selectRaw`/`orderByRaw` usam literais fixos, nunca input do usuário. | `app/Services/IndicadorService.php`, controllers |
| 2 | **XSS** | Todas as views usam `{{ }}` (escape automático); nenhuma ocorrência de `{!! !!}` no código da aplicação. | `resources/views/**` (verificável com `grep -r "{!!" resources/views`) |
| 3 | **CSRF** | `@csrf` em todos os formulários; middleware `ValidateCsrfToken` ativo no grupo web. | todas as views com `<form>` |
| 4 | **Mass assignment** | `$fillable` explícito em todos os 12 models; nenhum `$guarded = []`. | `app/Models/*.php` |
| 5 | **Autorização** | Negação por padrão: middleware `permission:`/`role:` em toda rota + Policies (`EventoAdversoPolicy`, `InvestigacaoPolicy`, `AcaoCorretivaPolicy`); acesso negado testado (403). | `routes/web.php`, `app/Policies/`, `tests/Feature/AutorizacaoTest.php` |
| 6 | **Senhas** | Hash bcrypt (cast `hashed`); política mínima de 8 caracteres com letra e número (`Password::min(8)->letters()->numbers()`); `throttle:5,1` no login + RateLimiter do Breeze. | `app/Models/User.php`, `routes/auth.php`, `app/Http/Requests/Auth/LoginRequest.php` |
| 7 | **Sessão** | Driver database; expiração 120 min (`SESSION_LIFETIME`); regeneração do ID no login; em produção `SESSION_SECURE_COOKIE=true`. | `.env`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php` |
| 8 | **Upload** | Validação de MIME real (`mimes:pdf,jpg,jpeg,png,docx,xlsx`), máx. 10 MB, nome randomizado pelo `store()`, gravado em `storage/app/privado/eventos/{id}` (fora do `public/`), download somente por rota autenticada com Policy. | `app/Http/Controllers/EventoAnexoController.php`, `tests/Feature/AnexoTest.php` |
| 9 | **Headers** | Middleware global adiciona `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin` e CSP básica. | `app/Http/Middleware/SecurityHeaders.php`, `bootstrap/app.php` |
| 10 | **Rate limiting** | `throttle:60,1` nas rotas JSON do dashboard; `throttle:5,1` no login e na recuperação de senha. | `routes/web.php`, `routes/auth.php` |
| 11 | **Auditoria** | `spatie/laravel-activitylog` em EventoAdverso, Investigacao, AcaoCorretiva, User, Setor, CategoriaEvento e Configuracao, com diff de valores; consulta em `/admin/auditoria`. | models (`getActivitylogOptions`), `app/Http/Controllers/Admin/AuditoriaController.php` |
| 12 | **Dados sensíveis** | Pseudonimização SHA-256 do paciente (ADR 0001); notificação anônima sem `causer` nem vínculo; `.env` fora do Git; `APP_DEBUG=false` em produção. | `app/Services/PseudonimizadorService.php`, `.gitignore` |

## Verificações automatizadas

- `tests/Feature/AutorizacaoTest.php` — 403 para perfis sem permissão em painel, triagem e admin.
- `tests/Feature/AnexoTest.php` — MIME inválido rejeitado; download exige autenticação e autorização.
- `tests/Feature/AutenticacaoTest.php` — bloqueio após 5 tentativas; usuário inativo não autentica; ausência de registro público.
- `tests/Feature/NotificacaoEventoTest.php` — anonimato sem vínculo no banco; pseudonimização do paciente.

## Recomendações para produção

1. `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true` e HTTPS obrigatório
   (o `AppServiceProvider` já força `https` em produção).
2. Usuário MySQL dedicado com privilégios mínimos (sem `DROP`/`GRANT`).
3. Backup diário do banco e do diretório `storage/app/privado`.
4. `MAIL_MAILER=smtp` com credenciais institucionais.
