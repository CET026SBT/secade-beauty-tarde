# ANÁLISE FUNCIONAL — BACKOFFICE DO GESTOR (PROPOSTA DE FUSÃO)

<!-- md-wrap-tables:max=220 — a tabela do ponto 3 tem 5 colunas com muitos spans de
     código longos; 217 colunas é o mínimo possível sem partir palavras ao meio. -->

**Ficheiro de trabalho (não normativo)** · branch **`agent-workspace`** · 23/09/2026
**Entrada analisada:** requisitos refinados do *Painel de Backoffice — Tipo de Conta: Gestor*
(Resumo/Dashboard · Contabilidade e Gestão Financeira · Recursos Humanos · Promoções e Campanhas) +
antecipação do perfil **Funcionário**.
**Cruzado com:** `especificacao_mvp.md` v1.1 (§2–§5, §11–§13, §17–§19, §22, §24–§26, §28, §29) e com o
código/BD reais (`index.php`, `app/config/api.php`, `app/{controllers,services,repositories}`,
`modules/backoffice/`, `DataBase_v2.sql`).

> ⚠️ **Âmbito:** fase **estritamente de análise e proposta de fusão**. **Nenhum requisito existente foi
> alterado, revogado ou sobrescrito.** A fonte única de verdade continua a ser `especificacao_mvp.md`
> (§29.3): nada do que aqui aparece como "novo" entra na especificação antes de decisão do gestor
> (§3 deste documento lista precisamente o que exige essa decisão).

`✅` já existe · `🟡` existe parcialmente · `⬜` não existe

---

## 1. PROPOSTA DE FUSÃO/INTEGRAÇÃO

### 1.1 Princípios de encaixe respeitados pela proposta

| #   | Princípio                                                                         | Onde está definido    |
| :-- | :-------------------------------------------------------------------------------- | :-------------------- |
| P1  | Nenhum menu paralelo: tudo entra em `/gestao/...`                                 | §25.5                 |
| P2  | Camadas View → `api.js` → `api.php` → Controller → Service → Repository+Mapper    | §18.1                 |
| P3  | Endpoints `admin-<dominio>-<acao>`; páginas `/gestao/<area>`                      | §19.3 · §25.5         |
| P4  | Decisões de gestão são **manuais**; nenhum indicador bloqueia ação                | §2.E · RN-05 · §12.3  |
| P5  | Alertas na convenção `alert-info/warning/danger` e **nunca bloqueiam**            | §12.4                 |
| P6  | Sem CRON: tudo on-demand e idempotente                                            | §22.1                 |
| P7  | **Sem novos pacotes/frameworks** (gráficos com CSS/SVG ou libs já presentes)      | `.clinerules` §3      |
| P8  | Repository: JOIN apenas N:1 de lookup; escrita na própria tabela                  | §18.2                 |
| P9  | Contrato de nomes front-end ↔ API; validators em `validators/`                    | §18.7 · `.clinerules` |
| P10 | Requisito novo → primeiro a especificação (`RF-nn`/`RN-nn`/`D-nn`), depois código | §29.3                 |

### 1.2 Quadro-resumo (o que entra onde)

| Requisito novo                   | Página                  | Endpoints propostos                 | Camadas                          | BD: reutilizar ✚ / criar ➕                          | Estado |
| :------------------------------- | :---------------------- | :---------------------------------- | :------------------------------- | :---------------------------------------------------- | :----: |
| KPIs Gastos vs Rendimentos ·     | `/gestao`               | `admin-dashboard-summary`           | ManagerService +                 | agregações `fetchRaw` sobre ✚ (sem tabela nova)      | 🟡     |
| Dívidas                          |                         |                                     | DashboardService                 |                                                       |        |
| Sininho (contador, lista, marcar | barra superior          | `admin-alert-summary` ·             | AlertService + FiscalService     | `alerta_fiscal.visualizado` ✚ + origem extensível ➕ | 🟡     |
| lido)                            |                         | `admin-alert-list` ·                |                                  |                                                       |        |
|                                  |                         | `admin-alert-read`                  |                                  |                                                       |        |
| Lembretes:                       | barra superior          | idem (tipos novos)                  | AlertService + SupplierService   | ➕ `notificacao` **ou** extensão de                   | ⬜     |
| fornecedores/contratos/carrinha  |                         |                                     |                                  | `obrigacao_fiscal`                                    |        |
| Ativos e Passivos + margem       | `/gestao/contabilidade` | `admin-accounting-balance`          | AccountingService +              | ✚ agendamento/rota_ambulante/agendamento_servico; ➕ | ⬜     |
| global                           |                         |                                     | AccountingRepository             | despesas                                              |        |
| Demonstração de Resultados       | `/gestao/contabilidade` | `admin-accounting-income-statement` | AccountingService + JS de barras | idem                                                  | ⬜     |
| (barras)                         |                         |                                     | CSS/SVG                          |                                                       |        |
| Simulador fiscal (RAI, IRC 20 %) | `/gestao/contabilidade` | `admin-accounting-tax-simulate`     | AccountingService +              | ➕ taxa configurável (`config_fiscal`)                | ⬜     |
|                                  |                         |                                     | FiscalService                    |                                                       |        |
| Balancete bancário + fluxo de    | `/gestao/contabilidade` | `admin-accounting-treasury`         | AccountingService                | ✚ `transacao_financeira` (hoje **sem UI**) ·         | ⬜     |
| caixa                            |                         |                                     |                                  | `fecho_caixa_diario`                                  |        |
| Controlo de IVA (apuramento)     | `/gestao/contabilidade` | `admin-accounting-vat`              | AccountingService +              | ✚ `obrigacao_fiscal(tipo=iva)` + ➕ taxa por serviço | ⬜     |
|                                  |                         |                                     | FiscalService                    |                                                       |        |
| Dívidas a fornecedores           | `/gestao/fornecedores`  | `admin-supplier-*` (§19.5 já os     | SupplierService +                | ➕ `fornecedor` + ➕ `fatura_fornecedor`              | ⬜     |
|                                  |                         | prevê)                              | SupplierRepository               |                                                       |        |
| RH: listagem + filtro por        | `/gestao/equipa`        | `admin-employee-list`               | EmployeeService ✚               | ✚ `funcionario.tipo_contrato`/`ativo` + `utilizador` | 🟡     |
| vínculo                          |                         |                                     |                                  |                                                       |        |
| RH: adicionar / editar /         | `/gestao/equipa`        | `admin-employee-save` ·             | EmployeeService +                | ✚ `funcionario.ativo` (soft-delete)                  | ⬜     |
| desativar                        |                         | `admin-employee-toggle`             | UserService/AuthService          |                                                       |        |
| RH → passivos (comissões,        | `/gestao/contabilidade` | (consumido pelo balanço)            | AccountingService +              | ✚                                                    | 🟡     |
| salários)                        |                         |                                     | GreenReceiptService              | `agendamento_servico.valor_recibo_verde_funcionario`, |        |
|                                  |                         |                                     |                                  | `funcionario.salario_base`                            |        |
| Promoções: campanhas com datas   | `/gestao/promocoes`     | `admin-promotion-list` ·            | PromotionService +               | ➕ `promocao`                                         | ⬜     |
|                                  |                         | `admin-promotion-save`              | PromotionRepository              |                                                       |        |
| Tags sazonais (Natal, Verão, …)  | `/gestao/promocoes`     | `admin-promotion-context-*`         | PromotionService                 | ➕ `promocao_contexto`                                | ⬜     |
| Matriz de impacto (serviços)     | `/gestao/promocoes`     | `admin-promotion-service-*`         | PromotionService                 | ➕ `promocao_servico` (N:N)                           | ⬜     |
| Aplicação ao agendamento (2      | `/agendar` (wizard)     | `booking-promotion-*`               | BookingService +                 | ✚ `agendamento_servico.preco_praticado` + ➕ canal   | ⬜     |
| canais)                          |                         |                                     | PromotionService                 |                                                       |        |
| Funcionário: agenda própria      | `/gestao/agenda`        | `employee-agenda-list`              | BookingService ✚ + RBAC         | ✚ (dados existem)                                    | ⬜     |
| Funcionário: comissões           | `/gestao/agenda`        | `employee-commission-list`          | GreenReceiptService ✚           | ✚ (dados existem em `agendamento_servico`)           | 🟡     |
| individuais                      |                         |                                     |                                  |                                                       |        |
| Funcionário: promoções só de     | `/gestao/promocoes`     | `admin-promotion-list` (perfil      | PromotionService + RBAC          | ➕                                                    | ⬜     |
| leitura                          |                         | autorizado)                         |                                  |                                                       |        |
| Menu/permissões por perfil       | `boNavbar`/`menuUserBo` | (transversal)                       | Session ✚ + mapa de permissões  | ➕ (código/config, sem tabela)                        | 🟡     |
|                                  |                         |                                     | por perfil                       |                                                       |        |

