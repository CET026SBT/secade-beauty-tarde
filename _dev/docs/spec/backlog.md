# Especificação — Gap e trabalho futuro

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../../especificacao_mvp.md).
> Capítulos: §24 · §25

## 24. REQUISITOS NOVOS / GAP ANALYSIS

> Comparação entre o que os **esclarecimentos de retificações** (§3) exigem e o que **está hoje implementado**.
> Cada linha foi **verificada no código**, não inferida.

### 24.0 Resumo executivo

| #        | Tema                                                                     | Estado     | Impacto Principal                 |
| :------- | :----------------------------------------------------------------------- | :--------- | :-------------------------------- |
| **24.1** | Re-avaliação dinâmica dos slots                                          | 🟡 parcial | UX — prevenção de slots obsoletos |
| **24.2** | Página de detalhes de serviço + carousel                                 | ⬜ ausente | Enriquecimento do Catálogo        |
| **24.3** | Encaminhamento por tipo de contrato                                      | ⬜ ausente | Regra de negócio operacional      |
| **24.4** | Multicidades + flexibilidade horária + alertas                           | ⬜ ausente | Gestão de Operação e Logística    |
| **24.5** | Sinal configurável + 10/90 + métodos de pagamento                        | 🟡 parcial | Componente Financeiro             |
| **24.6** | Regra das 24h + lembrete + cancelamento pelo cliente                     | ⬜ ausente | **Crítico / Operacional**         |
| **24.7** | Backoffice: dashboard, gráficos, agenda do funcionário e regra das rotas | ⬜ ausente | Entrada do backoffice e operação  |
| **24.8** | Defeito: dropdown do autocomplete visível no canto (`/registo`)          | ⬜ defeito | UI do registo                     |

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

### 24.7 — Backoffice: dashboard, gráficos, agenda do funcionário e regra das rotas
**Exigido (28/09/2026):** `/gestao` passa a ser o **dashboard do gestor** (KPIs e, por baixo, **gráficos** dos
dados contabilísticos — D-13 · D-14); o **funcionário** ganha uma **agenda em calendário** com os
agendamentos de **rotas confirmadas**, mantendo a aceitação em **listagem**; e uma rota **só** se confirma
com **todos** os serviços aceites.

**Verificado no código:**
- ❌ **Sem dashboard:** `/gestao` aponta para a lista (`index.php` L30 → `modules/backoffice/appointments.php`);
  não existe `dashboard.php`, `DashboardService` nem `admin-dashboard-summary`.
- ❌ **Sem biblioteca de gráficos:** `boFooter.php` L10-15 carrega só jQuery, Bootstrap e jq-preloader;
  nenhuma página de gestão serve Chart.js.
- ❌ **Regra das rotas não é imposta:** `BookingRepository::findAmbulatoryGroups` (L194-196) e
  `findDecidableByCityAndDate` (L266) incluem `pendente_aceitacao_funcionarios`, e `RotaService` L177
  expõe `awaitingAcceptance` → **é possível aprovar rota com serviços por aceitar** (RN-31).
- ❌ **Lista "Por aceitar" não separa rotas confirmadas:** `BookingServiceRepository::findPending`
  (L134-141) inclui pendentes de agendamentos em qualquer estado não terminal — **incluindo `confirmado`**
  (RN-32).
- ❌ **Sem agenda:** não existe `/gestao/agenda`, `agenda.php` nem `admin-employee-agenda-list`; o menu do
  funcionário (`boNavbar.php` L11-14) tem só "Serviços".
- ✅ **A tabela de rotas já agrega por dia+cidade** (`/gestao/rotas`: data, cidade, agendamentos, receita,
  combustível, rentabilidade, estado e **decisão por linha**) — `RotaService::listRoutes` · `routes.js`.
- ❌ **Sem diálogo de detalhes da rota:** a agregação traz `GROUP_CONCAT(a.id) AS agendamentos_ids`
  (`BookingRepository` L212), mas `RotaService::listRoutes` (L169-187) **não expõe** essa chave e
  `routes.js` não tem modal.
