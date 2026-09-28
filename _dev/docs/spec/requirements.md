# Especificação — Requisitos e regras de negócio

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../../especificacao_mvp.md).
> Capítulos: §4 · §5

## 4. REQUISITOS FUNCIONAIS (RF)

Legenda de estado: ✅ implementado · 🟡 parcial · ⬜ por implementar

### 4.1 Main — público e cliente

| ID    | Requisito                                                                           | Estado                         |
| :---- | :---------------------------------------------------------------------------------- | :----------------------------- |
| RF-01 | Home com hero, "Acerca", categorias e **testemunhos** (reais + fallback estático)   | ✅                             |
| RF-02 | Páginas institucionais: Sobre, Contacto                                             | ✅                             |
| RF-03 | Página 404 personalizada                                                            | ✅                             |
| RF-04 | Listagem de **categorias** (3 cards)                                                | ✅                             |
| RF-05 | **Catálogo** de serviços em cards com filtros (categoria, preço, duração, pesquisa) | ✅                             |
| RF-06 | Badge **"Apenas Loja"** quando `requer_espaco_fisico=1`                             | ✅                             |
| RF-07 | **Página de detalhes dedicada por serviço** com **carousel** + tempo estimado       | ⬜ (§3.6)                      |
| RF-08 | Registo de cliente (wizard) com autocomplete de morada e cidade suportada           | ✅                             |
| RF-09 | Login / logout / recuperar password                                                 | 🟡 (recuperar = página)        |
| RF-10 | Perfil do cliente: dados + **CRUD de moradas** (principal, criar, remover)          | ✅                             |
| RF-11 | Meus Agendamentos: histórico com filtros por estado                                 | ✅                             |
| RF-12 | **Cancelamento do agendamento pelo cliente**                                        | ✅ (`customer-booking-cancel`) |
| RF-13 | **Alerta/lembrete** ao cliente (≤ 24 h, sem rota) com sugestão de loja/reagendar    | ⬜ (§3.11)                     |
| RF-14 | Feedback do cliente após execução (1–5★ + comentário)                              | ✅                             |

### 4.2 Agendamento — Loja Física

| ID    | Requisito                                                          | Estado            |
| :---- | :----------------------------------------------------------------- | :---------------- |
| RF-20 | Wizard: serviços → canal → data/hora → resumo (sem morada/pessoas) | ✅                |
| RF-21 | Slots de 30 min, Terça–Sábado, 09:00–19:00                         | ✅                |
| RF-22 | Serviços **automaticamente aceites** na criação                    | ✅                |
| RF-23 | Sinal de **10 %** (simulado)                                       | ✅                |
| RF-24 | Validação de conflito de janela na criação (409)                   | ✅                |
| RF-25 | **Configuração do sinal no backoffice** (substituir constante)     | ⬜ (§3.5 · §3.10) |

### 4.3 Agendamento — Carrinha Ambulante

| ID    | Requisito                                                               | Estado                 |
| :---- | :---------------------------------------------------------------------- | :--------------------- |
| RF-30 | Wizard com **morada** (10 cidades) + **OTP** + **estrutura por pessoa** | ✅                     |
| RF-31 | OTP simulado: 6 dígitos, visível no ecrã, expira em 10 min, uso único   | ✅                     |
| RF-32 | Duração e valor **por pessoa** (serviços partilhados contam por pessoa) | ✅                     |
| RF-33 | Estado inicial `pendente_aceitacao_funcionarios`                        | ✅                     |
| RF-34 | Sinal **dispensado** (1.ª marcação)                                     | 🟡 (dispensado sempre) |
| RF-35 | **Re-avaliar a disponibilidade ao alterar serviços** após escolher data | 🟡 (§3.7)              |
| RF-36 | **Flexibilidade horária como exceção** (fim > 19:00 permitido)          | ⬜ (§3.9)              |

### 4.4 Backoffice — Funcionário

| ID    | Requisito                                                                                                                                                     | Estado     |
| :---- | :------------------------------------------------------------------------------------------------------------------------------------------------------------ | :--------: |
| RF-40 | Lista "Por aceitar" — serviços **pendentes** de agendamentos **ainda não incluídos em rota confirmada** (RN-32) — agrupada por agendamento → pessoa → serviço | ✅ (§24.7) |
| RF-41 | Filtros **visuais** por categoria e data                                                                                                                      | ✅         |
| RF-42 | **Aceitação individual** serviço a serviço                                                                                                                    | ✅         |
| RF-43 | **Desfazer** e **trocar** aceitação enquanto não consolidado (403/409)                                                                                        | ✅         |
| RF-44 | **Consolidação** ao aceitar o último serviço + **bloqueio da janela temporal**                                                                                | ✅         |
| RF-45 | **Simulador de Recibos Verdes** apresentado na aceitação                                                                                                      | ✅         |
| RF-46 | Lista "Aceites por mim" + totais                                                                                                                              | ✅         |