### 1.3 Módulo A — Resumo/Dashboard e Sininho

**A.1 Dashboard de entrada — o requisito não é novo, é um upgrade**

| Facto verificado                                                                              | Consequência para a fusão                                                                   |
| :-------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------ |
| `RF-63` já pede *"Dashboard/resumo e estrutura de menus convencionada por módulo"* — 🟡 (§25) | A proposta **promove** um requisito existente; não cria um paralelo                         |
| §25.4 (nice-to-have) prevê                                                                    | O pedido acrescenta-lhe o eixo **financeiro** (Gastos vs Rendimentos, dívidas) e o rigor de |
| *"Dashboard com estatísticas consolidadas (ocupação, receita, rotas, alertas)"*               | tesouraria                                                                                  |
| `/gestao` hoje aponta para `modules/backoffice/appointments.php` (`index.php` L30)            | Serve de painel? **Não** — ver conflito **C-01** (encaminhamento)                           |
| Sem biblioteca de gráficos disponível (`modules/common/lib` = Bootstrap 5.0.0, jQuery, Owl    | Gráficos com **CSS/SVG próprios** ou *progress bars* Bootstrap — **não** instalar           |
| Carousel, Wow, Lightbox, CounterUp)                                                           | Chart.js/ApexCharts (P7)                                                                    |

**Encaixe proposto**

- **Página:** `/gestao` como painel (cards de KPI + alertas), mantendo `/gestao/agendamentos` como lista.
- **Endpoint:** `admin-dashboard-summary` (GET, perfil `gestor`) → `{ period, revenue, costs, result, supplierDebt, nextObligations[] }`.
- **Camadas:** `AdminController::dashboardSummary` → `DashboardService` (novo) que **compõe** os serviços/repos existentes
  (`ManagerService`, `BookingRepository`, `RotaRepository`, `FiscalObligationRepository`, `GreenReceiptConfigRepository`).
  **Sem** repository novo com JOINs cruzados — a agregação sobe para o Service (§18.2).
- **KPI "Dívidas a Fornecedores":** depende do módulo Fornecedores (**§25.1**, prioridade 1 do futuro, hoje ⬜) → enquanto
  não existir, o card mostra **estado vazio explicativo** (*"módulo de fornecedores por implementar"*), nunca um valor inventado.

**A.2 Sininho de notificações — reutilizar o que já existe**

| Peça                                        | Já existe?                                                           | Proposta                                                              |
| :------------------------------------------ | :------------------------------------------------------------------- | :-------------------------------------------------------------------- |
| Persistência de alertas                     | ✅ `alerta_fiscal` (`tipo_alerta` 30/15/7/3/1/atraso, `data_alerta`, | Reutilizar como **fonte primária** do contador:                       |
|                                             | `visualizado`, chave única)                                          | `COUNT(*) WHERE visualizado = 0`                                      |
| API de alertas                              | ✅ `admin-fiscal-alert-list` · `admin-fiscal-alert-read` (§19.3)     | Manter e acrescentar `admin-alert-summary` (contador para a barra)    |
| Construção dos alertas                      | ✅ `FiscalService::generateAlerts()` on-demand e idempotente (§13)   | Manter o mesmo padrão para os novos tipos de lembrete (sem CRON — P6) |
| Local na UI                                 | ✅ `modules/backoffice/components/menuUserBo.php` +                  | O sino entra **no menu do utilizador** (barra superior), como pedido  |
|                                             | `includes/boNavbar.php`                                              |                                                                       |
| Lembretes a fornecedores/contratos/carrinha | ⬜ (o enum `obrigacao_fiscal.tipo` só tem                            | **Extensão de modelo necessária** → decidir entre extensão do enum ou |
|                                             | `iva`,`irc`,`seguranca_social`,`seguros`)                            | entidade nova (conflito **C-02**)                                     |
| "Lido" por conta                            | 🟡 `alerta_fiscal.visualizado` é **global** (não por utilizador)     | Com 2+ gestores, quem marca "lido" silencia os outros → decisão em    |
|                                             |                                                                      | **C-03**                                                              |

> 📌 **Divergência detetada (especificação ↔ BD).** §17.6 descreve `alerta_fiscal` com `mensagem` e `lido`,
> mas a tabela real (`DataBase_v2.sql`, L549) tem **`visualizado`** e **não tem `mensagem`**. O contador do
> sininho deve usar `visualizado`; se os alertas precisarem de texto próprio, é preciso ➕ coluna (hoje a
> mensagem é composta na apresentação, a partir do tipo/prazo). Ideia para a lista de limpezas técnicas.

**Regras que o sininho deve herdar** (para não colidir com o que já está decidido):

