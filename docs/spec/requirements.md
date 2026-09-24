# Especificação — Requisitos e regras de negócio

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../especificacao_mvp.md).
> Capítulos: §4 · §5

## 4. REQUISITOS FUNCIONAIS (RF)

Legenda de estado: ✅ implementado · 🟡 parcial · ⬜ por implementar

### 4.1 Main — público e cliente

| ID    | Requisito                                                                           | Estado                  |
| :---- | :---------------------------------------------------------------------------------- | :---------------------- |
| RF-01 | Home com hero, "Acerca", categorias e **testemunhos** (reais + fallback estático)   | ✅                      |
| RF-02 | Páginas institucionais: Sobre, Contacto                                             | ✅                      |
| RF-03 | Página 404 personalizada                                                            | ✅                      |
| RF-04 | Listagem de **categorias** (3 cards)                                                | ✅                      |
| RF-05 | **Catálogo** de serviços em cards com filtros (categoria, preço, duração, pesquisa) | ✅                      |
| RF-06 | Badge **"Apenas Loja"** quando `requer_espaco_fisico=1`                             | ✅                      |
| RF-07 | **Página de detalhes dedicada por serviço** com **carousel** + tempo estimado       | ⬜ (§3.6)               |
| RF-08 | Registo de cliente (wizard) com autocomplete de morada e cidade suportada           | ✅                      |
| RF-09 | Login / logout / recuperar password                                                 | 🟡 (recuperar = página) |
| RF-10 | Perfil do cliente: dados + **CRUD de moradas** (principal, criar, remover)          | ✅                      |
| RF-11 | Meus Agendamentos: histórico com filtros por estado                                 | ✅                      |
| RF-12 | **Cancelamento do agendamento pelo cliente**                                        | ⬜ (§3.11)              |
| RF-13 | **Alerta/lembrete** ao cliente (≤ 24 h, sem rota) com sugestão de loja/reagendar    | ⬜ (§3.11)              |
| RF-14 | Feedback do cliente após execução (1–5★ + comentário)                              | ✅                      |

### 4.2 Agendamento — Loja Física

| ID    | Requisito                                                          | Estado          |
| :---- | :----------------------------------------------------------------- | :-------------- |
| RF-20 | Wizard: serviços → canal → data/hora → resumo (sem morada/pessoas) | ✅              |
| RF-21 | Slots de 30 min, Terça–Sábado, 09:00–19:00                         | ✅              |
| RF-22 | Serviços **automaticamente aceites** na criação                    | ✅              |
| RF-23 | Sinal de **10 %** (simulado)                                       | ✅              |
| RF-24 | Validação de conflito de janela na criação (409)                   | ✅              |
| RF-25 | **Configuração do sinal no backoffice** (substituir constante)     | ⬜ (§3.5/§3.12) |

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

| ID    | Requisito                                                                               | Estado |
| :---- | :-------------------------------------------------------------------------------------- | :----: |
| RF-40 | Lista "Por aceitar" (ambulatório pendente), agrupada por agendamento → pessoa → serviço | ✅     |
| RF-41 | Filtros **visuais** por categoria e data                                                | ✅     |
| RF-42 | **Aceitação individual** serviço a serviço                                              | ✅     |
| RF-43 | **Desfazer** e **trocar** aceitação enquanto não consolidado (403/409)                  | ✅     |
| RF-44 | **Consolidação** ao aceitar o último serviço + **bloqueio da janela temporal**          | ✅     |
| RF-45 | **Simulador de Recibos Verdes** apresentado na aceitação                                | ✅     |
| RF-46 | Lista "Aceites por mim" + totais                                                        | ✅     |

### 4.5 Backoffice — Gestor

| ID    | Requisito                                                                   | Estado     |
| :---- | :-------------------------------------------------------------------------- | :--------: |
| RF-50 | Lista de agendamentos com filtros (data, local, estado, cidade) + paginação | ✅         |
| RF-51 | Detalhe por **serviço/funcionário** + progresso de aceitação + execução     | ✅         |
| RF-52 | Cancelamento de agendamento                                                 | ✅         |
| RF-53 | Registo de **execução** do serviço (idempotente)                            | ✅         |
| RF-54 | Rotas por **dia+cidade** com custos/lucros e **indicador 50 €** (visual)    | ✅         |
| RF-55 | **Decisão manual** de rota (aprovar/recusar) com auditoria                  | ✅         |
| RF-56 | **Rotas multicidades** (ordenação cronológica + validação de espaçamento)   | ⬜ (§3.9)  |
| RF-57 | **Alerta padronizado de custos/viabilidade** nas listagens                  | ⬜ (§3.9)  |
| RF-58 | **Regra das 24 h** na criação de rotas                                      | ⬜ (§3.11) |
| RF-59 | **Auto-cancelamento** de agendamentos a 24 h sem rota (retidos na BD)       | ⬜ (§3.11) |
| RF-60 | Calendário Fiscal (IVA, IRC, SS, Seguros) + alertas 30/15/7/3/1/atraso      | ✅         |
| RF-61 | Configuração do Simulador de Recibos Verdes (percentagens + vigência)       | ✅         |
| RF-62 | **Configuração do sinal** cobrado aos clientes                              | ⬜ (§3.12) |
| RF-63 | **Dashboard/resumo** e estrutura de menus convencionada por módulo          | 🟡 (§25)   |

### 4.6 Pagamentos (simulados)

