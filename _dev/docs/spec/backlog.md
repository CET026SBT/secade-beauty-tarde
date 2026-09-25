# Especificação — Gap e trabalho futuro

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../../especificacao_mvp.md).
> Capítulos: §24 · §25

## 24. REQUISITOS NOVOS / GAP ANALYSIS

> Comparação entre o que os **esclarecimentos de retificações** (§3) exigem e o que **está hoje implementado**.
> Cada linha foi **verificada no código**, não inferida.

### 24.0 Resumo executivo

| #        | Tema                                                 | Estado     | Impacto Principal                 |
| :------- | :--------------------------------------------------- | :--------- | :-------------------------------- |
| **24.1** | Re-avaliação dinâmica dos slots                      | 🟡 parcial | UX — prevenção de slots obsoletos |
| **24.2** | Página de detalhes de serviço + carousel             | ⬜ ausente | Enriquecimento do Catálogo        |
| **24.3** | Encaminhamento por tipo de contrato                  | ⬜ ausente | Regra de negócio operacional      |
| **24.4** | Multicidades + flexibilidade horária + alertas       | ⬜ ausente | Gestão de Operação e Logística    |
| **24.5** | Sinal configurável + 10/90 + métodos de pagamento    | 🟡 parcial | Componente Financeiro             |
| **24.6** | Regra das 24h + lembrete + cancelamento pelo cliente | ⬜ ausente | **Crítico / Operacional**         |

### 24.1 — Re-avaliação dinâmica dos slots (D-07)
**Exigido:** o tempo estimado deve ser re-avaliado sempre que o cliente adiciona/descarta serviços,
usando a validação de disponibilidade server-side existente.

**Verificado no código:**
- ✅ A **ordem** já garante que a hora é escolhida **depois** dos serviços (passo 1 → 3).
- ✅ A duração é recalculada no cliente (`bookingDuration()` → `Σ serviços`) e enviada no pedido
  de disponibilidade (`loadSlots()` usa `Math.max(bookingDuration(), 30)`).
- ✅ O servidor **revalida na submissão** (`countByDateWindow` com a duração final → **409**).
- ❌ **`loadSlots()` só é invocado no `change` de `#bookingDate`.** Alterar serviços **depois** de
  escolher a data **não** recarrega a lista → o cliente pode escolher um horário que já não cabe.

**Trabalho a fazer:** recalcular/refrescar os slots quando `state.selectedServiceIds` muda e já
existe `state.date` selecionada (ou invalidar a data/hora escolhida e exigir nova seleção),
mantendo a revalidação server-side como rede de segurança.

### 24.2 — Página de detalhes de serviço + carousel (D-06)
**Exigido:** página dedicada por serviço, com carousel de imagens, descrição e tempo estimado.

**Verificado:** existe apenas um **modal** (`#serviceDetailsModal` em `components/services.php`);
**não existe** rota de página de serviço em `index.php` (só `servicos` e `servicos/:category`).
A tabela `servico_foto` existe mas **sem conteúdo nem UI**.

**Trabalho a fazer:** rota `servicos/:category/:service` (ou `servico/:slug`), novo componente de
página, carousel (Owl Carousel já disponível) alimentado por `servico_foto` e um endpoint de detalhe.

### 24.3 — Encaminhamento por tipo de contrato (D-03)
**Exigido:** contrato fixo → predominantemente **loja**; recibo verde → **ambulatório** + simulador.

**Verificado:** `funcionario.tipo_contrato` existe (`efetivo_contratado`/`recibo_verde`) e o
simulador está implementado, mas **nada no código** restringe ou orienta a aceitação por esse campo.

**Trabalho a fazer (a confirmar com o requisito):** pelo menos **evidenciar** o tipo de contrato na
UI de aceitação e, se se pretender restringir, aplicar a regra no servidor com validação explícita.

### 24.4 — Multicidades, flexibilidade horária e alertas (D-09)
**Exigido:** rotas multicidades (com validação de espaçamento), flexibilidade horária como exceção
(fim > 19:00), e alerta padronizado de custos junto ao indicador de 50 €.

**Verificado:**
- ❌ **Horário rígido:** `validateStoreOpeningHours()` é aplicada a **ambos** os canais
  (`BookingService` linhas 152 e 209) e bloqueia fim > 19:00 (`STORE_CLOSE_HOUR = 19`).
- ❌ **Agrupamento fixo:** `findAmbulatoryGroups()` agrupa por `DATE(data_hora_pretendida)` +
  `cliente_morada.cidade_id` → cada grupo é **uma só cidade**; não há forma de juntar cidades.
