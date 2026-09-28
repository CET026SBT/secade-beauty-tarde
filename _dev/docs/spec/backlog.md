# Especificação — Gap e trabalho futuro

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../../especificacao_mvp.md).
> Capítulos: §24 · §25

## 24. REQUISITOS NOVOS / GAP ANALYSIS

> Comparação entre o que os **esclarecimentos de retificações** (§3) exigem e o que **está hoje implementado**.
> Cada linha foi **verificada no código**, não inferida.

### 24.0 Resumo executivo

| #         | Tema                                                                  | Estado     | Impacto Principal                 |
| :-------- | :-------------------------------------------------------------------- | :--------- | :-------------------------------- |
| **24.1**  | Re-avaliação dinâmica dos slots                                       | ✅ feito   | UX — prevenção de slots obsoletos |
| **24.2**  | Página de detalhes de serviço + carousel                              | ⬜ ausente | Enriquecimento do Catálogo        |
| **24.3**  | Encaminhamento por tipo de contrato                                   | ⬜ ausente | Regra de negócio operacional      |
| **24.4**  | Multicidades + flexibilidade horária + alertas                        | ⬜ ausente | Gestão de Operação e Logística    |
| **24.5**  | Sinal configurável + 10/90 + métodos de pagamento                     | 🟡 parcial | Componente Financeiro             |
| **24.6**  | Regra das 24h + lembrete + cancelamento pelo cliente                  | 🟡 parcial | **Crítico / Operacional**         |
| **24.7**  | Backoffice: painel, gráficos, agenda do funcionário e regra das rotas | 🟡 parcial | Entrada do backoffice e operação  |
| **24.8**  | Defeito: dropdown do autocomplete visível no canto (`/registo`)       | ✅ feito   | UI do registo                     |
| **24.9**  | RH: folha de salários e líquido a pagar **calculados**                | ⬜ ausente | Custo de pessoal e RH             |
| **24.10** | Calendário fiscal: 8 famílias de obrigações e calendário de 2026      | 🟡 parcial | Módulo do Calendário Fiscal       |

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

> ✅ **Feito (28/09/2026):** `bookingWizard.js` ganhou `refreshSlotsIfNeeded()`, chamado sempre que os
> serviços mudam — na lista de serviços **e** nos serviços por pessoa do wizard da carrinha. Quando já
> existe data escolhida, as horas são recalculadas e a hora escolhida é descartada; a revalidação
> server-side (409 na submissão) mantém-se. Verificado por `functional_test`/`http_test` (§26) e pelo
> contrato de JS (`js_syntax_check`).

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

> ✅ **Feito (28/09/2026) — item 1:** o cancelamento pelo cliente existe:
> `?action=customer-booking-cancel` (`Session::requireProfileApi(["cliente"])` + posse verificada contra
> a sessão + estados terminais recusados com **409**) e botão em `/agendamentos` com o aviso de que
> **não há penalização**. O horário libertado volta a aparecer na disponibilidade.
> ⬜ **Em falta: itens 2 a 5** — regra das 24 h na criação de rotas, auto-cancelamento sem rota,
> estado `expirou_sem_rota` e o lembrete ao cliente com sugestão de loja/reagendamento.
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

