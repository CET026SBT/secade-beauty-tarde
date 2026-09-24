# ANÁLISE FUNCIONAL — BACKOFFICE DO GESTOR (PROPOSTA DE FUSÃO)

<!-- md-wrap-tables:max=220 — a tabela do ponto 3 tem 5 colunas com muitos spans de
     código longos; 217 colunas é o mínimo possível sem partir palavras ao meio. -->

**Ficheiro de trabalho (não normativo)** · branch **`agent-workspace`** · **3.ª iteração** · 23/09/2026

**Entrada analisada (iteração 1):** requisitos refinados do *Painel de Backoffice — Tipo de Conta: Gestor*
(Resumo/Dashboard · Contabilidade e Gestão Financeira · Recursos Humanos · Promoções e Campanhas) +
antecipação do perfil **Funcionário**.

**Entrada analisada (iteração 2):** três requisitos de **frontend/catálogo**
(§1.11): secção resumida de serviços no **Home** e no **About** · **IVA incluído** nos valores mostrados
ao cliente · **imagens nos cards** de serviço do wizard de agendamento. Mais os **INPUTs** de
esclarecimento (perguntas do cliente) — ver §2.7 e o ficheiro `mensagem_teams.txt`.

**Entrada analisada (iteração 3 — esta):** dois **templates informativos** entregues pelo cliente:
`mapaMentalMVP/Menu APOIO 3.docx` (estrutura de menus do software de contabilidade + 3 capturas com o
**mapa de células** do balancete) e `mapaMentalMVP/CUSTOS RH 2.xlsx` (folha de cálculo do custo de pessoal:
REMUNERAÇÃO / SS / IRS). São **modelos**, não dados reais — as versões concretas virão com os ficheiros de
**balancete** a importar. Cruzamento e achados em **§1.12 (Módulo F)**; dúvidas novas em **§2.10**;
conflitos novos em **C-25…C-30**; prova de verificação em **§4**.

**Revisão desta iteração (auditoria de proveniência):** os dois avisos não fiscais que este documento
tratava como requisito — *"renovação de contratos"* e *"revisões da carrinha"* — foram submetidos a
prova de origem: **não têm nenhum ficheiro que os exija** → **§2.5.1**; as perguntas 18 e 19 do
`mensagem_teams.txt` foram reformuladas em conformidade.

**Revisão desta iteração (auditoria de artefactos):** além da proveniência dos avisos tratada acima,
foi feito um **varrimento de artefactos** a todo este documento. Tudo o que estava afirmado **sem
prova** (ficheiro + linha) está identificado, com a prova e a correção, em
**`mapaMentalMVP/auditoria_artefactos.md`** — casos **F-01…F-07**, aplicados em §1.3 · §1.7 ·
§1.10 · §1.11 e §2.5.1.

**Cruzado com:** `especificacao_mvp.md` v1.1 (§2–§5, §11–§13, §17–§19, §22, §24–§26, §28, §29), os
**templates da 3.ª iteração** (`Menu APOIO 3.docx` · `CUSTOS RH 2.xlsx`) e o código/BD reais (`index.php`,
`app/config/api.php`, `app/{controllers,services,repositories}`, `modules/main/`, `modules/backoffice/`,
`DataBase_v2.sql` · `DataBase_v3.sql`).

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
| P7  | **Sem novos pacotes/frameworks** (gráficos com CSS/SVG ou libs já presentes)      | `.clinerules` §1      |
| P8  | Repository: JOIN apenas N:1 de lookup; escrita na própria tabela                  | §18.2                 |
| P9  | Contrato de nomes front-end ↔ API; validators em `validators/`                    | §18.7 · `.clinerules` |
| P10 | Requisito novo → primeiro a especificação (`RF-nn`/`RN-nn`/`D-nn`), depois código | §29.3                 |

### 1.2 Quadro-resumo (o que entra onde)

| Requisito novo                   | Página                               | Endpoints propostos                  | Camadas                               | BD: reutilizar ✚ / criar ➕                          | Estado |
| :------------------------------- | :----------------------------------- | :----------------------------------- | :------------------------------------ | :---------------------------------------------------- | :----: |
| KPIs Gastos vs Rendimentos ·     | `/gestao`                            | `admin-dashboard-summary`            | ManagerService +                      | agregações `fetchRaw` sobre ✚ (sem tabela nova)      | 🟡     |
| Dívidas                          |                                      |                                      | DashboardService                      |                                                       |        |
| Sininho (contador, lista, marcar | barra superior                       | `admin-alert-summary` ·              | AlertService + FiscalService          | `alerta_fiscal.visualizado` ✚ + origem extensível ➕ | 🟡     |
| lido)                            |                                      | `admin-alert-list` ·                 |                                       |                                                       |        |
|                                  |                                      | `admin-alert-read`                   |                                       |                                                       |        |
| Lembretes:                       | barra superior                       | idem (tipos novos)                   | AlertService + SupplierService        | ➕ `notificacao` **ou** extensão de                   | ⬜     |
| fornecedores/contratos/carrinha  |                                      |                                      |                                       | `obrigacao_fiscal`                                    |        |
| Ativos e Passivos + margem       | `/gestao/contabilidade`              | `admin-accounting-balance`           | AccountingService +                   | ✚ agendamento/rota_ambulante/agendamento_servico; ➕ | ⬜     |
| global                           |                                      |                                      | AccountingRepository                  | despesas                                              |        |
| Demonstração de Resultados       | `/gestao/contabilidade`              | `admin-accounting-income-statement`  | AccountingService + JS de barras      | idem                                                  | ⬜     |
| (barras)                         |                                      |                                      | CSS/SVG                               |                                                       |        |
| Simulador fiscal (RAI, IRC 20 %) | `/gestao/contabilidade`              | `admin-accounting-tax-simulate`      | AccountingService +                   | ➕ taxa configurável (`config_fiscal`)                | ⬜     |
|                                  |                                      |                                      | FiscalService                         |                                                       |        |
| Balancete bancário + fluxo de    | `/gestao/contabilidade`              | `admin-accounting-treasury`          | AccountingService                     | ✚ `transacao_financeira` (hoje **sem UI**) ·         | ⬜     |
| caixa                            |                                      |                                      |                                       | `fecho_caixa_diario`                                  |        |
| Controlo de IVA (apuramento)     | `/gestao/contabilidade`              | `admin-accounting-vat`               | AccountingService +                   | ✚ `obrigacao_fiscal(tipo=iva)` + ➕ taxa por serviço | ⬜     |
|                                  |                                      |                                      | FiscalService                         |                                                       |        |
| Dívidas a fornecedores           | `/gestao/fornecedores`               | `admin-supplier-*` (§19.5 já os      | SupplierService +                     | ➕ `fornecedor` + ➕ `fatura_fornecedor`              | ⬜     |
|                                  |                                      | prevê)                               | SupplierRepository                    |                                                       |        |
| RH: listagem + filtro por        | `/gestao/equipa`                     | `admin-employee-list`                | EmployeeService ✚                    | ✚ `funcionario.tipo_contrato`/`ativo` + `utilizador` | 🟡     |
| vínculo                          |                                      |                                      |                                       |                                                       |        |
| RH: adicionar / editar /         | `/gestao/equipa`                     | `admin-employee-save` ·              | EmployeeService +                     | ✚ `funcionario.ativo` (soft-delete)                  | ⬜     |
| desativar                        |                                      | `admin-employee-toggle`              | UserService/AuthService               |                                                       |        |
| RH → passivos (comissões,        | `/gestao/contabilidade`              | (consumido pelo balanço)             | AccountingService +                   | ✚                                                    | 🟡     |
| salários)                        |                                      |                                      | GreenReceiptService                   | `agendamento_servico.valor_recibo_verde_funcionario`, |        |
|                                  |                                      |                                      |                                       | `funcionario.salario_base`                            |        |
| Promoções: campanhas com datas   | `/gestao/promocoes`                  | `admin-promotion-list` ·             | PromotionService +                    | ➕ `promocao`                                         | ⬜     |
|                                  |                                      | `admin-promotion-save`               | PromotionRepository                   |                                                       |        |
| Tags sazonais (Natal, Verão, …)  | `/gestao/promocoes`                  | `admin-promotion-context-*`          | PromotionService                      | ➕ `promocao_contexto`                                | ⬜     |
| Matriz de impacto (serviços)     | `/gestao/promocoes`                  | `admin-promotion-service-*`          | PromotionService                      | ➕ `promocao_servico` (N:N)                           | ⬜     |
| Aplicação ao agendamento (2      | `/agendar` (wizard)                  | `booking-promotion-*`                | BookingService +                      | ✚ `agendamento_servico.preco_praticado` + ➕ canal   | ⬜     |
| canais)                          |                                      |                                      | PromotionService                      |                                                       |        |
| Funcionário: agenda própria      | `/gestao/agenda`                     | `employee-agenda-list`               | BookingService ✚ + RBAC              | ✚ (dados existem)                                    | ⬜     |
| Funcionário: comissões           | `/gestao/agenda`                     | `employee-commission-list`           | GreenReceiptService ✚                | ✚ (dados existem em `agendamento_servico`)           | 🟡     |
| individuais                      |                                      |                                      |                                       |                                                       |        |
| Funcionário: promoções só de     | `/gestao/promocoes`                  | `admin-promotion-list` (perfil       | PromotionService + RBAC               | ➕                                                    | ⬜     |
| leitura                          |                                      | autorizado)                          |                                       |                                                       |        |
| Menu/permissões por perfil       | `boNavbar`/`menuUserBo`              | (transversal)                        | Session ✚ + mapa de permissões       | ➕ (código/config, sem tabela)                        | 🟡     |
|                                  |                                      |                                      | por perfil                            |                                                       |        |
| Secção resumida de serviços      | `/` (Home) e `/sobre`                | (sem endpoint — estático)            | Componente PHP partilhado (padrão do  | ✚ sem BD nova                                        | ⬜     |
|                                  |                                      |                                      | `about.php`)                          |                                                       |        |
| **IVA incluído** nos valores     | todas as listagens, wizard e tickets | (derivação/config)                   | `ServiceMapper`/`BookingService` +    | ➕ taxa (config/coluna) + valores no snapshot         | ⬜     |
|                                  |                                      |                                      | config                                |                                                       |        |
| **Imagem nos cards de serviço**  | `/agendar` (wizard) e `/servicos`    | `booking-services` (já existe; ganha | `ServiceRepository` + `ServiceMapper` | ✚ `servico_foto` (existe, dormente) + *seed* +       | ⬜     |
|                                  |                                      | `imageUrl`)                          |                                       | fallback                                              |        |

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

| Peça                                        | Já existe?                                                             | Proposta                                                              |
| :------------------------------------------ | :--------------------------------------------------------------------- | :-------------------------------------------------------------------- |
| Persistência de alertas                     | ✅ `alerta_fiscal` (`tipo_alerta` 30/15/7/3/1/atraso, `data_alerta`,   | Reutilizar como **fonte primária** do contador:                       |
|                                             | `visualizado`, chave única)                                            | `COUNT(*) WHERE visualizado = 0`                                      |
| API de alertas                              | ✅ `admin-fiscal-alert-list` · `admin-fiscal-alert-read` (§19.3)       | Manter e acrescentar `admin-alert-summary` (contador para a barra)    |
| Construção dos alertas                      | ✅ `FiscalService::generateAlerts()` on-demand e idempotente (§13)     | Manter o mesmo padrão para os novos tipos de lembrete (sem CRON — P6) |
| Local na UI                                 | ✅ `modules/backoffice/components/menuUserBo.php` +                    | O sino entra **no menu do utilizador** (barra superior), como pedido  |
|                                             | `modules/backoffice/includes/boNavbar.php` (⚠️ corrigido — a auditoria |                                                                       |
|                                             | de artefactos **F-03**; não existe `includes/` na raiz)                |                                                                       |
| Lembretes a fornecedores/contratos/carrinha | ⬜ (o enum `obrigacao_fiscal.tipo` só tem                              | **Extensão de modelo necessária** → decidir entre extensão do enum ou |
|                                             | `iva`,`irc`,`seguranca_social`,`seguros`)                              | entidade nova (conflito **C-02**)                                     |
| "Lido" por conta                            | 🟡 `alerta_fiscal.visualizado` é **global** (não por utilizador)       | Com 2+ gestores, quem marca "lido" silencia os outros → decisão em    |
|                                             |                                                                        | **C-03**                                                              |

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

