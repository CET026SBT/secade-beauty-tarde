# ANÁLISE FUNCIONAL — BACKOFFICE DO GESTOR (PROPOSTA DE FUSÃO)

<!-- md-wrap-tables:max=220 — a tabela do ponto 3 tem 5 colunas com muitos spans de
     código longos; 217 colunas é o mínimo possível sem partir palavras ao meio. -->

**Ficheiro de trabalho (não normativo)** · branch **`agent-workspace`** · **4.ª iteração** · 25/09/2026

**Entrada analisada (iteração 1):** requisitos refinados do *Painel de Backoffice — Tipo de Conta: Gestor*
(Resumo/Dashboard · Contabilidade e Gestão Financeira · Recursos Humanos · Promoções e Campanhas) +
antecipação do perfil **Funcionário**.

**Entrada analisada (iteração 2):** três requisitos de **frontend/catálogo**
(§1.11): secção resumida de serviços no **Home** e no **About** · **IVA incluído** nos valores mostrados
ao cliente · **imagens nos cards** de serviço do wizard de agendamento. Mais os **INPUTs** de
esclarecimento (perguntas do cliente) — ver §2.7 e o ficheiro `mensagem_teams.txt`.

**Entrada analisada (iteração 3):** dois **templates informativos** entregues pelo cliente:
`Menu APOIO 3.docx` (estrutura de menus do software de contabilidade + 3 capturas) e `CUSTOS RH 2.xlsx`
(folha de cálculo do custo de pessoal). Eram **modelos**, não dados reais; serviram de base até 24/09/2026.
Cruzamento e achados em **§1.12 (Módulo F)** · dúvidas em **§2.10** · conflitos **C-25…C-30**.

**Entrada analisada (iteração 4 — esta):** os dois ficheiros **novos** que **substituem** os da iteração 3 e
passam a ser a **fonte de verdade**: `_dev/mapaMentalMVP/Menu Dashboard.docx` (estrutura de menus e o
**mapa de células** de cada Card, com capturas dos dados da empresa) e
`_dev/mapaMentalMVP/Contabilidade Secade Beauty.xlsx` (**9 folhas**: Menu Dashboard · Menu Rácios ·
Lista de Inventários · Mapa Depreciações · Menu Financiamento · Análise financeira · Menu Recursos
Humanos · Custos Funcionários · DMR-DRI). Já **não** são modelos anónimos: identificam a **empresa**, os
**trabalhadores** e **dados reais** de janeiro a março de 2026. Os ficheiros anteriores **só** se invocam
onde **contradigam** os novos — lista fechada em **§1.12 · F.9**.

**Revisão desta iteração (auditoria de artefactos):** foi feito um **varrimento de artefactos** a todo
este documento. O que estava afirmado **sem prova** (ficheiro + linha) está identificado, com a prova
e a correção, em **`_dev/mapaMentalMVP/auditoria_plataforma.md`** — casos **F-01…F-07**.

**Cruzado com:** `especificacao_mvp.md` v1.1 (§2–§5, §11–§13, §17–§19, §22, §24–§26, §28, §29), os
**ficheiros da iteração 4** (`Menu Dashboard.docx` · `Contabilidade Secade Beauty.xlsx`) e o código/BD reais
(`index.php`, `app/config/api.php`, `app/{controllers,services,repositories}`, `modules/main/`,
`modules/backoffice/`, `DataBase_v2.sql` · `DataBase_v3.sql`).

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
| Lembretes: fornecedores          | barra superior                       | idem (tipos novos)                   | AlertService + SupplierService        | ➕ `notificacao` **ou** extensão de                   | ⬜     |
|                                  |                                      |                                      |                                       | `obrigacao_fiscal`                                    |        |
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
| Funcionário: agenda própria      | `/gestao/agenda`                     | `admin-employee-agenda-list`         | BookingService ✚ + RBAC              | ✚ (dados existem)                                    | ⬜     |
| Funcionário: comissões           | `/gestao/agenda`                     | `admin-employee-commission-list`     | GreenReceiptService ✚                | ✚ (dados existem em `agendamento_servico`)           | 🟡     |
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

| Peça                     | Já existe?                                                             | Proposta                                                              |
| :----------------------- | :--------------------------------------------------------------------- | :-------------------------------------------------------------------- |
| Persistência de alertas  | ✅ `alerta_fiscal` (`tipo_alerta` 30/15/7/3/1/atraso, `data_alerta`,   | Reutilizar como **fonte primária** do contador:                       |
|                          | `visualizado`, chave única)                                            | `COUNT(*) WHERE visualizado = 0`                                      |
| API de alertas           | ✅ `admin-fiscal-alert-list` · `admin-fiscal-alert-read` (§19.3)       | Manter e acrescentar `admin-alert-summary` (contador para a barra)    |
| Construção dos alertas   | ✅ `FiscalService::generateAlerts()` on-demand e idempotente (§13)     | Manter o mesmo padrão para os novos tipos de lembrete (sem CRON — P6) |
| Local na UI              | ✅ `modules/backoffice/components/menuUserBo.php` +                    | O sino entra **no menu do utilizador** (barra superior), como pedido  |
|                          | `modules/backoffice/includes/boNavbar.php` (⚠️ corrigido — a auditoria |                                                                       |
|                          | de artefactos **F-03**; não existe `includes/` na raiz)                |                                                                       |
| Lembretes a fornecedores | ⬜ (o enum `obrigacao_fiscal.tipo` só tem                              | **Extensão de modelo necessária** → decidir entre extensão do enum ou |
|                          | `iva`,`irc`,`seguranca_social`,`seguros`)                              | entidade nova (conflito **C-02**)                                     |
| "Lido" por conta         | 🟡 `alerta_fiscal.visualizado` é **global** (não por utilizador)       | Com 2+ gestores, quem marca "lido" silencia os outros → decisão em    |
|                          |                                                                        | **C-03**                                                              |

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

| Indicador pedido               | Fonte real já existente                                                                | Nota                                                                 |
| :----------------------------- | :------------------------------------------------------------------------------------- | :------------------------------------------------------------------- |
| Rendimentos (loja vs carrinha) | `agendamento.valor_total` + `local_prestacao` (`loja_fisica`/`carrinha_ambulante`) +   | por `data_hora_pretendida` (mês corrente), separável por canal ✅    |
|                                | `estado_reserva`                                                                       |                                                                      |
| Recebimentos                   | `transacao_financeira` (`sinal_inicial`, `restante_90_porcento`, `pagamento_integral`, | ⚠️ **sem UI** (§25.3) e `sinal_pago` está sempre `0` (§14.1)         |
|                                | `quota_parte_deslocacao`)                                                              |                                                                      |
| Custos de rota                 | `rota_ambulante.custo_estimado_combustivel`, `lucro_servicos`, `lucro_total`           | já calculados no módulo de rotas (§12.2): **receita − combustível**. |
|                                |                                                                                        | **Não existe** custo fixo por rota (o valor de 50 € é só indicador). |
| Obrigações fiscais             | `obrigacao_fiscal` + `alerta_fiscal`                                                   | `valor_estimado` é **introduzido à mão** (§13)                       |
| Prestadores (recibos verdes)   | `agendamento_servico.valor_recibo_verde_funcionario` + `config_recibo_verde`           | valor **snapshot** por aceitação (§11)                               |
| Efetivos                       | `funcionario.salario_base`                                                             | ⚠️ subsídios / 13.º-14.º mês **não** estão modelados                 |
| Caixa por funcionário/dia      | `fecho_caixa_diario` (`total_esperado_faturas`, `total_recolhido_campo`, `diferenca`)  | ⚠️ **sem UI** (§25.3)                                                |
| Gorjetas                       | `gorjeta`                                                                              | sem UI (§25.3)                                                       |

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

> 📌 **4.ª iteração.** O modelo **concreto** de ativos + depreciações, financiamentos (empréstimo) e a
> decomposição do custo de pessoal chegaram nos ficheiros do cliente — ver **§1.12 (F.0/F.4/F.5/F.6/F.8)**.
> O gap estrutural de **B.2** mantém-se: **nada disto existe na BD** (varrimento em §4.4: 24 tabelas, nenhuma
> de ativos, empréstimos, despesas ou fornecedores).

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

> **Convenção de endpoints (resolvida):** **uma só** família `admin-*` — `admin-employee-*` para a
> equipa e para a área própria do funcionário (`admin-employee-agenda-list`,
> `admin-employee-commission-list`); e `admin-service-*` (já existente) para a aceitação.

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
- **A minha agenda:** hoje só existe a lista de aceitação — `admin-employee-agenda-list` (dados já existem em
  `agendamento_servico.funcionario_id`).
- **Comissões / recibos verdes individuais:** Σ `valor_recibo_verde_funcionario` por período + histórico por serviço;
  **os dados já estão gravados** (§11) → `admin-employee-commission-list` (reutiliza `GreenReceiptService` ✚).

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

| ID sugerido  | Tema a registar                                                                                                                                                                    |
| :----------- | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RF-75..RF-79 | Painel do gestor: KPIs de gastos/rendimentos, dívidas a fornecedores, sininho com contador                                                                                         |
| RF-80..RF-83 | Contabilidade: ativos/passivos, DR, simulador fiscal (RAI + IRC), tesouraria e IVA                                                                                                 |
| RF-84..RF-85 | RH: listagem com filtro de vínculo; criar/editar/desativar perfil                                                                                                                  |
| RF-86..RF-90 | Promoções: campanhas com datas, tags sazonais, matriz de impacto, aplicação nos 2 canais                                                                                           |
| RF-91..RF-93 | Área do funcionário: agenda própria, comissões individuais, promoções em leitura                                                                                                   |
| RF-94..RF-97 | Frontend: secção resumida de serviços no Home/About · **preços com IVA incluído** · **imagem por serviço nos cards** · IVA apurado para apuramento                                 |
| RN-30..RN-35 | Regras de cálculo: resultado mensal, IRC (0 se RAI ≤ 0), comissões como passivo, desconto no `preco_praticado`, efeito no sinal, critério de período                               |
| D-12..D-16   | Decisões a fixar em §3: modelo de despesas/passivos · modelo de notificações · autorização por página · **origem dos dados (interno vs ficheiros)** · **regime de IVA dos preços** |

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

