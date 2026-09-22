# SIGEA — Manual do Usuário

Guia de uso por perfil de acesso. As telas citadas (T01–T24) correspondem ao plano de
desenvolvimento do TCC. *Capturas de tela podem ser geradas em uma sessão de demonstração
com os usuários abaixo.*

**Usuários de demonstração:** `admin@sigea.local` · `gestor@sigea.local` ·
`notificador@sigea.local` (senha `senha123`).

---

## 1. Acesso ao sistema (todos os perfis)

1. Acesse `http://localhost:8000` — você será direcionado ao **login** (T01).
2. Informe e-mail institucional e senha. Após 5 tentativas incorretas em 1 minuto o acesso
   é bloqueado temporariamente.
3. Esqueceu a senha? Use **"Esqueci minha senha"** (T02): um link de redefinição é enviado
   por e-mail (validade de 60 minutos).
4. No canto superior direito ficam o **sino de notificações** (contador de não lidas) e o
   menu do usuário, com acesso ao **Perfil** (T23) para atualizar dados e trocar a senha.

---

## 2. Perfil Notificador (profissional de saúde)

### 2.1 Notificar um evento adverso (T04)

1. Menu **Notificar Evento** → assistente em **4 passos**:
   - **Passo 1 — O que aconteceu:** categoria (taxonomia com busca), setor, data (não pode
     ser futura), hora, turno e local específico.
   - **Passo 2 — Descrição:** relato objetivo (mínimo de 30 caracteres — o contador ajuda),
     fatores contribuintes e ação imediata adotada.
   - **Passo 3 — Envolvidos:** identificador interno do paciente (o sistema grava apenas um
     código pseudonimizado — nunca digite nome ou CPF), faixa etária, sexo e profissionais
     envolvidos/testemunhas.
   - **Passo 4 — Revisão e envio:** confira o resumo, anexe documentos (PDF/JPG/PNG/DOCX/
     XLSX, até 10 MB) e escolha **Salvar rascunho** ou **Enviar notificação**.
2. Ao enviar, a tela de confirmação exibe o **número de protocolo** (`EA-AAAA-NNNNNN`) com
   botão de cópia.

**Notificação anônima:** marque a opção no passo 4. O sistema avisa que não haverá
devolutiva e que a opção é irreversível — anote o protocolo. Rascunho não é permitido para
notificações anônimas.

### 2.2 Acompanhar minhas notificações (T06)

Menu **Minhas Notificações**: lista com protocolo, status e a **devolutiva do NSP** quando
registrada. Rascunhos podem ser continuados (**Continuar**) ou excluídos.

### 2.3 Executar minhas ações (T14)

Menu **Minhas Ações**: ações corretivas sob sua responsabilidade. Para concluir, clique em
**Concluir** e registre a **evidência de execução** (obrigatória) — ex.: ata de treinamento
arquivada.

---

## 3. Perfil Gestor de Segurança (NSP)

### 3.1 Painel (T03)

Menu **Painel**: 4 cartões-síntese, 7 indicadores, filtros globais (período, setor,
categoria, grau de dano) e 5 gráficos. Na **matriz de risco 5×5**, clique em uma célula
para abrir a lista de eventos daquele cruzamento probabilidade × severidade.

### 3.2 Triagem (T09/T10)

1. Menu **Fila de Triagem**: eventos notificados, ordenados por tempo de espera; linhas em
   vermelho estouraram o SLA de triagem (padrão 48 h). Clique em **Assumir**.
2. Na tela de triagem, o **relato original** fica à esquerda e a **classificação** à
   direita: revisão da categoria, classificação do incidente, grau de dano, evitabilidade e
   os controles deslizantes de **probabilidade** e **severidade** — o score, o nível de
   risco, a prioridade sugerida e o prazo (SLA) são calculados **em tempo real**.
3. Salve a classificação e escolha o encaminhamento: **investigação**, **direto para
   ações** (bloqueado se a categoria exigir investigação), **conclusão direta** (apenas
   near miss / circunstância sem dano) ou **cancelamento** (justificativa obrigatória).
4. Registre a **devolutiva ao notificante** — ela combate a subnotificação.
5. Eventos elegíveis à ANVISA exibem alerta fixo até o registro do **protocolo NOTIVISA**.

### 3.3 Investigação de causa raiz (T11/T12)

1. Em evento *em investigação*, clique **Abrir investigação**, defina responsável, método
   (**5 Porquês** ou **Ishikawa**) e datas.
2. Registre as causas: no método dos 5 Porquês os campos são encadeados; no Ishikawa as
   causas são distribuídas nos 6 eixos (pessoa, processo, equipamento, ambiente, material,
   gestão). Marque a(s) **causa(s) raiz** (★).
3. O **diagrama de espinha de peixe** é desenhado automaticamente a partir das causas.
4. Cadastre as **ações corretivas** decorrentes. A conclusão da investigação exige **≥1
   causa raiz e ≥1 ação** — ao concluir, o evento passa a *aguardando ação*.

### 3.4 Ações corretivas (T13) e conclusão do evento

- Menu **Ações Corretivas**: filtros por status/responsável/setor; atrasadas destacadas.
- Avalie a **eficácia** das ações concluídas (eficaz / parcialmente eficaz / ineficaz).
- O evento só pode ser **concluído** quando 100% das ações estiverem concluídas ou
  canceladas. Eventos concluídos podem ser **reabertos** com justificativa.

### 3.5 Relatórios e exportação (T16)

Menu **Relatórios**: consolidado, por setor, por categoria e ações pendentes — todos com
filtros de período e exportação em **PDF** e **Excel**. A lista de eventos (T05) também
exporta CSV/XLSX respeitando os filtros aplicados, sem dados identificáveis.

---

## 4. Perfil Administrador

Tudo do gestor, mais o menu **Administração**:

- **Usuários (T17):** criação com perfil (notificador/gestor/administrador), edição,
  suspensão e reativação de acesso (sem exclusão física). Não há autocadastro.
- **Setores (T18):** CRUD com responsável e nº de leitos (denominador do indicador de
  pacientes-dia). Setor em uso não pode ser excluído — inative-o.
- **Categorias (T19):** árvore de 2 níveis da taxonomia ICPS, com as flags *notificável à
  ANVISA* e *exige investigação*.
- **Configurações (T20):** SLAs (triagem, investigação, ação), nome da instituição,
  permissão de notificação anônima, antecedência dos alertas e taxa de ocupação.
- **Auditoria (T21):** trilha completa de alterações com filtros e **diff** (valores
  anteriores × novos) de cada registro.

---

## 5. Rotinas automáticas

Com o agendador ativo (`php artisan schedule:work` ou cron):

| Horário | Rotina |
|---|---|
| Diário 06:00 | Ações vencidas são marcadas como **atrasadas** |
| Diário 07:00 | E-mail aos responsáveis por ações vencendo |
| Diário 07:30 | Alerta aos gestores sobre eventos com prazo próximo |
| Segunda 08:00 | Resumo semanal de indicadores aos gestores |
