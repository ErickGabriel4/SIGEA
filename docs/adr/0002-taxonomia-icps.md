# ADR 0002 — Taxonomia de eventos baseada na ICPS (WHO, 2009)

**Status:** aceita · **Data:** 2026-08

## Contexto

Sistemas de notificação sem taxonomia padronizada produzem dados não comparáveis entre
setores e períodos, limitação apontada na literatura e observada no NOTIVISA. O SIGEA
precisa de uma classificação estável, reconhecida internacionalmente e aderente às
categorias monitoradas pela ANVISA.

## Decisão

Adotar a **International Classification for Patient Safety (ICPS)** da OMS como base da
taxonomia, materializada na tabela `categorias_evento` em **dois níveis**:

- **13 categorias-raiz** (MED, CIR, QUE, IRA, IDE, DIS, SAN, NUT, OXI, LPP, DOC, LAB, GES),
  cada uma com o campo `codigo_icps` para rastreabilidade ao documento da OMS;
- **subcategorias operacionais** (≥3 por raiz) descritas em vocabulário clínico local.

Duas flags de negócio por categoria: `notificavel_anvisa` (dispara o alerta de notificação
compulsória) e `exige_investigacao` (impede a dispensa de investigação na triagem).

## Consequências

- Dados comparáveis e agregáveis por categoria-raiz nos dashboards e relatórios.
- O administrador pode evoluir a taxonomia sem deploy (CRUD com exclusão lógica), mas a
  estrutura de 2 níveis é imposta pela validação (`CategoriaEventoRequest`).
- A classificação complementar do incidente (near miss, evento adverso, never event…) e o
  grau de dano seguem igualmente a escala da ICPS, implementados como Enums PHP.