### 1.12 Módulo F — Contabilidade de apoio e custo de pessoal (ficheiros da 4.ª iteração)

> **Fonte de verdade (iteração 4).** `_dev/mapaMentalMVP/Menu Dashboard.docx` e
> `_dev/mapaMentalMVP/Contabilidade Secade Beauty.xlsx`. **Substituem** o `Menu APOIO 3.docx` e o
> `CUSTOS RH 2.xlsx` (iteração 3), que eram **modelos anónimos**: estes identificam a entidade e trazem
> **dados reais** de **janeiro a março de 2026**. Onde os modelos anteriores **divergem** dos novos, a
> diferença está isolada em **F.9** e **pergunta-se** — não se escolhe por inferência (`.clinerules` §0).
>
> **A entidade deixou de ser uma incógnita.** As capturas do modelo anterior mostravam *"Empresa Solução
> Certa", NIF 513122095*, com ativos `2026.00001..4` — **de exemplo**. Os ficheiros novos trazem a empresa
> real: **EMPRESA SECADE BEAUTY LDA · NIF 514740540 · NISS 11499501734 · Estabelecimento 0001**. Isto
> **encerra a Q-48** (a dúvida sobre se os dados eram reais) e torna os valores **citáveis**, não ilustrativos.

#### F.0 Os 9 blocos do ficheiro novo (o que existe, medido)

`Contabilidade Secade Beauty.xlsx` tem **9 folhas** (o anterior tinha **1**). É a mudança estrutural mais
importante: cada menu pedido passou a ter a sua folha, e o *docx* passou a indicar as **células** de cada Card.

| Folha                   | Conteúdo                                                   | Alimenta o Card            |
| :---------------------- | :--------------------------------------------------------- | :------------------------- |
| `Menu Dashboard`        | Balancete resumido (conta · débito · crédito · saldos)     | Disponibilidades · Dívidas |
| `Menu Rácios`           | **2 quadros**: *Balanço* (linhas 2-7) e *Rácios* (10-15)   | Ativo/Passivo · Capital ·  |
|                         |                                                            | os 5 rácios                |
| `Lista de Inventários`  | 2 ativos, com código · tipo · localização · vida útil      | Lista de ativos            |
| `Mapa Depreciações`     | 2 ativos, com depreciação acumulada e valor contabilístico | Mapa de depreciações       |
| `Menu Financiamento`    | Capital inicial · amortizado · em dívida                   | Financiamentos             |
| `Análise financeira`    | Rendimentos · Gastos · RAI · **IRC (fórmula `RAI * 20%`)** | Análise financeira         |
| `Menu Recursos Humanos` | 6 trabalhadores com **nome** e vencimento base             | Funcionários               |
| `Custos Funcionários`   | A fórmula de pessoal completa (SA, IRS, SS, líquido)       | Custos de funcionários     |
| `DMR - DRI`             | As duas declarações (layouts em captura)                   | Impostos (DMR e DRI)       |

#### F.1 O **mapa de células** deixou de ser um requisito escondido: o cliente entregou-o

Na iteração 3, o *docx* era um **guião** que pedia ao cliente *"identificar aos colegas do CET as células que
devem ir buscar"*. **Nesta iteração o cliente fez exatamente isso:** o `Menu Dashboard.docx` **declara a
célula de cada Card**. O requisito deixou de ser interpretação — é uma **lista verificável**, e foi
**confrontada uma a uma** contra o `.xlsx` (§4.2).

O que se mantém é a **decisão de arquitetura**: ler por mapa de células **vs** importar para tabelas.

| Via                           | O que exige                                                                        | Avaliação                               |
| :---------------------------- | :--------------------------------------------------------------------------------- | :-------------------------------------- |
| (a) Importar para tabelas     | Escrever N registos em tabelas novas, por período, com validação e histórico       | ⚠️ Amplo; cria **duas fontes** do mesmo |
|                               |                                                                                    | número (B.2 · C-20)                     |
| (b) **Mapa de células (ler)** | Guardar **de onde vem cada número** (ficheiro + folha + célula) e ler na altura do | ✅ **É o que o cliente entrega agora**  |
|                               | apuramento                                                                         |                                         |

A decisão fica em **C-29** · a viabilidade técnica em **F.7** · a correção da afirmação errada da 2.ª
iteração (*"`.xlsx` exigiria biblioteca externa"*) em **C-30**. Nota metodológica: a via (b) **não escreve**
na BD de negócio — logo **não colide** com "não alterar a BD sem justificação" (`.clinerules` §1).

**O mapa entregue tem duas lacunas reais** (ambas medidas, não supostas):

1. **`Passivo não corrente`** (Card dos Rácios) — o *docx* **não indica célula nenhuma** (a linha está vazia).
   Na folha o valor é `0` nas três colunas. **Pergunta-se** quais as células (ou se o Card é dispensável
   enquanto for sempre 0).
2. **Os 5 rácios** — o *docx* aponta `C3`/`D3`/`E3`…, mas as **colunas C-D-E** dessas linhas são os valores do
   **Balanço**, não os rácios, e a coluna `E` **nem existe** na folha. Os rácios vivem num **segundo quadro**
   (linhas 11-15, colunas B/C/D). Detalhe e prova em **F.9 · D-01**.

#### F.2 Os 6 menus que o cliente quer (e o que mudou face ao modelo anterior)

O `Menu Dashboard.docx` é mais **curto e preciso** do que o `Menu APOIO 3.docx`: manteve os 6 menus e trocou
as instruções genéricas (*"identificar as células"*) por **células concretas**.

| Menu pedido            | Card's pedidos                                            | Célula declarada (novo)                              |
| :--------------------- | :-------------------------------------------------------- | :--------------------------------------------------- |
| **Dashboard**          | Disponibilidades · Dívidas a receber · Dívidas a pagar    | `E3+E2` · `E8` · `F7+F9+E11+F10`                     |
| **Rácios**             | Ativo corrente · Ativo não corrente · Capital próprio ·   | `B4..D4` · `B3..D3` · `B5..D5` · *(vazio)* ·         |
|                        | Passivo não corrente · Passivo corrente                   | `B7..D7`                                             |
|                        | *+ 5 rácios* (Liquidez, Fundo de Maneio, Autonomia,       | ⚠️ células do *docx* **não existem** — ver **F.9**   |
|                        | Endividamento, Solvabilidade)                             |                                                      |
| **Ativos** *(dividir)* | Lista de inventário de ativos + **Mapa de depreciações**  | 2 folhas do `.xlsx` (não células)                    |
| **Financiamentos**     | Capital inicial · Capital amortizado · Capital em dívida  | `B2` · `B3` · `B4`                                   |
| **Análise financeira** | Rendimentos · Gastos · RAI + **caixa da taxa de IRC** +   | `B2` · `B3` · `B4` · IRC por **fórmula `RAI * 20%`** |
|                        | **caixa do valor de IRC a pagar**                         |                                                      |
| **Recursos Humanos**   | *(dividir)* Funcionários · Impostos (**DMR** e **DRI**) · | nomes `A4..A9` · vencimentos `B4..B9`                |
|                        | Custos de funcionários                                    |                                                      |

**O que o documento novo deixou cair** (existia no modelo anterior e **não** é retomado): *"podem adicionar
gráficos"*, o seletor **mensal / trimestral / anual**, o gráfico *"custos de salários por tipologia
(REMUNERAÇÃO, SS, IRS)"*, o exemplo do **SS** no Dashboard, a **imagem opcional do balanço funcional** e o
gráfico de **rendimentos vs gastos**. Não é **contradição** — é **estreitamento de âmbito**: os gráficos
passam a **opcionais** e o seletor de período **desaparece do pedido** (ver **F.9 · D-06**).

Mantém-se, do documento novo, **um pedido transversal explícito**: em *"Custos Funcionários"*, *"fazer gráfico
para relacionar o valor da remuneração com o valor que o funcionário recebe"* — é o **único** gráfico que os
ficheiros novos continuam a pedir.

#### F.3 Dashboard — o mapa novo, célula a célula (verificado)

Reprodução do que o *docx* declara agora, com o **valor real lido do `.xlsx`** (§4.2). As referências são
células da folha **`Menu Dashboard`**, que é o **balancete resumido** (conta · débito · crédito · saldo devedor
· saldo credor) — já **não** o balancete de um software externo.

| Card                  | Células declaradas    | Valor lido (jan-mar 2026)                                    |
| :-------------------- | :-------------------- | :----------------------------------------------------------- |
| **Disponibilidades**  | `E3 + E2`             | **44 686,42 €** = banco CTT 35 646,37 + caixa 9 040,05       |
| **Dívidas a receber** | `E8`                  | `Clientes` — a célula **existe mas está vazia** (0)          |
| **Dívidas a pagar**   | `F7 + F9 + E11 + F10` | **59 271,23 €** = 1 962,04 + 49 354,66 + 5 651,67 + 2 302,86 |

**Todas as células existem** (exceto `E8`, vazia) — confirmado por leitura do XML da folha, não por
arredondamento visual. **Quatro consequências, agora com números reais:**

1. **Disponibilidades = caixa + banco**, e os dois saldos são **distintos** na contabilidade. Responde a
   **Q-04** e reforça `conta_bancaria` **ou**, no mínimo, **dois parâmetros** (caixa · banco).
2. **"Dívidas a receber" = 0** — coerente com o sistema (recebimentos à cabeça; `sinal_pago` sempre `0`,
   §14.1 · Q-03). Serve de **caso de teste** (deve dar 0).