### 4.5 Backoffice — Gestor

| ID    | Requisito                                                                   | Estado                 |
| :---- | :-------------------------------------------------------------------------- | :--------------------: |
| RF-50 | Lista de agendamentos com filtros (data, local, estado, cidade) + paginação | ✅                     |
| RF-51 | Detalhe por **serviço/funcionário** + progresso de aceitação + execução     | ✅                     |
| RF-52 | Cancelamento de agendamento                                                 | ✅                     |
| RF-53 | Registo de **execução** do serviço (idempotente)                            | ✅                     |
| RF-54 | Rotas por **dia+cidade** com custos/lucros e **indicador 50 €** (visual)    | ✅                     |
| RF-55 | **Decisão manual** de rota (aprovar/recusar) com auditoria                  | ✅                     |
| RF-56 | **Rotas multicidades** (ordenação cronológica + validação de espaçamento)   | ⬜ (§3.9)              |
| RF-57 | **Alerta padronizado de custos/viabilidade** nas listagens                  | ⬜ (§3.9)              |
| RF-58 | **Regra das 24 h** na criação de rotas                                      | ⬜ (§3.11)             |
| RF-59 | **Auto-cancelamento** de agendamentos a 24 h sem rota (retidos na BD)       | ⬜ (§3.11)             |
| RF-60 | Calendário Fiscal (IVA, IRC, SS, Seguros) + alertas 30/15/7/3/1/atraso      | ✅                     |
| RF-61 | Configuração do Simulador de Recibos Verdes (percentagens + vigência)       | ✅                     |
| RF-62 | **Configuração do sinal** cobrado aos clientes                              | ⬜ (§3.10 · D-10)      |
| RF-63 | **Dashboard/resumo** e estrutura de menus convencionada por módulo          | 🟡 → **RF-77** (§24.7) |

### 4.6 Pagamentos (simulados)

| ID    | Requisito                                                         | Estado     |
| :---- | :---------------------------------------------------------------- | :--------: |
| RF-70 | Sinal 10 % simulado na criação                                    | ✅         |
| RF-71 | **Cobrança dos 90 %** no término do serviço                       | ⬜ (§3.10) |
| RF-72 | **Escolha simulada do método** (Dinheiro / Multibanco / MB Way)   | ⬜ (§3.10) |
| RF-73 | Cenário de **falha de internet** → pagamento restrito a numerário | ⬜ (§3.10) |
| RF-74 | Recibo manual — avaliar se existe; caso não, **trabalho futuro**  | ⬜ (§25)   |

### 4.7 Backoffice — importação, contabilidade, RH, fornecedores e calendário fiscal