- ❌ **Sem validação de espaçamento** entre cidades (não há cálculo de trânsito entre
  agendamentos de cidades diferentes).
- ❌ **Sem alerta de custos:** a listagem mostra o indicador de 50 € mas não há alerta padronizado
  de custos/viabilidade.
- ℹ️ `matriz_deslocacao.tempo_estimado_minutos` **existe** e pode servir de base à validação de
  espaçamento (não é usado para isso hoje).

**Trabalho a fazer:**
1. **Flexibilidade da carrinha:** permitir fim > 19:00 como **exceção** (tolerância configurável),
   mantendo 09:00–19:00 como regra da loja.
2. **Multicidades:** permitir selecionar agendamentos de várias cidades no mesmo grupo
   (a decisão continua sobre `dia + conjunto de cidades`), com:
   - ordenação cronológica obrigatória,
   - validação `tempo_estimado_minutos` ≥ intervalo livre entre o fim de um e o início do seguinte,
   - recusa (409) quando não há espaçamento suficiente.
3. **Convenção de alertas (§12.4):** aplicar o componente padronizado (`alert-info` /
   `alert-warning` / `alert-danger`), com o alerta de custos multicidades posicionado junto ao
   indicador de 50 €.

### 24.5 — Sinal configurável, 10/90 e métodos de pagamento (D-05 / D-10)
**Exigido:** sinal configurável no backoffice; 10 % na marcação + **90 % no término**; métodos de
pagamento simulados (Dinheiro/Multibanco/MB Way); falha de internet → numerário.

**Verificado:**
- 🟡 **Sinal hardcoded:** `private const DEPOSIT_PERCENTAGE = 10;` — **não configurável**.
- ❌ **Sem cobrança dos 90 %** e **sem escolha de método** em lugar nenhum.
- ❌ **Sem cenário de falha de internet.**
- ℹ️ `transacao_financeira` **existe** (com tipo `quota_parte_deslocacao`) e **não tem UI**.
- ℹ️ Já existe uma secção de **configuração** no backoffice (`/gestao/recibos-verdes`) que pode ser
  **generalizada** para alojar também a configuração do sinal (recomendação dos esclarecimentos de retificações).

**Trabalho a fazer:**
1. Tabela de configuração (ou generalização de `config_recibo_verde`) para o **% do sinal** e
   eventual **tolerância horária**; `BookingService` passa a ler a configuração em vigor.
2. **Registo dos 90 %** no término do serviço (aproveitar `transacao_financeira` + UI no detalhe do
   agendamento, junto ao **registo de execução** que já existe).
3. **Escolha simulada do método** de pagamento (Dinheiro / Multibanco / MB Way) com confirmação.
4. **Cenário offline:** permitir declarar "sem internet" → método forçado a **numerário**.
5. **Recibo manual:** confirmar se já existe; caso não, mover para **trabalho futuro** (§25).

### 24.6 — Janela de 24 h, lembrete e cancelamento pelo cliente (D-11) ⚠️ **CRÍTICO**
**Exigido:** ver §15 (não criar rotas a < 24 h; auto-cancelar sem rota às 24 h; lembrete ao cliente;
cancelamento pelo cliente sem penalização).

**Verificado:**
- ❌ **Não existe cancelamento pelo cliente.** O **único** endpoint é
  `admin-appointment-cancel` → `AdminController::appointmentCancel`, com
  `Session::requireProfileApi(["gestor"])`.
  - `modules/main/js/components/appointments.js` só tem **rótulos de estado**
    (`cancelado: "Cancelado"`, `cancelado: "bg-dark"`) — **nenhum botão/endpoint de cancelamento**.
- ❌ **Sem regra das 24 h** em `RotaService::decideRoute` (aceita qualquer data futura já em estado decidível).
- ❌ **Sem auto-cancelamento** de agendamentos sem rota às 24 h.
- ❌ **Sem lembretes/alerta ao cliente** (não existe modelo nem UI de notificações no Main).
- ℹ️ `agendamento.modo_urgencia` existe (sem uso no fluxo atual).

**Trabalho a fazer (por ordem sugerida):**
1. **Cancelamento pelo cliente:** endpoint `?action=customer-booking-cancel`
   (`Session::requireProfileApi(["cliente"])` + verificar posse do agendamento + estados canceláveis) e
   botão em `/agendamentos`; **sem penalização**.
2. **Regra das 24 h na criação de rotas:** `decideRoute`/listagem só consideram agendamentos com
   ≥ 24 h de antecedência; os demais são excluídos do grupo (e cancelados).