3. **"Dívidas a pagar" é uma soma de naturezas**: fornecedores (`F7`) · **financiamento** (`F9`) · IVA (`E11`) ·
   Segurança Social (`F10`). ⚠️ **Nota nova:** o IVA vem da coluna **Saldo Devedor** (`E11` = 5 651,67 €, ou
   seja **a favor** da empresa) e é **somado** a três saldos **credores**. Numa leitura económica isso **abate**
   à dívida em vez de a aumentar — é legítimo como *posição líquida*, mas não é o mesmo que "dívidas a pagar".
   **Pergunta-se** se é a posição líquida que se quer (**F.9 · D-02**).
4. **O IVA aparece como saldo do balancete**, e **não** como taxa de venda — confirma o que já se verificara na
   2.ª iteração (§E.2): na BD **não existe** taxa de IVA; o IVA do balancete é **IVA a pagar/receber ao
   Estado**, o mesmo conceito de `obrigacao_fiscal(tipo='iva')`. Reforça **C-17**.

#### F.4 RH — a folha `Custos Funcionários` (e o que mudou no subsídio de alimentação)

A folha nova é **nomeada**: `Custos com os Funcionários`, 9 cabeçalhos, **6 trabalhadores** com **nome** e
vencimento base (a lista nominal está na folha `Menu Recursos Humanos`). A **estrutura da fórmula** é a mesma do
modelo anterior — o que **muda são os números e o SA**.

| Coluna | Cabeçalho           | Fórmula no ficheiro novo           | Significado                                            |
| :----- | :------------------ | :--------------------------------- | :----------------------------------------------------- |
| A      | `remuneração`       | valor base mensal (1200/1000/1250) | salário base do trabalhador                            |
| B      | `SA`                | **`129,15+123+135,30` = 387,45**   | subsídio de alimentação — **3 meses, dias diferentes** |
| C      | `n meses`           | 3                                  | período                                                |
| D      | `REMUNERAÇÃO`       | `A*C`                              | remuneração bruta do período = **19 950,00 €**         |
| E      | `REMUNERAÇÃO` (2.ª) | `D+B`                              | **bruta + SA** = **22 262,40 €**                       |
| F      | `IRS`               | `D*8%` · `D*3,6%` · `D*8,56%`      | retenção na fonte de IRS (**por escalão**)             |
| G      | `SS 11`             | `D*0,11`                           | Segurança Social do **trabalhador**                    |
| H      | `SS 23,75`          | `D*0,2375`                         | Segurança Social da **entidade patronal**              |
| I      | `PAGAR AO PESSOAL`  | `E9-F9-G9`                         | líquido a pagar = **18 846,90 €**                      |

**Os Card's que o cliente pede** (rodapé da folha, linhas 12-14):

| Rótulo        | Valor novo      | De onde vem | Comentário                                                          |
| :------------ | :-------------- | :---------- | :------------------------------------------------------------------ |
| `REMUNERAÇÃO` | **22 262,40 €** | `E9`        | inclui o **subsídio de alimentação** (responde a **Q-09/Q-11**)     |
| `IRS`         | **1 221,00 €**  | `F9`        | é o IRS **retido** (dívida ao Estado), não um custo próprio         |
| `SS`          | **6 932,63 €**  | `G9+H9`     | ✅ **junta trabalhador + empresa** — leitura contabilística correta |

**O achado que muda a implementação — o SA deixou de ser uma constante:**

- **Modelo anterior:** `SA = 129,15 * 3` → tratava **129,15 €/mês** como fixo (6,15 €/dia × **21 dias**, todos
  os meses).
- **Ficheiro novo:** `SA = 129,15 + 123 + 135,30` → os três meses **não são iguais**:
  `129,15 = 6,15 × 21` · `123,00 = 6,15 × 20` · `135,30 = 6,15 × 22` dias. E um dos trabalhadores tem
  `375,15 = 129,15 + 116,85 + 129,15` (**19 + 21 + 21** dias).
- **Consequência:** o subsídio de alimentação é `6,15 € × (dias úteis do mês)` e **os dias úteis variam**. Não
  pode ser uma constante: ou é **dado de entrada por mês**, ou é **calculado** com um calendário de dias úteis.
  Isto **invalida** a leitura "129,15 €/mês fixo" da iteração 3 → **F.9 · D-03** e **C-28**.

**Leituras críticas que subsistem (revalidadas com os números novos):**

1. **A taxa de IRS varia *dentro* da mesma folha** — `8 %` (2 trabalhadores) · `3,6 %` (3) · `8,56 %` (1, o de
   remuneração mais alta). Prova que **a retenção de IRS não é uma percentagem da empresa**: depende do salário
   **e** da situação pessoal. **Não é derivável** de `salario_base` → tem de ser **introduzida** (ou lida por
   célula) → **C-28**.
2. **Segurança Social = 11 % + 23,75 % = 34,75 %** sobre a remuneração base. **O ficheiro novo confirma-o
   explicitamente:** a folha `DMR - DRI` imprime *"Taxa: 34,75 %"* no cabeçalho do DRI. Isto **encerra** a
   dúvida que estava aberta (se o Card da SS devia somar trabalhador + empresa **e** se as taxas eram essas).
3. **`PAGAR AO PESSOAL` subtrai IRS e SS *do trabalhador*, mas não a SS patronal** — não é erro: os 23,75 % são
   custo da empresa, não desconto do trabalhador. **Nota de implementação** para ninguém "corrigir" a fórmula.
4. **6 trabalhadores**, um com escalão e remuneração diferentes. Equipa **pequena** → tratável **à mão**, sem
   motor fiscal. Resolve a tensão de âmbito (**Q-30**) a favor do MVP.
5. **A SS incide só sobre `D` (remuneração), não sobre `E`** (+SA) — base de incidência = **remuneração**.

#### F.5 Ativos e depreciações — a fórmula mudou (medido, não suposto)

O modelo anterior trazia 4 ativos de exemplo com valores repetidos (`406,50` e `325,20`) e **vida útil 3** para
todos. O ficheiro novo traz **2 ativos reais**, com **vidas úteis diferentes** e valores distintos:

| Ativo                     | Código       | Tipo                       | Local. | Aquisição  | Valor           | Vida útil  |
| :------------------------ | :----------- | :------------------------- | :----- | :--------- | :-------------- | :--------- |
| Portátil Apple            | `2026.00002` | Equipamento administrativo | Sede   | 2026-01-01 | 913,99 €        | **3 anos** |
| Carrinha (*"Novo Ativo"*) | `2026.00004` | Equipamento de transporte  | sede   | 2026-01-01 | 44 715,45 €     | **4 anos** |
| **Totais**                |              |                            |        |            | **45 629,44 €** |            |

**Depreciação acumulada e valor contabilístico (jan-mar 2026, medidos no ficheiro):**

| Ativo     | Deprec. acumulada | Valor contabilístico |
| :-------- | :---------------- | :------------------- |
| Portátil  | 76,17 €           | 837,82 €             |
| Carrinha  | 2 794,71 €        | 41 920,74 €          |
| **Total** | **2 870,88 €**    | **42 758,56 €**      |

**A regra foi derivada e provada — e é *diferente* da que se tinha apurado na iteração 3.** Hipóteses testadas
por execução (§4.3), com o resultado medido:

| Hipótese                                                               | Portátil    | Carrinha       | Bate?                   |
| :--------------------------------------------------------------------- | :---------- | :------------- | :---------------------- |
| Regra da iteração 3: `floor(valor / (vida × 12)) × meses`              | 76,14 €     | 2 794,71 €     | ❌ **falha** o Portátil |
| **`mensal = round(valor / (vida × 12), 2)`; acum. = `mensal × meses`** | **76,17 €** | **2 794,71 €** | ✅ **bate ao cêntimo**  |
| `round(valor / (vida × 12) × meses, 2)` (arredondar só no fim)         | 76,17 €     | 2 794,72 €     | ❌ falha a Carrinha     |

Em números: `913,99 / 36 = 25,3886…` → **25,39 €/mês** → `× 3 = 76,17 €` ✔ · `44 715,45 / 48 = 931,5718…` →
**931,57 €/mês** → `× 3 = 2 794,71 €` ✔ · total `2 870,88 €` ✔ · líquidos `837,82 €` e `41 920,74 €` ✔.

**Consequências (todas de implementação):**

1. **A depreciação é calculada por ativo e o mensal é arredondado a cêntimos *antes* de multiplicar pelos meses**
   — arredondar só no fim dá **1 cêntimo** de diferença na Carrinha, e truncar em vez de arredondar dá **3
   cêntimos** no Portátil. É regra, não detalhe.
2. **A vida útil é por ativo** (3 e 4 anos), não um valor global — o esquema tem de a guardar por ativo.
3. **`Deprec. acumulada` ≠ depreciação do período** no caso geral (é acumulada desde o início). O Card do *Mapa
   de depreciações* deve deixar claro **qual** das duas mostra.
4. **O relatório do cliente tem mais colunas do que o modelo:** as capturas mostram `Número de Série` (no
   inventário) e `Valor revalorizado` · `Data última revalorização` · `Imparidade` (no balancete de ativos).
   São **vazias** e o cliente **não** as pediu nos Cards → **fora do âmbito**, mas convém registá-lo.
5. **O `Valor de Aquisição` não é um líquido de IVA "redondo":** `913,99 / 1,23 = 743,08 €` e
   `44 715,45 / 1,23 = 36 354,02 €` — **não** seguem a lógica dos serviços (§E.2). É **coerente** com o regime
   real (a empresa **deduz** o IVA dos ativos): é uma **segunda lógica de IVA** → **Q-53**.

#### F.6 Financiamentos e "Análise financeira" — agora com números

| Menu pedido            | Card's do cliente                                      | Estado no projeto hoje                                               |
| :--------------------- | :----------------------------------------------------- | :------------------------------------------------------------------- |
| **Financiamentos**     | Capital inicial · Capital amortizado · **Capital em    | ⬜ **Não existe** — nem entidade `emprestimo`, nem plano de          |
|                        | dívida** — valores `B2` · `B3` · `B4`                  | amortizações (varrimento em §4.4)                                    |
| **Análise financeira** | Rendimentos · Gastos · **RAI** — `B2` · `B3` · `B4`;   | 🟡 Rendimentos e Gastos já calculáveis (§B.1); **RAI existe como     |
|                        | + caixa de **taxa de IRC** + caixa do **valor de IRC** | conceito** (§1.4); o IRC 20 % **deixou de ser suposto** (ver abaixo) |