- **Nunca bloqueia** decisões do gestor — é apoio à decisão (RN-05 · §12.4 · P4/P5).
- Severidade na convenção da casa: `alert-info` / `alert-warning` / `alert-danger` (§12.4).
- Geração **on-demand** ao abrir o backoffice, idempotente (§13 · §22.1).
- Para o **funcionário**, o mesmo componente alimentado só com alertas **operacionais**
  (nova marcação atribuída à sua agenda, alteração/recusa de rota) — nunca dados financeiros.

### 1.4 Módulo B — Contabilidade e Gestão Financeira

**B.1 O que o sistema já consegue alimentar (verificado em `DataBase_v2.sql`)**

| Indicador pedido               | Fonte real já existente                                                                | Nota                                                               |
| :----------------------------- | :------------------------------------------------------------------------------------- | :----------------------------------------------------------------- |
| Rendimentos (loja vs carrinha) | `agendamento.valor_total` + `local_prestacao` (`loja_fisica`/`carrinha_ambulante`) +   | por `data_hora_pretendida` (mês corrente), separável por canal ✅  |
|                                | `estado_reserva`                                                                       |                                                                    |
| Recebimentos                   | `transacao_financeira` (`sinal_inicial`, `restante_90_porcento`, `pagamento_integral`, | ⚠️ **sem UI** (§25.3) e `sinal_pago` está sempre `0` (§14.1)       |
|                                | `quota_parte_deslocacao`)                                                              |                                                                    |
| Custos de rota                 | `rota_ambulante.custo_estimado_combustivel`, `lucro_servicos`, `lucro_total`           | já calculados no módulo de rotas (§12.2), incl. custo fixo de 50 € |
| Obrigações fiscais             | `obrigacao_fiscal` + `alerta_fiscal`                                                   | `valor_estimado` é **introduzido à mão** (§13)                     |
| Prestadores (recibos verdes)   | `agendamento_servico.valor_recibo_verde_funcionario` + `config_recibo_verde`           | valor **snapshot** por aceitação (§11)                             |
| Efetivos                       | `funcionario.salario_base`                                                             | ⚠️ subsídios / 13.º-14.º mês **não** estão modelados               |
| Caixa por funcionário/dia      | `fecho_caixa_diario` (`total_esperado_faturas`, `total_recolhido_campo`, `diferenca`)  | ⚠️ **sem UI** (§25.3)                                              |
| Gorjetas                       | `gorjeta`                                                                              | sem UI (§25.3)                                                     |

**B.2 Gap estrutural — o achado mais importante desta análise**

`transacao_financeira` é um **livro de entradas de caixa**, não um balancete:

- `agendamento_id` **NOT NULL** e `funcionario_id` **NOT NULL** → é **impossível** registar uma despesa
  que não pertença a um agendamento e a um funcionário;
- `tipo_transacao` cobre apenas recebimentos → não há compras a fornecedores, salários, IVA a pagar,
  seguros nem manutenção da carrinha;
- não tem conta bancária, saldo inicial, IVA, documento nem fornecedor.

**Consequência:** o módulo pedido exige **modelo de despesas novo** (tabela + Service + Repository + UI).
Duas vias — decisão em **C-04**:

| Via                                | O que implica                                                                                                                              | Avaliação          |
| :--------------------------------- | :----------------------------------------------------------------------------------------------------------------------------------------- | :----------------- |
| (a) Alargar `transacao_financeira` | Tornar `agendamento_id`/`funcionario_id` opcionais e acrescentar *saídas* ao enum. Menos tabelas, mas **muda o significado** de uma tabela | ⚠️ Frágil          |
|                                    | do MVP e arrisca os 289 testes                                                                                                             |                    |
| (b) Criar entidades próprias       | ➕ `despesa` (+ `fornecedor`/`fatura_fornecedor` para dívidas) e manter `transacao_financeira` como livro de recebimentos                  | ✅ **Recomendada** |

**B.3 Encaixe por sub-módulo**

| Sub-módulo pedido      | Dados                                                  | Artefactos novos                                     | Observações                                            |
| :--------------------- | :----------------------------------------------------- | :--------------------------------------------------- | :----------------------------------------------------- |
| Ativos e Passivos      | ✚ agendamento · rota_ambulante · funcionario ·        | `AccountingService` + `AccountingRepository` ·       | margem = rendimentos − custos; loja e carrinha         |
|                        | agendamento_servico · ➕ despesas                      | `admin-accounting-balance` · `/gestao/contabilidade` | separadas                                              |
| DR (gráfico de barras) | o mesmo                                                | `admin-accounting-income-statement` · JS de barras   | Resultado Líquido = RAI − IRC (confirmar **Q-24**)     |
|                        |                                                        | CSS/SVG                                              |                                                        |
| Simulador fiscal       | o mesmo                                                | `admin-accounting-tax-simulate` · taxa de IRC em ➕  | Não deve **escrever** em `obrigacao_fiscal` → **C-05** |
|                        |                                                        | `config_fiscal`                                      |                                                        |
| Balancete / Tesouraria | ✚ `transacao_financeira` · `fecho_caixa_diario` + ➕  | UI (cumpre §25.3) · `admin-accounting-treasury`      | saldo = inicial + entradas − saídas                    |
|                        | saldo inicial/conta                                    |                                                      |                                                        |
| Controlo de IVA        | ✚ `obrigacao_fiscal(tipo='iva')` + ➕ base de IVA por | `admin-accounting-vat`                               | depende de **Q-01**                                    |
|                        | serviço                                                |                                                      |                                                        |

**B.4 Princípios herdados que o módulo financeiro tem de respeitar**

- **Proibido** introduzir gatilho automático sobre rotas: os 50 € continuam **indicador visual** (RN-05 · §3.1) → **C-06**.
- Recibos verdes: os valores são **snapshot** na aceitação e alterar percentagens só afeta aceitações
  futuras (§11) → a contabilidade **lê** o que está gravado, **nunca recalcula**.
- Pagamentos continuam **simulados** (§22.1): não há gateway e `sinal_pago` permanece `0` → **Q-03**.
- Nada de CRON: os totais são calculados **na leitura** (P6).

### 1.5 Módulo C — Recursos Humanos (Equipa)

**Estado real:** `funcionario` já tem `tipo_contrato` (`efetivo_contratado`/`recibo_verde`), `salario_base`,
`cc`, `ativo`; existem `EmployeeService`/`EmployeeRepository`. O que **não** existe é a **página** e o ecrã que crie
perfis `funcionario`/`gestor` (§23.4) — embora `auth-register` já permita ao gestor criar perfis (§19.1).