| ID    | Requisito                                                         | Estado     |
| :---- | :---------------------------------------------------------------- | :--------: |
| RF-70 | Sinal 10 % simulado na criação                                    | ✅         |
| RF-71 | **Cobrança dos 90 %** no término do serviço                       | ⬜ (§3.10) |
| RF-72 | **Escolha simulada do método** (Dinheiro / Multibanco / MB Way)   | ⬜ (§3.10) |
| RF-73 | Cenário de **falha de internet** → pagamento restrito a numerário | ⬜ (§3.10) |
| RF-74 | Recibo manual — avaliar se existe; caso não, **trabalho futuro**  | ⬜ (§25)   |

## 5. REGRAS DE NEGÓCIO (RN)

### 5.1 Regras

| ID    | Regra                                                                                                 | Onde é aplicada                                            |
| :---- | :---------------------------------------------------------------------------------------------------- | :--------------------------------------------------------- |
| RN-01 | Serviços com `requer_espaco_fisico=1` → **só loja física**                                            | Catálogo + wizard (bloqueia carrinha)                      |
| RN-02 | Loja: **Terça a Sábado, 09:00–19:00**, slots de **30 min**                                            | `BookingService::validateBookingDate` + `findAvailability` |
| RN-03 | **Sinal de 10 %** na loja; **dispensado** na 1.ª marcação em ambulatório                              | `BookingService::createStoreBooking`                       |
| RN-04 | **Categorias são apenas filtros visuais** (aceitação livre)                                           | Backoffice funcionário (UI)                                |
| RN-05 | **Decisão de rotas manual**; 50 € é **apenas indicador visual** (`meetsReference`)                    | `RotaService::decideRoute`                                 |
| RN-06 | **Aceitação individual** serviço a serviço; o último aceite **consolida** (`totalmente_aceite`)       | `ServiceAcceptanceService::consolidateIfComplete`          |
| RN-07 | **Desfazer/trocar** permitido **apenas enquanto** o agendamento **não** estiver consolidado (409)     | `ServiceAcceptanceService::unacceptService`                |
| RN-08 | A consolidação **bloqueia a janela temporal** para agendamentos concorrentes                          | `countByDateWindow` + `assertNoWindowConflict`             |
| RN-09 | Na aceitação corre o **Simulador de Recibos Verdes** com a percentagem em vigor (70/30)               | `GreenReceiptService`                                      |
| RN-10 | Decisão de rota: `aprovada` → agendamentos `confirmado`; `recusada` → `cancelado`                     | `RotaService::decideRoute`                                 |
| RN-11 | **Calendário Fiscal**: alertas progressivos **30/15/7/3/1 dia** + atraso, geração idempotente         | `FiscalService::generateAlerts`                            |
| RN-12 | **Feedback** só após serviço **executado**, **uma vez** por agendamento, e é **público**              | `FeedbackService::createFeedback`                          |
| RN-13 | Duração e valor do ambulatório somados **por pessoa** (serviço partilhado conta por pessoa)           | `BookingService::resolveServicesForPeople`                 |
| RN-14 | **Uma morada por agendamento** de ambulatório (`agendamento.cliente_morada_id`)                       | `BookingService::createAmbulatoryBooking`                  |
| RN-15 | A **cidade** deriva da morada (`cliente_morada → cidade`)                                             | `BookingRepository::findAmbulatoryGroups`                  |
| RN-16 | OTP: **6 dígitos**, expira em **10 min**, **uso único**, validado na **sessão**                       | `OTPService::request/verify`                               |
| RN-17 | Serviços de **loja** não podem ser aceites no backoffice de funcionário (409)                         | `assertAcceptableBooking`                                  |
| RN-18 | Rota **recusada** → **todos** os agendamentos dessa cidade+dia passam a `cancelado`                   | `RotaService::decideRoute`                                 |
| RN-19 | A decisão é sempre sobre a **rota inteira** (dia+cidade) — não há rotas parciais                      | `RotaService::decideRoute`                                 |
| RN-20 | Obrigações fiscais: valor **manual**; `periodicidade` ∈ {mensal, trimestral, anual}; pagar grava data | `FiscalService`                                            |
| RN-21 | **Um registo por (agendamento, pessoa, serviço)** em `agendamento_servico`                            | `BookingService`                                           |
| RN-22 | Percentagem dos recibos verdes aplica-se ao **`preco_praticado`**; alterações afetam aceitações       | `GreenReceiptService`                                      |
| RN-23 | A **cobrança de 90 %** ocorre no **término do serviço** (a implementar)                               | §24.5                                                      |
| RN-24 | Nenhum gestor cria rota com agendamentos a **menos de 24 h** (a implementar)                          | §24.6                                                      |
| RN-25 | Agendamento a **24 h sem rota** → **auto-cancelado** das listagens, **retido na BD** (a implementar)  | §24.6                                                      |
| RN-26 | Cliente **pode cancelar**; após associação a rota, **sem penalização financeira** (a implementar)     | §24.6                                                      |
| RN-27 | **Multicidades** permitido com validação de **espaçamento temporal** entre cidades (a implementar)    | §24.4                                                      |
| RN-28 | Carrinha: **flexibilidade horária como exceção** (fim > 19:00 permitido) (a implementar)              | §24.4                                                      |
| RN-29 | Em pagamento com **falha de internet**, apenas **numerário** (a implementar)                          | §24.5                                                      |