**Valores do ficheiro novo:** capital inicial **50 000,00 €** · amortizado **25 983,20 €** · em dívida
**24 016,80 €** (e `inicial − amortizado = 24 016,80` ✔, provado em §4.3). Análise financeira: rendimentos
**24 102,05 €** · gastos **45 807,31 €** · **RAI = −21 705,26 €** (fórmula `B2−B3`).

**Três achados, um deles novo e com risco de dupla leitura:**

1. **⚠️ Há dois "capitais em dívida" diferentes** — e é a primeira vez que isto se pode afirmar com números:
   - folha `Menu Financiamento`: **24 016,80 €** (o que **falta amortizar** do empréstimo);
   - folha `Menu Dashboard`, conta **25 — Financiamento**, saldo credor: **49 354,66 €** (o saldo
     **contabilístico** da dívida), que é também uma das parcelas de *"Dívidas a pagar"* (`F9`).
   São conceitos diferentes (plano de amortização vs conta 25) e o cliente usa **os dois** em Cards
   diferentes. **Pergunta-se qual alimenta qual** (**F.9 · D-04**) — se o Card do Financiamento e a parcela das
   Dívidas a pagar devem mostrar o mesmo número ou não.
2. **O empréstimo é uma entidade própria, com plano de amortizações.** O trio pedido (*inicial · amortizado ·
   em dívida*) é **exatamente** o que sai de uma tabela de amortização. Não basta uma "despesa" → ➕
   `emprestimo` (+ prestações, ou calculadas na leitura) → **C-26**.
3. **O IRC deixou de ser uma suposição.** A folha `Análise financeira` escreve a **fórmula `RAI * 20%`**, e o
   *docx* pede **duas caixas**: uma para a **taxa** e outra para o **valor do IRC a pagar**. Isto:
   - **confirma** a taxa de 20 % para o IRC (deixou de ser inferência);
   - **fecha** a discussão "20 % fixo vs configurável" a favor de **configurável** (**C-14**): o cliente quer
     **mexer nela na UI**;
   - **confirma** que o simulador **não escreve sozinho** em `obrigacao_fiscal` — o gestor vê a estimativa e
     **decide** o valor (**C-05**). *"Boa notícia: não é preciso alterar o desenho."*
   Nota: com RAI **negativo**, o IRC é **0**, e o ficheiro não mostra valor — coerente com a regra "0 se RAI ≤ 0".

**O que isto faz ao módulo B (Contabilidade):** o menu pedido tem **6 secções**, não 5 — a 6.ª são os **Rácios**
(Ativo corrente/não corrente, Capital próprio, Passivo não corrente, ± fundo de maneio), que **só existem com o
balancete** e que **nenhuma tabela do projeto alimenta**. Os Rácios são, por isso, o **melhor candidato** à via
"mapa de células" (F.1b) e o **pior** a "recalcular internamente".

#### F.7 Prova de viabilidade técnica: ler `.xlsx` **sem instalar nada** (corrige C-19/Q-43)

A 2.ª iteração concluiu que ler `.xlsx` exigiria biblioteca externa (proibida — `.clinerules` §1). **Fui verificar
e a conclusão está errada.** Factos medidos no ambiente real, **repetidos sobre os ficheiros novos**:

| Verificação                                         | Resultado (iteração 4)                                  |
| :-------------------------------------------------- | :------------------------------------------------------ |
| Extensão `zip` do PHP disponível?                   | ❌ **não** (`ZipArchive` inexistente)                   |
| Extensão `zlib` disponível?                         | ✅ **sim** (`gzinflate` confirmado)                     |
| `.xlsx`/`.docx` são ZIP?                            | ✅ sim — **26** entradas no `.xlsx` e **23** no `.docx` |
| Dá para as ler com `zlib` **sem** a extensão `zip`? | ✅ **SIM — 26/26 e 23/23 entradas lidas** (0 ilegíveis) |

Um **leitor mínimo de ZIP em PHP puro** (localizar o *End Of Central Directory*, percorrer o diretório central de
cada entrada, `gzinflate` do bloco comprimido) resolve o problema **sem pacote nenhum** — e é o mesmo mecanismo
que já foi usado, nesta análise, para **extrair tudo o que a iteração 4 afirma**. **Prova e comandos em §4.1.**

> ⚠️ **Armadilha medida neste leitor (vale a pena registar).** Uma célula vazia pode vir **auto-fechada**
> (`<c r="D2" s="4"/>`) em vez de `<c r="D2">…</c>`. Um padrão que exija `>` logo a seguir à referência faz a
> célula vazia **engolir o valor da seguinte** — os números aparecem **deslocados uma coluna** e o resultado
> parece plausível. Aconteceu nesta iteração: a primeira extração pôs `E2` onde está `F2` e fez parecer que
> células **existentes** estavam **ausentes** (quase gerou um "achado" falso contra o cliente). **Correção:**
> aceitar `/>` **ou** `>…</c>` na mesma expressão e **conferir contra o XML cru** antes de concluir que um dado
> do cliente falta.

Consequências:

- **`C-19`/`Q-43` deixam de ter fundamento técnico** para excluir `.xlsx` — a limitação era **minha**, não do
  stack. Passa a ser uma decisão de **âmbito** (importar vs mapa de células), não de **possibilidade** →
  **C-30**.
- Deve ser criada, **se essa via for aprovada**, uma ferramenta reutilizável (ex.: `_dev/tools/xlsx-read.php`, com o
  mesmo padrão dos utilitários de `_dev/tools/` — dry-run por omissão, exit code 0/1) e registada em
  `_dev/tools/README.md` e `_dev/tools/README.md` §2. **Só depois da decisão** — não se implementa sem aprovação (§29.3).
- ⚠️ **Limite honesto:** um leitor próprio lê o **conteúdo** (células, valores, fórmulas), **não** a *pintura*
  visual, macros, gráficos nem formatação condicional. Para "ler as células que o cliente indicou" **chega
  perfeitamente**; para "reproduzir o balancete como no Excel" **não**.

> 📌 **Nota de 25/09/2026 — rota decidida, facto inalterado.** A importação passa a usar a biblioteca
> **`PhpSpreadsheet`** do material de formação (§5 · `core.md` §3.12 · D-12 · E-1), com a extensão **`zip`**
> ativa — **medido nesta data**: lê o balancete em **135 ms**, as **9 folhas** e **exatamente os mesmos
> valores** que o leitor próprio (E2/E3/E8/F7/F9/E11/F10 · Rácios `B11..D15` · Financiamento · Análise
> financeira · datas `46023 → 01/01/2026`). Dois métodos independentes a concordar: os números do cliente
> ficam **confirmados**. O leitor em PHP puro (`PharData`/`gzinflate` + `SimpleXML`) continua **viável e
> testado** e fica registado como alternativa (não necessita da extensão `zip`).
> ⚠️ A armadilha da célula auto-fechada (`<c r="D2"/>`) **desaparece** com leitura por XML/AST
> (`SimpleXML` ou `PhpSpreadsheet`) — só afeta leitores por regex.

#### F.8 Os impostos do pessoal — `DMR` e `DRI` (novo: já há declaração)

Este é o **bloco que mais ganhou** na iteração 4. No modelo anterior, *"IMPOSTOS"* era uma linha **genérica**
(*"dividir em DMR e DRI"*, com *"PDF/imagem das declarações"* opcional). Agora existe uma folha **`DMR - DRI`** e
o *docx* cola **as duas declarações reais** — com **campos que o `.xlsx` não tem** (as capturas passam a ser fonte
inegável nessa parte):

| Campo da declaração          | Valor lido                                                                                     |
| :--------------------------- | :--------------------------------------------------------------------------------------------- |
| **Entidade**                 | EMPRESA SECADE BEAUTY LDA · **NIF 514740540** · **NISS 11499501734**                           |
| **Estabelecimento**          | `0001`                                                                                         |
| **Mês de referência**        | `2026/01`                                                                                      |
| **Taxa** (DRI)               | **34,75 %** (= 11 % + 23,75 %)                                                                 |
| **Trabalhadores (DRI)**      | 6 — com **NISS**, **nome**, **data de nascimento**, **nº de dias** (`29,0`) e **código `P`**   |
| **DMR — Rendimentos**        | Sujeitos **6 650,00 €** · Isentos *(vazio)* · **Não Sujeitos 774,90 €** · **Total 7 424,90 €** |
| **DMR — Tipo de Rendimento** | códigos `A` e `A21` por trabalhador                                                            |

**Três leituras:**

1. **A DMR fecha com a folha de custos:** *Rendimentos Sujeitos* `6 650,00 €` = **exatamente** a soma dos 6
   vencimentos base **de um mês**; e *Não Sujeitos* `774,90 €` = `2 × 387,45` (o SA). Prova que a DMR é
   **mensal** e a folha de custos é **trimestral** — **períodos diferentes**, e é preciso não os somar.
2. **A taxa de 34,75 % está impressa na própria declaração** (F.4 · ponto 2) — deixa de haver margem para achar
   que o Card da SS podia mostrar só uma das parcelas.
3. **O `Nº de Dias = 29,0` não bate com os dias usados no SA** (`19`/`20`/`21`/`22`). São **critérios
   diferentes** (dias de trabalho declarados vs dias úteis para o subsídio) ou **um erro numa das folhas** — não
   se infere: **pergunta-se** (**F.9 · D-05**).