| Pedido                                   | Encaixe proposto                                                       | Nota / risco                                                           |
| :--------------------------------------- | :--------------------------------------------------------------------- | :--------------------------------------------------------------------- |
| Listagem centralizada                    | Página **`/gestao/equipa`** + `admin-employee-list` (GET) → reutilizar | Sem tabela nova                                                        |
|                                          | `EmployeeService` ✚                                                   |                                                                        |
| **Filtro por vínculo contratual**        | Filtro `tipo_contrato` + `ativo` na listagem (dados já existem) ✚     | É a segmentação efetivo vs recibo verde que o pedido descreve ✅       |
| Adicionar / editar perfil                | `admin-employee-save` (POST) → `EmployeeService` + `UserService` ✚    | Decidir se reutiliza `auth-register` ou endpoint dedicado (**Q-11**)   |
| Desativar perfil                         | `admin-employee-toggle` → `funcionario.ativo = 0` (**soft-delete**)    | ⚠️ *Hard delete* é **impossível**: `transacao_financeira`, `gorjeta` e |
|                                          |                                                                        | `fecho_caixa_diario` usam `ON DELETE RESTRICT`                         |
| Efetivos: salário base / subsídios       | `salario_base` ✚ ; "subsídios" ➕ (não modelados)                     | Definir se entra no passivo mensal (**Q-09**)                          |
| Recibos verdes: comissão / produção      | `agendamento_servico.valor_recibo_verde_funcionario` ✚ (snapshot por  | Alimenta o passivo **sem recálculo** → **C-10**                        |
|                                          | aceitação, §11)                                                        |                                                                        |
| "Alimenta indiretamente passivos/gastos" | Consumido pelo `AccountingService` (§1.4) — **sem** endpoint novo      | Define a **fonte única** de custo de pessoal → **C-10**                |

**Fronteira com o que já está decidido:** §22.2 diz que *"o perfil é de leitura (dados pessoais não editáveis no MVP)"* —
refere-se ao **perfil do cliente em `/perfil`**. A edição pelo gestor em `/gestao/equipa` é **nova** e não revoga
aquela limitação, mas convém confirmá-lo explicitamente → **C-07**.

### 1.6 Módulo D — Promoções e Campanhas (módulo totalmente novo)

Verificação: **não existe** tabela, página, endpoint, Service, Repository ou JS de promoções (24 tabelas em
`DataBase_v2.sql`; rotas em `index.php` L30-35; 37 endpoints em `app/config/api.php`).

**Modelo proposto (➕ 4 tabelas — está em `.clinerules` que alterações de BD exigem justificação, ver §3)**

| Tabela proposta        | Campos mínimos                                                                                                 | Papel no pedido                    |
| :--------------------- | :------------------------------------------------------------------------------------------------------------- | :--------------------------------- |
| ➕ `promocao`          | `nome`, `descricao`, `data_inicio`, `data_fim`, `tipo_desconto` (`percentagem`/`valor_fixo`), `valor`, `ativo` | Campanha com datas de início e fim |
| ➕ `promocao_contexto` | `nome` (*Natal*, *Verão*, *Black Friday*, *Campanha Local Évora*), `cor`/`icone`                               | Filtro por contexto sazonal (tags) |
| ➕ `promocao_servico`  | `promocao_id`, `servico_id` (N:N)                                                                              | **Matriz de impacto** por serviço  |
| ➕ `promocao_canal`    | `promocao_id`, `canal` (`loja_fisica`/`carrinha_ambulante`)                                                    | Aplicável à loja **e** à carrinha  |

**Encaixe**

- **Página** `/gestao/promocoes` (mesma convenção §25.5): grelha principal + **chips de tags por baixo da grelha** (pedido).
- **Endpoints** `admin-promotion-list` · `admin-promotion-save` · `admin-promotion-context-*` · `admin-promotion-service-*`
  → `PromotionController`/`PromotionService`/`PromotionRepository` + `PromotionMapper` (padrão §18.1-§18.3).
- **Aplicação ao agendamento:** o campo que materializa o preço efetivo é **`agendamento_servico.preco_praticado`** ✚.
  Proposta: o desconto é resolvido **no servidor** (`BookingService` + `PromotionService`) e gravado por serviço;
  o wizard apenas mostra o preço já calculado e o resumo identifica a campanha aplicada.
- **Funcionário:** mesma página em **modo leitura** (sem botões de escrita) → depende do RBAC (§1.7).
- **Impactos cruzados obrigatórios** (preços, sinal, recibos verdes, testes) → **C-08**.

### 1.7 Antecipação do perfil Funcionário (e a autorização por perfil)

**Estado real (verificado):** as **páginas** do backoffice **não** são segregadas por perfil — o gestor abre
`/gestao/servicos` em supervisão —, mas as **APIs** são: `admin-service-*` recusa aceitar ao gestor com **403** (§22.2).
Ou seja, já existe autorização **por endpoint**; falta autorização **por página/menu**.

**Proposta**

1. **Mapa de permissões por perfil** (código/config, sem tabela nova): página → perfis; endpoint → perfis
   (hoje cada Controller faz `requireManager()`/`requireEmployee()` — manter e passar a cobrir as páginas).
2. `boNavbar.php` e `menuUserBo.php` **filtram** as entradas pelo perfil em sessão, mas o **enforcement**
   continua **server-side** (nunca só esconder no menu) → risco apontado em **C-09**.
3. Novas páginas e respetivo perfil:

| Página nova             | Gestor             | Funcionário                      |
| :---------------------- | :----------------- | :------------------------------- |
| `/gestao` (painel)      | ✅ escrita/leitura | ❌ (vê a sua agenda como *home*) |
| `/gestao/contabilidade` | ✅ escrita/leitura | ❌ oculto                        |
| `/gestao/equipa`        | ✅ escrita/leitura | ❌ oculto                        |
| `/gestao/promocoes`     | ✅ escrita         | 👁️ **só leitura**                |
| `/gestao/fornecedores`  | ✅ escrita         | ❌ oculto                        |
| `/gestao/agenda`        | 👁️ supervisão      | ✅ escrita (aceitação)           |

**O que se aproveita para o funcionário (como pedido)**

- **Sininho operacional:** novas marcações atribuídas à sua agenda e alterações/recusa de rota (§1.3, âmbito filtrado).
- **Promoções em leitura:** para saber que descontos estão ativos ao aplicar um serviço.
- **A minha agenda:** hoje só existe a lista de aceitação — `employee-agenda-list` (dados já existem em
  `agendamento_servico.funcionario_id`).
- **Comissões / recibos verdes individuais:** Σ `valor_recibo_verde_funcionario` por período + histórico por serviço;
  **os dados já estão gravados** (§11) → `employee-commission-list` (reutiliza `GreenReceiptService` ✚).

### 1.8 Mapa de encaixe (páginas e tabelas)

**Antes → depois (rotas de página, `index.php`)**

