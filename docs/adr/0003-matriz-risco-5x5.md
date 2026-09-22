# ADR 0003 — Matriz de risco 5×5 com SLA derivado

**Status:** aceita · **Data:** 2026-08

## Contexto

A priorização dos eventos precisa ser **objetiva e reprodutível** (US15). A ANVISA (2019)
recomenda matrizes de probabilidade × severidade para a gestão de risco em serviços de
saúde; o formato 5×5 é o mais difundido nos NSPs brasileiros.

## Decisão

Implementar em `MatrizRiscoService` a matriz 5×5:

| Score (P × S) | Nível | Prioridade sugerida | SLA de conclusão |
|---|---|---|---|
| 1–4 | Baixo | Baixa | 60 dias |
| 5–9 | Moderado | Média | 45 dias |
| 10–15 | Alto | Alta | 30 dias |
| 16–25 | Extremo | Crítica | 15 dias |

- `score_risco` é uma **coluna gerada** no MySQL (`probabilidade * severidade`), garantindo
  consistência independentemente da camada de aplicação.
- **Regra de exceção:** grau de dano *grave/óbito* ou classificação *never event* força a
  prioridade **crítica**, independentemente do score.
- O prazo (`prazo_conclusao`) é preenchido automaticamente pelo SLA, mas permanece editável
  pelo gestor (registro no histórico/auditoria).
- O cálculo é replicado em JavaScript na tela de triagem (T10) para retorno em tempo real,
  com a fonte de verdade no serviço PHP.

## Consequências

- Priorização uniforme entre gestores; a tabela de faixas é coberta por testes unitários
  (`MatrizRiscoServiceTest`, 8 casos de borda).
- Os SLAs são constantes do serviço; uma evolução futura pode movê-los para a tabela
  `configuracoes` sem alteração de contrato.