> **Nota:** o *docx* novo deixa a linha *"IMPOSTOS –"* **em branco** (o modelo anterior dizia *"dividir em DMR e
> DRI"*). O requisito **subsiste**: a folha `DMR - DRI` existe e traz as duas declarações. A divisão
> **impostos → DMR + DRI** mantém-se, agora com conteúdo.

#### F.9 ⚠️ Onde os modelos anteriores **divergem** dos ficheiros novos

Regra aplicada: os ficheiros novos são a **fonte de verdade**; os anteriores só se invocam **aqui**. Cada linha é
um ponto **específico** — com a prova medida e a pergunta correspondente.

| #    | Ponto                         | Modelo anterior (it. 3)                               | Ficheiro novo (it. 4)                                     | Leitura / ação                                            |
| :--- | :---------------------------- | :---------------------------------------------------- | :-------------------------------------------------------- | :-------------------------------------------------------- |
| D-01 | **Células dos 5 rácios**      | `C3`·`D3`·`E3`… (por linha)                           | Na folha, essas colunas são o **Balanço**; a              | **Confirmar as células**; a leitura provável é            |
|      |                               |                                                       | **coluna E não existe**; os rácios estão no               | `B11`…`D15`                                               |
|      |                               |                                                       | **2.º quadro** (linhas 11-15, cols B/C/D)                 |                                                           |
| D-02 | **IVA em "Dívidas a pagar"**  | IVA como dívida (`K86`)                               | `E11` é **Saldo Devedor** 5 651,67 € somado a 3 credores  | **Posição líquida ou dívida bruta?**                      |
| D-03 | **Subsídio de alimentação**   | `129,15 €/mês` **fixo** (21 dias)                     | `129,15+123+135,30` → **dias variam** (20/21/22;          | O SA **não é constante** → parâmetro por mês              |
|      |                               |                                                       | 19/21/21)                                                 |                                                           |
| D-04 | **Capital em dívida**         | *(não havia valores)*                                 | **24 016,80 €** (plano) **vs** **49 354,66 €** (conta 25) | **Qual alimenta o Card** e a parcela das Dívidas a pagar? |
| D-05 | **Dias**                      | 21 dias úteis (SA)                                    | DRI imprime **29,0 dias**                                 | Critérios diferentes ou erro? **Perguntar**               |
| D-06 | **Âmbito (gráficos/período)** | Gráficos, seletor mensal/trimestral/anual, imagem do  | **Deixou cair** esses pedidos; mantém **só** o gráfico de | **Estreitamento**, não contradição: tratar como           |
|      |                               | balanço funcional                                     | remuneração vs líquido                                    | **opcional**                                              |
| D-07 | **Regra da depreciação**      | `floor(valor/(vida×12)) × meses`                      | `round(valor/(vida×12),2) × meses` — a anterior **falha** | **Adotar a nova** (provada em §4.3)                       |
|      |                               |                                                       | o Portátil (76,14 vs 76,17)                               |                                                           |
| D-08 | **Vida útil dos ativos**      | 3 anos para **todos**                                 | Portátil **3** · Carrinha **4**                           | Vida útil é **por ativo**                                 |
| D-09 | **Entidade e ativos**         | *"Empresa Solução Certa"* NIF 513122095 · 4 ativos de | **SECADE Beauty Lda** NIF 514740540 · **2 ativos reais**  | **Resolvido** (era exemplo) — encerra **Q-48**            |
|      |                               | exemplo                                               |                                                           |                                                           |
| D-10 | **`Passivo não corrente`**    | *(o docx antigo também não dava célula)*              | Valor `0` nas 3 colunas                                   | **Confirmar células** ou dispensar o Card                 |

> **O que fazer com o resto do documento antigo: nada.** As secções §F.1–F.7 foram **reescritas** com os dados
> novos; esta lista existe para que nenhuma divergência se perca. **Nenhuma** destas 10 linhas foi decidida por
> inferência — todas vão para a `mensagem_teams.txt` ou para a checklist §3.1.
## 2. DÚVIDAS TÉCNICAS/NEGOCIAIS

> Todas resultaram **do cruzamento** com o que já existe. Nenhuma é resolúvel por inferência do código —
> cada uma muda números, modelos de dados ou regras.

### 2.0 Estado das dúvidas das iterações anteriores (o que esta iteração fechou)

| Dúvida                                      | Estado agora                                                                                                                                                           |
| :------------------------------------------ | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Q-01** — os preços são com IVA? Que taxa? | ✅ **RESOLVIDA por verificação** (§E.2): `preco_base` é **líquido**, taxa implícita **23 %** (35/35 preços × 1,23 = redondos). Falta só a decisão de **onde** gravar a |
|                                             | taxa (**C-17**)                                                                                                                                                        |
| **Q-01 (variante "onde está o IVA na BD")** | ✅ **RESOLVIDA**: **não existe**; só `obrigacao_fiscal.tipo='iva'` (obrigação, não taxa)                                                                               |
| **Q-09 / Q-11** — que subsídios?            | ✅ **RESOLVIDA quanto à natureza** (§F.4): o subsídio é o de **alimentação** — mas **não é uma constante**:                                                            |
|                                             | `6,15 € × dias úteis do mês` (20/21/22 dias no ficheiro novo) → **Q-63**. O **13.º/14.º mês** continuam                                                                |
|                                             | **não modelados** e continuam em aberto                                                                                                                                |
| **Q-14** — a SS patronal entra no custo?    | ✅ **RESOLVIDA pela folha do cliente** (§F.4): o Card da SS **junta** trabalhador (11 %) **e** empresa (23,75 %) = **34,75 %**                                         |
| **Q-04** — uma conta ou várias?             | 🟡 **PARCIALMENTE RESOLVIDA** (§F.3): o balancete mostra **caixa e banco separados** (**9 040,05 €** +                                                                 |
|                                             | **35 646,37 €**); resta decidir se são ➕ tabela `conta_bancaria` ou parâmetros — **C-25**                                                                             |
| **Q-08 / C-14** — IRC 20 % fixo ou config.? | ✅ **RESOLVIDA** (§F.6): o cliente quer **editar a taxa na UI** → **configurável**, com o valor de IRC **decidido pelo gestor** (confirma **C-05**)                    |
| **Q-43 / C-19** — `.xlsx` é tratável?       | ✅ **RESOLVIDA (e corrigida)** (§F.7): **é** tratável com `zlib` em PHP puro — **26/26** entradas no `.xlsx` e **23/23** no `.docx`. A limitação anterior era          |
|                                             | **incorreta** → **C-30**                                                                                                                                               |
| **Q-41 / pergunta 17 (Teams)** — o          | ✅ **JÁ DECIDIDO na especificação** — **não é dúvida**: **D-11** · §15.3 · **RF-13** · §28.2/13 fixam o **lembrete das ≤ 24 h** ao cliente (sugere loja física ou      |
| cliente recebe lembretes?                   | reagendamento); falta **implementar** (§24.6). Ficam abertos apenas: **(a)** 1 ou 2 mecanismos (**C-21**) e **(b)** onde aparece (*"e/ou"* de §24.6)                   |
| **Q-48 · Q-50 · Q-57** (it. 3)              | ✅ **FECHADAS** pelos ficheiros novos: entidade identificada (**SECADE Beauty Lda**), mapa de células entregue e divisão **DMR/DRI** existente                         |
| **Q-02 … Q-30**, **Q-31…Q-47** e            | ⬜ **em aberto** — seguem válidas; as novas da iteração 4 são **Q-58…Q-65** (§2.10)                                                                                    |
| **Q-49 · Q-51…Q-56**                        |                                                                                                                                                                        |

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

| ID   | Dúvida                                                                                           | Impacto / opções                                              |
| :--- | :----------------------------------------------------------------------------------------------- | :------------------------------------------------------------ |
| Q-23 | O contador de alertas é **por utilizador** ou **global**? (`alerta_fiscal.visualizado` é global) | Com 2+ gestores, o "lido" de um silencia o outro → **C-03**   |
| Q-26 | Os lembretes mantêm-se **on-demand** (sem CRON — §22.1)?                                         | Confirmação de que o projeto continua sem CRON                |
| Q-27 | O sino agrega **tudo** num contador ou separa por origem (fiscal · fornecedores · operacional)?  | Muda o componente do topo (`menuUserBo.php`) e a UX do gestor |

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

| ID   | Pergunta do cliente                                       | O que o código/BD diz hoje                                                          | Opção / decisão necessária                                  |
| :--- | :-------------------------------------------------------- | :---------------------------------------------------------------------------------- | :---------------------------------------------------------- |
| Q-31 | Os KPIs vêm dos `.csv`/`.xls` do Balancete ou de serviços | Rendimentos **existem** (`agendamento.valor_total` + `local_prestacao`, por canal); | **Interno como fonte primária** + registo/importação manual |
|      | internos da plataforma?                                   | custos de rota **existem** (`rota_ambulante`); despesas externas **não existem**    | só para despesas externas (**C-19**)                        |
| Q-32 | O que são "entradas brutas"? De onde vêm (banco ou        | Ambíguo: pode ser **receita faturada** (serviços) ou **entradas de caixa** (banco)  | Proposta:                                                   |
|      | serviços)?                                                |                                                                                     | **receita bruta = Σ serviços prestados, COM IVA**, antes de |
|      |                                                           |                                                                                     | deduzir custos — distinta de *recebimento*                  |
| Q-33 | O lucro das vendas efetuadas vem da plataforma ou de      | A receita e o custo de rota são **100 % internos**; nada de vendas vem de ficheiros | **Interno**, sem exceção (evita divergência)                |
|      | ficheiros externos?                                       |                                                                                     |                                                             |
| Q-34 | O que é a "margem de lucro" e como se calcula?            | Não existe cálculo hoje                                                             | Proposta em §2.7.1 (fórmula explícita e auditável)          |
| Q-35 | De onde derivam internamente as despesas operacionais?    | Internamente só:                                                                    | Interno o que existe + **entrada manual** para o resto      |
|      | Incluem CSV/XLS?                                          | **combustível das rotas + comissões de recibos verdes + salários base**.            | (formulário primeiro; CSV como conveniência)                |
|      |                                                           | Rendas, consumíveis, eletricidade, seguros, manutenção: **nada**                    |                                                             |
| Q-36 | As obrigações fiscais são as definidas na página Fiscal?  | ✅ Sim — `obrigacao_fiscal` (§13), `tipo` ∈ {iva, irc, seguranca_social, seguros},  | Confirmar: o **IVA apurado** (calculado) **não** substitui  |
|      |                                                           | valor **manual**                                                                    | a obrigação manual (§2.7.2)                                 |

#### 2.7.1 Proposta de definição da margem de lucro (Q-34)

```text
Receita bruta (periodo)  = SOMA agendamento.valor_total  [so estados executado/concluido]
Custos variaveis         = comissoes de recibos verdes (valor_recibo_verde_funcionario)
                         + combustivel das rotas (rota_ambulante.custo_estimado_combustivel)
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

| ID   | Pergunta                                                            | O que o código/BD diz hoje                                           | Opção / decisão necessária                                           |
| :--- | :------------------------------------------------------------------ | :------------------------------------------------------------------- | :------------------------------------------------------------------- |
| Q-44 | A secção de serviços no Home/About é **estática** ou gerida no      | `hero`, `about` e `testimonial` são **componentes estáticos**; não   | **Estática** (proposta), como o About — evita um módulo de CMS fora  |
|      | backoffice?                                                         | existe CMS                                                           | do âmbito                                                            |
| Q-45 | Quem fornece as **imagens dos serviços** e em que formato/dimensão? | Não existe nenhuma imagem de serviço. As **3** categorias em uso são | A definir com o cliente: formato (`.jpg`/`.png`), dimensão e quem    |
|      |                                                                     | `.png`                                                               |                                                                      |
|      |                                                                     | carregadas por *slug* (7 ficheiros no total — **F-07**)              | carrega                                                              |
| Q-46 | A taxa de IVA é **uniforme (23 %)** ou                              | Não há taxa em lado nenhum; a aritmética aponta para 23 % em         | Confirmar com o grupo de contabilidade (há serviços com taxas        |
|      | **varia por serviço/categoria**?                                    | **todos** os 35 serviços                                             | diferentes em Portugal)                                              |
| Q-47 | Os **preços de montra** (redondos) devem substituir os atuais na    | A BD tem os líquidos (4,07 €); a montra implica 5,00 € com IVA       | Decidir com **C-17**; a proposta é manter o líquido na BD e gravar o |
|      | BD?                                                                 |                                                                      | bruto na marcação                                                    |

### 2.10 Dúvidas — contabilidade de apoio e custo de pessoal (4.ª iteração)

> **Reescrita nesta iteração** com os ficheiros novos. As perguntas que os ficheiros **responderam** ficam
> marcadas ✅ e **saem** da `mensagem_teams.txt`; as que **nasceram** do confronto (F.9) entram como novas.

| ID   | Pergunta / decisão                                                    | O que os ficheiros dizem hoje                                       | Ação                                                                |
| :--- | :-------------------------------------------------------------------- | :------------------------------------------------------------------ | :------------------------------------------------------------------ |
| Q-48 | ~~A entidade das capturas é real ou de exemplo?~~                     | ✅ **RESOLVIDA:** os ficheiros novos identificam                    | Fechada. **Sem pergunta** ao cliente.                               |
|      |                                                                       | **EMPRESA SECADE BEAUTY LDA** · NIF 514740540 · NISS 11499501734    |                                                                     |
| Q-49 | O balancete em `.xlsx` — mantém as **abas** e a **ordem** de emissão  | O ficheiro novo tem **9 folhas nomeadas**; a prova é que as células | **Alimenta C-29/C-30.** Perguntar se as **posições** se mantêm de   |
|      | a                                                                     |                                                                     |                                                                     |
|      | cada emissão, ou mudam?                                               | indicadas **batem** nas folhas atuais                               | emissão para emissão (**F.9 · D-01**).                              |
| Q-50 | ~~O mapa de células mantém-se ou muda?~~ *(as células antigas já não  | ✅ O cliente **entregou** um mapa novo e **verificado**             | Substituída por **Q-58** e **Q-59** (as duas lacunas do mapa novo). |
|      | existem)*                                                             |                                                                     |                                                                     |
| Q-51 | As **dívidas a pagar** decompõem-se por natureza — confirmam          | O novo mapa dá **fornecedores · financiamento · IVA · SS**          | Confirmar as naturezas **e** a leitura do IVA (**Q-60**).           |
|      | **fornecedores · financiamento · IVA · Segurança Social**?            | (`F7` + `F9` + `E11` + `F10`)                                       |                                                                     |
| Q-52 | A **localização** do ativo (Sede · loja · carrinha) é relevante para  | 2 ativos, ambos em **Sede/sede**; não existe entidade `ativo`       | Define se o ativo é "da empresa" ou "de um local" — e se a          |
|      | o mapa de depreciações?                                               |                                                                     | depreciação entra em custos **por canal**.                          |
| Q-53 | O **valor de aquisição** é líquido, bruto não dedutível, ou bruto com | `913,99 / 1,23` e `44 715,45 / 1,23` **não** dão valores redondos   | Confirmar que é **valor contabilístico** (IVA dedutível) e que      |
|      | IVA dedutível?                                                        | (ao contrário dos preços de montra, §E.2)                           | **não** se reconcilia com a lógica dos serviços.                    |
| Q-54 | A **depreciação acumulada** do Card é a do **período** ou a           | ✅ **Regra derivada e provada** (§F.5 · §4.3): mensal =             | **Confirmar a fórmula** e decidir **qual** das duas leituras o Card |
|      | **acumulada desde a aquisição**?                                      | `round(valor / (vida×12), 2)` × meses                               | mostra (nos ativos de janeiro coincidem).                           |
| Q-55 | Os **movimentos de ativos** (compras e **alienações/vendas**) entram  | 2 ativos, ambos adquiridos em **2026-01-01**; nada de abates        | Se houver vendas, é preciso **documento + data de saída** e cálculo |
|      | no âmbito, ou só os ativos existentes à data?                         |                                                                     | da mais-valia — **âmbito novo** se sim.                             |
| Q-56 | O **empréstimo** é **um só** ou vários? A taxa e o prazo são fixos    | Capital inicial **50 000** · amortizado **25 983,20** · em dívida   | Define ➕ `emprestimo` + plano de amortizações (**C-26**) e resolve |
|      | durante o contrato?                                                   | **24 016,80**                                                       | **Q-61** (dois "capitais em dívida").                               |
| Q-57 | ~~Os impostos do RH dividem-se em DMR e DRI?~~                        | ✅ **RESOLVIDA:** existe a folha `DMR - DRI` e as capturas das duas | Fechada quanto à **divisão**. Resta **Q-62** (anexos).              |
|      |                                                                       | declarações; o DRI imprime a taxa **34,75 %**                       |                                                                     |

**Perguntas novas, nascidas do confronto antigo↔novo (F.9):**

| ID   | Pergunta                                                                                              |
| :--- | :---------------------------------------------------------------------------------------------------- |
| Q-58 | **Rácios:** as células indicadas apontam para as colunas do *Balanço* e a coluna `E` **não            |
|      | existe** na folha. Os 5 rácios estão no **2.º quadro** (linhas 11-15, colunas B/C/D).                 |
|      | Confirmam **`B11..D15`**?                                                                             |
| Q-59 | **Passivo não corrente:** não indicaram **célula nenhuma** e o valor é `0` nas três colunas.          |
|      | Quais as células — ou o Card é dispensável enquanto for sempre 0?                                     |
| Q-60 | **IVA nas "Dívidas a pagar":** o valor vem do **Saldo Devedor** (IVA a favor) e é somado a três       |
|      | saldos credores. É a **posição líquida** que querem, ou a dívida **bruta**?                           |
| Q-61 | **Capital em dívida:** o plano de financiamento dá **24 016,80 €** e a conta 25 do balancete dá       |
|      | **49 354,66 €** (que também entra nas Dívidas a pagar). Qual alimenta o Card?                         |
| Q-62 | **Anexos fiscais:** querem o **PDF/imagem das declarações** anexado ao registo do calendário?         |
|      | (Hoje o calendário fiscal **não** guarda anexos.)                                                     |
| Q-63 | **Subsídio de alimentação:** o valor varia com os **dias úteis do mês** (20/21/22 dias × 6,15 €).     |
|      | Confirmam que é **introduzido por mês**, ou devem os dias ser calculados?                             |
| Q-64 | **Dias:** a DRI declara **29 dias** por trabalhador; o subsídio usa **~21**. São critérios            |
|      | diferentes ou há uma gralha numa das folhas?                                                          |
| Q-65 | **Gráficos e período:** o documento novo **deixou cair** os gráficos e o seletor mensal/              |
|      | trimestral/anual. Mantêm só o gráfico *remuneração vs líquido* em Custos de funcionários?             |
| Q-66 | **Origem dos dados importados:** os valores **vindos dos ficheiros do cliente** (balancete) e os      |
|      | valores **calculados pela própria plataforma** (receita de agendamentos, custos de rota, comissões)   |
|      | podem entrar **somados no mesmo indicador**? Qual das duas fontes **prevalece** em cada cartão?       |
|      | (Ex.: "Gastos" do balancete *vs.* custos de rota que o sistema já conhece.) → **pergunta 36** de      |
|      | `mensagem_teams.txt`. Enquanto não houver resposta, cada widget **declara a sua origem** (D-12 · §5). |

#### 2.10.1 Nota de implementação — fórmula do custo de pessoal (corrigida)

```text
Por trabalhador (periodo = n meses):
  remuneracao_bruta_p = salario_base * n
  SA_p                = soma(dias_uteis_mes * 6.15)        <- VARIA por mes (F.4)
  IRS_p               = remuneracao_bruta_p * taxa_irs_p    <- taxa POR trabalhador
  SS_trab_p           = remuneracao_bruta_p * 0,11
  SS_empresa_p        = remuneracao_bruta_p * 0,2375
  liquido_a_pagar_p   = remuneracao_bruta_p + SA_p - IRS_p - SS_trab_p

Cards:
  REMUNERACAO = soma(remuneracao_bruta_p + SA_p)   -> 22 262,40 (jan-mar, 6 trab.)
  IRS         = soma IRS_p                         ->  1 221,00
  SS          = soma(SS_trab_p + SS_empresa_p)     ->  6 932,63   (= 34,75 %)
```

**Regras provadas pelos ficheiros novos:** a base de incidência da SS é a **remuneração bruta**, *não* `+ SA`;
a taxa de IRS é **por trabalhador**; o líquido a pagar **não** desconta a SS patronal; e **o SA não é uma
constante mensal** — é `6,15 € × dias úteis do mês`.

## 3. CONFLITOS A RESOLVER MANUALMENTE

> Pontos em que os **novos requisitos colidem com regras/modelos já definidos** e que **não podem ser
> decididos por inferência**. Nenhum foi alterado: cada linha está à espera de decisão do gestor.

| ID   | Requisito novo                                | Regra / artefacto existente                                      | Natureza do conflito                    | Decisão necessária (recomendação)                    |
| :--- | :-------------------------------------------- | :--------------------------------------------------------------- | :-------------------------------------- | :--------------------------------------------------- |
| C-01 | Painel de entrada do gestor                   | `/gestao` **já é** a lista de agendamentos (`index.php` L30 →    | Duas funções para a mesma rota          | **`/gestao` = painel** e                             |
|      |                                               | `modules/backoffice/appointments.php`)                           |                                         | `/gestao/agendamentos` mantém a lista                |
|      |                                               |                                                                  |                                         | (rota já existe). Alternativa: painel                |
|      |                                               |                                                                  |                                         | em `/gestao/painel`                                  |
| C-02 | Lembretes de **fornecedores** (não fiscais)   | `obrigacao_fiscal.tipo` ∈                                        | Alargar o enum mistura encargos fiscais | Recomendação: ➕ `notificacao` genérica              |
|      |                                               | {`iva`,`irc`,`seguranca_social`,`seguros`} + `alerta_fiscal`;    | com não fiscais; criar entidade nova    | (com `origem`) alimentada pelo mesmo                 |
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
|      | balancete — §F.3)                             | inicial"* como parâmetro de configuração                         | dashboard não é calculável              | inicial, tipo) **ou** **(2)** manter só dois         |
|      |                                               |                                                                  |                                         | parâmetros. **Valores reais:** caixa **9 040,05 €**  |
|      |                                               |                                                                  |                                         | + banco **35 646,37 €**                              |
| C-26 | **Capital inicial · amortizado · em dívida**  | Não existe entidade `emprestimo` nem plano de amortizações;      | Terceira fonte de passivo, a par de     | ➕ `emprestimo` + prestações (tabela **ou** cálculo  |
|      | (§F.6)                                        | o saldo da conta 25 vive no balancete                            | `despesa` (C-04) e da conta 25          | na leitura). **Decidir qual dos valores alimenta o   |
|      |                                               |                                                                  |                                         | card** — plano (**24 016,80 €**) ou conta 25         |
|      |                                               |                                                                  |                                         | (**49 354,66 €**) → **Q-61**                         |
| C-27 | **Dívidas a pagar decompostas por natureza**  | `obrigacao_fiscal` já tem `iva` e `seguranca_social`; **não**    | Dois sítios a dizer o mesmo número =    | **(1)** mapear as naturezas novas para os **tipos    |
|      | (§F.3)                                        | tem `fornecedores` nem **retenções na fonte**                    | risco de **dupla contagem** (C-20)      | de obrigação** existentes/novos, **ou** **(2)** ler  |
|      |                                               |                                                                  |                                         | por célula e **não** replicar no calendário.         |
|      |                                               |                                                                  |                                         | **Naturezas no ficheiro novo:** fornecedores ·       |
|      |                                               |                                                                  |                                         | financiamento · IVA · Segurança Social               |
| C-28 | **IRS retido na fonte** precisa de **taxa por | `funcionario` só tem `salario_base`; não há taxa de IRS em       | Se se derivar uma % única da empresa,   | **Não derivar.** Taxa introduzida por trabalhador    |
|      | trabalhador** (§F.4: 8 % / 3,6 % / 8,56 %)    | lado nenhum                                                      | os recibos saem **errados**             | (ou lida por célula); documentar que **não é**       |
|      |                                               |                                                                  |                                         | constante da empresa. ⚠️ **Somou-se um segundo       |
|      |                                               |                                                                  |                                         | caso:** o **subsídio de alimentação** também varia   |
|      |                                               |                                                                  |                                         | (dias úteis do mês) → **Q-63**                       |
| C-29 | **Importar o balancete para tabelas** *vs*    | Nada existe; mas o cliente **entregou** o **mapa de células**    | Decidir isto define **todo** o módulo   | **Recomendada a via (b) — mapa de células**: cumpre  |
|      | **ler por mapa de células** (§F.1)            | desta iteração, **verificado célula a célula** (§F.3)            | da contabilidade e o esforço de teste   | o pedido literal, não duplica fontes (C-20) e não    |
|      |                                               |                                                                  |                                         | escreve na BD de negócio. **Falta confirmar as       |
|      |                                               |                                                                  |                                         | duas lacunas do mapa** → **Q-58** e **Q-59**         |
| C-30 | **`.xlsx` é afinal tratável** (corrige C-19)  | C-19/Q-43 diziam *"exigiria biblioteca externa"* — **errado**:   | Reabre a via da importação; se for      | Manter o **mapa de células** como via principal e,   |
|      | — §F.7                                        | `zlib`+`gzinflate` leem o ZIP (**26/26** entradas no `.xlsx`)    | aceite, ➕ `_dev/tools/xlsx-read.php`   | **só se aprovado**, acrescentar o leitor como        |
|      |                                               |                                                                  | (só depois de decisão)                  | ferramenta de `_dev/tools/` (`.clinerules` §3)       |

### 3.1 Checklist de decisões (para fechar esta fase)

| #   | Decisão                                                                                                | ID   | Estado |
| :-- | :----------------------------------------------------------------------------------------------------- | :--- | :----- |
| 1   | `/gestao` passa a painel e a lista fica em `/gestao/agendamentos`                                      | C-01 | ⬜     |
| 2   | Modelo de lembretes (entidade genérica vs extensão do calendário fiscal)                               | C-02 | ⬜     |
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
| 17  | Questões Q-02…Q-65 (§2) — período, saldo inicial, subsídios, tags, contador, gráficos, fontes de dados | §2   | ⬜     |
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
| 31  | **`.xlsx` viável sem pacotes** (corrige C-19) — incluir ou não `_dev/tools/xlsx-read.php`              | C-30 | ⬜     |
| 32  | **Rácios por mapa de células**: confirmar as células do 2.º quadro (`B11..D15`)                        | C-29 | ⬜     |
| 33  | **IVA nas dívidas a pagar**: posição líquida (como está no ficheiro) ou dívida bruta                   | C-27 | ⬜     |
| 34  | **Capital em dívida**: plano (**24 016,80 €**) ou conta 25 (**49 354,66 €**)                           | C-26 | ⬜     |
| 35  | **Subsídio de alimentação variável**: dias úteis por mês vs valor fixo                                 | C-28 | ⬜     |

**Depois das decisões:** registar em `especificacao_mvp.md` (é o único documento normativo) como
`RF-75+`/`RN-30+`/`D-12+` (§1.10) e só então implementar — ciclo de §29.3.

---

## 4. ANEXO DE EVIDÊNCIA (provas executadas na 4.ª iteração)

> Tudo o que esta iteração afirma foi **executado e lido**; nada foi inferido. **Nenhum script ficou no
> projeto** — correram em `%TEMP%` e não entram em branch nenhuma (a única ferramenta que se propõe criar é
> `_dev/tools/xlsx-read.php`, e **só depois de decisão**).

### 4.1 Extração dos ficheiros (o `.docx` e o `.xlsx` são ZIP)

O PHP deste Laragon **não** tem a extensão `zip` (`ZipArchive` inexistente) mas **tem** `zlib`/`gzinflate`. Foi
usado um **leitor mínimo de ZIP em PHP puro** (End Of Central Directory + `gzinflate`), o mesmo mecanismo de
`§F.7`:

```text
Menu Dashboard.docx            -> 23 entradas  ·  0 ilegiveis  ·  RESULTADO: 23 de 23 lidas
Contabilidade Secade Beauty.xlsx -> 26 entradas ·  0 ilegiveis  ·  RESULTADO: 26 de 26 lidas
powered-by: zlib/gzinflate      (extensao 'zip': INDISPONIVEL)
```

⚠️ **Erro medido e corrigido nesta iteração.** A primeira extração deu valores **deslocados uma coluna**: um
padrão que exigia `>` a seguir à referência de célula deixava uma célula **auto-fechada** (`<c r="D2"/>`)
engolir o valor da **seguinte**. A leitura parecia plausível e quase produziu um "achado" falso (células do
cliente dadas como ausentes). Correção: aceitar `/>` ou `>…</c>`, e **conferir contra o XML cru** antes de
afirmar que um dado falta. Registo em **§F.7**.

### 4.2 O mapa de células do *docx* confrontado com o `.xlsx`

**42 referências** verificadas, uma a uma, contra as folhas reais (**não** por leitura visual):

| Resultado                        | Quantidade | Detalhe                                                                                                                                                               |
| :------------------------------- | :--------- | :-------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ✅ **Célula existe e tem valor** | **36**     | Dashboard (`E3`,`E2`,`F7`,`F9`,`E11`,`F10`) · Rácios (`B3..D3`, `B4..D4`, `B5..D5`, `B7..D7`) · Financiamento (`B2`,`B3`,`B4`) · Análise (`B2`,`B3`,`B4`) · RH (nomes |
|                                  |            | `A4..A9`, vencimentos `B4..B9`)                                                                                                                                       |
| ⚠️ Existe mas **vazia**          | **1**      | `E8` — `Clientes` (as "Dívidas a receber")                                                                                                                            |
| ❌ **Não existe**                | **5**      | `E3..E7` dos Rácios — a **coluna E não existe** nessa folha                                                                                                           |
| ⚠️ Card **sem célula indicada**  | **1**      | `Passivo não corrente` (Rácios)                                                                                                                                       |

⇒ As **5 ausentes** e o card sem célula são as duas lacunas do mapa → **F.9 · D-01/D-10** · **Q-58/Q-59**.

### 4.3 Verificação numérica (o script que provou as regras)

Um único script testou **31 identidades**. Resultado: **30 OK · 1 FALHA** — e a **falha é o resultado
pretendido**: é a **regra da iteração 3** a **não** reproduzir o ficheiro novo.

```text
Portatil: mensal = round(913,99 / 36, 2)                  OK   obtido=25.39    esperado=25.39
Carrinha: mensal = round(44 715,45 / 48, 2)               OK   obtido=931.57   esperado=931.57
Portatil: acumulada = mensal x 3                          OK   obtido=76.17    esperado=76.17
Carrinha: acumulada = mensal x 3                          OK   obtido=2794.71  esperado=2794.71
TOTAL deprec. acumulada / valor contabilistico            OK   2870.88 / 42758.56
(regra ANTIGA) Portatil floor(mensal)*3  [deve FALHAR]    FALHA  obtido=76.14 esperado=76.17
SA de 3 meses (20+21+22 dias x 6,15)                      OK   obtido=387.45   esperado=387.45
SA com 19+21+21 dias x 6,15                               OK   obtido=375.15   esperado=375.15
REMUNERACAO bruta (3 meses)                               OK   obtido=19950    esperado=19950
REMUNERACAO + SA (folha)                                  OK   obtido=22262.4  esperado=22262.4
IRS retido (2 x 8% + 3 x 3,6% + 1 x 8,56%)                OK   obtido=1221     esperado=1221
SS 11% + SS 23,75% -> Card SS                             OK   6932.63  (= 34,75 %)
PAGAR AO PESSOAL                                          OK   obtido=18846.9  esperado=18846.9
DMR: Sujeitos 6 650 (= 6 vencimentos) + Nao Sujeitos 774,90  OK  total=7424.90
Disponibilidades = banco (E3) + caixa (E2)                OK   obtido=44686.42
RAI = rendimentos (F5) - gastos (E4)                      OK   obtido=-21705.26
Dividas a pagar = F7+F9+E11+F10                           OK   obtido=59271.23
Capital em divida = inicial - amortizado                  OK   obtido=24016.80
Racios: Liquidez / Fundo de Maneio / Autonomia            OK   (3 identidades, erro < 1e-7)
```

### 4.4 Varrimento de inexistência (o que **não** existe na BD)

```text
DataBase_v3.sql -> 24 tabelas: agendamento, agendamento_pessoa, agendamento_servico, alerta_fiscal,
  base_partida, categoria_profissional, cidade, cliente, cliente_morada, config_recibo_verde,
  execucao_agendamento, fecho_caixa_diario, feedback_cliente, funcionario, gorjeta, matriz_deslocacao,
  obrigacao_fiscal, rota_ambulante, rota_funcionario, servico, servico_foto, servico_local,
  transacao_financeira, utilizador
pesquisa por: ativo_fixo | deprecia | amortiza | emprestimo | financiamento | balancete | dmr | dri
  -> 0 ocorrencias no codigo e no schema
pesquisa por: fornecedor -> 4 ocorrencias, TODAS comentarios de modules\common\js\api\geocodingApi.js
  (o "fornecedor" e o servico de geocoding, nao uma entidade de negocio)
```

⇒ **Nenhuma** tabela suporta ativos, depreciações, empréstimos, financiamentos, balancete, DMR/DRI, despesas
ou fornecedores. As conclusões de §F.4/§F.5/§F.6 (e os conflitos **C-25…C-27**) ficam confirmadas por
varrimento, **não** por suposição.

### 4.5 Nota sobre os dados — mudou de **natureza**, não só de valor

|               | Iteração 3 (modelos)                | Iteração 4 (esta)                                                |
| :------------ | :---------------------------------- | :--------------------------------------------------------------- |
| Empresa       | *"Empresa Solução Certa"* (exemplo) | **EMPRESA SECADE BEAUTY LDA** · NIF 514740540 · NISS 11499501734 |
| Trabalhadores | 6 anónimos (só vencimento)          | **6 com nome**, NISS e data de nascimento                        |
| Ativos        | 4 de exemplo                        | **2 reais** (`Portátil Apple`, `Carrinha`)                       |
| Período       | indefinido                          | **janeiro a março de 2026**                                      |

**Consequência:** os valores passam a ser **citáveis** — servem de **caso de teste** e de conferência do que o
sistema vier a calcular. Deixam de ser meras ilustrações de mecânica. (Os antigos `406,50` · `325,20` ·
`16 954,73` · `1 463,40` **já não pertencem a nenhuma folha** e não devem ser usados como referência.)

> 📌 **Nota sobre versionamento.** Os dois ficheiros estão em `_dev/mapaMentalMVP/`, que está no `.gitignore`
> (`/_dev/mapaMentalMVP/`) e **só existe versionada na branch `agent-workspace`** (§11.5). Sendo **entradas do
> cliente**, ficam **no disco mas invisíveis para o Git** nas restantes branches. Se o gestor quiser os
> originais versionados, tem de decidir **onde** os colocar (fora de `_dev/mapaMentalMVP/`) — é decisão do
> utilizador, não do agente (§11.2).

### 4.6 Encaixe nos módulos — o que a 4.ª iteração altera

| Onde                          | O que muda                                                                                           |
| :---------------------------- | :--------------------------------------------------------------------------------------------------- |
| §1.12 **reescrita** (F.0—F.9) | Novos ficheiros como fonte de verdade; **F.8** (DMR/DRI) e **F.9** (confronto antigo↔novo) são novas |
| §F.3 Dashboard                | Células **novas e verificadas**; valores reais; **IVA é saldo devedor** dentro das dívidas a pagar   |
| §F.4 RH                       | **SA deixou de ser constante** (dias úteis variam); taxa de SS **34,75 %** confirmada pelo DRI       |
| §F.5 Ativos                   | **Fórmula da depreciação corrigida** (`round` do mensal); **vida útil por ativo** (3 e 4 anos)       |
| §F.6 Financiamentos           | Dois "capitais em dívida" (**24 016,80 €** vs **49 354,66 €**) → decisão nova (**Q-61**)             |
| §F.6 Análise financeira       | IRC: `RAI * 20 %` **escrito no ficheiro**; duas caixas (taxa + valor) confirmadas                    |
| §2.10 **reescrita**           | Q-48/Q-50/Q-57 **fechadas**; **Q-58…Q-65** novas, todas nascidas do confronto                        |
| §2.10.1                       | Fórmula do custo de pessoal corrigida (SA variável)                                                  |
| §3 C-25…C-30                  | Atualizados com valores reais; **C-28** passa a ter **dois** casos (IRS e SA)                        |
| `mensagem_teams.txt`          | Reescrita: saem as perguntas respondidas pelos ficheiros, entram **Q-58…Q-65**                       |

---

## 5. DECISÃO DE IMPORTAÇÃO (25/09/2026) — o que muda nesta análise

> Registada a pedido do gestor. Esta secção **não** cria regra nova: a decisão normativa ficou em
> `_dev/docs/spec/core.md` **§3.12 (D-12)** e **§1.2 (E-1…E-4)**; os requisitos e a regra em
> `requirements.md` (**RF-75 · RF-76 · RN-30**) e a justificação de BD em `data-api.md` **§17.9**.

| Ponto                                   | Antes (análise)                                        | Agora (decidido)                                                        |
| :-------------------------------------- | :----------------------------------------------------- | :---------------------------------------------------------------------- |
| Leitura do `.xlsx`                      | leitor próprio em PHP puro (`zlib`) — §F.7             | **`PhpSpreadsheet`** do material de formação (E-1), com `zip` (E-2)     |
| Destino dos dados                       | "mapa de células" lido em runtime (**C-29**, proposto) | **Persistir em BD** e os widgets lerem **da BD** (D-12)                 |
| Nova importação                         | não estava definido                                    | **Substitui** integralmente a anterior, em transação (**RN-30**)        |
| Upload na demonstração                  | não estava definido                                    | **Funciona**; a importação ao vivo substitui qualquer importação prévia |
| Gráficos                                | CSS/SVG próprios (**P7**)                              | **Chart.js** permitido nos gráficos de gestão (**E-3**)                 |
| `C-19` / `Q-43` (o `.xlsx` é tratável?) | corrigido por §F.7 (*sim*, com `zlib`)                 | Mantém-se: é tratável — agora **também** com biblioteca → **C-30**      |
| Origem dos números                      | `Q-31` / `C-20` (proposta: o interno é primário)       | **`Q-66` nova**: importado *vs.* plataforma → **pergunta 36** cliente   |

**Estado dos itens desta análise, depois da decisão:**

- **`C-29` → decidido**: a via é a **importação para tabelas**, não o mapa de células em runtime.
- **`C-19` / `C-30` → arquivados**: a limitação técnica que os originou já era falsa (§F.7) e a rota mudou.
- **`Q-58`…`Q-65` → mantêm-se abertas** (são sobre o conteúdo dos ficheiros, não sobre a rota).
- **`Q-66` → nova e aberta** (§2.10 · pergunta 36 de `mensagem_teams.txt`).

---

**Ficheiro:** `_dev/mapaMentalMVP/analise_backoffice_gestor.md` · **branch:** `agent-workspace` ·
**Companheiro:** `_dev/mapaMentalMVP/mensagem_teams.txt` (resumo para o grupo/Teams, sem detalhe técnico) ·
**Nota:** documento de apoio à decisão, **não normativo**; a autoridade é `especificacao_mvp.md` (§29.2).