| Indicador pedido               | Fonte real já existente                                                                | Nota                                                                  |
| :----------------------------- | :------------------------------------------------------------------------------------- | :-------------------------------------------------------------------- |
| Rendimentos (loja vs carrinha) | `agendamento.valor_total` + `local_prestacao` (`loja_fisica`/`carrinha_ambulante`) +   | por `data_hora_pretendida` (mês corrente), separável por canal ✅     |
|                                | `estado_reserva`                                                                       |                                                                       |
| Recebimentos                   | `transacao_financeira` (`sinal_inicial`, `restante_90_porcento`, `pagamento_integral`, | ⚠️ **sem UI** (§25.3) e `sinal_pago` está sempre `0` (§14.1)          |
|                                | `quota_parte_deslocacao`)                                                              |                                                                       |
| Custos de rota                 | `rota_ambulante.custo_estimado_combustivel`, `lucro_servicos`, `lucro_total`           | já calculados no módulo de rotas (§12.2). ⚠️ **F-06:** o "custo fixo" |
|                                |                                                                                        | de 50 € do código (`RotaService` L22) **coincide** com a referência   |
|                                |                                                                                        | visual de 50 € (L23) — decidir antes de somar "Gastos" (auditoria)    |
| Obrigações fiscais             | `obrigacao_fiscal` + `alerta_fiscal`                                                   | `valor_estimado` é **introduzido à mão** (§13)                        |
| Prestadores (recibos verdes)   | `agendamento_servico.valor_recibo_verde_funcionario` + `config_recibo_verde`           | valor **snapshot** por aceitação (§11)                                |
| Efetivos                       | `funcionario.salario_base`                                                             | ⚠️ subsídios / 13.º-14.º mês **não** estão modelados                  |
| Caixa por funcionário/dia      | `fecho_caixa_diario` (`total_esperado_faturas`, `total_recolhido_campo`, `diferenca`)  | ⚠️ **sem UI** (§25.3)                                                 |
| Gorjetas                       | `gorjeta`                                                                              | sem UI (§25.3)                                                        |

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

> 📌 **3.ª iteração.** O modelo **concreto** de ativos + depreciações, financiamentos (empréstimo) e a
> decomposição do custo de pessoal chegaram nos templates do cliente — ver **§1.12 (F.4/F.5/F.6)**. O gap
> estrutural de **B.2** mantém-se: **nada disto existe na BD** (varrimento: não há `ativo`, `emprestimo`,
> `despesa` nem qualquer campo de depreciação).

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

**Modelo proposto (➕ 4 tabelas — está em `.clinerules` que alterações de BD exigem justificação, ver `.clinerules` §1)**

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

> ⚠️ **F-04 (auditoria de artefactos):** este documento usa **três** prefixos para a mesma área —
> `employee-*` (§1.7/§1.9) · `admin-employee-*` (§1.5/§1.9) · e o real **`admin-service-*`**
> (§19.2, 4 endpoints). Escolher **um** antes de escrever qualquer endpoint (viola o P3).

**Estado real (verificado):** as **páginas** do backoffice **não** são segregadas por perfil — o gestor abre
`/gestao/servicos` em supervisão —, mas as **APIs** são: `admin-service-*` recusa aceitar ao gestor com **403** (§22.2).
Ou seja, já existe autorização **por endpoint**; falta autorização **por página/menu**.

**Proposta**

1. **Mapa de permissões por perfil** (código/config, sem tabela nova): página → perfis; endpoint → perfis
   (o mecanismo real é **`Session::requireProfileApi([...])`** — ex.: `AdminController` L18,
   `FiscalController` L21; ⚠️ a auditoria de artefactos **F-02** corrigiu a referência anterior a
   `requireManager()`/`requireEmployee()`, que **não existem** no código).
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
> ⚠️ **F-05 (auditoria de artefactos):** a lista abaixo começa em **RF-80** — ou seja, **RF-75…RF-79
> ficam sem uso nem reserva declarada**. Alinhar (começar em RF-75) ou justificar a reserva.

| ID sugerido   | Tema a registar                                                                                                                                                                    |
| :------------ | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RF-80..RF-84  | Painel do gestor: KPIs de gastos/rendimentos, dívidas a fornecedores, sininho com contador                                                                                         |
| RF-85..RF-88  | Contabilidade: ativos/passivos, DR, simulador fiscal (RAI + IRC), tesouraria e IVA                                                                                                 |
| RF-89..RF-90  | RH: listagem com filtro de vínculo; criar/editar/desativar perfil                                                                                                                  |
| RF-91..RF-95  | Promoções: campanhas com datas, tags sazonais, matriz de impacto, aplicação nos 2 canais                                                                                           |
| RF-96..RF-98  | Área do funcionário: agenda própria, comissões individuais, promoções em leitura                                                                                                   |
| RF-99..RF-102 | Frontend: secção resumida de serviços no Home/About · **preços com IVA incluído** · **imagem por serviço nos cards** · IVA apurado para apuramento                                 |
| RN-30..RN-35  | Regras de cálculo: resultado mensal, IRC (0 se RAI ≤ 0), comissões como passivo, desconto no `preco_praticado`, efeito no sinal, critério de período                               |
| D-12..D-16    | Decisões a fixar em §3: modelo de despesas/passivos · modelo de notificações · autorização por página · **origem dos dados (interno vs ficheiros)** · **regime de IVA dos preços** |

### 1.11 Módulo E — Frontend e catálogo (requisitos da 2.ª iteração)

**E.1 Secção resumida de serviços no Home **e** na página About**

| Facto verificado                                                                                                     | Consequência                                                                                   |
| :------------------------------------------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------------------- |
| `home.php` compõe `hero` + `about` + `serviceCategories` + `testimonial`                                             | O Home já junta componentes institucionais reutilizáveis — **não** é preciso rota nova         |
| `components/about.php` é usado nas **duas** páginas (o botão *"Mais sobre nós"* só aparece quando **não** estamos no | **O padrão pedido já existe** para o About; basta replicá-lo para os serviços                  |
| About — `$currentPage != "about.php"`)                                                                               |                                                                                                |
| Já existe `serviceCategories.php` (3 cards de categoria, com imagens) e o catálogo `/servicos`                       | A secção pedida é **texto institucional** ("o que fazemos"), não o catálogo — não duplica nada |
| Não existe nenhuma secção que **descreva os serviços prestados**                                                     | ⬜ **novo**                                                                                    |

**Proposta:** novo componente partilhado `modules/main/components/servicesSummary.php` — 3 blocos
(cabeleireiro · barbearia · estética) + texto resumido + CTA para `/servicos` — incluído em
`home.php` e na página `/sobre`, **exatamente** com o padrão do `about.php`. **Sem BD nova**: é conteúdo
institucional estático (como `hero.php` e `about.php`), e evita uma tabela de CMS fora do âmbito.

**E.2 IVA incluído nos valores mostrados ao cliente — o achado desta iteração**

Pergunta do cliente: *"de onde vamos buscar esse IVA? (ver se há alguma informação sobre isso na base de dados)"*.

**Resposta: não existe informação de IVA em lado nenhum da BD.** A única ocorrência de "IVA" no schema é
`obrigacao_fiscal.tipo = 'iva'` (calendário fiscal) — que é uma **obrigação a pagar**, não uma taxa de
venda. Nem `servico`, nem `agendamento`, nem `agendamento_servico`, nem `transacao_financeira` têm taxa
ou valor de imposto.

**Mas os próprios preços denunciam a taxa.** Testei os 35 serviços: **35 de 35 `servico.preco_base` × 1,23
dão valores exatos e redondos** —

| Serviço               | `preco_base` | × 1,23 (valor de montra) |
| :-------------------- | :----------- | :----------------------- |
| Barba                 | 4,07 €       | **5,00 €**               |
| Design de Sobrancelha | 8,13 €       | **10,00 €**              |
| Corte de Cabelo       | 12,20 €      | **15,00 €**              |
| Limpeza Facial        | 24,39 €      | **30,00 €**              |
| Trança Twist          | 40,65 €      | **50,00 €**              |
| Cordelete / Retro     | 48,78 €      | **60,00 €**              |

**Conclusão (verificada, não inferida):** `preco_base` é o valor **LÍQUIDO (sem IVA)** e a taxa
implícita é **23 %** (taxa geral de IVA em Portugal continental). Os preços "de montra" são os valores
redondos. Consequência imediata: **`agendamento.valor_total` é hoje a soma de valores líquidos**, ou seja
**o cliente está a ver preços sem IVA** — para consumidor final os preços ao público têm de incluir IVA.

**Duas vias de fusão (decisão em C-17):**

| Via                         | O que implica                                                                                                                                                | Avaliação                |
| :-------------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------- | :----------------------- |
| (a) Derivar na apresentação | Manter a BD líquida e multiplicar na UI (`preco × (1 + taxa)`); taxa em configuração. Zero alterações de schema, mas o valor **cobrado** não fica gravado em | ⚠️ Frágil para faturação |
|                             | nenhum lado                                                                                                                                                  |                          |
| (b) Gravar o bruto          | ➕ taxa (configurável, com *fallback* global) e persistir **valor líquido + taxa + IVA + total bruto** em `servico` e em `agendamento_servico`               | ✅ **Recomendada**       |

A via (b) segue o **mesmo racional do snapshot dos recibos verdes** (§11): o valor tem de ficar
**congelado no momento da marcação**, porque uma alteração futura da taxa **não pode** reescrever
marcações antigas (faturação e testes).

**Impactos obrigatórios a tratar em conjunto (ver C-17 e C-18):** `valor_total` · sinal de 10 % (RN-03) ·
base dos recibos verdes 70/30 (§11) · **filtro de preço do catálogo** (`services.php` tem
`max="50"` e `services.js` `maxPrice: 50` → com IVA o serviço mais caro passa a 60,00 € e **sai do
filtro**) · exemplos de teste (§8.3: Barba 4,07 €) · resumo/ticket do wizard.

**E.3 Imagens nos cards de serviço do wizard**

| Facto verificado                                                                                                             | Consequência                                                         |
| :--------------------------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------- |
| `servico_foto` **existe** (`servico_id`, `url_foto`, `destaque`, `ordem_exibicao`, FK CASCADE)                               | Tabela **já criada para isto** — não é preciso inventar nada         |
| `servico_foto` tem **0 registos** e **não é referida em nenhum `.php`/`.js`** (varrimento completo)                          | É uma tabela **dormente** desde o schema inicial                     |
| `servico` **não tem** coluna de imagem                                                                                       | O campo pedido "não existe" — mas a alternativa (a tabela) já existe |
| **Precedente de convenção:** os cards de categoria carregam a imagem por **slug do nome** → `/modules/common/img/<slug>.png` | Já há um padrão de imagens no projeto que se pode seguir             |
| (`serviceCategories.js`)                                                                                                     |                                                                      |
| Não existem imagens por serviço em `modules/common/img` (só 3 de categoria em uso num total de 7                             | 35 serviços precisam de imagens **e** de um *fallback*               |
| `barbearia`·`cabelereiro`·`estetica`·`manicure`·`massage`·`pedicure`·`skin-care` — **F-07** — + 4 de                         |                                                                      |
| equipa + 4 testemunhos)                                                                                                      |                                                                      |

O cliente pediu *"criar um campo na base de dados com um link para a respetiva imagem"*. Há duas vias:

| Via                                | O que implica                                                                                                                      | Avaliação          |
| :--------------------------------- | :--------------------------------------------------------------------------------------------------------------------------------- | :----------------- |
| (a) ➕ coluna `servico.url_imagem` | 1 imagem por serviço, simples, atende ao pedido **literalmente** — mas deixa `servico_foto` órfã e duplica a modelação             | ⚠️ Duplica         |
| (b) Usar `servico_foto`            | **A tabela já existe e foi feita para isto**: `destaque=1` é a imagem do card, `ordem_exibicao` ordena, e as restantes alimentam o | ✅ **Recomendada** |
|                                    | **carousel da página de detalhe** (§24.2)                                                                                          |                    |

**Recomendação:** via (b) — resolve **os três** casos com **zero alterações de schema** (cards do wizard ·
cards do catálogo · carousel do §24.2) e respeita a restrição de não alterar a BD sem justificação.
Contrapartida: exige *seed* de imagens e um **fallback** obrigatório (sem foto → imagem por omissão por
categoria), porque 35 serviços sem imagem dariam cards quebrados.

**Encaixe:** `ServiceRepository` passa a devolver a imagem de destaque no mesmo SELECT (JOIN permitido
**N:1** de lookup — §18.2) → `ServiceMapper` expõe-a como `imageUrl`; o contrato de nomes passa a ter
`imageUrl` nos cards do catálogo e do wizard (`.clinerules`: a API dita o contrato).

### 1.12 Módulo F — Contabilidade de apoio ("MENU APOIO") e custo de pessoal (templates da 3.ª iteração)

> **Natureza dos ficheiros.** `Menu APOIO 3.docx` e `CUSTOS RH 2.xlsx` são **templates informativos** (modelos),
> não dados reais: o `Menu APOIO` descreve a **estrutura de menus de um software de contabilidade** e inclui
> 3 capturas desse software; o `CUSTOS RH` é a folha de cálculo que produz os números de pessoal. A entidade
> das capturas (**"Empresa Solução Certa", NIF 513122095**, ativos `2026.00001..4`, classe 435 *Equipamento
> administrativo*) é **de exemplo** — confirmar em **Q-48**. As versões concretas devem chegar com os ficheiros
> de **balancete** a integrar.

#### F.1 O requisito real escondido nos dois ficheiros: o **mapa de células**

O `Menu APOIO 3.docx` é, na prática, um **guião** dirigido aos alunos do CET. A instrução repete-se em quase
todos os menus:

> *"enviar o excel do balancete **e identificar aos colegas do CET as células que devem ir buscar** para os
> Card's"* — e, no RH: *"identificar as células que devem ir buscar pra os Card's"*.

Ou seja: o cliente **já tem** a informação contabilística (num software que **exporta Excel**) e o que pede é um
**mapeamento declarativo** — *"este card lê a célula J16 do balancete"*. Isto **não** é o mesmo que *"importar um
ficheiro para as tabelas"*:

| Via                           | O que exige                                                                        | Avaliação                                 |
| :---------------------------- | :--------------------------------------------------------------------------------- | :---------------------------------------- |
| (a) Importar para tabelas     | Escrever N registos em tabelas novas, por período, com validação e histórico       | ⚠️ Amplo; cria **duas fontes** do mesmo   |
|                               |                                                                                    | número (B.2 · C-20)                       |
| (b) **Mapa de células (ler)** | Guardar **de onde vem cada número** (ficheiro + folha + célula) e ler na altura do | ✅ **Aderente ao que o cliente descreve** |
|                               | apuramento                                                                         |                                           |

A decisão entre as duas fica em **C-29**. Nota metodológica: a via (b) **não escreve** na BD de negócio — logo
**não colide** com "não alterar a BD sem justificação" (`.clinerules` §1); só precisa de um **mapa** (ficheiro de
configuração ou tabela de apoio, a decidir).

> ⚠️ **Correção de uma afirmação da 2.ª iteração (C-19 · Q-43).** Ficou escrito que `.xlsx` *"é binário e exigiria
> biblioteca externa"*. A verificação desta iteração **desmente** essa afirmação: **F.7**. Ver **C-30**.

#### F.2 Os 6 menus que o cliente quer (estrutura do "MENU APOIO")

| Menu pedido            | Card's pedidos                                            | Origem declarada pelo cliente                              |
| :--------------------- | :-------------------------------------------------------- | :--------------------------------------------------------- |
| **Dashboard**          | Disponibilidades · Dívidas a receber · Dívidas a pagar    | Células do **balancete** (F.3); gráficos opcionais         |
| **Rácios**             | Ativo corrente · Ativo não corrente · Capital próprio ·   | Células do balancete; *fundo de maneio* opcional; imagem   |
|                        | Passivo não corrente                                      | do *balanço funcional* (opcional)                          |
| **Ativos** *(dividir)* | Lista de inventário de ativos + **Mapa de depreciações**  | **2 exports**: inventário de ativos e balancete de ativos  |
|                        |                                                           | /depreciações (**F.5**)                                    |
| **Financiamentos**     | Capital inicial · Capital amortizado · Capital em dívida  | **Plano de amortizações do empréstimo** (Excel)            |
| **Análise financeira** | Rendimentos · Gastos · **RAI**                            | **DR em Excel** + *caixa* para a **taxa de IRC** e *caixa* |
|                        |                                                           | com o **valor de IRC a pagar**                             |
| **Recursos Humanos**   | *(dividir)* Funcionários · Impostos (**DMR** e **DRI**) · | `CUSTOS RH 2.xlsx` (**F.4**); PDF/imagem das declarações   |
|                        | Custos de funcionários                                    |                                                            |

Pedidos transversais do documento: **"podem adicionar gráficos"**, opção **mensal / trimestral / anual**, e um
gráfico específico do **RH** — *"custos de salários divididos pela tipologia: REMUNERAÇÃO, SS, IRS"* — e outro em
*"CUSTOS FUNCIONÁRIOS"*: *"relacionar o valor da remuneração com o valor que o funcionário recebe"*.

#### F.3 O **mapa de células** já está desenhado nas capturas (imagem 1)

A 3.ª imagem do `.docx` é um **manuscrito** com o mapeamento que o cliente quer ver implementado. Reprodução
literal (as referências são **células do ficheiro do balancete**):

```text
Dashboard
  Disponibilidades = 16 954,73      ->  "vai buscar ao balancete"
        caixa 246  +  banco 16 708,73   =>  J13 / J25
  Dívidas a receber  = 0 €          =>  J16
  Dívidas a pagar    ->  fornecedores          K48
                         retenções/impostos    K85
                         IVA                   K86
                         Segurança Social      K113
                         (empresa?)            K118
```

**Verificação aritmética (feita, não assumida):** `246,00 + 16 708,73 = 16 954,73` ✔ — a Disponibilidades do card
é **exatamente** caixa + banco. Prova em **§4**.

**Quatro consequências diretas deste mapa:**

1. **A "Disponibilidades" confirma a existência de caixa e banco separados** — a tesouraria **não** é um saldo
   único. Responde parcialmente a **Q-04** (que perguntava se havia uma ou várias contas) e reforça a proposta de
   `conta_bancaria` **ou**, no mínimo, **dois parâmetros** (caixa · banco).
2. **"Dívidas a receber = 0 €"** é hoje **coerente** com o sistema: os recebimentos são registados à cabeça e
   `sinal_pago` está sempre `0` (§14.1 · Q-03) — não há crédito a clientes. Serve de **caso de teste** (deve dar 0).
3. **"Dívidas a pagar" decompõe-se por natureza**, não num total: fornecedores · impostos (retenções na fonte) ·
   **IVA** · Segurança Social. É a **mesma decomposição** que `obrigacao_fiscal.tipo` já usa
   (`iva` / `irc` / `seguranca_social` / `seguros`) — logo há encaixe direto com o **calendário fiscal** (§13).
4. **O IVA aparece como dívida a pagar** e **não** como taxa de venda. Confirma o que já se tinha verificado na
   2.ª iteração (§E.2): na BD **não existe taxa de IVA em lado nenhum**; o IVA do balancete é **IVA a pagar ao
   Estado** — o mesmo conceito de `obrigacao_fiscal(tipo='iva')`. Reforça **C-17** e **Q-36**.

#### F.4 `CUSTOS RH 2.xlsx` — a estrutura do custo de pessoal, decifrada

Uma folha (`Folha1`), 9 cabeçalhos, 6 linhas de trabalhador. É a **fórmula completa** de pessoal, com o
**subsídio de alimentação a aparecer explicitamente** — o que responde a perguntas que estavam em aberto.

| Coluna | Cabeçalho           | Fórmula real no ficheiro           | Significado                                    |
| :----- | :------------------ | :--------------------------------- | :--------------------------------------------- |
| C      | `remuneração`       | valor base mensal (1200/1000/1250) | salário base do trabalhador                    |
| D      | `SA`                | `129,15*3` = **387,45**            | **subsídio de alimentação** (129,15 €/mês × 3) |
| E      | `n meses`           | 3                                  | período                                        |
| F      | `REMUNERAÇÃO`       | `C*E`                              | remuneração bruta do período = 19 950,00 €     |
| G      | `REMUNERAÇÃO` (2.ª) | `F+D`                              | **bruta + SA** = 22 274,70 €                   |
| H      | `IRS`               | `F*0,08` · `F*3,6%` · `F*8,56%`    | retenção na fonte de IRS (**por escalão**)     |
| I      | `SS 11`             | `F*0,11`                           | Segurança Social do **trabalhador**            |
| J      | `SS 23,75`          | `F*0,2375`                         | Segurança Social da **entidade patronal**      |
| K      | `PAGAR AO PESSOAL`  | `G9-H9-I9`                         | líquido a pagar ao pessoal = 18 859,20 €       |