> ✅ **Feito (28/09/2026):** **itens 1, 3, 4, 5, 6, 7, 9, 10 e 11** —
> `/gestao` passou a ser o **painel do gestor** (`DashboardController`/`Service`/`Repository`, KPIs e
> gráficos com o **Chart.js 2.9.4 local** em `modules/common/lib/chartjs/`, carregado **só** no
> backoffice) e o **funcionário** é encaminhado para a **agenda** (calendário próprio, sem biblioteca
> nova, só rotas `confirmado` — RN-33); **RN-31/RN-32/RN-34** aplicadas (`findAmbulatoryGroups` agrega
> apenas qualificados, decidir com pendentes devolve **409**, `findPending` exclui `confirmado` e o
> gestor inclui/exclui agendamentos antes da decisão); **diálogo de detalhes da rota** com os
> agendamentos qualificados; **sidebar** do backoffice (menu por perfil, `collapse` abaixo de `lg`);
> **avisos** por perfil com contador no sino (`admin-alert-summary|list|read`); **página das comissões**
> (`/gestao/comissoes`, valores da aceitação); **botões de ação** pela convenção única
> (ícone + `title`, dourado/azul/vermelho) nas listagens de rotas e agendamentos.
> ⬜ **Em falta: item 2 (o Chart.js local está feito; faltam os dados contabilísticos) e os módulos
> 6.2 contabilidade (RF-75/76/79 · §17.9) e 6.3 RH (RF-82 · §24.9).**
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
11. **Comissões do funcionário (RF-84):** página própria `/gestao/comissoes`, acessível pelo **menu do
    utilizador** e pela **sidebar**; os dados já existem
    (`agendamento_servico.valor_recibo_verde_funcionario`, snapshot por aceitação — §11).

> **Fases (28/09/2026):** os módulos do backoffice ficam **alinhados na Fase 6** (pedido do gestor, pelo
> prazo), **incluindo a sidebar e a página das comissões** (28/09/2026: **descem da então Fase 7**, que
> deixa de existir); `/gestao/agendamentos` **não** é renomeado. O Chart.js é copiado para
> `modules/common/lib/chartjs/` na **mesma fase** em que os gráficos forem implementados.
> **Ordem interna (de §1.9, sem conflito bloqueante):** 6.0 painel + autorização por perfil → 6.1
> fornecedores → 6.2 contabilidade → 6.3 RH → 6.4 **sidebar + comissões** → 6.5 área do funcionário
> (**promoções saem da Fase 6** — passam a **Fase 7**, com o risco de **retro-atualização de histórico e
> agendamentos passados** a avaliar antes de implementar). **Dependências assinaladas:** o card
> *Dívidas a Fornecedores* do dashboard só existe depois de 6.1 (C-11 — até lá, estado vazio explicativo).

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

### 24.9 — RH: folha de salários e líquido a pagar calculados (28/09/2026)
**Exigido:** *"Relativamente à coluna Pagar Ao Pessoal, a tabela está preenchida. Mas se pudermos calcular
através do site, será melhor."* → o **líquido a pagar** deixa de ser um número fixo do ficheiro e passa a ser
**calculado** pela plataforma (módulo de RH, Fase **6.3**).

**Verificado — a folha de 28/09 e o Balancete oficial dizem o mesmo:**

| Rubrica                    | Fórmula do ficheiro           | Calculado     | Balancete           |
| :------------------------- | :---------------------------- | :------------ | :------------------ |
| Remuneração do período     | base × 3 meses                | 19 950,00     | 6321 = 19 950,00    |
| Subsídio de alimentação    | dias úteis × 6,15             | 2 312,40      | 6324 = 2 312,40     |
| **Total de remunerações**  | —                             | **22 262,40** | **632 = 22 262,40** |
| IRS retido                 | base × (8 % · 3,6 % · 8,56 %) | 1 221,00      | — (retenção)        |
| SS do trabalhador          | base × 11 %                   | 2 194,50      | —                   |
| SS da entidade             | saldo × 23,75 %               | 4 721,68      | 635 = 4 721,70      |
| **Líquido a pagar**        | total − IRS − SS 11 %         | **18 846,90** | —                   |
| **Custo total (conta 63)** | 632 saldo + 635 + 636 + 638   | **27 234,13** | **63 = 27 234,13**  |

**Ficheiro substituído:** o `CUSTOS RH 2.xlsx` (23/09) trazia subsídio **387,45 para todos** e SS patronal
**4 738,13** — **não** fecha com o Balancete (2 312,40 · 4 721,70) e **não** é fonte.

