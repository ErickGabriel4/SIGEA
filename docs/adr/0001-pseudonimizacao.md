# ADR 0001 — Pseudonimização do identificador do paciente

**Status:** aceita · **Data:** 2026-08

## Contexto

O SIGEA registra eventos adversos que envolvem pacientes. A LGPD (Lei nº 13.709/2018)
classifica dados de saúde como **dados sensíveis**, e os requisitos éticos do projeto de
pesquisa vedam o uso de dados reais. Ainda assim, o NSP precisa reconhecer **recorrência**
de eventos com o mesmo paciente (ex.: quedas repetidas) para a análise de risco.

## Decisão

O campo `eventos_adversos.paciente_codigo` **nunca** armazena nome, CPF ou número real de
prontuário. No momento da gravação, o identificador digitado é convertido por
`PseudonimizadorService` em:

```
'PAC-' . substr(hash('sha256', identificador . APP_KEY), 0, 16)
```

- **Determinístico:** o mesmo identificador gera sempre o mesmo código, preservando a
  capacidade analítica de agrupar eventos do mesmo paciente.
- **Não reversível:** SHA-256 truncado com a `APP_KEY` como segredo torna inviável a
  reidentificação a partir do banco de dados isoladamente.
- **Minimização:** faixa etária e sexo são as únicas características clínico-demográficas
  registradas, ambas em categorias amplas.

## Consequências

- Atende aos princípios de **minimização** e **segurança** da LGPD (art. 6º, III e VII).
- A troca da `APP_KEY` invalida o agrupamento histórico — decisão consciente: a chave faz
  parte do segredo e não é versionada.
- Testes automatizados (`PseudonimizadorServiceTest`) garantem prefixo, determinismo e a
  ausência do identificador original no código gravado.