| ID    | Requisito                                                                                                                                                                                   | Estado             |
| :---- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | :----------------: |
| RF-75 | **Importar ficheiros** (CSV/XLSX) no backoffice por **upload**, com **persistência em BD** e substituição integral da importação anterior                                                   | ⬜ (§3.12)         |
| RF-76 | **Página `/gestao/contabilidade`** com cartões, tabelas e gráficos alimentados pelos **dados importados**                                                                                   | ⬜ (§3.12)         |
| RF-77 | **Painel do gestor** em `/gestao`: KPIs no topo + **gráficos** (Chart.js) + **sininho** com contador de não lidos; o funcionário é encaminhado para a agenda                                | ✅ (§3.13 · §24.7) |
| RF-78 | **Agenda do funcionário** em `/gestao/agenda`: **calendário** com os agendamentos de **rotas confirmadas** (todos os serviços aceites)                                                      | ✅ (§3.14 · §24.7) |
| RF-79 | **Gráficos como forma principal** de apresentação nos ecrãs **contabilísticos/financeiros**, com tabelas apenas onde fizer sentido                                                          | ⬜ (§3.13)         |
| RF-80 | **Excluir agendamentos** de uma rota **ainda não decidida** (volta a *qualificado* — nunca a cancelado); a decisão aplica-se ao **conjunto** incluído                                       | ✅ (§24.7)         |
| RF-81 | **Página centralizada de avisos** por perfil, no **menu do utilizador** e pelo **clique no sino** (contador de não lidos)                                                                   | ✅ (§3.15)         |
| RF-82 | **Custos com pessoal calculados na plataforma**: remuneração × meses + **subsídio (dias úteis × 6,15 €)** − IRS retido − SS do trabalhador (11 %), e SS patronal (23,75 %) →                | ⬜ (§3.12 · §24.9) |
|       | **líquido a pagar** por trabalhador e por mês                                                                                                                                               |                    |
| RF-83 | **Calendário fiscal com as 8 famílias** de obrigações do calendário oficial (SAF-T, DMR, retenções na fonte, Segurança Social, IVA-declaração e IVA-pagamento, IRC, IES/DA), **importável** | ⬜ (§13 · §24.10)  |
|       | do ficheiro                                                                                                                                                                                 |                    |
| RF-84 | **Página das comissões** por funcionário, com os valores **já gravados na aceitação** (`valor_recibo_verde_funcionario`), alcançável pelo menu do utilizador e pela **sidebar**             | ✅ (§11 · §24.7)   |
| RF-85 | **Gestão de fornecedores** em `/gestao/fornecedores` (listagem, pesquisa, criar/editar, ativar/desativar) sobre a tabela `fornecedor` **já carregada** com os 43 fornecedores reais         | ✅ (§25.1 · §17.9) |
| RF-86 | **Carga dos ficheiros entregues pelo cliente** (durações dos serviços, fornecedores e clientes) por **migração idempotente gerada a partir do ficheiro** — nunca por transcrição manual     | ✅ (§24.11)        |
| RF-87 | **Apresentação dos valores ao cliente com IVA**: catálogo, modal de detalhes, resumo do wizard (loja e carrinha) e "Meus Agendamentos" convertem o preço **net** gravado na BD              | ✅ (§3.16)         |
| RF-88 | **Taxa de IVA em configuração** (`IVA_RATE`), consumida por **uma só** utilidade (`vatUtils`) — sem números de IVA escritos nos componentes                                                 | ✅ (§3.16)         |

## 5. REGRAS DE NEGÓCIO (RN)

### 5.1 Regras