3. **Auto-cancelamento:** rotina **on-demand** (ao abrir as listagens do gestor/funcionário, sem CRON)
   que marca `cancelado` os agendamentos de ambulatório **sem rota** com ≤ 24 h, mantendo-os na BD e
   **excluindo-os das listagens ativas** (`findPending` e `findAmbulatoryGroups`).
4. **Estado intermédio (opcional):** avaliar um estado/flags distintos para "cancelado por
   indisponibilidade operacional" (ex.: `expirou_sem_rota`) para não confundir com cancelamento
   voluntário — a decisão de esquema deve ser tomada antes de implementar (ver §25).
5. **Lembrete ao cliente:** modelo de notificação + apresentação em `/agendamentos` (e/ou na home),
   com sugestão de **loja física** ou **reagendamento**; notificação **simulada**.

## 25. TRABALHO FUTURO PRIORIZADO

> Ordem **vinculativa**: as implementações futuras devem seguir esta prioridade.
> **A prioridade máxima entre todas é Fornecedores** (indicação explícita dos esclarecimentos de retificações, §3).

### 25.1 Prioridade 1 — Fornecedores ⭐
Gestão de fornecedores (produtos/consumíveis, custos, contactos) integrada no backoffice,
seguindo a **estrutura de menus** existente (§25.5).
- Requer: nova entidade + módulo no backoffice + endpoints `admin-supplier-*`.
- Nota: alinhar com `transacao_financeira` (custos) e com o calendário fiscal (encargos).

### 25.2 Prioridade 2 — Requisitos adicionais (§24)
| Ordem   | Item de Desenvolvimento                                                     | Referência |
| :------ | :-------------------------------------------------------------------------- | :--------- |
| **2.1** | **Cancelamento pelo cliente** + **janela de 24 h** + **lembrete**           | §24.6      |
| **2.2** | **Sinal configurável** no backoffice + **10/90** + **métodos de pagamento** | §24.5      |
| **2.3** | **Re-avaliação dinâmica dos slots** (prevenção de conflitos de UX)          | §24.1      |
| **2.4** | **Multicidades** + **flexibilidade horária** + **convenção de alertas**     | §24.4      |
| **2.5** | **Página de detalhes de serviço + carousel**                                | §24.2      |
| **2.6** | **Encaminhamento por tipo de contrato**                                     | §24.3      |

### 25.3 Prioridade 3 — Consolidações técnicas
- **Migrar o backoffice** de `modules/backoffice/` para a pasta raiz **`admin/`** prevista no
  prevista no planeamento v3.0 (bloqueado pela instrução de não tocar em `/admin`).
- **UI para `transacao_financeira`, `fecho_caixa_diario` e `gorjeta`** (existem na BD, sem UI).
- **Notificações** (SMS/e-mail) reais ou persistidas, em vez de simuladas.
- **Recibo manual** (se não existir) — a alinhar com as restantes melhorias.
- **Estimar o custo de deslocação intra-cidade:** a `matriz_deslocacao` só cobre o percurso
  **base → cidade**; o combustível **dentro da cidade** **não está modelado**. Era esse o papel
  provisório que os 50 € fixos desempenhavam (removidos em 24/09/2026). Avaliar uma estimativa real
  — eventualmente com serviço de geolocalização, o que exigiria centralizar o do `AddressAutocomplete`
  numa utilidade própria (padrão `apiClient.js`/`api.js`).

### 25.4 Prioridade 4 — Nice-to-have
- Dashboard com estatísticas consolidadas (ocupação, receita, rotas, alertas).
- Histórico/auditoria de decisões e alterações.
- Anexos/documentos nas obrigações fiscais.
- Algoritmos/simuladores sobre os dados retidos (agendamentos auto-cancelados — §15.2).

### 25.5 Estrutura de menus do backoffice (convenção a manter)
```
/gestao/agendamentos   → gestor      (lista, filtros, detalhe, execução, cancelamento, pagamentos*)
/gestao/rotas          → gestor      (dia+cidade, decisão manual, alertas padronizados)
/gestao/fiscal         → gestor      (calendário, obrigações, alertas progressivos)
/gestao/recibos-verdes → gestor      (config. de percentagens + [config. do sinal*] + histórico)
/gestao/servicos       → funcionário (aceitação, desfazer/trocar) — o gestor vê em supervisão
/gestao/fornecedores*  → gestor      (PRIORIDADE 1 do futuro)
```
`*` = por implementar. **Toda a implementação futura deve encaixar nesta estrutura** (não criar
menus paralelos).