```text
/gestao                     → PAINEL DE KPIs + sininho            gestor        (NOVO)
/gestao/agendamentos        → lista, filtros, detalhe, execução   gestor        (existe)
/gestao/rotas               → dia+cidade, decisão manual          gestor        (existe)
/gestao/servicos            → aceitação serviço a serviço         funcionário   (existe; gestor supervisiona)
/gestao/fiscal              → calendário + alertas progressivos   gestor        (existe)
/gestao/recibos-verdes      → percentagens + simulador            gestor        (existe)
/gestao/contabilidade       → ativos/passivos · DR · fiscal · tesouraria · IVA   gestor  (NOVO)
/gestao/equipa              → listagem + vínculo + CRUD           gestor        (NOVO)
/gestao/promocoes           → campanhas + tags + matriz impacto   gestor (escrita) / funcionário (leitura)  (NOVO)
/gestao/fornecedores        → fornecedores + dívidas              gestor        (NOVO - §25.1, prioridade 1)
/gestao/agenda              → a minha agenda + comissões          funcionário   (NOVO)
```

**Base de dados: 24 tabelas hoje → proposta**

| Grupo                    | Tabelas                                                                                                   | Nota                                     |
| :----------------------- | :-------------------------------------------------------------------------------------------------------- | :--------------------------------------- |
| Indispensável ao pedido  | ➕ `fornecedor` · `fatura_fornecedor` · `despesa` · `promocao` · `promocao_contexto` · `promocao_servico` | 6 tabelas novas                          |
| A decidir antes de criar | ➕ `promocao_canal` · `conta_bancaria` (ou saldo em config) · `config_fiscal` (taxas)                     | Podem ser colunas ou configuração        |
| Alternativa a decidir    | ➕ `notificacao` genérica **ou** extensão de `obrigacao_fiscal.tipo` + `alerta_fiscal`                    | Ver **C-02**                             |
| **Sem alterações**       | ✚ todas as restantes (agendamento, funcionario, transacao_financeira, …)                                 | Nenhuma tabela do MVP deixa de funcionar |

### 1.9 Ordem de implementação sugerida (compatível com §25)

> §25 diz que a ordem é **vinculativa** e que **Fornecedores é a prioridade máxima**. A proposta abaixo
> respeita isso: o módulo de fornecedores **deixa de ser um item isolado** e passa a ser a fundação do eixo
> de passivos da contabilidade.

| Fase | Âmbito                                                 | Depende de     | Reaproveita ✚                                          | Novo ➕                                                 |
| :--- | :----------------------------------------------------- | :------------- | :------------------------------------------------------ | :------------------------------------------------------ |
| 6.0  | Autorização por perfil + painel v1 (KPIs já            | —              | `Session`, `FiscalService`, `BookingRepository`,        | `DashboardService`, mapa de permissões, `/gestao`       |
|      | calculáveis) + sininho fiscal                          |                | `alerta_fiscal`                                         |                                                         |
| 6.1  | **Fornecedores + despesas** (§25.1 — prioridade 1)     | 6.0            | `transacao_financeira` (referência de custos)           | `fornecedor`, `fatura_fornecedor`, `despesa`,           |
|      |                                                        |                |                                                         | `SupplierService/Repo`, `/gestao/fornecedores`          |
| 6.2  | Contabilidade: ativos/passivos · DR · simulador fiscal | 6.1 (despesas) | `rota_ambulante`, `obrigacao_fiscal`,                   | `AccountingService/Repo`, `config_fiscal`,              |
|      | · tesouraria/IVA                                       |                | `fecho_caixa_diario`                                    | `/gestao/contabilidade`                                 |
| 6.3  | RH (`/gestao/equipa`) + custo de pessoal no balanço    | 6.2            | `EmployeeService/Repo`, `funcionario`,                  | `admin-employee-*`                                      |
|      |                                                        |                | `agendamento_servico`                                   |                                                         |
| 6.4  | Promoções + aplicação no wizard                        | 6.2 (opcional) | `agendamento_servico.preco_praticado`, wizard existente | 4 tabelas, `PromotionService/Repo`, `/gestao/promocoes` |
| 6.5  | Área do funcionário: agenda + comissões + promoções    | 6.0 · 6.4      | `ServiceAcceptanceService`, `GreenReceiptService`       | `/gestao/agenda`, `employee-*`                          |
|      | (leitura)                                              |                |                                                         |                                                         |

Cada fase deve **fechar o ciclo de §29.3.6** (atualizar §4 estado, §24 gap, §28 critérios e §26 testes na
mesma alteração) e manter as **289 verificações** atuais a passar.

### 1.10 Requisitos a registar (sugestão de IDs — **nada foi escrito na especificação**)

Por §29.3.2, novos requisitos entram como `RF-nn`, regras como `RN-nn` e decisões como `D-nn`.
Blocos livres verificados: `RF-75+` (§4.6 termina em RF-74), `RN-30+` (§5.1 termina em RN-29), `D-12+` (§3.11 termina em D-11).

| ID sugerido  | Tema a registar                                                                                                                                      |
| :----------- | :--------------------------------------------------------------------------------------------------------------------------------------------------- |
| RF-80..RF-84 | Painel do gestor: KPIs de gastos/rendimentos, dívidas a fornecedores, sininho com contador                                                           |
| RF-85..RF-88 | Contabilidade: ativos/passivos, DR, simulador fiscal (RAI + IRC), tesouraria e IVA                                                                   |
| RF-89..RF-90 | RH: listagem com filtro de vínculo; criar/editar/desativar perfil                                                                                    |
| RF-91..RF-95 | Promoções: campanhas com datas, tags sazonais, matriz de impacto, aplicação nos 2 canais                                                             |
| RF-96..RF-98 | Área do funcionário: agenda própria, comissões individuais, promoções em leitura                                                                     |
| RN-30..RN-35 | Regras de cálculo: resultado mensal, IRC (0 se RAI ≤ 0), comissões como passivo, desconto no `preco_praticado`, efeito no sinal, critério de período |
| D-12..D-14   | Decisões a fixar em §3: modelo de despesas/passivos · modelo de notificações · autorização por página                                                |

## 2. DÚVIDAS TÉCNICAS/NEGOCIAIS

> Todas resultaram **do cruzamento** com o que já existe. Nenhuma é resolúvel por inferência do código —
> cada uma muda números, modelos de dados ou regras.

### 2.1 Contabilidade e tesouraria

