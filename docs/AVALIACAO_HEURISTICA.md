# SIGEA — Avaliação Heurística de Usabilidade (Nielsen)

Avaliação das telas críticas segundo as 10 heurísticas de Nielsen (1994), conforme a
Seção 5.6 da metodologia do TCC.

**Telas avaliadas:** T03 Painel · T04 Wizard de notificação · T05 Lista de eventos ·
T07 Detalhe do evento · T10 Triagem · T12 Investigação.

**Escala de severidade:** 0 = não é problema · 1 = cosmético · 2 = menor ·
3 = maior · 4 = catastrófico.

## Matriz heurísticas × telas

Legenda das células: **A** = atendida (evidência); número = severidade de problema
encontrado (descrito na Seção 2).

| # | Heurística | T03 | T04 | T05 | T07 | T10 | T12 |
|---|---|---|---|---|---|---|---|
| H1 | Visibilidade do estado do sistema | A — KPIs e filtros ativos visíveis | A — stepper com passo ativo/concluído | A — badges de status/risco/prazo | A — badges + timeline completa | A — score/nível em tempo real | A — status e contagem de causas |
| H2 | Correspondência com o mundo real | A — vocabulário clínico | A — "near miss", "grau de dano" com tooltips | A | A — abas nomeadas pelo processo do NSP | A — escalas "rara→frequente" | A — eixos do Ishikawa em português |
| H3 | Controle e liberdade do usuário | A — botão limpar filtros | A — rascunho + voltar em cada passo | A | A — reabertura com justificativa | A — prazo/prioridade editáveis | A — causas removíveis |
| H4 | Consistência e padrões | A — AdminLTE em todo o sistema | A | A | A | A | A |
| H5 | Prevenção de erros | A | A — validação por passo + contador de 30 caracteres | **P1 (2)** | A — modais de confirmação | A — encaminhamentos desabilitados até classificar | A — conclusão bloqueada sem causa raiz/ação |
| H6 | Reconhecimento em vez de memorização | A — filtros com valores atuais | A — resumo de revisão no passo 4 | A — selects nomeados | A — dados completos por aba | A — relato original lado a lado | A — evento no cabeçalho |
| H7 | Flexibilidade e eficiência | **P2 (1)** | A — autocomplete de profissionais | A — filtros combináveis | A — links diretos por aba | A — sliders + teclado | A |
| H8 | Estética e design minimalista | A — 4 KPIs principais + detalhes recolhidos | A — um assunto por passo | A | **P3 (1)** | A — duas colunas | A |
| H9 | Ajudar a reconhecer e recuperar erros | A | A — mensagens por campo em pt-BR | A | A — flash de erro descritivo | A — mensagens da máquina de estados | A — motivo do bloqueio no botão |
| H10 | Ajuda e documentação | A — tooltip nos indicadores | A — tooltips LGPD/anonimato | **P4 (1)** | A | A — tooltips das escalas | A — tooltip dos métodos |

## 2. Problemas encontrados e tratamento

| ID | Tela | Heurística | Severidade | Descrição | Situação |
|---|---|---|---|---|---|
| P1 | T05 | H5 | 2 (menor) | A exportação usa os filtros atuais sem confirmar o volume de linhas; uma base grande pode gerar arquivo demorado sem aviso. | Aceito para a versão 1 (600 eventos); mitigação futura: contagem prévia no botão. |
| P2 | T03 | H7 | 1 (cosmético) | Não há atalho para períodos comuns ("últimos 30 dias", "ano corrente") — o usuário digita as datas. | Backlog de melhoria. |
| P3 | T07 | H8 | 1 (cosmético) | Com muitos botões contextuais (gestor, evento em aguardando ação), o cabeçalho fica denso em telas estreitas. | Mitigado com quebra responsiva (`flex-wrap`); revisão visual no backlog. |
| P4 | T05 | H10 | 1 (cosmético) | Os filtros de risco/prioridade não têm tooltip explicando as faixas da matriz. | Backlog; a legenda existe no painel (T03). |

**Síntese:** nenhuma violação de severidade ≥ 3. Os quatro apontamentos são menores ou
cosméticos e não impedem as tarefas principais; três foram registrados como melhoria
futura e um mitigado na própria versão.

## 3. Conformidades de acessibilidade verificadas

- Badges comunicam **cor + texto** (nunca apenas cor) — WCAG 1.4.1.
- Indicador de prazo tem texto alternativo (`sr-only`) além da cor do ponto.
- Formulários com `label` associado, `aria-label` em ícones e `aria-live` no score da matriz.
- Contraste dos badges na paleta AdminLTE atende AA para texto pequeno em todos os pares
  usados (verificação com WebAIM Contrast Checker).
- Wizard operável por teclado; células da matriz de risco no painel são focáveis
  (`tabindex=0`) e acionáveis por Enter/Espaço.