**Trabalho a fazer:**
1. `PayrollService` com as fórmulas de **RN-35**; as **taxas de IRS por trabalhador** são **dados**
   (por trabalhador), não constantes de código — ⚠️ se mudam de mês para mês continua **por responder**
   (pergunta **19**).
2. Página do **RH** com os trabalhadores, os valores do mês e o **líquido a pagar calculado** (RF-82).
3. **Dias úteis** do mês como base do subsídio — ⚠️ critério de feriados **não fechado** (perguntas **33** e
   **34**): em 2026 janeiro desconta **1 dia** (1 de janeiro) e fevereiro **não** desconta o Carnaval.
4. **Dias por trabalhador** — a folha usa **63** para cinco trabalhadores e **61** para um → falta a origem
   dos 2 dias (faltas/férias) → **perguntar**.

**⚠️ As identidades dos 6 trabalhadores não constam de nenhum ficheiro entregue.** Verificado nas duas
fontes que os descrevem — `Contabilidade Secade Beauty.xlsx` (folha «Custos Funcionários») e
`Gastos e Rendimentos_Simulador processamento de salários Secade.xlsx` (folha «Processamento de salário»):
ambas trazem **apenas** vencimento base (1200 · 1200 · 1000 · 1000 · 1000 · 1250) e a taxa de IRS
(8 % · 8 % · 3,6 % · 3,6 % · 3,6 % · 8,56 %) — **nenhum nome, NIF ou NISS** (a folha *DMR – DRI* está vazia).
Consequências registadas:
1. **A BD não pode ser completada** com os 6 trabalhadores sem **perguntar** os nomes → pergunta **nova**.
2. Até lá, `funcionario` tem **1 registo** (o de teste) e o contador público **"Profissionais"** mostraria
   **1** em vez dos **6** documentados → a chave `team` está em **`SITE_STATS_DOCUMENTAL`**
   (`app/config/config.php`) e publica o **valor documental** enquanto a contagem da BD não estiver completa.

### 24.10 — Calendário fiscal: 8 famílias de obrigações e calendário de 2026 (28/09/2026)
**Exigido:** o calendário de 2026 entregue cobre **8 famílias** e **substitui** a lista anterior: SAF-T ·
DMR · retenções na fonte IRS/IRC · Segurança Social · IVA (declaração e pagamento) · IRC (Modelo 22, por
conta e final) · IES/DA.

**Verificado:** `obrigacao_fiscal.tipo` ∈ {iva, irc, seguranca_social, seguros} — cobre **4 de 8**;
`alerta_fiscal` (30/15/7/3/1 dia + atraso) **não muda**. Padrão mensal e prazos: §13.

**Trabalho a fazer:**
1. **Alargar o enum** (`saft`, `dmr`, `retencoes`, `ies`) com a justificação de BD registada — **revê a
   D-15**, que fixou o enum para não receber lembretes **de fornecedores**; estas famílias são **fiscais**.
2. **Importar** o calendário do ficheiro (RF-83): 53 linhas mensais + o bloco anual, normalizando estados e
   prazos em fim de semana.
3. **Confirmar** a regra do fim de semana — o próprio ficheiro trata casos iguais de forma diferente (§13).

### 24.11 — Carga dos ficheiros de serviços, fornecedores e clientes (28/09/2026)

**Entregues:** `Secade Duração Serviços 1.ods` (durações reais dos serviços) e
`Serviços, Clientes e Fornecedores.xlsx` (43 fornecedores + 65 clientes reais).

**Feito — `database_migration_v4.sql`** (idempotente, ids explícitos + `ON DUPLICATE KEY UPDATE`;
aplicada **duas vezes** sem erro):