- ⚠️ **Botões de ação sem convenção única:** rotas usam `btn-success`/`btn-danger` (`routes.js` L36-43) e
  agendamentos usam `btn-outline-primary`/`btn-outline-danger` (`appointments.js` L46/49/240).
- ✅ **Dados já existem** (§1.2 · §1.7 do planeamento): `agendamento_servico.funcionario_id` +
  `agendamento.data_hora_pretendida` + `agendamento.estado_reserva` permitem o calendário **sem tabela nova**.

**Trabalho a fazer:**
1. **Dashboard** (`/gestao`, perfil `gestor`): página nova, `DashboardService` que **delega nos
   Repositories** (§18.1–§18.2 — **nenhuma query no Service**) e `admin-dashboard-summary`; KPIs no topo
   (Disponibilidades · Dívidas a receber · Dívidas a pagar · Resultado) e **gráficos por baixo**.
   O card *Dívidas a Fornecedores* só existe após o módulo Fornecedores (§25.1) — até lá, **estado vazio
   explicativo** (nunca valor inventado).
2. **Chart.js local:** copiar `Chart.bundle.min.js` (v2.9.4) de `admin/vendor/chart.js/` para
   `modules/common/lib/chartjs/` e carregá-lo no `boFooter.php` (**só backoffice**; `/admin` não é tocado).
3. **Agenda do funcionário** (`/gestao/agenda`, perfil `funcionario`): calendário + `admin-employee-agenda-list`
   (intervalo de datas) — reutiliza os dados existentes.
4. **RN-31 — rotas:** `findAmbulatoryGroups`/`findDecidableByCityAndDate` passam a considerar **apenas**
   `totalmente_aceite_funcionarios`; decidir com pendentes → **409**; a listagem de rotas pode **mostrar**
   os que aguardam aceitação, mas **não os agrega**.
5. **RN-32 — lista "Por aceitar":** `findPending` exclui os agendamentos **já em rota confirmada**
   (`estado_reserva = 'confirmado'`); o acompanhamento desses passa a ser a agenda (RN-33).
6. **Sininho:** contador de alertas **não lidos** no menu do utilizador (`menuUserBo.php`), reutilizando
   `alerta_fiscal` + `admin-alert-summary`; leitura **global** enquanto houver um só gestor (§22.2 · C-03).
7. **UX de rotas (decidido 28/09/2026):** `/gestao/rotas` é o **único** ecrã de formação e decisão de rota;
   a agregação inclui **por defeito todos** os agendamentos **qualificados** do dia+cidade (RN-31); cada
   linha ganha **Ver detalhes** — **diálogo Bootstrap** com a lista dos agendamentos qualificados
   associados à rota potencial (`admin-routes-list` passa a expor `agendamentosIds`; alteração de §19 a
   registar na implementação, por `data-api.md` estar no limite de 400 linhas).
8. **Convenção dos botões de ação (coluna de ações):** sem texto — apenas **ícone + atributo `title`**,
   **fundo transparente** e borda/ícone com a **mesma cor** por tipo de ação: **dourado = detalhes ·
   azul = editar · vermelho = remover**; aplicar **uniformemente** às listagens do backoffice (rotas e
   agendamentos incluídos) — **retrofit** às listagens existentes (decisão de 28/09/2026).
9. **Excluir agendamento de uma rota (RF-80 · RN-34):** **não existe** no código — `RotaRepository`
   expõe apenas `create` (L77) e `updateDecision` (L104), `BookingRepository::updateEstadoMany` (L235)
   grava o estado em bloco e a recusa aplica `cancelado` a **todos** (RN-18); a procura por
   `excluir`/`reverter` em `app/` e `modules/` não devolve resultados. O gestor tem de poder **retirar**
   um agendamento da rota **antes da decisão** → volta a `totalmente_aceite_funcionarios`; a decisão passa
   a aplicar-se ao **conjunto** incluído. **Ponto em aberto:** excluir depois de a rota estar `aprovada`
   (exige desfazer a decisão ou criar rota complementar — decidir na implementação, sem partir a RN-19).
10. **Página centralizada de avisos (RF-81 · D-15):** `/gestao/avisos`, alcançável no **menu do
    utilizador** e pelo **clique no sino**; contador de não lidos por `alerta_fiscal` (global) e, quando
    existirem lembretes não fiscais, ➕ `notificacao` com leitura **por utilizador**.