| ID    | Regra                                                                                                                                               | Onde é aplicada                                            |
| :---- | :-------------------------------------------------------------------------------------------------------------------------------------------------- | :--------------------------------------------------------- |
| RN-01 | Serviços com `requer_espaco_fisico=1` → **só loja física**                                                                                          | Catálogo + wizard (bloqueia carrinha)                      |
| RN-02 | Loja: **Terça a Sábado, 09:00–19:00**, slots de **30 min**                                                                                          | `BookingService::validateBookingDate` + `findAvailability` |
| RN-03 | **Sinal de 10 %** na loja; **dispensado** na 1.ª marcação em ambulatório                                                                            | `BookingService::createStoreBooking`                       |
| RN-04 | **Categorias são apenas filtros visuais** (aceitação livre)                                                                                         | Backoffice funcionário (UI)                                |
| RN-05 | **Decisão de rotas manual**; 50 € é **apenas indicador visual** (`meetsReference`)                                                                  | `RotaService::decideRoute`                                 |
| RN-06 | **Aceitação individual** serviço a serviço; o último aceite **consolida** (`totalmente_aceite`)                                                     | `ServiceAcceptanceService::consolidateIfComplete`          |
| RN-07 | **Desfazer/trocar** permitido **apenas enquanto** o agendamento **não** estiver consolidado (409)                                                   | `ServiceAcceptanceService::unacceptService`                |
| RN-08 | A consolidação **bloqueia a janela temporal** para agendamentos concorrentes                                                                        | `countByDateWindow` + `assertNoWindowConflict`             |
| RN-09 | Na aceitação corre o **Simulador de Recibos Verdes** com a percentagem em vigor (70/30)                                                             | `GreenReceiptService`                                      |
| RN-10 | Decisão de rota: `aprovada` → agendamentos `confirmado`; `recusada` → `cancelado`                                                                   | `RotaService::decideRoute`                                 |
| RN-11 | **Calendário Fiscal**: alertas progressivos **30/15/7/3/1 dia** + atraso, geração idempotente                                                       | `FiscalService::generateAlerts`                            |
| RN-12 | **Feedback** só após serviço **executado**, **uma vez** por agendamento, e é **público**                                                            | `FeedbackService::createFeedback`                          |
| RN-13 | Duração e valor do ambulatório somados **por pessoa** (serviço partilhado conta por pessoa)                                                         | `BookingService::resolveServicesForPeople`                 |
| RN-14 | **Uma morada por agendamento** de ambulatório (`agendamento.cliente_morada_id`)                                                                     | `BookingService::createAmbulatoryBooking`                  |
| RN-15 | A **cidade** deriva da morada (`cliente_morada → cidade`)                                                                                           | `BookingRepository::findAmbulatoryGroups`                  |
| RN-16 | OTP: **6 dígitos**, expira em **10 min**, **uso único**, validado na **sessão**                                                                     | `OTPService::request/verify`                               |
| RN-17 | Serviços de **loja** não podem ser aceites no backoffice de funcionário (409)                                                                       | `assertAcceptableBooking`                                  |
| RN-18 | Rota **recusada** → **todos** os agendamentos dessa cidade+dia passam a `cancelado`                                                                 | `RotaService::decideRoute`                                 |
| RN-19 | A decisão é sempre sobre a **rota inteira** (dia+cidade) — não há rotas parciais                                                                    | `RotaService::decideRoute`                                 |
| RN-20 | Obrigações fiscais: valor **manual**; `periodicidade` ∈ {mensal, trimestral, anual}; pagar grava data                                               | `FiscalService`                                            |
| RN-21 | **Um registo por (agendamento, pessoa, serviço)** em `agendamento_servico`                                                                          | `BookingService`                                           |
| RN-22 | Percentagem dos recibos verdes aplica-se ao **`preco_praticado`**; alterações afetam aceitações                                                     | `GreenReceiptService`                                      |
| RN-23 | A **cobrança de 90 %** ocorre no **término do serviço** (a implementar)                                                                             | §24.5                                                      |
| RN-24 | Nenhum gestor cria rota com agendamentos a **menos de 24 h** (a implementar)                                                                        | §24.6                                                      |
| RN-25 | Agendamento a **24 h sem rota** → **auto-cancelado** das listagens, **retido na BD** (a implementar)                                                | §24.6                                                      |
| RN-26 | Cliente **pode cancelar**; após associação a rota, **sem penalização financeira** (a implementar)                                                   | §24.6                                                      |
| RN-27 | **Multicidades** permitido com validação de **espaçamento temporal** entre cidades (a implementar)                                                  | §24.4                                                      |
| RN-28 | Carrinha: **flexibilidade horária como exceção** (fim > 19:00 permitido) (a implementar)                                                            | §24.4                                                      |
| RN-29 | Em pagamento com **falha de internet**, apenas **numerário** (a implementar)                                                                        | §24.5                                                      |
| RN-30 | **Importar substitui o importado**: uma nova importação apaga os dados importados antes de gravar os novos, **numa transação**; nunca soma nem      | `ImportService` (a criar · §3.12)                          |
|       | acumula                                                                                                                                             |                                                            |
| RN-31 | **Rota só agrega agendamentos com todos os serviços aceites** (`totalmente_aceite_funcionarios`); a decisão do gestor **recusa (409)** grupos com   | ✅ `RotaService::decideRoute` · §24.7                      |
|       | serviços pendentes                                                                                                                                  |                                                            |
| RN-32 | A lista **"Por aceitar"** mostra apenas serviços pendentes de agendamentos **fora de rota confirmada** — a partir do momento em que o agendamento   | ✅ `BookingServiceRepository::findPending` · §24.7         |
|       | entra numa rota confirmada **deixa de aparecer**                                                                                                    |                                                            |
| RN-33 | A **agenda** do funcionário mostra **apenas** agendamentos de **rotas confirmadas** (`confirmado`); o que ainda se aceita/desfaz fica na listagem   | ✅ §10.1 · §24.7                                           |
| RN-34 | **Reverter/excluir** um agendamento só é possível **antes** de a rota ser confirmada: volta a `totalmente_aceite_funcionarios`                      | ✅ `RotaService::decideRoute` · §24.7                      |
|       | (**qualificado**), **nunca** a `cancelado`; em **rota confirmada** o agendamento **não se altera**                                                  |                                                            |
| RN-35 | **Subsídio de alimentação = dias úteis × 6,15 €** (dias úteis seg–sex; 21/20/22 no 1.º trimestre de 2026); a **SS patronal (23,75 %)** incide sobre | `PayrollService` (a criar · §24.9)                         |
|       | o **saldo de remunerações** (19 880,77) e **não** sobre o bruto — a base da SS do trabalhador é a remuneração do período                            |                                                            |
| RN-36 | **Preço gravado = líquido; preço mostrado ao cliente = com IVA.** A BD guarda o **valor base tributável** e **todo** o ecrã virado ao cliente       | `vatUtils` · §3.16 · D-16                                  |
|       | apresenta o valor **com IVA** por uma só utilidade (`vatUtils`, taxa em `IVA_RATE`); o **sinal de 10 %** incide sobre o valor **com IVA**           |                                                            |
|       | o **saldo de remunerações** (19 880,77) e **não** sobre o bruto — a base da SS do trabalhador é a remuneração do período                            |                                                            |