| ID   | Dúvida                                                                                        | Impacto / opções                                                                           |
| :--- | :-------------------------------------------------------------------------------------------- | :----------------------------------------------------------------------------------------- |
| Q-01 | Os preços (`preco_base`, `preco_praticado`) são **com IVA incluído**? Que taxa(s) se aplicam  | Sem isto o *"controlo de IVA"* não tem base. Opções: taxa por serviço (nova coluna), taxa  |
|      | aos serviços (6 % / 23 %)?                                                                    | global em config, ou IVA incluído com extração automática                                  |
| Q-02 | O que conta como **"Rendimento do mês"**: agendamentos `executado`/`concluido` por            | Muda todos os KPIs. Os estados `cancelado`/`recusado` entram a zero?                       |
|      | `data_hora_pretendida`, por data de execução, ou recebimentos em `transacao_financeira`?      |                                                                                            |
| Q-03 | `sinal_pago` está **sempre `0`** (§14.1) e não há gateway. A tesouraria usa `valor_sinal`     | O *"saldo atualizado em tempo real"* é, por definição, **simulado**. Confirmar que é       |
|      | (teórico) ou `transacao_financeira`?                                                          | aceitável (§22.1)                                                                          |
| Q-04 | Existe **saldo inicial**? Uma conta ou várias (numerário, banco)?                             | Sem saldo inicial o balancete nunca fecha. Opções: parâmetro em config (sem tabela) ou ➕  |
|      |                                                                                               | `conta_bancaria`                                                                           |
| Q-05 | O `fecho_caixa_diario` (esperado vs recolhido vs diferença) alimenta a tesouraria ou fica só  | Define se a "tesouraria" é por dia/funcionário ou agregada por período                     |
|      | como auditoria (§25.3)?                                                                       |                                                                                            |
| Q-06 | Como se modela a **dívida a fornecedor**: fatura com prazo (`data_vencimento`, estado) +      | Define o desenho de `fatura_fornecedor`/`despesa` e se há pagamentos parciais              |
|      | pagamentos parciais, ou apenas despesa com data?                                              |                                                                                            |
| Q-07 | As dívidas/despesas de fornecedores entram também no **calendário fiscal** (§13) como         | §25.1 diz *"alinhar com o calendário fiscal (encargos)"* — mas `obrigacao_fiscal.tipo` não |
|      | encargos, ou só na contabilidade?                                                             | tem fornecedores                                                                           |
| Q-24 | No DR, **Resultado Líquido = RAI − IRC**? O IRC entra como gasto no mês em que é **estimado** | Muda o valor da barra "Resultado Líquido"                                                  |
|      | ou em que é **pago**?                                                                         |                                                                                            |
| Q-25 | *"Mês corrente"* = mês de calendário ou período fiscal (trimestral)?                          | Define o filtro de datas do painel e do DR                                                 |

### 2.2 Fiscal

| ID   | Dúvida                                                                                     | Impacto / opções                                                                              |
| :--- | :----------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------------------------- |
| Q-08 | A taxa de **IRC 20 %** é constante de código ou **configurável**? (Na realidade há derrama | Proposta: configurável em ➕ `config_fiscal` e constante de código como *fallback* (padrão do |
|      | municipal e taxa reduzida de PME.)                                                         | projeto: `DEPOSIT_PERCENTAGE`)                                                                |
| Q-09 | A Segurança Social (e os subsídios) entram no **passivo**?                                 | `obrigacao_fiscal.tipo` já tem `seguranca_social`, mas sem base de cálculo associada          |
| Q-10 | O simulador fiscal **escreve** em `obrigacao_fiscal` ou é só simulação?                    | §13 diz que `valor_estimado` é introduzido à mão → proposta: **não escrever**, oferecer ação  |
|      |                                                                                            | explícita "criar obrigação com este valor"                                                    |

### 2.3 Recursos Humanos

| ID   | Dúvida                                                                                          | Impacto / opções                                                                            |
| :--- | :---------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------ |
| Q-11 | *"Subsídios"* dos efetivos: que parcelas (férias/Natal, 13.º/14.º)? Valor fixo, % do salário ou | Define colunas novas em `funcionario` e se o passivo é mensal ou provisionado               |
|      | campo livre?                                                                                    |                                                                                             |
| Q-12 | Criar/editar funcionário reutiliza `auth-register` (que já aceita gestor) ou cria endpoint      | Decide `admin-employee-save` vs reutilização; afeta validators e fluxo de credenciais       |
|      | dedicado? Quem define a **password inicial**?                                                   |                                                                                             |
| Q-13 | **Desativar** funcionário bloqueia o login? O que acontece a serviços pendentes, aceites e      | `utilizador` não tem `ativo`; só `funcionario.ativo`. Definir política antes de implementar |
|      | rotas futuras?                                                                                  |                                                                                             |
| Q-14 | A Segurança Social patronal entra no custo do trabalhador mostrado no RH?                       | Se sim, o RH precisa de mais dados do que `salario_base`                                    |

### 2.4 Promoções e campanhas

| ID   | Dúvida                                                                                                | Impacto / opções                                                                     |
| :--- | :---------------------------------------------------------------------------------------------------- | :----------------------------------------------------------------------------------- |
| Q-15 | Desconto **percentual**, **valor fixo** ou ambos? Acumulável com outra campanha?                      | Define `promocao.tipo_desconto`/`valor` e a regra de acumulação (a fixar como RN)    |
| Q-16 | A promoção é aplicada **automaticamente** (melhor desconto) ou escolhida pelo cliente? Existe código  | Muda o wizard (5/7 passos) e o resumo final                                          |
|      | de campanha?                                                                                          |                                                                                      |
| Q-17 | **A base dos recibos verdes (§11) é o `preco_praticado` com ou sem desconto?**                        | ⚠️ Afeta o rendimento do funcionário e a margem da plataforma (70/30) — **C-08**     |
| Q-18 | O **sinal de 10 %** (RN-03) e o `valor_total` passam a ser calculados sobre o valor **com desconto**? | Afeta a criação de agendamento e os testes existentes                                |
| Q-19 | As **tags sazonais** são lista fixa no código ou **editável** pelo gestor (CRUD de contexto)?         | Se editável, `promocao_contexto` precisa de CRUD e proteção contra remoção em uso    |
| Q-20 | Como se combinam promoções com serviços **"Apenas Loja"** (`requer_espaco_fisico = 1`) e com o canal? | Define se `promocao_canal` é obrigatório ou opcional (por omissão = ambos os canais) |

### 2.5 Notificações (sininho)

| ID   | Dúvida                                                                                                   | Impacto / opções                                                               |
| :--- | :------------------------------------------------------------------------------------------------------- | :----------------------------------------------------------------------------- |
| Q-21 | *"Renovação de contratos"* refere-se a contratos **de trabalho**, **de fornecimento** ou **de aluguer**? | Muda a origem do dado (funcionario, fornecedor, instalações)                   |
| Q-22 | *"Revisões da carrinha"*: **não existe entidade veículo** na BD (1 carrinha polivalente — §3.4/D-04). ➕ | Decisão de modelo; cruza com §3.8 (logística de condução é **fora de escopo**) |
|      | `veiculo`+`manutencao_veiculo` ou tratar como obrigação/despesa periódica?                               |                                                                                |
| Q-23 | O contador de alertas é **por utilizador** ou **global**? (`alerta_fiscal.visualizado` é global)         | Com 2+ gestores, o "lido" de um silencia o outro → **C-03**                    |
| Q-26 | Os lembretes mantêm-se **on-demand** (sem CRON — §22.1)? Nesse caso, "revisão da carrinha a 30 dias" só  | Confirmação de que o projeto continua sem CRON                                 |
|      | aparece quando o gestor abre o backoffice                                                                |                                                                                |
| Q-27 | O sino agrega **tudo** num contador ou separa por origem (fiscal · fornecedores · operacional)?          | Muda o componente do topo (`menuUserBo.php`) e a UX do gestor                  |

