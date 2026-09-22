# SIGEA — Arquitetura do Sistema

## 1. Visão em camadas (MVC + Services)

```mermaid
flowchart TB
    subgraph Cliente["Navegador"]
        UI["Blade + AdminLTE 3<br/>Chart.js · JS do wizard/matriz/Ishikawa"]
    end

    subgraph Http["Camada HTTP"]
        MW["Middlewares<br/>auth · permission/role · SecurityHeaders<br/>RegistrarUltimoAcesso · throttle"]
        CT["Controllers (magros)<br/>Evento · Triagem · Investigacao · Acao<br/>Dashboard · Relatorio · Admin/*"]
        FR["Form Requests<br/>validação + mensagens pt-BR"]
        PO["Policies<br/>EventoAdverso · Investigacao · AcaoCorretiva"]
    end

    subgraph Dominio["Camada de Domínio"]
        SV["Services (gordos)<br/>ProtocoloService · MatrizRiscoService<br/>WorkflowEventoService · IndicadorService<br/>NotivisaService · PseudonimizadorService"]
        EN["Enums PHP 8 tipados<br/>StatusEvento, GrauDano, …"]
        OB["Observers<br/>EventoAdversoObserver"]
        NT["Notifications<br/>database + mail"]
    end

    subgraph Dados["Camada de Dados"]
        MD["Models Eloquent (12)<br/>SoftDeletes · LogsActivity · casts de Enum"]
        DB[("MySQL 8<br/>InnoDB · utf8mb4<br/>20 migrations")]
    end

    UI --> MW --> CT
    CT --> FR
    CT --> PO
    CT --> SV
    SV --> EN
    SV --> MD
    OB --> SV
    MD --> DB
    SV --> NT
```

**Princípios aplicados:** controllers magros → services gordos; nenhuma query no Blade;
enums sempre tipados com `label()`/`cor()`; toda listagem paginada (15/página); toda escrita
de domínio auditada.

## 2. Diagrama de classes (domínio)

```mermaid
classDiagram
    class User {
        +nome, email, matricula
        +categoria_profissional: Enum
        +ativo: bool
        +setor() BelongsTo
        +eventosNotificados() HasMany
    }
    class Setor {
        +nome, sigla, tipo: Enum
        +leitos: int
    }
    class CategoriaEvento {
        +codigo, nome, codigo_icps
        +notificavel_anvisa: bool
        +exige_investigacao: bool
        +pai() BelongsTo
        +filhas() HasMany
    }
    class EventoAdverso {
        +protocolo: string
        +anonima: bool
        +classificacao, grau_dano, evitabilidade: Enum
        +probabilidade, severidade, score_risco
        +nivel_risco, status, prioridade: Enum
        +classificacaoCompleta() bool
        +pendenteNotivisa() bool
    }
    class Investigacao {
        +metodo, status: Enum
        +causa_raiz, conclusao
        +podeConcluir() bool
    }
    class InvestigacaoCausa {
        +categoria: Enum
        +eh_causa_raiz: bool
    }
    class AcaoCorretiva {
        +tipo, status, eficacia: Enum
        +prazo, data_conclusao
        +evidencia_conclusao
    }
    class EventoHistorico {
        +status_anterior, status_novo, acao
        <<imutável>>
    }
    class Comentario {
        +interno: bool
        <<polimórfico>>
    }

    User "1" --> "0..*" EventoAdverso : notifica
    User "1" --> "0..*" EventoAdverso : gerencia
    Setor "1" --> "0..*" EventoAdverso : ocorrência
    CategoriaEvento "1" --> "0..*" EventoAdverso : classifica
    CategoriaEvento "1" --> "0..*" CategoriaEvento : subcategorias
    EventoAdverso "1" --> "0..1" Investigacao
    EventoAdverso "1" --> "0..*" AcaoCorretiva
    EventoAdverso "1" --> "0..*" EventoHistorico
    EventoAdverso "1" --> "0..*" Comentario
    Investigacao "1" --> "0..*" InvestigacaoCausa
    Investigacao "1" --> "0..*" AcaoCorretiva : propõe
```

## 3. Fluxo principal — notificação → conclusão (diagrama de atividade)

```mermaid
flowchart TD
    A([Profissional identifica o evento]) --> B[Wizard de notificação T04]
    B --> C{Salvar como?}
    C -- Rascunho --> D[(status: rascunho)] --> B
    C -- Enviar --> E[Gera protocolo EA-AAAA-NNNNNN<br/>status: notificado]
    E --> F[Notifica gestores do NSP]
    F --> G[Gestor assume na fila de triagem T09]
    G --> H[Classifica: taxonomia, dano,<br/>evitabilidade, P×S T10]
    H --> I{Score/nível de risco<br/>define prazo SLA}
    I --> J{Encaminhamento}
    J -- "near miss sem dano" --> K([Concluído])
    J -- "duplicidade/trote" --> L([Cancelado])
    J -- "dispensa investigação" --> P
    J -- investigar --> M[Investigação T12<br/>5 Porquês / Ishikawa]
    M --> N{≥1 causa raiz e<br/>≥1 ação corretiva?}
    N -- não --> M
    N -- sim --> P[status: aguardando_acao]
    P --> Q[Responsáveis executam ações<br/>com evidência obrigatória T14]
    Q --> R{Todas tratadas?}
    R -- não --> Q
    R -- sim --> S[Gestor conclui o evento<br/>+ devolutiva ao notificante]
    S --> K
    K -. reabertura com justificativa .-> M
```

## 4. Fluxo da matriz de risco (triagem)

```mermaid
sequenceDiagram
    actor G as Gestor
    participant T as Tela T10 (JS)
    participant C as TriagemController
    participant M as MatrizRiscoService
    participant N as NotivisaService
    participant E as EventoAdverso

    G->>T: move sliders P e S
    T->>T: score = P×S, nível, prioridade,<br/>prazo (tempo real)
    G->>C: PUT /triagem/{evento}
    C->>M: aplicar(evento)
    M->>E: nivel_risco, prioridade,<br/>prazo_conclusao (SLA)
    C->>N: aplicar(evento)
    N->>E: notificavel_anvisa (se elegível)
    C->>E: save() → activity_log
```

## 5. Decisões registradas

- [ADR 0001 — Pseudonimização do paciente](adr/0001-pseudonimizacao.md)
- [ADR 0002 — Taxonomia ICPS](adr/0002-taxonomia-icps.md)
- [ADR 0003 — Matriz de risco 5×5](adr/0003-matriz-risco-5x5.md)

## 6. Máquina de estados e modelo de dados

Ver [`MODELO_DADOS.md`](MODELO_DADOS.md) — DER completo, dicionário de dados e diagrama de
estados do evento adverso.