**As 4 linhas de rodapé (o que o cliente quer nos Card's)** — e o comentário:

| Rótulo             | Valor       | De onde vem | Comentário                                                              |
| :----------------- | :---------- | :---------- | :---------------------------------------------------------------------- |
| `REMUNERAÇÃO`      | 22 274,70 € | `G9`        | inclui o **subsídio de alimentação** (responde a **Q-09/Q-11**)         |
| `IRS`              | 1 221,00 €  | `H9`        | é o IRS **retido** (dívida da empresa ao Estado), não um custo próprio  |
| `SS`               | 6 932,63 €  | `I9+J9`     | ✅ **junta trabalhador + empresa** — é a leitura contabilística correta |
| *(K9, disponível)* | 18 859,20 € | `G9-H9-I9`  | o **líquido** a pagar ao pessoal (o cliente não lhe deu Card)           |

**Leituras críticas (o que isto altera no que estava planeado):**

1. **`SA = 129,15 €/mês` = 6,15 €/dia × 21 dias úteis** (verificado em §4). **Não** vem de tabela nenhuma: é dado
   de entrada → mais um parâmetro de configuração. Cruza com **Q-11** ("que subsídios?"): agora sabe-se que **é** o
   de alimentação; o **13.º/14.º mês continuam não modelados**.
2. **A taxa de IRS varia *dentro* da mesma folha** (8 % · 3,6 % · 8,56 %). Prova que **a retenção de IRS não é uma
   percentagem da empresa**: depende do salário **e** da situação pessoal do trabalhador. **Não é derivável** de
   `salario_base` — tem de ser **introduzida** (ou lida por célula, F.1(b)) → **C-28**.
3. **Segurança Social = 11 % + 23,75 % = 34,75 %** sobre a remuneração base. São os **primeiros números reais** de
   SS no projeto. Cruza com **Q-09** e **Q-14** — e a folha do cliente **responde afirmativamente** à segunda
   (o Card da SS junta trabalhador + empresa).
4. **`K9` está correto e é subtil:** subtrai IRS e SS **do trabalhador**, mas **não** a SS patronal. Não é erro: os
   23,75 % são **custo da empresa**, não desconto do trabalhador. Fica como **nota de implementação** para que
   ninguém "corrija" a fórmula.
5. **6 trabalhadores, um com escalão diferente (8,56 %) e remuneração mais alta (1250 €).** A equipa é **pequena**
   → tratável **à mão**, sem motor fiscal. Resolve a tensão de âmbito (**Q-30**) a favor do MVP.
6. **A SS incide só sobre `F` (remuneração), não sobre `G`** (+SA) — coerente com o regime real (o SA em cartão
   tem limite de isenção). Simplifica o cálculo e confirma que a **base de incidência é a remuneração**.

#### F.5 Ativos e depreciações — o que as capturas provam

As imagens 2 e 3 são **dois ecrãs do software de contabilidade** (não o Excel do balancete): a *Lista de
Inventário de Ativos* e o *Balancete de Ativos com Análise Contabilística*. Do que se vê:

| Campo do software                          | Exemplo                              | Tradução para o projeto                                        |
| :----------------------------------------- | :----------------------------------- | :------------------------------------------------------------- |
| `Código de Ativo`                          | `2026.00001`                         | identificador do ativo (código, não AUTO_INCREMENT)            |
| `Descrição`                                | Computador Desktop Lenovo            | designação                                                     |
| `Tipo de Ativo` / `Classe de Investimento` | Equipamento administrativo / **435** | **classe contabilística** (equipamento administrativo)         |
| `Localização`                              | Sede                                 | local (Sede / loja / carrinha — ver **Q-52**)                  |
| `Data de Aquisição`                        | 2026-01-10                           | ponto de partida das depreciações                              |
| `Valor de Aquisição`                       | 406,50 €                             | valor de compra (base da depreciação)                          |
| `Vida Útil Esperada`                       | **3**                                | anos → **36 meses**                                            |
| `Data de Início da Depreciação`            | 2026-01-10                           | pode diferir da data de aquisição                              |
| `Deprec. Acum.` / `Valor contabilístico`   | 135,48 € / 271,02 €                  | acumulado / valor de balanço                                   |
| `Imparidade`, `Revalorização`              | vazio                                | **fora do âmbito** (não pedido)                                |
| `Sub-Total 435` · `Sub-Total 43` · `TOTAL` | 1 463,40 €                           | subtotais por classe e por grupo (43 = ativos fixos tangíveis) |

**A fórmula da depreciação foi reproduzida — o achado mais fino desta iteração.** Os valores do ficheiro **não**
saem de `total ÷ vida útil`:

| Hipótese                                                        | Resultado    | Bate com o ficheiro?         |
| :-------------------------------------------------------------- | :----------- | :--------------------------- |
| (A) `Σ valores de aquisição ÷ 3 anos` = 1463,40 / 3             | 487,80 €     | ❌ não (ficheiro: 487,68 €)  |
| (B) `floor(1463,40 / 36) × 12`                                  | 487,80 €     | ❌ não                       |
| **(C) por ativo: `floor(valor / 36) × 12`, truncando ao cêntimo | **487,68 €** | ✅ **sim** (bate ao cêntimo) |
| *por ativo*, e só depois somando**                              |              |                              |

Em números: `406,50 / 36 = 11,2916…` → **11,29 €/mês** (truncado) → `× 12 = 135,48 €`;
`325,20 / 36 = 9,03 €/mês` → `× 12 = 108,36 €`. Somando:
`135,48 + 135,48 + 108,36 + 108,36 = 487,68` ✔ · `271,02 = 406,50 − 135,48` ✔ ·
`216,84 = 325,20 − 108,36` ✔ · `975,72 = 1 463,40 − 487,68` ✔. **Prova executada em §4.**

**Consequências:**

1. A depreciação **tem de ser calculada por ativo, com arredondamento a cêntimos por ativo** — fazê-lo no total
   dá **0,12 €** de diferença (e o software do cliente não o faz). É **regra de implementação**, não detalhe.
2. **`Deprec. Acum.` ≠ depreciação do ano** no caso geral (é acumulado desde o início; aqui coincidem porque os
   ativos são de janeiro). O Card *"Mapa de depreciações"* deve mostrar **as duas** leituras, ou o gestor lê mal o
   valor de balanço.
3. **O `Valor de Aquisição` não é um líquido de IVA "redondo":** `406,50 / 1,23 = 330,49 €` e
   `325,20 / 1,23 = 264,39 €` — **não** seguem a lógica implícita dos serviços (§E.2, onde `preco_base × 1,23` é
   sempre redondo). É **coerente** com o regime real (a empresa **deduz** o IVA dos ativos), mas é uma **segunda
   lógica de IVA** a conviver com a primeira → **Q-53**.

#### F.6 Financiamentos e "Análise financeira" — o que muda face ao planeado

| Menu pedido            | Card's do cliente                                      | Estado no projeto hoje                                             |
| :--------------------- | :----------------------------------------------------- | :----------------------------------------------------------------- |
| **Financiamentos**     | Capital inicial · Capital amortizado · **Capital em    | ⬜ **Não existe** — nem entidade `emprestimo`, nem plano de        |
|                        | dívida**                                               | amortizações (varrimento em §4)                                    |
| **Análise financeira** | Rendimentos · Gastos · **RAI** + caixa de **taxa IRC** | 🟡 Rendimentos e Gastos já calculáveis (§B.1); **RAI existe        |
|                        | + caixa do **valor de IRC**                            | como conceito** (§1.4), mas o IRC 20 % está *hardcoded* (**Q-08**) |

**Três achados relevantes:**

1. **O empréstimo é uma entidade própria, com plano de amortizações.** O capricho do pedido (*"capital inicial,
   amortizado e em dívida"*) é **exatamente** o trio que sai de uma tabela de amortização francesa/linear. Logo
   não basta uma "despesa" — precisa de ➕ `emprestimo` (+ tabela de prestações, ou prestações calculadas na
   leitura). Isto **acrescenta uma tabela** à lista de §1.8 → **C-26**.
2. **A caixa "taxa de IRC" confirma o que já se propunha em Q-08/C-14:** a taxa tem de ser **configurável**, não
   constante de código. O cliente quer **poder mexer nela na UI** — logo é **parâmetro de configuração** (não
   valor escrito em BD de negócio). Fecha a discussão "20 % fixo vs configurável" a favor de **configurável**.
3. **A caixa "valor do IRC que a empresa vai pagar" diz que o simulador não escreve sozinho** — o gestor vê a
   **estimativa** e decide o valor. Confirma a via recomendada em **C-05** (*"não escrever em
   `obrigacao_fiscal`; oferecer ação explícita 'criar obrigação com este valor'"*). **Boa notícia: não é preciso
   alterar o desenho.**

**O que isto faz ao módulo B (Contabilidade):** o menu pedido tem **6 secções**, não 5 — a nº. 6 são os
**Rácios** (Ativo corrente/não corrente, Capital próprio, Passivo não corrente, ± fundo de maneio), que **só
existem com o balancete** e que, hoje, **nenhuma tabela do projeto alimenta**. Os Rácios são, por isso, o
**melhor candidato** à via "mapa de células" (F.1b) e o pior candidato a "recalcular internamente".

#### F.7 Prova de viabilidade técnica: ler `.xlsx` **sem instalar nada** (corrige C-19/Q-43)

A 2.ª iteração concluiu que ler `.xlsx` exigiria biblioteca externa (proibida — `.clinerules` §1). **Fui verificar
e a conclusão está errada.** Factos medidos no ambiente real:

| Verificação                                         | Resultado                                                |
| :-------------------------------------------------- | :------------------------------------------------------- |
| Extensão `zip` do PHP disponível?                   | ❌ **não** (`ZipArchive` inexistente)                    |
| Extensão `zlib` disponível?                         | ✅ **sim** (`gzinflate` confirmado)                      |
| `.xlsx`/`.docx` são ZIP?                            | ✅ sim — 12 e 15 entradas, código **deflate (método 8)** |
| Dá para as ler com `zlib` **sem** a extensão `zip`? | ✅ **SIM — 12/12 e 15/15 entradas lidas com sucesso**    |

Ou seja, um **leitor mínimo de ZIP em PHP puro** (localizar o *End Of Central Directory*, percorrer o diretório
central de cada entrada, `gzinflate` do bloco comprimido) resolve o problema **sem pacote nenhum** — e é o mesmo
mecanismo que já foi usado, nesta análise, para **extrair o conteúdo destes dois templates**. **Prova e comando em §4.**

Consequências:

- **`C-19`/`Q-43` deixam de ter fundamento técnico** para excluir `.xlsx` — a limitação era **minha**, não do
  stack. Passa a ser uma decisão de **âmbito** (importar vs mapa de células), não de **possibilidade** →
  **C-30**.
- Deve ser criada, **se essa via for aprovada**, uma ferramenta reutilizável (ex.: `tools/xlsx-read.php`, com o
  mesmo padrão dos utilitários de `tools/` — dry-run por omissão, exit code 0/1) e registada em
  `tools/README.md` e `tools/README.md` §2. **Só depois da decisão** — não se implementa sem aprovação (§29.3).
- ⚠️ **Limite honesto:** um leitor próprio lê o **conteúdo** (células, valores, fórmulas), **não** a *pintura*
  visual, macros, gráficos nem formatação condicional. Para "ler as células que o cliente indicou" **chega
  perfeitamente**; para "reproduzir o balancete como no Excel" **não**.

## 2. DÚVIDAS TÉCNICAS/NEGOCIAIS

> Todas resultaram **do cruzamento** com o que já existe. Nenhuma é resolúvel por inferência do código —
> cada uma muda números, modelos de dados ou regras.

### 2.0 Estado das dúvidas das iterações anteriores (o que esta iteração fechou)

| Dúvida                                      | Estado agora                                                                                                                                                           |
| :------------------------------------------ | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Q-01** — os preços são com IVA? Que taxa? | ✅ **RESOLVIDA por verificação** (§E.2): `preco_base` é **líquido**, taxa implícita **23 %** (35/35 preços × 1,23 = redondos). Falta só a decisão de **onde** gravar a |
|                                             | taxa (**C-17**)                                                                                                                                                        |
| **Q-01 (variante "onde está o IVA na BD")** | ✅ **RESOLVIDA**: **não existe**; só `obrigacao_fiscal.tipo='iva'` (obrigação, não taxa)                                                                               |
| **Q-09 / Q-11** — que subsídios?            | ✅ **PARCIALMENTE RESOLVIDA** (§F.4): o subsídio é o de **alimentação** (`129,15 €/mês = 6,15 × 21 dias`), tratado como **dado de entrada**. O **13.º/14.º mês**       |
|                                             | continuam **não modelados** e continuam em aberto                                                                                                                      |
| **Q-14** — a SS patronal entra no custo?    | ✅ **RESOLVIDA pela folha do cliente** (§F.4): o Card da SS **junta** trabalhador (11 %) **e** empresa (23,75 %) = **34,75 %**                                         |
| **Q-04** — uma conta ou várias?             | 🟡 **PARCIALMENTE RESOLVIDA** (§F.3): o balancete mostra **caixa e banco separados** (246 € + 16 708,73 €); resta decidir se são ➕ tabela `conta_bancaria` ou         |
|                                             | parâmetros de configuração — **C-25**                                                                                                                                  |
| **Q-08 / C-14** — IRC 20 % fixo ou config.? | ✅ **RESOLVIDA** (§F.6): o cliente quer **editar a taxa na UI** → **configurável**, com o valor de IRC **decidido pelo gestor** (confirma **C-05**)                    |
| **Q-43 / C-19** — `.xlsx` é tratável?       | ✅ **RESOLVIDA (e corrigida)** (§F.7): **é** tratável com `zlib` em PHP puro — **12/12** e **15/15** entradas lidas. A limitação anterior era **incorreta** → **C-30** |
| **Q-41 / pergunta 17 (Teams)** — o          | ✅ **JÁ DECIDIDO na especificação** — **não é dúvida**: **D-11** · §15.3 · **RF-13** · §28.2/13 fixam o **lembrete das ≤ 24 h** ao cliente (sugere loja física ou      |
| cliente recebe lembretes?                   | reagendamento); falta **implementar** (§24.6). Ficam abertos apenas: **(a)** 1 ou 2 mecanismos (**C-21**) e **(b)** onde aparece (*"e/ou"* de §24.6)                   |
| **Q-21 / Q-22 (Teams 18/19)** — avisos de   | ⬜ **origem não provada** (§2.5.1): **nenhum ficheiro** do projeto os exige — a ocorrência mais                                                                        |
| *"contratos"* e *"carrinha"*                | antiga é a 1.ª iteração desta análise. Passam a **pergunta de confirmação** ao cliente. A parte                                                                        |
|                                             | verificável (*"não existe entidade veículo"* — §3.4/D-04 · §3.8) mantém-se                                                                                             |
| **Q-02 … Q-30** (restantes) e **Q-31…Q-47** | ⬜ **em aberto** — seguem válidas; as novas desta iteração são **§2.10**                                                                                               |

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

> ⚠️ **Q-21 / Q-22:** a proveniência destes dois avisos foi auditada e **não tem prova documental** →
> **§2.5.1**. As perguntas mantêm-se, mas **reformuladas como confirmação**, não como requisito.

| ID   | Dúvida                                                                                                  | Impacto / opções                                                              |
| :--- | :------------------------------------------------------------------------------------------------------ | :---------------------------------------------------------------------------- |
| Q-21 | ⚠️ *"Renovação de contratos"* — **origem não provada** (§2.5.1): **existe** este aviso? Se sim, é sobre | Muda a origem do dado (funcionario, fornecedor, instalações) e **condiciona a |
|      | contratos **de trabalho**, **de fornecimento** ou **de aluguer**?                                       | própria existência do requisito**                                             |
| Q-22 | ⚠️ *"Revisões da carrinha"* — **origem não provada** (§2.5.1). O que é facto: **não existe entidade     | Decisão de modelo — **só se o aviso for confirmado**; cruza com §3.8          |
|      | veículo** na BD (1 carrinha polivalente — §3.4/D-04). ➕ `veiculo`+`manutencao_veiculo` ou tratar como  | (logística de condução é **fora de escopo**)                                  |
|      | obrigação/despesa periódica?                                                                            |                                                                               |
| Q-23 | O contador de alertas é **por utilizador** ou **global**? (`alerta_fiscal.visualizado` é global)        | Com 2+ gestores, o "lido" de um silencia o outro → **C-03**                   |
| Q-26 | Os lembretes mantêm-se **on-demand** (sem CRON — §22.1)?                                                | Confirmação de que o projeto continua sem CRON; independente de quais sejam   |
|      |                                                                                                         | os avisos (§2.5.1)                                                            |
| Q-27 | O sino agrega **tudo** num contador ou separa por origem (fiscal · fornecedores · operacional)?         | Muda o componente do topo (`menuUserBo.php`) e a UX do gestor                 |

#### 2.5.1 Nota de correção — proveniência de *"renovação de contratos"* e *"revisões da carrinha"*

**Auditoria executada (23/09/2026):** os dois avisos **não fiscais** que este documento tratava como
requisito — *"renovação de contratos"* (**Q-21**) e *"revisões da carrinha"* (**Q-22**/**Q-26**) —
foram submetidos a prova de origem. **Resultado: nenhum ficheiro do projeto os exige.** A ocorrência
mais antiga é a **minha própria introdução** na 1.ª iteração desta análise.

| Verificação (executada na raiz do projeto)                       | Resultado                                                                          |
| :--------------------------------------------------------------- | :--------------------------------------------------------------------------------- |
| `git log --all -S 'Renovação de contratos' --oneline`            | **2** commits: `1fa89e3` (1.ª iteração **desta análise**) · `b848ab8` (3.ª)        |
| `git log --all -S 'Revisões da carrinha' --oneline`              | **1** commit: `1fa89e3` (1.ª iteração desta análise)                               |
| `git log --all -i -S 'renova' --oneline`                         | **4**: os três acima + `4884f61` (*"Saio sempre renovada!"*, depoimento do site em |
|                                                                  | `components/testimonial.php` — sem relação com o tema)                             |
| `git grep -i -E 'renova\|revis(ão\|oes\|ões)' 7568031 -- '*.md'` | nos documentos de planeamento **arquivados antes da remoção** existe apenas        |
|                                                                  | *"revisão de convenções"* e *"Revisão da secção 13.1"* — **nada** de contratos nem |
|                                                                  | de carrinha                                                                        |
| Leitura direta (sem qualquer ocorrência)                         | `especificacao_mvp.md` · `DataBase_v2.sql` · `DataBase_v3.sql` · `README.md` ·     |
|                                                                  | `guia_teste_manual.md` · `mapa_fluxo_dados.md` · `Menu APOIO 3.docx` ·             |
|                                                                  | `CUSTOS RH 2.xlsx`                                                                 |

**Onde os dois avisos aparecem hoje** — sempre **dentro deste par de ficheiros**, nunca antes de `1fa89e3`:

| Onde (secção)                      | Forma                                           | Estatuto depois desta auditoria                                           |
| :--------------------------------- | :---------------------------------------------- | :------------------------------------------------------------------------ |
| §1.2 Quadro-resumo                 | *"fornecedores/contratos/carrinha"*             | ⬜ **hipótese**, não requisito                                            |
| §1.3 Sininho (A.2)                 | *"Lembretes a fornecedores/contratos/carrinha"* | ⬜ **hipótese**, não requisito                                            |
| §2.5 **Q-21**                      | *"Renovação de contratos"*                      | ⬜ **hipótese** — **reformulada**                                         |
| §2.5 **Q-22**                      | *"Revisões da carrinha"* · ➕ `veiculo` +       | 🟡 **válida** apenas na parte verificável                                 |
|                                    | `manutencao_veiculo`                            | (*"não existe entidade veículo"*)                                         |
| §2.5 **Q-26**                      | *"revisão da carrinha a 30 dias"*               | ⬜ **exemplo ilustrativo** — removido da                                  |
|                                    |                                                 | pergunta                                                                  |
| §1.4 B.2 · Teams Q-10              | *"seguros nem manutenção da carrinha"*          | ✅ afirmação de **ausência** de dados na BD (verificável) — é **exemplo** |
|                                    |                                                 | de despesa, não requisito de aviso                                        |
| §3 **C-02** · §3.1 checklist (2)   | lembrete de contratos/carrinha                  | ⬜ mantém-se — é o **conflito** a decidir                                 |
| Teams §3 perguntas **18** e **19** | idem                                            | **reformuladas** nesta revisão                                            |

**O que se mantém firme (independente da proveniência):**

- **Não existe entidade veículo.** `veiculo` / `manutencao_veiculo` (ou equivalente) **não existem** em
  `DataBase_v2.sql`/`DataBase_v3.sql`; §3.4 (**D-04**) fixa **uma única carrinha polivalente** e §3.8
  (**D-08**) coloca a **logística de condução fora de escopo**. A parte factual de **Q-22** não depende
  de o aviso existir.
- **O calendário fiscal é fiscal.** §13 define os tipos como IVA, IRC, Segurança Social e Seguros —
  confirmado na BD: `obrigacao_fiscal.tipo` = `enum('iva','irc','seguranca_social','seguros')`
  (`DataBase_v3.sql` L394 · `DataBase_v2.sql` L528). Um **seguro** é obrigação periódica **com prazo**;
  uma **revisão** não é. Nada aqui autoriza alargar o enum → **C-02** (recomendação ➕ `notificacao`
  genérica continua em aberto).
- **Sem CRON.** §22.1 mantém tudo *on-demand* — verdadeiro **independentemente** de quais sejam os
  avisos (**Q-26**).

**Conclusão.** A partir desta revisão, *"renovação de contratos"* e *"revisões da carrinha"*
**deixam de ser afirmados como requisitos** e passam a **pergunta de confirmação** ao cliente
(`mensagem_teams.txt`, perguntas **18** e **19**). Se o cliente os confirmar, a origem do dado e o
modelo decidem-se em **C-02**; se não os confirmar, **saem do âmbito sem custo**.

### 2.6 Transversais (UX, técnica e prazo)

| ID   | Dúvida                                                                                       | Impacto / opções                                                                        |
| :--- | :------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------------------- |
| Q-28 | Gráficos: **sem novas bibliotecas** (P7) — barras CSS/SVG próprias, ou autoriza-se           | Se autorizado, contradiz *"sem frameworks externos"* (`.clinerules` §2/§3)              |
|      | Chart.js/ApexCharts em `modules/common/lib`?                                                 |                                                                                         |
| Q-29 | A promoção altera preços do **modelo de referência dos testes** (Barba 4,07 € — §8.3). Como  | Proposta: *seed* sem promoções ativas e testes que ativam/desativam explicitamente      |
|      | manter as 289 verificações verdes?                                                           |                                                                                         |
| Q-30 | O âmbito total (painel + contabilidade + RH + promoções + área do funcionário) cabe no prazo | §25 diz que a ordem é **vinculativa** (Fornecedores → §24 → consolidações); pode exigir |
|      | académico?                                                                                   | faseamento explícito                                                                    |

### 2.7 Dúvidas novas — de onde vêm os números (CSV/XLS vs plataforma)

> **Resposta transversal a todas:** o sistema **já produz** receita e parte dos custos; tudo o que é
> **externo à operação** (rendas, água/luz, consumíveis, seguros, manutenção, honorários, banco) **não
> existe** em nenhuma tabela. Proposta de princípio: **fonte interna para o que a plataforma faz, entrada
> manual/importada para o que vem de fora** — nunca duplicar (ver **C-20**).

| ID   | Pergunta do cliente                                       | O que o código/BD diz hoje                                                            | Opção / decisão necessária                                  |
| :--- | :-------------------------------------------------------- | :------------------------------------------------------------------------------------ | :---------------------------------------------------------- |
| Q-31 | Os KPIs vêm dos `.csv`/`.xls` do Balancete ou de serviços | Rendimentos **existem** (`agendamento.valor_total` + `local_prestacao`, por canal);   | **Interno como fonte primária** + registo/importação manual |
|      | internos da plataforma?                                   | custos de rota **existem** (`rota_ambulante`); despesas externas **não existem**      | só para despesas externas (**C-19**)                        |
| Q-32 | O que são "entradas brutas"? De onde vêm (banco ou        | Ambíguo: pode ser **receita faturada** (serviços) ou **entradas de caixa** (banco)    | Proposta:                                                   |
|      | serviços)?                                                |                                                                                       | **receita bruta = Σ serviços prestados, COM IVA**, antes de |
|      |                                                           |                                                                                       | deduzir custos — distinta de *recebimento*                  |
| Q-33 | O lucro das vendas efetuadas vem da plataforma ou de      | A receita e o custo de rota são **100 % internos**; nada de vendas vem de ficheiros   | **Interno**, sem exceção (evita divergência)                |
|      | ficheiros externos?                                       |                                                                                       |                                                             |
| Q-34 | O que é a "margem de lucro" e como se calcula?            | Não existe cálculo hoje                                                               | Proposta em §2.7.1 (fórmula explícita e auditável)          |
| Q-35 | De onde derivam internamente as despesas operacionais?    | Internamente só:                                                                      | Interno o que existe + **entrada manual** para o resto      |
|      | Incluem CSV/XLS?                                          | **combustível + custo fixo 50 €/rota + comissões de recibos verdes + salários base**. | (formulário primeiro; CSV como conveniência)                |
|      |                                                           | Rendas, consumíveis, eletricidade, seguros, manutenção: **nada**                      |                                                             |
| Q-36 | As obrigações fiscais são as definidas na página Fiscal?  | ✅ Sim — `obrigacao_fiscal` (§13), `tipo` ∈ {iva, irc, seguranca_social, seguros},    | Confirmar: o **IVA apurado** (calculado) **não** substitui  |
|      |                                                           | valor **manual**                                                                      | a obrigação manual (§2.7.2)                                 |

#### 2.7.1 Proposta de definição da margem de lucro (Q-34)

```text
Receita bruta (periodo)  = SOMA agendamento.valor_total  [so estados executado/concluido]
Custos variaveis         = comissoes de recibos verdes (valor_recibo_verde_funcionario)
                         + combustivel das rotas (rota_ambulante.custo_estimado_combustivel)
                         + custo fixo operacional das rotas (n x 50 EUR)
Margem bruta             = Receita bruta - Custos variaveis
Margem de lucro          = Margem bruta / Receita bruta x 100
Despesas operacionais    = custos variaveis + custos fixos externos (registados manualmente)
Resultado (RAI)          = Receita bruta - Despesas operacionais
```

**Nota:** se a receita incluir IVA, a margem fica **distorcida** (o IVA não é receita da empresa) —
preferir o **valor líquido** para as margens e o **bruto** para o que o cliente paga (**C-17**).

#### 2.7.2 IVA: obrigação fiscal vs IVA apurado (Q-36)

São **duas coisas diferentes** que não podem ser confundidas:

| Conceito                  | Origem                                                           | Papel                                           |
| :------------------------ | :--------------------------------------------------------------- | :---------------------------------------------- |
| **Obrigação fiscal IVA**  | `obrigacao_fiscal` (página Fiscal) — `valor_estimado` **manual** | O que se **paga** ao Estado, com prazo/alertas  |
| **IVA apurado (posição)** | Calculado: IVA das vendas − IVA dedutível das despesas           | O que se **deveria** pagar; apoio ao apuramento |

O cliente pediu *"controlo de IVA (posicionamento para apuramento periódico)"* → é o **segundo**.
Proposta: mostrar o apurado e oferecer *"criar obrigação com este valor"* (mantém a decisão humana —
**C-05**).

### 2.8 Dúvidas novas — RH, notificações e acesso do Funcionário

| ID   | Pergunta do cliente                                                | O que o código/BD diz hoje                                           | Opção / decisão necessária                                          |
| :--- | :----------------------------------------------------------------- | :------------------------------------------------------------------- | :------------------------------------------------------------------ |
| Q-40 | Como é que os recibos verdes alimentam **indiretamente** os        | Os valores **já estão gravados** por aceitação                       | Mecanismo em §2.8.1 — **sem** novo lançamento (risco de dupla       |
|      | passivos?                                                          | (`agendamento_servico.valor_recibo_verde_funcionario`, snapshot —    | contagem — **C-10**)                                                |
|      |                                                                    | §11)                                                                 |                                                                     |
| Q-41 | ✅ **Reformulada** — detalhe em **§2.8.2**. O *"se"* está          | O cliente **não tem** sistema de notificações no Main; §15.3 e §24.6 | ✅ **Já decidido** que **sim** — **D-11** · §15.3 · **RF-13** ·     |
|      | **resolvido**; o que fica *aberto* é o *"como"*: (a) o sino do     | preveem o **lembrete das 24 h**, mas **sem UI**                      | §28.2/13 (estado: ⬜ por implementar). Abertos (a) e (b) → **C-21** |
|      | backoffice e os avisos ao cliente são **1 ou 2 mecanismos**? (b) o |                                                                      |                                                                     |
|      | lembrete aparece em `/agendamentos`, na **home** ou **nos dois**?  |                                                                      |                                                                     |
| Q-42 | A que terá acesso o **Funcionário** no backoffice?                 | Hoje: **só** `/gestao/servicos` (aceitação); as APIs recusam o resto | Matriz concreta em **§1.7**; agregar em `/gestao/agenda` — ver      |
|      |                                                                    | com 403 (§22.2)                                                      | **C-22**                                                            |
| Q-43 | (técnica) Um `.csv` é tratável? E `.xls`?                          | **Sem bibliotecas** (proibido instalar — `.clinerules` §1): `.csv` é | Preferir **CSV** (ou `.xlsx` previamente convertido) — ver **C-19** |
|      |                                                                    | nativo do PHP; `.xls`/`.xlsx` é **binário** e exigiria biblioteca    |                                                                     |
|      |                                                                    | externa                                                              |                                                                     |

#### 2.8.1 Mecanismo concreto: recibos verdes → passivos/gastos (Q-40)

```text
Custo de pessoal VARIAVEL do periodo (prestadores a recibo verde)
  = SOMA agendamento_servico.valor_recibo_verde_funcionario
    WHERE estado_aceitacao = 'aceite' AND aceito_em BETWEEN periodo

Custo de pessoal FIXO do periodo (efetivos)
  = SOMA funcionario.salario_base  WHERE tipo_contrato = 'efetivo_contratado' AND ativo = 1
```

Estes dois valores entram nos **Passivos** e nas **Despesas operacionais** (§2.7.1) — **sem** criar
lançamento novo em `transacao_financeira`, porque a comissão **já está gravada** no serviço aceite.
Uma segunda gravação seria **dupla contagem** (é a razão do **C-10**).

#### 2.8.2 Nota de correção — o lembrete ao cliente **não é** requisito novo (Q-41)

A pergunta chegou formulada como *"os lembretes devem ser enviados ao cliente?"* — e essa formulação
**reabre uma decisão já tomada**. Verificado em `especificacao_mvp.md`:

| Onde                 | O que já está decidido                                                                    |
| :------------------- | :---------------------------------------------------------------------------------------- |
| **§3.11 (D-11)**     | *"O sistema despoleta um **alerta/lembrete automático ao cliente**"*, quando falta ≤ 24 h |
|                      | e o agendamento **não foi incluído em nenhuma rota**                                      |
| **§15.3**            | Conteúdo: impossibilidade de execução + sugestão de **loja física** ou **reagendamento**; |
|                      | notificação **simulada**                                                                  |
| **§15.5 · §20.2**    | O lembrete faz parte do fluxo do auto-cancelamento (soft), não é um extra                 |
| **RF-13 (§5)**       | Requisito formalmente registado                                                           |
| **§24.6 · §28.2/13** | É **critério de aceitação** — estado: ⬜ **por implementar**                              |

**Conclusão:** o *"se"* está fechado (o cliente **recebe**); o que está aberto é só:

- **(a)** o sino do backoffice e os avisos ao cliente são **um ou dois mecanismos** → **C-21**;
- **(b)** **onde** o cliente vê o lembrete — §24.6 escreve *"em `/agendamentos` (e/ou na home)"*, e o
  *"e/ou"* **não está decidido**.

A **mesma redundância** existia no ficheiro de comunicação (`mensagem_teams.txt`, **pergunta 17**, já
presente na 2.ª iteração) — **reformulada nesta iteração** para não reabrir o que a especificação fixou.

### 2.9 Dúvidas novas — frontend desta iteração

| ID   | Pergunta                                                            | O que o código/BD diz hoje                                         | Opção / decisão necessária                                           |
| :--- | :------------------------------------------------------------------ | :----------------------------------------------------------------- | :------------------------------------------------------------------- |
| Q-44 | A secção de serviços no Home/About é **estática** ou gerida no      | `hero`, `about` e `testimonial` são **componentes estáticos**; não | **Estática** (proposta), como o About — evita um módulo de CMS fora  |
|      | backoffice?                                                         | existe CMS                                                         | do âmbito                                                            |
| Q-45 | Quem fornece as **imagens dos serviços** e em que formato/dimensão? | Não existe nenhuma imagem de serviço; as de categoria são `.png`   | A definir com o cliente: formato (`.jpg`/`.png`), dimensão e quem    |
|      |                                                                     | carregadas por *slug*                                              | carrega                                                              |
| Q-46 | A taxa de IVA é **uniforme (23 %)** ou                              | Não há taxa em lado nenhum; a aritmética aponta para 23 % em       | Confirmar com o grupo de contabilidade (há serviços com taxas        |
|      | **varia por serviço/categoria**?                                    | **todos** os 35 serviços                                           | diferentes em Portugal)                                              |
| Q-47 | Os **preços de montra** (redondos) devem substituir os atuais na    | A BD tem os líquidos (4,07 €); a montra implica 5,00 € com IVA     | Decidir com **C-17**; a proposta é manter o líquido na BD e gravar o |
|      | BD?                                                                 |                                                                    | bruto na marcação                                                    |

### 2.10 Dúvidas novas — templates "MENU APOIO" e custo de pessoal (3.ª iteração)

| ID   | Pergunta                                                           | O que o código/BD diz hoje                                          | Opção / decisão necessária                                           |
| :--- | :----------------------------------------------------------------- | :------------------------------------------------------------------ | :------------------------------------------------------------------- |
| Q-48 | A entidade das capturas (**"Empresa Solução Certa", NIF            | Nada no projeto refere esta empresa nem este NIF                    | **Confirmar que é de exemplo** (template). Se for a empresa real, os |
|      | 513122095**, ativos                                                |                                                                     |                                                                      |
|      | `2026.00001..4`) é a empresa real ou um exemplo do template?       |                                                                     | dados de teste e o seed passam a ter de a refletir                   |
| Q-49 | O balancete chega em **`.xlsx` do software de contabilidade** — e  | Nada de importação existe                                           | Alimenta **C-29**/**C-30**. Saber se o export tem **abas estáveis**  |
|      | com que                                                            |                                                                     |                                                                      |
|      | regularidade: mensal, trimestral ou anual?                         |                                                                     | (nome e ordem fixos) ou se muda a cada emissão                       |
| Q-50 | O **mapa de células** (`J13`, `J16`, `J25`, `K48`, `K85`, `K86`,   | Desconhecido: as células referem-se ao ficheiro do **cliente**, que | ⚠️ **Bloqueante para os Rácios** (as referências da captura estão    |
|      | `K113`,                                                            |                                                                     |                                                                      |
|      | `K118`) mantém-se nas próximas emissões, ou **muda de posição**?   | **ainda não temos**                                                 | **riscadas/corrigidas** à mão — sinal de instabilidade) → **C-29**   |
| Q-51 | As **dívidas a pagar** decompõem-se em fornecedores (**K48**) ·    | `obrigacao_fiscal.tipo` ∈ {iva, irc, seguranca_social, seguros};    | Decidir se estes 5 passam a **tipos** de obrigação, ou se o card das |
|      | retenções/                                                         |                                                                     |                                                                      |
|      | impostos (**K85**) · IVA (**K86**) · Segurança Social (**K113**) · | **fornecedores não existe** e                                       | dívidas é lido por célula e **não** duplicado no calendário          |
|      | (**K118**).                                                        | **retenções (IRS na fonte) não existe**                             | (**C-27**)                                                           |
|      | O que é **K118** — é a SS da empresa ou outra coisa?               |                                                                     |                                                                      |
| Q-52 | Os ativos têm **`Localização`** (Sede). Devem ser atribuídos a     | Não existe entidade `ativo`                                         | Define se o ativo é "da empresa" ou "de um local" — e se a           |
|      | **loja ·                                                           |                                                                     | depreciação                                                          |
|      | carrinha · sede**, ou é indiferente para o mapa de depreciações?   |                                                                     | entra em custos **por canal** (loja vs carrinha, como as rotas)      |
| Q-53 | Os **valores de aquisição não são líquidos de IVA "redondos"**     | §E.2: os serviços seguem 23 % com valores redondos de montra        | Confirmar que o valor do ativo é **valor contabilístico** (sem IVA   |
|      | (`406,50/1,23`                                                     |                                                                     |                                                                      |
|      | `= 330,49`). O valor de aquisição é **líquido**,                   |                                                                     | dedutível) e que **não** deve ser reconciliado com a lógica dos      |
|      | **bruto não dedutível** ou                                         |                                                                     | serviços                                                             |
|      | **bruto com IVA dedutível**?                                       |                                                                     |                                                                      |
| Q-54 | A **depreciação acumulada** de que o Card fala é a do **período**  | Nada calcula depreciações                                           | Define a fórmula do Card (**§F.5**: por ativo, cêntimo a cêntimo);   |
|      | ou a                                                               |                                                                     |                                                                      |
|      | **acumulada desde a aquisição**?                                   |                                                                     | aqui são iguais porque os ativos são de **janeiro de 2026**          |
| Q-55 | Os **movimentos de ativos** (compras e **alienações/vendas**)      | Não existe entidade `ativo` nem movimentos                          | Se houver vendas, é preciso **documento + data de saída** e cálculo  |
|      | entram no                                                          |                                                                     | da                                                                   |
|      | âmbito, ou só os ativos ativos à data?                             |                                                                     | mais-valia — **âmbito novo** se sim                                  |
| Q-56 | O **empréstimo** é **um só** (financiamento da                     | Não existe entidade `emprestimo`                                    | Define ➕ `emprestimo` + plano de amortizações (**C-26**) e se as    |
|      | carrinha/equipamento) ou vários?                                   |                                                                     |                                                                      |
|      | A taxa e o prazo são fixos durante a vida do contrato?             |                                                                     | prestações são calculadas na leitura ou tabela                       |
| Q-57 | Os **Impostos** do RH devem dividir-se em **DMR** (Modelo 30) e    | `obrigacao_fiscal` tem `seguranca_social` e `irc`, mas **não**      | Mapear DMR/DRI para tipos de obrigação ou criar **subtipos**; e      |
|      | **DRI**                                                            |                                                                     | decidir                                                              |
|      | (Modelo 22 — IRC)? O **PDF/imagem das declarações** é anexo do     | tem DMR/DRI nem **anexos** (§13: *"sem anexos"*)                    | se o MVP aceita anexos (contra §13) ou só o comprovativo simulado    |
|      | registo?                                                           |                                                                     |                                                                      |

#### 2.10.1 Nota de implementação — fórmula do custo de pessoal (de Q-14 e §F.4)

```text
Por trabalhador (periodo = n meses, dado de entrada):
  remuneracao_bruta_p = salario_base * n
  SA_p                = subsidio_alimentacao_mensal * n      (129,15 EUR/mes no template)
  IRS_p               = remuneracao_bruta_p * taxa_irs_p      (taxa POR trabalhador!)
  SS_trab_p           = remuneracao_bruta_p * 0,11
  SS_empresa_p        = remuneracao_bruta_p * 0,2375
  liquido_a_pagar_p   = remuneracao_bruta_p + SA_p - IRS_p - SS_trab_p

Cards:
  REMUNERACAO = soma (remuneracao_bruta_p + SA_p)
  IRS         = soma IRS_p
  SS          = soma (SS_trab_p + SS_empresa_p)
```

**Regras que ficam provadas pela folha do cliente:** a **base de incidência da SS é a remuneração bruta**, *não*
`+ SA`; a **taxa de IRS é por trabalhador** (não da empresa); e o **líquido a pagar não desconta a SS patronal**.

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
| C-17 | **IVA incluído** nos valores mostrados ao     | `preco_base`/`preco_praticado` são **líquidos**; `valor_total` é | Não há taxa em lado nenhum; mudar a     | **(1)** onde vive a taxa (coluna por serviço com     |
|      | cliente                                       | a soma líquida; RN-03 (sinal 10 %) e §11 (base dos 70/30) usam   | apresentação **propaga-se** a sinal,    | *fallback* global em config); **(2)** gravar         |
|      |                                               | esse valor; §8.3 fixa "Barba 4,07 €" nos testes                  | recibos verdes, margens, exemplos de    | **líquido + taxa + IVA + bruto** na marcação         |
|      |                                               |                                                                  | teste e tickets                         | (snapshot, como §11); **(3)** margens sobre o        |
|      |                                               |                                                                  |                                         | **líquido**, montra sobre o **bruto**; **(4)**       |
|      |                                               |                                                                  |                                         | estratégia para os testes                            |
| C-18 | Cards/filtros do catálogo passam a mostrar    | `services.php` `max="50"` e `services.js` `maxPrice: 50`; o      | O filtro de preço máximo                | Subir o máximo para 60 € (com IVA)                   |
|      | preço com IVA                                 | serviço mais caro é 48,78 € líquido = **60,00 €** com IVA        | **deixa de alcançar** os serviços mais  | **na mesma alteração**; decidir se o filtro usa      |
|      |                                               |                                                                  | caros (regressão silenciosa)            | bruto ou líquido                                     |
| C-19 | KPIs/financeiro alimentados por `.csv`/`.xls` | Nada de importação existe; e **não** se podem instalar           | Prometer importação de Excel é prometer | Preferir **CSV**; e definir um princípio único:      |
|      | do Balancete                                  | bibliotecas (`.clinerules` §1) → `.xlsx` é binário, `.csv` é     | trabalho que o stack proíbe; e dois     | **interno para o que a plataforma faz** (vendas,     |
|      |                                               | nativo                                                           | donos do mesmo número geram divergência | comissões, rotas), **manual/importado** só para o    |
|      |                                               |                                                                  |                                         | externo (rendas, utilities, seguros)                 |
| C-20 | Importar o Balancete **e** somar aos dados    | `agendamento.valor_total` já contém as vendas                    | Se o ficheiro importado já incluir as   | Decidir o **dono de cada número**: a receita é       |
|      | internos                                      |                                                                  | vendas, somá-las aos agendamentos       | **sempre** interna; ficheiros externos só trazem o   |
|      |                                               |                                                                  | **duplica a receita**                   | que o sistema não produz (ou são puramente           |
|      |                                               |                                                                  |                                         | informativos)                                        |
| C-21 | Separação **sino interno** ↔                  | O **lembrete ao cliente** das 24 h **já é requisito** (**D-11**  | O pedido                                | **Separar por canal/âmbito** (recomendado) e         |
|      | **avisos ao cliente**                         | · §15.3 · **RF-13**), só **não está implementado**; o sino do    | *"o sino também para o cliente?"*       | **decidir onde** o cliente vê o lembrete:            |
|      |                                               | backoffice mostra dados de gestão (fisco, fornecedores)          | **não cria requisito novo** — já está   | `/agendamentos`, **home** ou ambos (*"e/ou"* de      |
|      |                                               |                                                                  | na especificação; o que se decide é a   | §24.6)                                               |
|      |                                               |                                                                  | **arquitetura** e o **sítio**           |                                                      |
| C-22 | Ocultar módulos financeiros ao Funcionário    | As **páginas** não são segregadas por perfil; só as **APIs**     | Repetição do problema de autorização,   | Aplicar a **mesma** matriz de perfis (página +       |
|      |                                               | recusam (403) — já é o **C-09**                                  | agora com mais páginas novas            | endpoint) definida em **C-09**, com testes de 403    |
| C-23 | ➕ campo na BD com o link da imagem do        | `servico_foto` **já existe** (`url_foto`, `destaque`,            | O pedido literal (coluna nova)          | Usar `servico_foto` (via recomendada em §E.3) **ou** |
|      | serviço                                       | `ordem_exibicao`) mas está **vazia e sem uso**; `servico` não    | **duplica** modelação existente e viola | justificar a coluna nova; em qualquer caso é         |
|      |                                               | tem imagem                                                       | "não alterar BD sem justificação"       | obrigatório um **fallback** para os serviços sem     |
|      |                                               |                                                                  |                                         | foto                                                 |
| C-24 | Secção de serviços no Home e no About         | `about.php` é componente partilhado (padrão a replicar); não     | Introduzir gestão de conteúdo no        | Conteúdo **estático** num componente partilhado      |
|      |                                               | existe CMS                                                       | backoffice para uma secção              | (`servicesSummary.php`), como                        |
|      |                                               |                                                                  | institucional é âmbito novo não pedido  | `hero`/`about`/`testimonial`                         |
| C-25 | **Disponibilidades = caixa + banco** (card do | Não existe `conta_bancaria`; a §1.4/§25.3 propunha *"saldo       | Sem contas separadas o card do          | **(1)** ➕ `conta_bancaria` (nº de contas, saldo     |
|      | balancete — §F.3)                             | inicial"* como parâmetro de configuração                         | dashboard (F.2) não é calculável        | inicial, tipo) **ou** **(2)** manter só dois         |
|      |                                               |                                                                  | fielmente                               | parâmetros (caixa · banco) em configuração           |
| C-26 | **Empréstimo** com *capital inicial ·         | Não existe entidade `emprestimo` nem plano de amortizações;      | É uma **terceira** fonte de passivo, a  | ➕ `emprestimo` + prestações (tabela **ou** cálculo  |
|      | amortizado · em dívida* (§F.6)                | `transacao_financeira` é livro de entradas (§B.2)                | par de `despesa` (C-04) e de            | na leitura). Decidir se a **prestação é despesa** do |
|      |                                               |                                                                  | `fatura_fornecedor`                     | mês (juros + capital) e onde entra no DR             |
| C-27 | **Dívidas a pagar decompostas por natureza**  | O calendário fiscal já tem `iva` · `seguranca_social`; **não**   | Dois sítios a dizer o mesmo número =    | **(1)** mapear estes 5 para os                       |
|      |                                               |                                                                  |                                         | **tipos de obrigação**                               |
|      | (fornecedores · retenções · IVA · SS · K118)  | tem `fornecedores` nem **retenções na fonte**                    | risco de **dupla contagem** (C-20)      | existentes/novos, **ou** **(2)** ler por célula e    |
|      | — §F.3 · §F.4                                 |                                                                  |                                         | **não** replicar no calendário                       |
| C-28 | **IRS retido na fonte** precisa de **taxa por | `funcionario` só tem `salario_base`; não há taxa de IRS em       | Se se derivar uma % única da empresa,   | **Não derivar.** Taxa introduzida por trabalhador    |
|      | trabalhador** (§F.4: 8 % / 3,6 % / 8,56 %)    | lado nenhum                                                      | os recibos saem **errados**             | (ou lida por célula); documentar que **não é**       |
|      |                                               |                                                                  |                                         | constante da empresa                                 |
| C-29 | **Importar o balancete para tabelas** *vs*    | Nada existe; mas o **mapa de células já está desenhado** pelo    | Decidir isto define **todo** o módulo   | **Recomendada a via (b) — mapa de células**: cumpre  |
|      | **ler por mapa de células** (§F.1)            | cliente (`J13`, `J16`, `K48`, `K85`, `K86`, `K113`, `K118`)      | da contabilidade e o esforço de teste   | o pedido literal, não duplica fontes (C-20) e não    |
|      |                                               |                                                                  |                                         | escreve na BD de negócio                             |
| C-30 | **`.xlsx` é afinal tratável** (corrige C-19)  | C-19/Q-43 diziam *"exigiria biblioteca externa"* — **errado**:   | Reabre a via da importação; se for      | Manter o **mapa de células** como via principal e,   |
|      | — §F.7                                        | `zlib`+`gzinflate` leem o ZIP (**12/12** e **15/15** entradas)   | aceite, ➕ `tools/xlsx-read.php` (só    | **só se aprovado**, acrescentar o leitor como        |
|      |                                               |                                                                  | depois de decisão)                      | ferramenta de `tools/` (§12 do `.clinerules`)        |

### 3.1 Checklist de decisões (para fechar esta fase)

| #   | Decisão                                                                                                | ID   | Estado |
| :-- | :----------------------------------------------------------------------------------------------------- | :--- | :----- |
| 1   | `/gestao` passa a painel e a lista fica em `/gestao/agendamentos`                                      | C-01 | ⬜     |
| 2   | Modelo de lembretes (entidade genérica vs extensão do calendário fiscal) — ⚠️ *"revisões da carrinha"* | C-02 | ⬜     |
|     | e *"renovação de contratos"* **sem origem provada** (§2.5.1): decidir **só se o cliente as confirmar** |      |        |
| 3   | Lido dos alertas: global ou por utilizador                                                             | C-03 | ⬜     |
| 4   | **Modelo de despesas**: criar `despesa` (+ fornecedor/fatura) ou alargar `transacao_financeira`        | C-04 | ⬜     |
| 5   | Simulador fiscal não escreve no calendário fiscal                                                      | C-05 | ⬜     |
| 6   | Reafirmar que nenhum KPI/indicador bloqueia ou decide rotas                                            | C-06 | ⬜     |
| 7   | Âmbito do RH (criar/editar/desativar) e fronteira com §22.2                                            | C-07 | ⬜     |
| 8   | Regras das promoções (base dos 70/30, sinal, preço no Main, testes)                                    | C-08 | ⬜     |
| 9   | Autorização por página + endpoints (403 server-side)                                                   | C-09 | ⬜     |
| 10  | Fonte única do custo de pessoal (comissões e salários)                                                 | C-10 | ⬜     |
| 11  | Sequência: Fornecedores antes da contabilidade completa                                                | C-11 | ⬜     |
| 12  | Âmbito/critérios de aceitação: aceitar os 4 módulos novos como Fase 6 acrescida                        | C-12 | ⬜     |
| 13  | Plano de testes por fase                                                                               | C-13 | ⬜     |
| 14  | IRC: 20 % fixo (académico) ou configurável                                                             | C-14 | ⬜     |
| 15  | Tesouraria antes ou depois de §24.5 (10/90 + métodos)                                                  | C-15 | ⬜     |
| 16  | Definição da "agenda do funcionário" face à aceitação e às rotas                                       | C-16 | ⬜     |
| 17  | Questões Q-02…Q-57 (§2) — período, saldo inicial, subsídios, tags, contador, gráficos, fontes de dados | §2   | ⬜     |
| 18  | **IVA**: onde vive a taxa + gravar líquido/taxa/IVA/bruto na marcação                                  | C-17 | ⬜     |
| 19  | Filtro de preço do catálogo sobe para 60 € (bruto)                                                     | C-18 | ⬜     |
| 20  | Formato de importação (**CSV**) e princípio interno-vs-externo                                         | C-19 | ⬜     |
| 21  | Dono de cada número (receita sempre interna; evitar duplicação)                                        | C-20 | ⬜     |
| 22  | Notificações: separar backoffice (interno) de cliente (Main)                                           | C-21 | ⬜     |
| 23  | Acesso do Funcionário: matriz de perfis por página + endpoint                                          | C-22 | ⬜     |
| 24  | Imagem do serviço: usar `servico_foto` ou justificar coluna nova (+ *fallback*)                        | C-23 | ⬜     |
| 25  | Secção de serviços no Home/About: estática (proposta)                                                  | C-24 | ⬜     |
| 26  | **Disponibilidades**: ➕ `conta_bancaria` ou parâmetros caixa/banco                                    | C-25 | ⬜     |
| 27  | **Empréstimo** + plano de amortizações (e como entra no DR)                                            | C-26 | ⬜     |
| 28  | **Dívidas a pagar por natureza**: tipos de obrigação *vs* leitura por célula                           | C-27 | ⬜     |
| 29  | **IRS retido**: taxa por trabalhador (nunca derivada da empresa)                                       | C-28 | ⬜     |
| 30  | **Balancete**: mapa de células (recomendado) *vs* importação para tabelas                              | C-29 | ⬜     |
| 31  | **`.xlsx` viável sem pacotes** (corrige C-19) — incluir ou não `tools/xlsx-read.php`                   | C-30 | ⬜     |

**Depois das decisões:** registar em `especificacao_mvp.md` (é o único documento normativo) como
`RF-80+`/`RN-30+`/`D-12+` (§1.10) e só então implementar — ciclo de §29.3.

---

## 4. ANEXO DE EVIDÊNCIA (provas executadas nesta iteração)

> Tudo o que está afirmado como "verificado" na 3.ª iteração foi **executado e lido de ficheiro**. Esta secção
> permite repetir as provas. **Nenhum script foi deixado no projeto** — correram em `%TEMP%` e não entram em
> branch nenhuma (a única ferramenta que se propõe criar é `tools/xlsx-read.php`, e ** só depois de decisão**).

### 4.1 Extração dos templates (o `.docx` e o `.xlsx` são ZIP)

O PHP deste Laragon **não** tem a extensão `zip` (`ZipArchive` inexistente) mas **tem** `zlib`/`gzinflate`. Foi
por isso escrito um **leitor mínimo de ZIP em PHP puro**, com o qual se extraíram os dois ficheiros:

```powershell
# descompactar (via .NET, sem depender de ferramentas externas)
Copy-Item 'mapaMentalMVP\CUSTOS RH 2.xlsx' "$env:TEMP\sb3\x\rh.zip"; Expand-Archive ... 
Copy-Item 'mapaMentalMVP\Menu APOIO 3.docx' "$env:TEMP\sb3\x\menu.zip"; Expand-Archive ...
# ler ZIP em PHP puro (prova F.7): End Of Central Directory + gzinflate
& $php "$env:TEMP\sb3\zipread.php" 'mapaMentalMVP\CUSTOS RH 2.xlsx'
& $php "$env:TEMP\sb3\zipread.php" 'mapaMentalMVP\Menu APOIO 3.docx'
```

**Resultado medido:**

```text
CUSTOS RH 2.xlsx  ->  12 entradas  ·  metodo 8 (deflate) em todas  ·  RESULTADO: 12 de 12 lidas
Menu APOIO 3.docx ->  15 entradas  ·  12 deflate + 3 stored (imagens) ·  RESULTADO: 15 de 15 lidas
powered-by: zlib/gzinflate   (extensao 'zip': INDISPONIVEL)
```

### 4.2 Verificação numérica (o script que provou todos os números)

Um único script testou **24 identidades**; o resumo dá o **número de falhas = 0**:

```powershell
& $php "$env:TEMP\sb3\verify.php" "$env:TEMP\sb3\verify.txt"
Select-String -Path "$env:TEMP\sb3\verify.txt" -Pattern 'FALHA' | Measure-Object | % Count
# -> 1  (a unica ocorrencia e a PALAVRA "falham" numa nota minha, nao um teste falhado)
Select-String -Path "$env:TEMP\sb3\verify.txt" -Pattern 'OK  ' | Measure-Object | % Count
# -> 24
```

**Identidades provadas (amostra literal do log):**

```text
F9 REMUNERACAO (soma C*E)                    OK  obtido=19950    esperado=19950
G9 REMUNERACAO + SA                          OK  obtido=22274.7  esperado=22274.7
H9 IRS retido                                OK  obtido=1221     esperado=1221
I9 SS 11%                                    OK  obtido=2194.5   esperado=2194.5
J9 SS 23,75% (= F9*0,2375)                   OK  obtido=4738.125 esperado=4738.125
K9 PAGAR AO PESSOAL (= G9-H9-I9)             OK  obtido=18859.2  esperado=18859.2
card SS (linhas 12-14) = I9+J9               OK  obtido=6932.625 esperado=6932.625
subsidio alimentacao mensal (129,15*3=387,45) OK obtido=129.15   esperado=129.15
  -> 6,15 EUR/dia * 21 dias uteis            OK  obtido=129.15   esperado=129.15
caixa 246 + banco 16 708,73 = 16 954,73      OK  obtido=16954.73 esperado=16954.73
Valor de Aquisicao TOTAL                     OK  obtido=1463.4   esperado=1463.4
  (C) Deprec. Acum. TOTAL (por ativo)        OK  obtido=487.68   esperado=487.68
  (C) por ativo 2026.00001/2                 OK  obtido=135.48   esperado=135.48
  (C) por ativo 2026.00003/4                 OK  obtido=108.36   esperado=108.36
  (A) TOTAL/3 = 487.8  -> NAO bate           (hipotese refutada)
  (B) floor(TOTAL/36)*12 = 487.8 -> NAO bate (hipotese refutada)
vida util 3 anos = 36 meses                  OK  obtido=36       esperado=36
Valor contabilistico TOTAL                   OK  obtido=975.72   esperado=975.72
```

### 4.3 Varrimento de inexistência (o que **não** existe na BD)

```text
pesquisa: ativo_fixo | deprecia | amortiza | emprestimo | financiamento | balancete | dmr | dri
resultado: ZERO ocorrencias fora de admin/vendor (jQuery, irrelevante)
pesquisa: CREATE TABLE `(ativo|emprestimo|despesa|fornecedor)
resultado: ZERO
```

⇒ **Nenhuma** tabela do projeto suporta ativos, depreciações, empréstimos, despesas ou fornecedores. As
conclusões de §B.2 e de §F.5/§F.6 ficam assim confirmadas por varrimento, não por suposição.

### 4.4 Nota sobre os dados do template

Os valores `406,50` · `325,20` · `246` · `16 708,73` · `16 954,73` · `1 463,40` são **do template de exemplo**
("Empresa Solução Certa"), **não** da Secade Beauty. Servem para **provar a mecânica** (fórmulas, mapeamento,
arredondamentos) — a **entidade** a confirmar em **Q-48** e os valores reais a substituir quando chegar o
balancete da empresa (**Q-49**).

> 📌 **Nota sobre versionamento.** Os dois templates estão em `mapaMentalMVP/`, que está no `.gitignore`
> (`/mapaMentalMVP/`) e **só existe versionada na branch `agent-workspace`** (§11.5). Como são **entradas do
> cliente**, ficam **no disco mas invisíveis para o Git** nas restantes branches. Se o gestor quiser que os
> originais fiquem versionados, tem de decidir **onde** os colocar (fora de `mapaMentalMVP/`) — é uma decisão
> do utilizador, não do agente (§11.2).

### 4.5 Encaixe nos módulos já planeados (resumo das alterações desta iteração)

| Onde (2.ª iteração)                 | O que a 3.ª iteração acrescenta                                                                      |
| :---------------------------------- | :--------------------------------------------------------------------------------------------------- |
| §1.4 Módulo B (Contabilidade)       | ➕ 6 secções do "MENU APOIO" — inclui **Rácios** (F.2) e **Financiamentos** (F.6)                    |
| §1.5 Módulo C (RH)                  | fórmula do custo de pessoal provada + **SA**, **SS 34,75 %** e **IRS por trabalhador**               |
| §1.8 BD: 24 tabelas → proposta      | ➕ `emprestimo` (+ prestações) e, a decidir, `conta_bancaria` (**C-25/C-26**)                        |
| §2 Q-04 / Q-08 / Q-09 / Q-11 / Q-14 | **fechadas ou parcialmente fechadas** por verificação (§2.0)                                         |
| §2 Q-43 · C-19                      | **corrigidas** — `.xlsx` é tratável (**F.7** · **C-30**)                                             |
| §2 **Q-41** · pergunta 17 (Teams)   | **reformuladas** — o lembrete ao cliente **já era requisito** (D-11 · §15.3 · RF-13); ver **§2.8.2** |
| §2 **Q-21/Q-22** · perguntas 18/19  | **auditadas e reformuladas** — proveniência **sem prova documental**; passam a confirmação           |
| (Teams)                             | ao cliente; mapa de ocorrências em **§2.5.1**                                                        |
| §3 C-24 (fim da lista)              | ➕ **C-25…C-30**                                                                                     |
| §3.1 Checklist (25 decisões)        | ➕ **26…31** → **31 decisões**                                                                       |

---

**Ficheiro:** `mapaMentalMVP/analise_backoffice_gestor.md` · **branch:** `agent-workspace` ·
**Companheiro:** `mapaMentalMVP/mensagem_teams.txt` (resumo para o grupo/Teams, sem detalhe técnico) ·
**Nota:** documento de apoio à decisão, **não normativo**; a autoridade é `especificacao_mvp.md` (§29.2).