> **Fases (28/09/2026):** os módulos do backoffice ficam **alinhados na Fase 6** (pedido do gestor, pelo
> prazo); a **Fase 7** reserva-se à **sidebar** do backoffice (a renomeação de `/gestao/agendamentos`
> **não** avança). O Chart.js é copiado para `modules/common/lib/chartjs/` na **mesma fase** em que os
> gráficos forem implementados. **Ordem interna (de §1.9, sem conflito bloqueante):** 6.0 painel +
> autorização por perfil → 6.1 fornecedores → 6.2 contabilidade → 6.3 RH → 6.4 promoções → 6.5 área do
> funcionário. **Dependências assinaladas:** o card *Dívidas a Fornecedores* do dashboard só existe depois
> de 6.1 (C-11 — até lá, estado vazio explicativo) e as promoções mexem em preços de referência dos testes
> (C-08 · C-18 · Q-29).

### 24.8 — Defeito: dropdown do autocomplete visível no canto superior esquerdo (`/registo`)
**Sintoma:** ao carregar `/registo`, o *dropdown* do autocomplete aparece **vazio no canto superior esquerdo**.

**Causa (verificada):**
- `modules/common/js/utils/addressAutocomplete.js` **L60-64** cria o `<ul>` com a classe **`show`** e
  anexa-o ao `<body>`; a regra `display: none` de `.autocomplete-dropdown` (`style.css` **L588**) é
  **derrotada** por `.dropdown-menu.show { display: block }` do Bootstrap (0,2,0 vs 0,1,0) → fica visível
  logo no carregamento.
- O posicionamento depende **só** de CSS Anchor Positioning (`style.css` **L576-583**: `position-anchor`,
  `anchor()`, `anchor-size()`, `position-try-options`), **sem *fallback***: sem suporte ou sem o *anchor*
  resolvido, a caixa cai na posição estática do `<body>`.

**Alcance:** 1 sítio — `modules/main/js/components/customerRegister.js` **L64** (só `/registo`).

**Trabalho a fazer:** (1) não incluir `show` na construção e esconder no `#initDOM()`; (2) posicionar por
`getBoundingClientRect()` com `position: fixed`, sob `CSS.supports('position-anchor: --x')`; (3) *guard* de
regressão no `asset_test.php`. **Estado:** ⬜ corrigir **depois** do alinhamento — branch de contexto própria
(`rules §4`).

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
- **Notificações centralizadas** (página única de avisos, por perfil) — o *dashboard* deixou de ser nice-to-have: é **RF-77** (§24.7).
- Histórico/auditoria de decisões e alterações.
- Anexos/documentos nas obrigações fiscais.
- Algoritmos/simuladores sobre os dados retidos (agendamentos auto-cancelados — §15.2).

### 25.5 Estrutura de menus do backoffice (convenção a manter)
```
/gestao                → gestor      (dashboard: KPIs no topo + gráficos + sininho)  (NOVO — §24.7)
/gestao/agendamentos   → gestor      (lista, filtros, detalhe, execução, cancelamento, pagamentos*)
/gestao/rotas          → gestor      (dia+cidade, decisão manual, alertas padronizados)
/gestao/fiscal         → gestor      (calendário, obrigações, alertas progressivos)
/gestao/recibos-verdes → gestor      (config. de percentagens + [config. do sinal*] + histórico)
/gestao/servicos       → funcionário (aceitação/desfazer em **listagem**) — o gestor vê em supervisão
/gestao/agenda         → funcionário (agenda em **calendário**: rotas confirmadas)   (NOVO — §24.7)
/gestao/fornecedores*  → gestor      (PRIORIDADE 1 do futuro)
```
`*` = por implementar. **Toda a implementação futura deve encaixar nesta estrutura** (não criar
menus paralelos).
**Notas (28/09/2026):** a navbar do backoffice está no limite de lotação — a migração para **sidebar**
(componente exclusivo do backoffice) fica para a **Fase 7**; a renomeação de `/gestao/agendamentos`
**não avança** (o nome fica) e a **renomeação** de outras rotas só se fizer com a sidebar.