### 2.6 Transversais (UX, técnica e prazo)

| ID   | Dúvida                                                                                       | Impacto / opções                                                                        |
| :--- | :------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------------------- |
| Q-28 | Gráficos: **sem novas bibliotecas** (P7) — barras CSS/SVG próprias, ou autoriza-se           | Se autorizado, contradiz *"sem frameworks externos"* (`.clinerules` §2/§3)              |
|      | Chart.js/ApexCharts em `modules/common/lib`?                                                 |                                                                                         |
| Q-29 | A promoção altera preços do **modelo de referência dos testes** (Barba 4,07 € — §8.3). Como  | Proposta: *seed* sem promoções ativas e testes que ativam/desativam explicitamente      |
|      | manter as 289 verificações verdes?                                                           |                                                                                         |
| Q-30 | O âmbito total (painel + contabilidade + RH + promoções + área do funcionário) cabe no prazo | §25 diz que a ordem é **vinculativa** (Fornecedores → §24 → consolidações); pode exigir |
|      | académico?                                                                                   | faseamento explícito                                                                    |

## 3. CONFLITOS A RESOLVER MANUALMENTE

> Pontos em que os **novos requisitos colidem com regras/modelos já definidos** e que **não podem ser
> decididos por inferência**. Nenhum foi alterado: cada linha está à espera de decisão do gestor.

| ID   | Requisito novo                                | Regra / artefacto existente                                      | Natureza do conflito                    | Decisão necessária (recomendação)                    |
| :--- | :-------------------------------------------- | :--------------------------------------------------------------- | :-------------------------------------- | :--------------------------------------------------- |
| C-01 | Painel de entrada do gestor                   | `/gestao` **já é** a lista de agendamentos (`index.php` L30 →    | Duas funções para a mesma rota          | **`/gestao` = painel** e                             |
|      |                                               | `modules/backoffice/appointments.php`)                           |                                         | `/gestao/agendamentos` mantém a lista                |
|      |                                               |                                                                  |                                         | (rota já existe). Alternativa: painel                |
|      |                                               |                                                                  |                                         | em `/gestao/painel`                                  |
| C-02 | Lembretes de fornecedores, contratos e        | `obrigacao_fiscal.tipo` ∈                                        | Alargar o enum mistura encargos fiscais | Recomendação: ➕ `notificacao` genérica              |
|      | revisões da carrinha                          | {`iva`,`irc`,`seguranca_social`,`seguros`} + `alerta_fiscal`;    | com não fiscais; criar entidade nova    | (com `origem`) alimentada pelo mesmo                 |
|      |                                               | §13 define o calendário como **fiscal**                          | duplica mecanismos                      | padrão on-demand, e o                                |
|      |                                               |                                                                  |                                         | **calendário fiscal fica fiscal** (§13               |
|      |                                               |                                                                  |                                         | intacto). Contador = `alerta_fiscal` +               |
|      |                                               |                                                                  |                                         | `notificacao`                                        |
| C-03 | *Sininho* com contador e marcação de          | `alerta_fiscal.visualizado` é **global** (não por utilizador)    | Com 2+ gestores, marcar lido silencia   | Se houver **1 gestor** no MVP: manter                |
|      | lido                                          |                                                                  | os outros                               | global e registar como limitação                     |
|      |                                               |                                                                  |                                         | (§22.2). Se ≥ 2: ➕ tabela de leitura                |
|      |                                               |                                                                  |                                         | por utilizador                                       |
| C-04 | Ativos/Passivos, DR, tesouraria               | `transacao_financeira` é livro de **recebimentos**:              | **Não é possível registar despesas**    | **Alteração de BD obrigatória** (e                   |
|      |                                               | `agendamento_id` e `funcionario_id` **NOT NULL**,                | (fornecedores, salários, IVA, seguros)  | `.clinerules` exige justificação).                   |
|      |                                               | `tipo_transacao` só com recebimentos                             |                                         | Recomendação: via **(b)** de §B.2 — ➕               |
|      |                                               |                                                                  |                                         | `despesa` (+                                         |
|      |                                               |                                                                  |                                         | `fornecedor`/`fatura_fornecedor`) e                  |
|      |                                               |                                                                  |                                         | **não** deformar `transacao_financeira`              |
| C-05 | Simulador fiscal automático (RAI, IRC)        | §13: obrigações fiscais com `valor_estimado`                     | Automação vs lançamento manual (fonte   | Simulador **só lê e calcula** (não                   |
|      |                                               | **introduzido manualmente**; alertas 30/15/7/3/1/atraso          | de erro e de divergência de números)    | escreve); ação explícita                             |
|      |                                               |                                                                  |                                         | *"criar obrigação com este valor"*                   |
|      |                                               |                                                                  |                                         | mantém a decisão humana                              |
| C-06 | KPIs financeiros no painel                    | RN-05 / §3.1 / §12.3: 50 € é **indicador visual** e a decisão de | Risco de reintroduzir, pela porta do    | Confirmar proibição explícita: nenhum                |
|      |                                               | rotas é **livre e manual**                                       | dashboard, um **gatilho automático** de | KPI/alerta bloqueia ou decide rotas.                 |
|      |                                               |                                                                  | viabilidade                             | Registar como RN novo                                |
| C-07 | RH: criar / editar / desativar perfis         | §22.2:                                                           | Edição pelo gestor é **nova** e pode    | Esclarecer que §22.2 se refere ao                    |
|      |                                               | *"o perfil é de leitura (dados pessoais não editáveis no MVP)"*; | ser lida como revogação daquela         | **cliente em `/perfil`** e definir o                 |
|      |                                               | `auth-register` já permite ao gestor criar perfis                | limitação                               | âmbito do RH (só funcionários? password              |
|      |                                               |                                                                  |                                         | inicial? e-mail editável?)                           |
| C-08 | Promoções aplicadas a marcações               | §11 (base = `preco_praticado`), RN-03 (sinal 10 %),              | O desconto **propaga-se** a recibos     | Definir: (1) base dos 70/30 com/sem                  |
|      |                                               | `valor_total`, §5.2 (*"catálogo é somente leitura no Main"*) e   | verdes, sinal, margem e testes — e      | desconto; (2) sinal sobre valor com                  |
|      |                                               | os testes de referência                                          | altera preços apresentados no Main      | desconto; (3) se o Main mostra preço                 |
|      |                                               |                                                                  |                                         | promocional; (4) estratégia de testes                |
| C-09 | Ocultar módulos financeiros ao                | As páginas **não** são segregadas por perfil (o gestor abre      | Esconder no menu **não** é autorizar;   | Autorização **por página + endpoint**                |
|      | funcionário                                   | `/gestao/servicos`; só as APIs recusam)                          | sem enforcement server-side cria-se uma | (matriz de perfis) e testes de **403**               |
|      |                                               |                                                                  | falha de segurança                      | como já existe para `admin-service-*`                |
| C-10 | RH                                            | §11: valores de recibos verdes são **snapshot** na aceitação;    | Risco de **dupla contagem** (comissão   | Fixar **fonte única** de custo de pessoal: comissões |
|      | *"alimenta indiretamente os passivos/gastos"* | §25.3: `transacao_financeira`/`fecho_caixa_diario`/`gorjeta`     | gravada em `agendamento_servico` **e**  | do mês = Σ                                           |
|      |                                               | **sem UI**                                                       | lançada como despesa) e de divergência  | `agendamento_servico.valor_recibo_verde_funcionario` |
|      |                                               |                                                                  | de fontes                               | (**sem** novo lançamento); salários = `salario_base` |
|      |                                               |                                                                  |                                         | (+ subsídios a definir, Q-11)                        |
| C-11 | Card *"Dívidas a Fornecedores"* no painel     | **§25.1** (Fornecedores = prioridade 1, ainda ⬜) e §25 ordem    | O painel pede um indicador cujo módulo  | Sequenciar: **Fornecedores/despesas antes** da       |
|      |                                               | **vinculativa**                                                  | **não existe**; cria pressão para       | contabilidade completa (§1.9, fase 6.1) e mostrar    |
|      |                                               |                                                                  | inverter prioridades                    | estado vazio explícito no painel                     |
| C-12 | Módulos novos (contabilidade, RH, promoções)  | §22.3 (fora de escopo declarado) e §28 (critérios de aceitação)  | Acrescentar módulos                     | Decisão explícita de âmbito + registo em §4          |
|      |                                               | **não** os incluem; §4.5 só tem RF-50…RF-63                      | **altera o âmbito do MVP** e os         | (estado), §24 (gap), §28.2 (critérios) e §26         |
|      |                                               |                                                                  | critérios de aceitação                  | (testes) — ciclo §29.3.6                             |
| C-13 | Testes das novas funcionalidades              | 289 verificações atuais (§26.1) + §26.4 (cobertura em falta para | Promoções podem alterar preços de       | Definir por fase: testes *server-to-end* para        |
|      |                                               | a §24)                                                           | referência; módulos financeiros podem   | cálculo de IRC, passivos, filtro de vínculo e regras |
|      |                                               |                                                                  | mexer em dados partilhados              | de promoção, mantendo as 289 verdes                  |
| C-14 | IRC fixo em 20 %                              | Nenhuma regra fiscal no projeto define taxa; §13 só guarda       | Rigor real: taxa geral 20 %             | Confirmar **20 % fixo académico** (simples) ou       |
|      |                                               | `valor_estimado`                                                 | **+ derrama municipal** (até 9 %); PME  | **taxa configurável** (➕ `config_fiscal`); registar |
|      |                                               |                                                                  | tem taxa reduzida até 50 000 €          | como `D-nn`/`RN-nn` com o critério de aceitação      |
| C-15 | Tesouraria / fluxo de caixa                   | §14.2 (P-1…P-5): sinal configurável, cobrança dos 90 %, métodos  | A tesouraria depende de pagamentos que  | Decidir se a tesouraria (§6.2) só avança **depois**  |
|      |                                               | de pagamento — **ainda ⬜** (§24.5)                              | hoje são teóricos (`sinal_pago = 0`)    | de §24.5 (10/90 + métodos) ou se assume tudo como    |
|      |                                               |                                                                  |                                         | simulado                                             |
| C-16 | Área do funcionário (agenda própria)          | §10: a aceitação é a dinâmica central do funcionário; §22.2: o   | A "agenda do funcionário" pode          | Definir a diferença: agenda = serviços               |
|      |                                               | gestor supervisiona `/gestao/servicos`                           | confundir-se com a lista de aceitação e | **já aceites** por ele (passado/futuro), distinta da |
|      |                                               |                                                                  | com o `rota_funcionario` (as rotas não  | lista *"Por aceitar"* e da rota (que não é           |
|      |                                               |                                                                  | guardam filhos — §17.8)                 | persistida por agendamento)                          |