| Carga                                                              | Resultado verificado                                                                                                                     |
| :----------------------------------------------------------------- | :--------------------------------------------------------------------------------------------------------------------------------------- |
| **Durações** dos 35 serviços (Cabeleireiro · Estética · Barbearia) | 35 casados por nome; os **preços já coincidiam** com a BD — nada mudou                                                                   |
| **Fornecedores**                                                   | tabela `fornecedor` (**nova**) + **43 registos**                                                                                         |
| **Clientes**                                                       | `utilizador` (ids **100–164**, `password_hash='*'` — login impossível de propósito), `cliente` (65) e `cliente_morada` (ids **200–264**) |
| **Conferência**                                                    | 65 clientes reais · 65 moradas novas · 65 utilizadores novos · **0 e-mails duplicados**                                                  |

**Decisões tomadas na carga (todas por ausência de dado — nada foi inventado):**

1. **E-mail gerado** — a folha não tem e-mails: `<slug-do-nome>.<NIF|sN>@cliente.secade.local`
   (domínio inexistente, só para garantir a unicidade da coluna). ⚠️ **Confirmar** se fica assim ou se o
   cliente fornece os endereços reais.
2. **Telefone em branco** (a coluna vem vazia) e **morada só com a cidade** (`rua` e `codigo_postal`
   vazios) — nos agendamentos ao domicílio a cidade define a **rota** (§8) e o resto pede-se no primeiro
   agendamento.
3. **5 fornecedores sem NIF válido** (o campo trazia um rótulo de canal/observação): Temu, ViceDeal.com,
   Aliexpress, Consumíveis e *Bandido Portugal.pt* → `nif` = `NULL` e o rótulo passa para `observacoes`.
4. **"Manuel jacinto (renda)" aparece duas vezes** com NIFs diferentes → são **duas rendas distintas**;
   ambas mantidas (não é duplicado a limpar).
5. **Correções por semelhança no que casava por nome:** `Cordolete`→**Cordelete** (id 2),
   `Tranças Box Braids`→**Box Braids** (id 1), cidade `Arraiaolos`→**Arraiolos** (5 registos).
   ⚠️ **A confirmar com o cliente** — foram correções por semelhança, não por evidência documental.

**Natureza dos dados:** é **dados reais de cliente**, não demonstração — numa instalação de raiz entra pela
migração **v4** (§27.2) e **não** pelo `database_seed.sql`.

### 24.12 — Ficheiro de faturas de vendas: cruzamento pendente (28/09/2026)

**Entregue:** `Faturas de vendas SECADE BEAUTY,Lda.xlsx` — **1 folha**, ainda por cruzar com o Balancete,
a DR e o IVA.

**O que o ficheiro diz (lido, ainda não cruzado):**

| Bloco                            | Total de clientes | Valor base   | IVA          | Total c/ IVA  |
| :------------------------------- | :---------------- | :----------- | :----------- | :------------ |
| **Cabeleireiro — espaço físico** | 133               | 4 774,06     | 1 098,03     | 5 872,09      |
| **Área ambulante**               | 117               | 3 505,56     | 806,28       | 4 311,84      |
| **Janeiro (detalhe mensal)**     | —                 | **8 279,62** | **1 904,31** | **10 183,93** |

- ⚠️ **O cabeçalho do ficheiro não corresponde ao detalhe mensal:** traz *Total Vendas/IVA* **29 638,74** e
  *Total Vendas S/IVA* **24 101,65**, quando o detalhe de janeiro soma **10 183,93** / **8 279,62** → são
  **períodos diferentes** no mesmo ficheiro. **Não se infere** qual é qual: **perguntar**.
- ⚠️ Os valores de **deslocação** do bloco ambulante (1,60 · 2,40 · 2,80 €) **têm** de ser cruzados com a
  `matriz_deslocacao` (§8) — é a única fonte que confirma as distâncias por cidade.
- **Trabalho a fazer:** cruzar com **71/vendas** e **72/IVA** do Balancete e com a DR; se fechar, passa a ser
  fonte de referência para o simulador de vendas (Fase **6.2**).
