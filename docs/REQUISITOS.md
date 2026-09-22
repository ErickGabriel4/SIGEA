# SIGEA — Requisitos e Rastreabilidade

Requisitos funcionais (RF) e não funcionais (RNF) rastreados aos **objetivos específicos do
TCC** (letras c–h) e à implementação. Detalhamento completo (user stories, casos de uso,
critérios de aceite) em [`CASOS_DE_USO.md`](CASOS_DE_USO.md).

## Requisitos funcionais

| ID | Requisito | Obj. | Implementação (evidência) | Status |
|---|---|---|---|---|
| RF01 | Autenticação por e-mail/senha com 3 perfis de acesso (RBAC) | e | Breeze + spatie/permission; `routes/auth.php`, `PerfilSeeder` | ✅ |
| RF02 | Registro estruturado de eventos (tipo, gravidade, setor, envolvidos, descrição) | e | Wizard T04; `EventoAdversoController@store`, `EventoAdversoRequest` | ✅ |
| RF03 | Notificação anônima | e | `anonima=true` sem `notificante_id`; irreversível; `EventoAdversoObserver` | ✅ |
| RF04 | Protocolo único, sequencial por ano (EA-AAAA-NNNNNN) | e | `ProtocoloService` com `lockForUpdate` | ✅ |
| RF05 | Classificação por taxonomia padronizada (tipo, dano, evitabilidade) | e | Taxonomia ICPS (57 categorias); Enums; triagem T10 | ✅ |
| RF06 | Cálculo do nível de risco por matriz probabilidade × severidade | e | `MatrizRiscoService`; coluna gerada `score_risco`; cálculo em tempo real na T10 | ✅ |
| RF07 | Workflow do ciclo de vida (notificação → conclusão) | e | `WorkflowEventoService` (máquina de estados + histórico imutável) | ✅ |
| RF08 | Investigação com métodos estruturados de causa raiz | e | 5 Porquês e Ishikawa com diagrama SVG; T12 | ✅ |
| RF09 | Ações corretivas com responsável, prazo e eficácia | e | `AcaoCorretivaController`; evidência obrigatória; avaliação de eficácia | ✅ |
| RF10 | Painéis com gráficos e indicadores | f | Dashboard T03 (5 gráficos Chart.js, 7 indicadores, heatmap clicável) | ✅ |
| RF11 | Relatórios em PDF e exportação em planilha | f | dompdf + maatwebsite/excel; `RelatorioController`, `EventosExport` | ✅ |
| RF12 | Trilha de auditoria de todas as escritas | e | activitylog em 7 models; diff em `/admin/auditoria` | ✅ |
| RF13 | Sinalização NOTIVISA e registro do protocolo | e | `NotivisaService`; alerta fixo na T07; registro manual | ✅ |
| RF14 | Notificações internas e por e-mail nas transições e prazos | e | 6 classes em `app/Notifications`; sino na navbar; jobs agendados | ✅ |
| RF15 | Anexos com armazenamento privado | e | `storage/app/privado`; download autenticado; MIME validado | ✅ |
| RF16 | Devolutiva ao notificante | e | `triagem.feedback`; visível em T06/T07 | ✅ |
| RF17 | Parametrização de prazos e regras | e | Tabela `configuracoes` + tela T20 | ✅ |
| RF18 | Gestão de usuários, setores e categorias | e | CRUDs em `/admin/*` com exclusão lógica | ✅ |

## Requisitos não funcionais

| ID | Requisito | Verificação | Status |
|---|---|---|---|
| RNF01 | Arquitetura MVC com Laravel | Inspeção: Controllers → Services → Models → Blade | ✅ |
| RNF02 | MySQL com schema por migrations | 20 migrations InnoDB/utf8mb4 | ✅ |
| RNF03 | Interface responsiva (mobile no wizard) | Bootstrap/AdminLTE; media queries no wizard | ✅ |
| RNF04 | Dashboard < 2 s com 600 eventos | Índices compostos + cache de indicadores (10 min) | ✅ |
| RNF05 | Proteção contra SQLi, XSS e CSRF | [`SEGURANCA.md`](SEGURANCA.md) itens 1–3 | ✅ |
| RNF06 | Senhas com hash criptográfico | bcrypt (cast `hashed`) | ✅ |
| RNF07 | Pseudonimização de pacientes | [`adr/0001`](adr/0001-pseudonimizacao.md); testes | ✅ |
| RNF08 | Interface integralmente em pt-BR | Views, validação e traduções `lang/pt_BR` | ✅ |
| RNF09 | Dez heurísticas de Nielsen | [`AVALIACAO_HEURISTICA.md`](AVALIACAO_HEURISTICA.md) | ✅ |
| RNF10 | Cobertura ≥ 70% em Services e Controllers | 110 testes / 384 asserções (`php artisan test`) | ✅ |
| RNF11 | Contraste WCAG 2.1 AA | Badges com cor + texto; paleta AdminLTE | ✅ |
| RNF12 | Instalável apenas pela documentação | `README.md` (teste de instalação limpa) | ✅ |
| RNF13 | Versionamento Git com commits descritivos | Repositório do projeto | ✅ |
| RNF14 | Exclusivamente dados simulados | Seeders; avisos na interface; pseudonimização | ✅ |

## Rastreabilidade aos objetivos específicos

| Objetivo do TCC | Artefatos |
|---|---|
| (c) Levantar e documentar requisitos | Este documento + `CASOS_DE_USO.md` |
| (d) Projetar arquitetura e modelo de dados | `ARQUITETURA.md` + `MODELO_DADOS.md` |
| (e) Desenvolver a plataforma | RF01–RF09, RF12–RF18 |
| (f) Painéis de visualização e análise | RF10, RF11, RNF04 |
| (g) Testes funcionais e de usabilidade | RNF09–RNF11; `tests/` |
| (h) Avaliar o potencial da ferramenta | Critérios de aceite globais (plano de desenvolvimento) |