### 3.1 Checklist de decisões (para fechar esta fase)

| #   | Decisão                                                                                         | ID   | Estado |
| :-- | :---------------------------------------------------------------------------------------------- | :--- | :----- |
| 1   | `/gestao` passa a painel e a lista fica em `/gestao/agendamentos`                               | C-01 | ⬜     |
| 2   | Modelo de lembretes (entidade genérica vs extensão do calendário fiscal) + veículo/manutenção   | C-02 | ⬜     |
| 3   | Lido dos alertas: global ou por utilizador                                                      | C-03 | ⬜     |
| 4   | **Modelo de despesas**: criar `despesa` (+ fornecedor/fatura) ou alargar `transacao_financeira` | C-04 | ⬜     |
| 5   | Simulador fiscal não escreve no calendário fiscal                                               | C-05 | ⬜     |
| 6   | Reafirmar que nenhum KPI/indicador bloqueia ou decide rotas                                     | C-06 | ⬜     |
| 7   | Âmbito do RH (criar/editar/desativar) e fronteira com §22.2                                     | C-07 | ⬜     |
| 8   | Regras das promoções (base dos 70/30, sinal, preço no Main, testes)                             | C-08 | ⬜     |
| 9   | Autorização por página + endpoints (403 server-side)                                            | C-09 | ⬜     |
| 10  | Fonte única do custo de pessoal (comissões e salários)                                          | C-10 | ⬜     |
| 11  | Sequência: Fornecedores antes da contabilidade completa                                         | C-11 | ⬜     |
| 12  | Âmbito/critérios de aceitação: aceitar os 4 módulos novos como Fase 6 acrescida                 | C-12 | ⬜     |
| 13  | Plano de testes por fase                                                                        | C-13 | ⬜     |
| 14  | IRC: 20 % fixo (académico) ou configurável                                                      | C-14 | ⬜     |
| 15  | Tesouraria antes ou depois de §24.5 (10/90 + métodos)                                           | C-15 | ⬜     |
| 16  | Definição da "agenda do funcionário" face à aceitação e às rotas                                | C-16 | ⬜     |
| 17  | Questões Q-01…Q-30 (§2) — IVA, período, saldo inicial, subsídios, tags, contador, gráficos      | §2   | ⬜     |

**Depois das decisões:** registar em `especificacao_mvp.md` (é o único documento normativo) como
`RF-80+`/`RN-30+`/`D-12+` (§1.10) e só então implementar — ciclo de §29.3.

---

**Ficheiro:** `mapaMentalMVP/analise_backoffice_gestor.md` · **branch:** `agent-workspace` ·
**Nota:** documento de apoio à decisão, **não normativo**; a autoridade é `especificacao_mvp.md` (§29.2).
