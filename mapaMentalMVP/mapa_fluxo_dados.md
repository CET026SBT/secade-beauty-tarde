# MAPA DE FLUXO DE DADOS — SECADE BEAUTY MVP
**Guia visual de funcionalidades e fluxo ponta-a-ponta**
Data: 21/09/2026 · Comprovar com: `guia_teste_manual.md`

---

## ÍNDICE

| §   | Conteúdo                                                     |
| :-- | :----------------------------------------------------------- |
| 1   | Legenda e convenções                                         |
| 2   | Arquitetura em camadas (visão geral)                         |
| 3   | Mapa hierárquico de funcionalidades                          |
| 4   | Fluxo ponta-a-ponta: **LOJA FÍSICA** (5 passos)              |
| 5   | Fluxo ponta-a-ponta: **CARRINHA AMBULANTE** (7 passos + OTP) |
| 6   | Fluxo ponta-a-ponta: **ACEITAÇÃO / FASE 3**                  |
| 7   | Fluxo ponta-a-ponta: **DECISÃO DE ROTAS / FASE 4**           |
| 8   | Fluxo ponta-a-ponta: **CALENDÁRIO FISCAL / FASE 4**          |
| 9   | Fluxo ponta-a-ponta: **EXECUÇÃO + FEEDBACK / FASE 2**        |
| 10  | Máquina de estados (`estado_reserva` / `estado_rota`)        |
| 11  | Ciclo de vida de um pedido (request)                         |
| 12  | Mapa de tabelas por funcionalidade                           |
| 13  | Matriz: página ↔ endpoint ↔ ficheiros                        |
| 14  | Regras de negócio ↔ código                                   |
| 15  | Segurança: matriz de permissões                              |

---

## 1. LEGENDA E CONVENÇÕES

```
╔══════════════╗   Bloco de INTERFACE (ecrã/página)
║              ║
╚══════════════╝
┌──────────────┐   Bloco de CÓDIGO (ficheiro/classe)
│              │
└──────────────┘
[ TABELA ]         Tabela da base de dados
(( ESTADO ))       Estado / máquina de estados
═══▶               Fluxo normal (dados/controlo)
─ ─ ▶              Fluxo assíncrono (AJAX)
╌╌▶                Escrita na base de dados
✗                  Ponto de rejeição / validação que falha
⭐                 Funcionalidade-chave da avaliação
```

**Camadas de código** (padrão do projeto):
`View (PHP)` → `JS component` → `api.js` → `api.php` → `Controller` → `Service` → `Repository` (+`Mapper`) → `MySQL`

## 2. ARQUITETURA EM CAMADAS (visão geral)

```
╔════════════════════════════════════════════════════════════════════════╗
║                       NAVEGADOR (Browser)                              ║
║   modules/main/*.php                 modules/backoffice/*.php          ║
║   (páginas públicas / cliente)       (área de gestão)                  ║
╚════════════════════════════════════════════════════════════════════════╝
                    │                                │
                    │  HTML + CSS + JS               │
                    ▼                                ▼
╔════════════════════════════════════════════════════════════════════════╗
║  JS:  modules/common/js/                                               ║
║    api/apiClient.js      (cache + $.ajax)                              ║
║    api/api.js            (API.auth / booking / customer / admin /      ║
║                           feedback)                                    ║
║    utils/form.utils.js   (Form + FormValidators)                       ║
║    utils/general.utils.js(formatCurrency/Duration/DateTime)            ║
║    validators/*.js       (login, user, customer, booking, …)           ║
║    lib-our/jq-preloader  (overlays + skeletons)                        ║
║                                                                        ║
║  Componentes:                                                          ║
║   main/js/components/{services, bookingWizard, profile,                ║
║                       appointments, testimonial}.js                    ║
║   backoffice/js/components/{appointments, routes, services,            ║
║                             fiscal, greenReceipts}.js                  ║
║   backoffice/js/bo.utils.js  (badges, selects, labels)                 ║
╚════════════════════════════════════════════════════════════════════════╝
                    │
                    │  AJAX  ?action=<dominio>-<acao>   (JSON)
                    ▼
╔════════════════════════════════════════════════════════════════════════╗
║  index.php ── Front Controller                                         ║
║   ├── path == "api" ou ?action=…  ══▶ app/config/api.php               ║
║   │                                    └─ tabela de rotas →            ║
║   │                                        Controller::metodo()        ║
║   └── caso contrário               ══▶ rotas de página                 ║
║                                         (modules/**.php)               ║
╚════════════════════════════════════════════════════════════════════════╝
                    │
                    ▼
╔════════════════════════════════════════════════════════════════════════╗
║  CONTROLLERS  (app/controllers/)                                       ║
║   Auth · Booking · Category · City · Customer · CustomerAddress        ║
║   Admin · Rota · Service · Fiscal · Feedback     (+ BaseController)    ║
║   ▸ getRequestData()  (POST ou JSON body)                              ║
║   ▸ requireCustomer() / requireProfileApi([...])  ← AUTORIZAÇÃO        ║
╚════════════════════════════════════════════════════════════════════════╝
                    │
                    ▼
╔════════════════════════════════════════════════════════════════════════╗
║  SERVICES  (app/services/) — REGRAS DE NEGÓCIO                         ║
║   Auth · User · Customer · CustomerAddress · Employee · Manager        ║
║   Category · City · Booking · OTP · Rota · ServiceAcceptance ⭐        ║
║   GreenReceipt ⭐ · Fiscal ⭐ · Execution · Feedback ⭐ (+ BaseService)  ║
║   ▸ validate(data, rules)     → Validator + ValidationException        ║
║   ▸ executeTransactional(cb)  → transação ANINHÁVEL                    ║
║   ▸ invoca OUTROS services (composição)                                ║
╚════════════════════════════════════════════════════════════════════════╝
                    │
                    ▼
╔════════════════════════════════════════════════════════════════════════╗
║  REPOSITORIES (app/repositories/) — SQL (Prepared Statements)          ║
║   um por tabela (+ BaseRepository)                                     ║
║   ▸ fetch / fetchAll       → aplicam o Mapper                          ║
║   ▸ fetchRaw / fetchAllRaw → lookups e AGREGAÇÕES (sem mapper)         ║
╚════════════════════════════════════════════════════════════════════════╝
                    │
                    ▼
╔════════════════════════════════════════════════════════════════════════╗
║  MAPPERS (app/mappers/) — BD(PT, snake_case) → código(EN, camelCase)   ║
║   BaseMapper::cast()  (chaves NULL são OMITIDAS do resultado)          ║
╚════════════════════════════════════════════════════════════════════════╝
                    │
                    ▼
╔════════════════════════════════════════════════════════════════════════╗
║  MySQL 8.4.3 — base `secade_beauty` (24 tabelas)                       ║
╚════════════════════════════════════════════════════════════════════════╝
```

## 3. MAPA HIERÁRQUICO DE FUNCIONALIDADES

### 3.1 Site público e cliente (Main)

```
SECADE BEAUTY — MVP (parte 1: Main)
│
├── 1. SITE PÚBLICO (sem sessão) ───────────────────────────── /modules/main
│   ├── Home ................................................ /
│   │   ├── Hero, Acerca, Categorias
│   │   └── Testemunhos ◀── [feedback_cliente] (fallback estático)
│   ├── Categorias de serviços ............................. /servicos
│   │   └── GET category-all ──▶ CategoryController ──▶ [categoria_profissional]
│   ├── Catálogo de serviços ⭐ ............................ /servicos/:category
│   │   ├── GET booking-services ──▶ ServiceRepository::findActive ──▶ [servico]
│   │   ├── Filtros: categoria · preço · duração · pesquisa
│   │   ├── Modal de detalhes (badge "Apenas Loja")
│   │   └── CTA "Agendar" ──▶ /agendar?services=<id>   (requer sessão)
│   ├── Sobre .............................................. /sobre
│   ├── Contactos .......................................... /contacto
│   └── 404 personalizado .................................. (rota desconhecida)
│
├── 2. AUTENTICAÇÃO E REGISTO
│   ├── Login .............................................. /login
│   │   └── POST auth-login ──▶ AuthService::authenticateLogin
│   │       ├── UserRepository::find(null, email) ──▶ [utilizador]
│   │       ├── password_verify(hash)
│   │       └── Session::createLoginSession(user)   ← define o PERFIL
│   ├── Registo de cliente (2 passos) ...................... /registo
│   │   └── POST auth-register ──▶ AuthService::register
│   │       └── CustomerService::createCustomer   (TRANSAÇÃO)
│   │           ├── UserService::createUser .........──▶ [utilizador]
│   │           ├── CustomerRepository::create ......──▶ [cliente]
│   │           └── CustomerAddressService::createAddress ──▶ [cliente_morada]
│   ├── Recuperar password ................................. /recuperar-passe
│   └── Logout ............................................. POST auth-logout
│
├── 3. CLIENTE: PERFIL E MORADAS
│   └── /perfil   (🅰 cliente)
│       ├── GET customer-profile ......... CustomerService::getCustomerProfile
│       │      ├── UserService::find ──▶ [utilizador]
│       │      ├── CustomerRepository::find ──▶ [cliente]
│       │      └── CustomerAddressService::fetchCustomerAddresses
│       │             └── CustomerAddressRepository::find + CityRepository
│       └── Gestão de moradas (CRUD)
│           ├── POST customer-address-store ......... createAddress ──▶[cliente_morada]
│           ├── POST customer-address-set-principal . setPrincipal  (TRANSAÇÃO)
│           └── POST customer-address-delete ........ deleteAddress
│
├── 4. CLIENTE: WIZARD DE AGENDAMENTO ⭐⭐
│   └── /agendar   (🅰 cliente) ──▶ modules/main/components/bookingWizard.php
│       │
│       ├── PASSO 1 — Serviços
│       │     GET booking-services + category-all
│       │     Totais dinâmicos (duração + valor)
│       │
│       ├── PASSO 2 — Canal
│       │     ├── Loja Física ────────────────────────┐
│       │     └── Carrinha Ambulante ──┐               │
│       │         ✗ BLOQUEADO se algum serviço        │
│       │           tiver requer_espaco_fisico = 1     │
│       │                                              │
│       ├── [LOJA] PASSO 3 — Data e Hora ◀────────────┘
│       │     GET booking-availability (slots 30 min, 09:00-19:00, Ter-Sáb)
│       │
│       ├── [LOJA] PASSO 4 — Profissional  (informativo: "Sem preferência")
│       │
│       ├── [LOJA] PASSO 5 — Resumo (sinal 10% simulado)
│       │     └── POST booking-create-store ⭐
│       │
│       ├── [CARRINHA] PASSO 2B — Morada + Pessoas ⭐
│       │     GET customer-address-list + city-supported
│       │     Estrutura por pessoa (Pessoa 1 = cliente logado)
│       │
│       ├── [CARRINHA] PASSO 2C — OTP ⭐
│       │     POST booking-otp-request ──▶ OTPService::request
│       │        └── código 6 dígitos na SESSÃO (expira 10 min)
│       │     validação client-side vs. window.bookingOtpCode
│       │
│       ├── [CARRINHA] PASSO 3 — Data e Hora (duração = soma por pessoa)
│       │
│       ├── [CARRINHA] PASSO 4 — Política de Sinal (dispensado + termos)
│       │
│       └── [CARRINHA] PASSO 5 — Resumo
│             └── POST booking-create-amb ⭐ (valida OTP server-side)
│
└── 5. CLIENTE: HISTÓRICO E FEEDBACK ⭐
    └── /agendamentos   (🅰 cliente)
        ├── GET booking-my ........──▶ [agendamento] + [agendamento_servico]
        │                                + [agendamento_pessoa]
        ├── Filtros por estado
        └── Formulário de feedback (só se estado = executado/concluido) ⭐
              └── POST feedback-create ──▶ [feedback_cliente]
```

### 3.2 Backoffice (gestor e funcionário)

```
SECADE BEAUTY — MVP (parte 2: Backoffice) ─── /modules/backoffice
│
├── 6. FUNCIONÁRIO: ACEITAÇÃO (FASE 3) ⭐ ──────────────────────────────────
│   └── /gestao/servicos   (🅰 funcionario — o gestor também ABRE a página,
│       │                    mas as APIs recusam-lhe aceitar: 403)
│       ├── Lista "Por aceitar"
│       │     GET admin-service-pending-list
│       │       └── BookingServiceRepository::findPending
│       │             (ambulatório + estado 'pendente_aceitacao_funcionarios')
│       ├── Lista "Aceites por mim" + totais
│       │     GET admin-service-accepted-list
│       ├── ACEITAR serviço ⭐
│       │     POST admin-service-accept ──▶ ServiceAcceptanceService::acceptService
│       │       ├── GreenReceiptService::resolveEmployeePercentage
│       │       ├── BookingServiceRepository::accept  ╌╌▶ [agendamento_servico]
│       │       │      grava: funcionario_id, estado_aceitacao='aceite',
│       │       │             percentagem_funcionario_aplicada,
│       │       │             valor_recibo_verde_funcionario / _plataforma
│       │       └── consolidateIfComplete()  ⭐⭐
│       │             ├── countPendingByBooking == 0 ?
│       │             ├── ✗ 409 se assertNoWindowConflict falhar
│       │             └── updateEstado('totalmente_aceite_funcionarios')
│       │                   └── ╌╌▶ [agendamento]
│       └── DESFAZER aceitação
│             POST admin-service-unaccept
│               ✗ 403 se não foi ele que aceitou
│               ✗ 409 se estado == 'totalmente_aceite_funcionarios'
│
├── 7. GESTOR: AGENDAMENTOS
│   └── /gestao/agendamentos   (🅰 gestor)
│       ├── Lista + filtros (data/local/estado/cidade) + paginação
│       │     GET admin-appointments-list
│       ├── DETALHE por serviço/funcionário ⭐
│       │     GET admin-appointment-details ──▶ ExecutionService
│       │       ::findBookingDetailForAdmin
│       │         → booking + services + progress + execution
│       ├── CANCELAR ........ POST admin-appointment-cancel  (✗ 404/409)
│       └── REGISTAR EXECUÇÃO ⭐
│             POST admin-appointment-execute
│               └── ExecutionService::registerExecution
│                     ├── ✗ 409 se estado não executável
│                     ├── idempotente (alreadyRegistered)
│                     ├── ExecutionRepository::create ╌╌▶ [execucao_agendamento]
│                     └── updateEstado('executado') ╌╌▶ [agendamento]
│
├── 8. GESTOR: ROTAS (FASE 4) ⭐⭐
│   └── /gestao/rotas   (🅰 gestor)
│       ├── Lista por dia+cidade: receita, combustível, custo total,
│       │   rentabilidade, estado, decisão
│       │     GET admin-routes-list ──▶ RotaService::findRouteSummaries
│       │       ├── BookingRepository::findAmbulatoryGroups (AGREGAÇÃO)
│       │       ├── RotaRepository::listWithDetails
│       │       ├── RotaRepository::getFuelCost ──▶ [matriz_deslocacao]
│       │       └── ➜ meetsReference = (rentabilidade >= 50 €)
│       │             ← APENAS INDICADOR VISUAL (não decide nada)
│       ├── APROVAR rota .... POST admin-route-decide {decision:'aprovada'}
│       └── RECUSAR rota .... POST admin-route-decide {decision:'recusada'}
│             └── RotaService::decideRoute ⭐⭐  (TRANSAÇÃO)
│                   ├── findDecidableByCityAndDate
│                   ├── updateEstadoMany(ids,'confirmado'|'cancelado') ╌╌▶[agendamento]
│                   └── RotaRepository::create|updateDecision ╌╌▶ [rota_ambulante]
│                         (decidido_por, decidido_em, observacoes_decisao)
│
├── 9. GESTOR: CALENDÁRIO FISCAL (FASE 4) ⭐
│   └── /gestao/fiscal   (🅰 gestor)
│       ├── Resumo: total / pendentes / vencem em 7 dias / em atraso
│       ├── Lista de obrigações + filtros (tipo, estado)
│       │     GET admin-fiscal-calendar-list ──▶ FiscalService::findCalendar
│       │       ├── generateAlerts(today)   ← ON-DEMAND e IDEMPOTENTE
│       │       │     30/15/7/3/1 dia + em_atraso (diário)
│       │       └── resolveAlertLevel(daysLeft)
│       ├── Criar obrigação . POST admin-fiscal-obligation-create
│       │                      ✗ 422 tipo/periodicidade/prazo/value
│       │                      └── ╌╌▶ [obrigacao_fiscal]
│       ├── Marcar como pago  POST admin-fiscal-obligation-paid  (✗ 404/409)
│       ├── Alertas ......... GET admin-fiscal-alert-list ──▶ [alerta_fiscal]
│       └── Marcar lidos .... POST admin-fiscal-alert-read
│
├── 10. GESTOR: RECIBOS VERDES (FASE 3) ⭐
│   └── /gestao/recibos-verdes   (🅰 gestor)
│       ├── Configuração em vigor (70/30 ou registada)
│       │     GET admin-green-receipt-config
│       ├── Nova configuração com vigência por data
│       │     POST admin-green-receipt-config-save
│       │       ✗ 422 se soma das percentagens != 100
│       │       └── ╌╌▶ [config_recibo_verde]
│       └── Histórico de configurações
│
└── 11. FLUXO TRANSVERSAL: FEEDBACK (FASE 2) ⭐
    ├── Cliente avalia (em /agendamentos) ..... POST feedback-create
    │     ✗ 403 se não é o dono do agendamento
    │     ✗ 409 se já avaliado ou não executado
    │     └── ╌╌▶ [feedback_cliente]
    └── Público vê (na home) .................. GET feedback-list
          └── FeedbackRepository::recent + média (fallback estático)
```

## 4. FLUXO PONTA-A-PONTA: LOJA FÍSICA (5 passos) ⭐

### 4.1 Diagrama de sequência

```
 CLIENTE          BROWSER (JS)             PHP                      MySQL
   │                 │                      │                         │
   │  /agendar       │                      │                         │
   ├────────────────▶│                      │                         │
   │                 │ GET booking-services ──▶ BookingController     │
   │                 │ GET category-all     │   ::serviceList         │
   │                 │                      ├── ServiceRepository ──▶│ [servico]
   │                 │                      │     ::findActive        │ (ativo=1)
   │                 │◀──── 35 serviços ────┤                         │
   │                 │                      │                         │
   │  PASSO 1        │                      │                         │
   │  marcar serviços│                      │                         │
   │  Barba + Design │                      │                         │
   │  (12,20€ / 50min)                      │                         │
   ├────────────────▶│                      │                         │
   │                 │                      │                         │
   │  PASSO 2        │ ✗ se requer_espaco_fisico=1 → carrinha OFF    │
   │  "Loja Física"  │                      │                         │
   ├────────────────▶│                      │                         │
   │                 │                      │                         │
   │  PASSO 3        │                      │                         │
   │  data + hora    │                      │                         │
   │  terça futura   │                      │                         │
   ├────────────────▶│                      │                         │
   │                 │ GET booking-availability?date&duration&local   │
   │                 ├─────────────────────▶│ BookingService          │
   │                 │                      │  ::findAvailability     │
   │                 │                      │  ├ 09:00-19:00 / 30min  │
   │                 │                      │  └ countByDateWindow ──▶│ [agendamento]
   │                 │◀── slots 09:00..18:00┤                           │
   │  clicar 10:00   │                      │                           │
   ├────────────────▶│                      │                           │
   │                 │                      │                           │
   │  PASSO 4        │  Profissional: "Sem preferência" (informativo)   │
   │  PASSO 5        │  Resumo: 12,20€ | sinal 10% = 1,22€              │
   │  CONFIRMAR      │  (Barba 4,07 + Design de Sobrancelha 8,13)       │
   │  CONFIRMAR      │                      │                           │
   ├────────────────▶│                      │                           │
   │                 │ POST booking-create-store                        │
   │                 ├─────────────────────▶│ BookingController         │
   │                 │                      │  ::createStoreBooking     │
   │                 │                      │ ┌── TRANSAÇÃO ─────────┐  │
   │                 │                      │ │ validate date/time   │  │
   │                 │                      │ │ validateBookingDate  │  │
   │                 │                      │ │ ✗ Ter-Sáb, futuro    │ │
   │                 │                      │ │ resolveServices      │  │
   │                 │                      │ │ validateStoreHours   │  │
   │                 │                      │ │ ✗ conflito janela ──▶││[agendamento]
   │                 │                      │ │                      │ │
   │                 │                      │ │ BookingRepository    │ │
   │                 │                      │ │  ::create ╌╌╌╌╌╌╌╌╌╌▶││[agendamento]
   │                 │                      │ │  estado='pendente_   │ │
   │                 │                      │ │   validacao_logistica│ │
   │                 │                      │ │   _loja'             │ │
   │                 │                      │ │ BookingServiceRepo   │ │
   │                 │                      │ │  ::create ╌╌╌╌╌╌╌╌╌╌▶││[agendamento_servico]
   │                 │                      │ │  estado='aceite' ⭐   ││
   │                 │                      │ │  (AUTOMÁTICO)        │ │
   │                 │                      │ └──────────────────────┘ │
   │                 │◀── {bookingId: N} ───┤                          │
   │◀── /agendamento-sucesso?id=N           │                          │
   │                 │                       │                         │
```

### 4.2 Cadeia de código

```
bookingWizard.js
  └── API.booking.createStore(payload)
        └── POST ?action=booking-create-store
              └── BookingController::createStoreBooking()
                    └── BookingService::createStoreBooking()  ⭐ TRANSAÇÃO
                          ├─ validate() → Validator
                          ├─ validateBookingDate()      ✗ 422 (passado / fds)
                          ├─ resolveServices()          ──▶ [servico]
                          ├─ validateStoreOpeningHours()✗ 422
                          ├─ countByDateWindow()        ✗ 409
                          ├─ BookingRepository::create()          ──▶ [agendamento]
                          └─ BookingServiceRepository::create()   ──▶ [agendamento_servico]
```

### 4.3 Dados gravados

| Tabela                | Campos-chave                  | Valor esperado                                 |
| --------------------- | ----------------------------- | ---------------------------------------------- |
| `agendamento`         | `local_prestacao`             | `loja_fisica`                                  |
|                       | `cliente_morada_id`           | **NULL** (loja não tem morada)                 |
|                       | `estado_reserva`              | `pendente_validacao_logistica_loja`            |
|                       | `valor_total` / `valor_sinal` | **`12.20` / `1.22`** (2 serviços: 4,07 + 8,13) |
| `agendamento_servico` | `agendamento_pessoa_id`       | **NULL** (sem estrutura por pessoa)            |
|                       | `estado_aceitacao`            | **`aceite`** ← aceitação automática            |
|                       | registos                      | **2** (serviços deduplicados — Barba, Design)  |
| `agendamento_pessoa`  | —                             | **sem registos**                               |

> ⚠️ Contraste com a carrinha (§5.4): aqui o mesmo serviço pedido por duas
> pessoas **não** é duplicado — na loja não existem "pessoas", apenas a lista
> de serviços. Daí 2 registos (12,20 €) em vez de 3 (16,27 €).

## 5. FLUXO PONTA-A-PONTA: CARRINHA AMBULANTE (7 passos + OTP) ⭐⭐

### 5.1 Diagrama de sequência

```
 CLIENTE        BROWSER (JS)                PHP                        MySQL
   │                │                       │                            │
   │ PASSO 1        │  serviços sem requer_espaco_fisico                 │
   │ PASSO 2        │  "Carrinha Ambulante"                              │
   ├───────────────▶│                       │                            │
   │                │                       │                            │
   │ PASSO 2B       │  GET customer-address-list ──▶ CustomerAddress     │
   │ MORADA         │  GET city-supported ─────────▶ CityService         │
   │ + PESSOAS      │◀─ moradas (Évora) + 10 cidades ──────────────────┤
   ├───────────────▶│                       │                            │
   │  Pessoa 1 = "João Cliente" (pré-preenchido)                         │
   │  Pessoa 2 = "Maria Familiar"                                        │
   │  Pessoa 1: [Barba]           (20 min)                               │
   │  Pessoa 2: [Barba][Design]   (20+30 min)                            │
   │  → duração = 70 min ⭐ (soma por pessoa, NÃO deduplicada)           │
   │                │                       │                            │
   │ PASSO 2C       │                       │                            │
   │ OTP            │  [Enviar Código]      │                            │
   ├───────────────▶│ POST booking-otp-request                           │
   │                ├──────────────────────▶│ BookingController          │
   │                │                       │  ::otpRequest              │
   │                │                       │   └ OTPService::request    │
   │                │                       │       ├ random 6 dígitos   │
   │                │                       │       └ $_SESSION['otp_    │
   │                │                       │          ambulatorio']     │
   │                │◀─ {otpCode:"123456"} ─┤        (expira 10 min)     │
   │◀── "Simulação SMS: 123456" ────────────┤                            │
   │  (grava window.bookingOtpCode)         │                            │
   ├───────────────▶│                       │                            │
   │  ✗ validação CLIENT-SIDE primeiro      │                           │
   │  escrever 123456 → verde               │                            │
   │                │                       │                            │
   │ PASSO 3        │  GET booking-availability?duration=70              │
   │ DATA+HORA      │                       │  &local=carrinha_ambulante│
   │  09:00         │                       │                           │
   ├───────────────▶│                       │                           │
   │ PASSO 4        │  Sinal dispensado + termos obrigatórios           │
   │ PASSO 5        │  Resumo com PESSOAS:                              │
   │                │   Pessoa 1 (João): Barba                          │
   │                │   Pessoa 2 (Maria): Barba, Design                 │
   │                │   Total 16,27€ | Sinal: Dispensado                │
   │  CONFIRMAR     │                       │                           │
   ├───────────────▶│ POST booking-create-amb                           │
   │                ├──────────────────────▶│ BookingController         │
   │                │                       │  ::createAmbulatoryBooking│
   │                │                       │ ┌── TRANSAÇÃO ───────────┐│
   │                │                       │ │ validate addressId +   ││
   │                │                       │ │          otpCode       ││
   │                │                       │ │ validateBookingDate    ││
   │                │                       │ │ OTPService::verify ⭐   ││
   │                │                       │ │  ✗ 422 se inválido/    ││
   │                │                       │ │    expirado            ││
   │                │                       │ │ resolveServicesForPeople││
   │                │                       │ │  (conta POR PESSOA) ⭐ ││
   │                │                       │ │ validateStoreHours    ││
   │                │                       │ │ conflito carrinha ✗409 ││
   │                │                       │ │                        ││
   │                │                       │ │ BookingRepository      ││
   │                │                       │ │  ::create ╌╌╌╌╌╌╌╌╌╌╌▶││[agendamento]
   │                │                       │ │  estado='pendente_     ││
   │                │                       │ │   aceitacao_funcionarios'
   │                │                       │ │   cliente_morada_id = X││
   │                │                       │ │                        ││
   │                │                       │ │ por cada PESSOA:       ││
   │                │                       │ │  BookingPersonRepo     ││
   │                │                       │ │   ::create ╌╌╌╌╌╌╌╌╌╌╌▶││[agendamento_pessoa]
   │                │                       │ │ por cada SERVIÇO:      ││
   │                │                       │ │  BookingServiceRepo    ││
   │                │                       │ │   ::create ╌╌╌╌╌╌╌╌╌╌╌▶││[agendamento_servico]
   │                │                       │ │   estado='pendente' ⭐ ││
   │                │                       │ └────────────────────────┘│
   │                │◀─ {bookingId: N} ─────┤                           │
   │◀── /agendamento-sucesso?id=N&local=carrinha_ambulante              │
   │     "O agendamento está PENDENTE de validação da rota"             │
```

### 5.2 Diferenças-chave face à loja

| Aspeto               | Loja                                | Carrinha                                          |
| -------------------- | ----------------------------------- | ------------------------------------------------- |
| Morada               | ❌ não                              | ✅ `cliente_morada_id` obrigatório                |
| OTP                  | ❌ não                              | ✅ **obrigatório** (validado server-side)         |
| Estrutura por pessoa | ❌ não                              | ✅ `agendamento_pessoa` (Pessoa 1..N)             |
| Duração              | Soma única dos serviços             | **Soma por pessoa** (serviço partilhado conta 2×) |
| Aceitação            | Automática (`aceite`)               | Manual pelo funcionário (`pendente`)              |
| Estado inicial       | `pendente_validacao_logistica_loja` | `pendente_aceitacao_funcionarios`                 |
| Sinal                | 10% simulado                        | **Dispensado** (1ª marcação)                      |
| Passo "Profissional" | ✅ presente (informativo)           | ❌ ausente                                        |

### 5.3 Fórmula da duração e do valor (ponto crítico)

```
duração_total = Σ (duração dos serviços de cada pessoa,
                   sem duplicar DENTRO da mesma pessoa)

Exemplo testado:
  Pessoa 1: [Barba 20]              →  20 min
  Pessoa 2: [Barba 20, Design 30]   →  50 min
  ────────────────────────────────────────────────
  TOTAL                                70 min

valor_total = Σ (preço de cada serviço POR PESSOA)
  = 4,07 (Barba P1) + 4,07 (Barba P2) + 8,13 (Design P2)
  = 16,27 €
```

> ⚠️ **Bug corrigido relevante:** a versão inicial deduplicava os serviços entre pessoas
> (`array_unique` sobre todos os ids), pelo que **o mesmo serviço partilhado não era contabilizado**.
> Agora conta **por pessoa**, conforme o modelo "um registo por (agendamento, pessoa, serviço)".

### 5.4 Dados gravados (carrinha)

| Tabela                | Campos-chave                  | Valor esperado                    |
| --------------------- | ----------------------------- | --------------------------------- |
| `agendamento`         | `local_prestacao`             | `carrinha_ambulante`              |
|                       | `cliente_morada_id`           | **preenchido**                    |
|                       | `estado_reserva`              | `pendente_aceitacao_funcionarios` |
|                       | `valor_total` / `valor_sinal` | `16.27` / `0.00`                  |
| `agendamento_pessoa`  | 2 registos                    | `João Cliente`, `Maria Familiar`  |
| `agendamento_servico` | 3 registos                    | Barba(P1), Barba(P2), Design(P2)  |
|                       | `agendamento_pessoa_id`       | **preenchido** em todos           |
|                       | `estado_aceitacao`            | **`pendente`** ← requer aceitação |

### 5.5 OTP — ciclo de vida

```
[Cliente] Enviar Código
     │
     ▼
OTPService::request(customerId)
     ├── random_int(0, 999999) → pad 6 dígitos   ex.: "042817"
     ├── $_SESSION['otp_ambulatorio'] = {
     │       customerId, code, expiresAt: now + 600s }
     └── devolve otpCode  ──▶ MOSTRADO NO ECRÃ (simulação SMS)
                                └── JS: window.bookingOtpCode

[Cliente] escreve o código
     │
     ├── validação CLIENT-SIDE (booking.validator.js::otpCode)
     │     ✗ vazio / não 6 dígitos / diferente → erro imediato
     │
     └── [Confirmar] → POST booking-create-amb
             └── OTPService::verify(customerId, code)  ← SERVER-SIDE
                   ✗ sem sessão / outro cliente
                   ✗ expirado (> 600s) → unset
                   ✗ hash_equals falha
                   ✓ → unset (CONSUMIDO: não reutilizável)
```

## 6. FLUXO PONTA-A-PONTA: ACEITAÇÃO E CONSOLIDAÇÃO (FASE 3) ⭐⭐

### 6.1 Diagrama de sequência

```
FUNCIONÁRIO      BROWSER (JS)              PHP                        MySQL
   │                │                       │                           │
   │ /gestao/servicos                       │                           │
   ├───────────────▶│ GET admin-service-pending-list                    │
   │                ├──────────────────────▶│ ServiceController         │
   │                │                       │  ::pendingList            │
   │                │                       │   └ ServiceAcceptance     │
   │                │                       │      ::listPendingServices│
   │                │                       │       ├ findPending ─────▶│[agendamento_servico]
   │                │                       │       │  (JOINs enriquecem)│+[agendamento]
   │                │                       │       ├ CategoryRepository│+[agendamento_pessoa]
   │                │                       │       └ GreenReceipt config│
   │                │◀─ 3 serviços pendentes ┤                           │
   │                │   + 70/30 em vigor    │                            │
   │                │                       │                            │
   │ [Aceitar Barba │                       │                            │
   │  (João)]       │                       │                            │
   ├───────────────▶│ POST admin-service-accept                          │
   │                │  {bookingServiceId, bookingId}                     │
   │                ├──────────────────────▶│ ServiceController::accept  │
   │                │                       │  └ requireProfileApi(['funcionario']) ⭐
   │                │                       │      ✗ 403 se gestor/cliente
   │                │                       │  └ ServiceAcceptance      │
   │                │                       │     ::acceptService       │
   │                │                       │ ┌── TRANSAÇÃO ───────────┐│
   │                │                       │ │ resolveService        ││
   │                │                       │ │ requireBooking        ││
   │                │                       │ │ assertAcceptableBooking││
   │                │                       │ │  ✗ 409 se não é carrinha││
   │                │                       │ │  ✗ 409 se cancelado/   ││
   │                │                       │ │    executado           ││
   │                │                       │ │ GreenReceipt::resolve  ││
   │                │                       │ │  EmployeePercentage    ││
   │                │                       │ │   → 70.00              ││
   │                │                       │ │ BookingServiceRepo     ││
   │                │                       │ │  ::accept ╌╌╌╌╌╌╌╌╌╌╌▶││[agendamento_servico]
   │                │                       │ │   funcionario_id = 2   ││
   │                │                       │ │   estado = 'aceite'    ││
   │                │                       │ │   percentagem = 70.00  ││
   │                │                       │ │   rv_func = 2.85       ││
   │                │                       │ │   rv_plat = 1.22       ││
   │                │                       │ │ consolidateIfComplete  ││
   │                │                       │ │  countPending == 0?    ││
   │                │                       │ │   NÃO (ainda há 2)     ││
   │                │                       │ └───────────────────────┘│
   │                │◀─ {consolidated:false, greenReceipt:{...}}        │
   │◀── "Serviço aceite! RV: 2,85€ / 1,22€" │                           │
```

### 6.2 Desfazer, troca e consolidação

```
   │ [Desfazer]     │                       │                            │
   ├───────────────▶│ POST admin-service-unaccept                        │
   │                │                       │ ✗ 403 se não foi ele      │
   │                │                       │ ✗ 409 se consolidado      │
   │                │                       │ BookingServiceRepo         │
   │                │                       │  ::unaccept ╌╌╌╌╌╌╌╌╌╌╌╌╌▶││[agendamento_servico]
   │                │                       │   funcionario_id = NULL   │
   │                │                       │   estado = 'pendente'     │
   │                │                       │   rv_* = NULL             │
   │                │                       │                           │
   │ [Aceitar os 2  │                       │                           │
   │  restantes]    │                       │                           │
   ├───────────────▶│ POST admin-service-accept (último serviço)        │
   │                │                       │ ┌── TRANSAÇÃO ───────────┐│
   │                │                       │ │ ... accept() ...      ││
   │                │                       │ │ consolidateIfComplete ││
   │                │                       │ │  countPending == 0 ✓  ││
   │                │                       │ │ assertNoWindowConflict││
   │                │                       │ │  countByDateWindow ──▶││[agendamento]
   │                │                       │ │   ✗ 409 se sobrepõe   ││
   │                │                       │ │ updateEstado ╌╌╌╌╌╌╌╌▶││[agendamento]
   │                │                       │ │  'totalmente_aceite_  ││
   │                │                       │ │   funcionarios' ⭐⭐   ││
   │                │                       │ └───────────────────────┘│
   │                │◀─ {consolidated:true} ┤                             │
   │◀── "...Agendamento TOTALMENTE ACEITE   │                             │
   │     (janela temporal bloqueada)"       │                             │
   │                │                       │                             │
   │ [Desfazer] ✗   │                       │                            │
   ├───────────────▶│ POST admin-service-unaccept                         │
   │                │                       │ ✗ 409 "O agendamento já    │
   │◀── BLOQUEADO ──┤                       │   está totalmente aceite"   │
```

### 6.3 Cadeia de código

```
backoffice/js/components/services.js
  └── API.admin.services.accept(bookingServiceId, bookingId)
        └── POST ?action=admin-service-accept
              └── ServiceController::accept()
                    ├── requireEmployee()   ← helper privado que chama
                    │     Session::requireProfileApi(["funcionario"])  (403)
                    └── ServiceAcceptanceService::acceptService()  ⭐ TRANSAÇÃO
                          ├─ resolveService() → findByIdAndBooking
                          ├─ assertAcceptableBooking()      ✗ 409
                          ├─ GreenReceiptService::resolveEmployeePercentage()
                          ├─ BookingServiceRepository::accept()           ──▶ [agendamento_servico]
                          └─ consolidateIfComplete()  ⭐⭐
                                ├─ countPendingByBooking() === 0
                                ├─ assertNoWindowConflict()  ✗ 409
                                └─ BookingRepository::updateEstado()       ──▶ [agendamento]
```

### 6.4 Bloqueio da janela temporal — como funciona

```
consolidateIfComplete(bookingId)
   │
   ├── 1. Ainda há serviços 'pendente'? ──sim──▶ devolve false (não consolida)
   │
   └── 2. Não há → assertNoWindowConflict(bookingId)
          │
          ├── duração = SUM(duracao_minutos) do agendamento
          ├── countByDateWindow(dataHora, duração, 'carrinha_ambulante',
          │                     excludeIds=[bookingId])
          │      └── procura agendamentos em:
          │            'pendente_validacao_logistica_loja'
          │            'totalmente_aceite_funcionarios'
          │            'confirmado'
          │          que se SOBREPONHAM no tempo
          │
          ├── conflitos > 0 ──▶ ✗ 409
          │       "Existe outro agendamento consolidado na mesma janela temporal."
          └── conflitos == 0 ─▶ ✓ updateEstado('totalmente_aceite_funcionarios')
```

### 6.5 Ciclo de vida do serviço em `agendamento_servico`

```
pendente ──[aceitar]──▶ aceite
   ▲                      │
   │                      │
   └──[desfazer]──────────┘
        ✗ BLOQUEADO (409) se o agendamento já estiver
          'totalmente_aceite_funcionarios'

TROCA: aceitar um serviço JÁ 'aceite' por OUTRO funcionário
       → transfere (isSwap = true); quem pode desfazer passa a ser o novo
```

### 6.6 Recibos Verdes — cálculo na aceitação

```
GreenReceiptService::resolveEmployeePercentage(employeeId, date)
   └── vigência por data em [config_recibo_verde]
         │
         ├── config vigente?  ──sim──▶ percentagem dessa config
         └── não ──▶ DEFAULTS: 70.00 (funcionário) / 30.00 (plataforma)

Cálculo (na aceitação, gravado em [agendamento_servico]):
   valor_recibo_verde_funcionario = preço_serviço × %funcionário / 100
   valor_recibo_verde_plataforma  = preço_serviço × %plataforma  / 100

Exemplo (Barba 4,07 €, 70/30):
   funcionário = 4,07 × 0,70 = 2,85 €
   plataforma  = 4,07 × 0,30 = 1,22 €
```

## 7. FLUXO PONTA-A-PONTA: DECISÃO MANUAL DE ROTAS (FASE 4) ⭐⭐

### 7.1 Princípio de desenho (o mais importante)

```
╔═══════════════════════════════════════════════════════════════════════╗
║  A DECISÃO DE ROTAS É **MANUAL E LIVRE** — FEITA PELO GESTOR          ║
║                                                                       ║
║   rentabilidade = receita − (combustível + CUSTO_FIXO 50 €)           ║
║                          │                                            ║
║                          ▼                                            ║
║   meetsReference = (rentabilidade >= 50 €)                            ║
║   ► INDICADOR VISUAL (badge "acima/abaixo do valor de referência")    ║
║     NÃO altera estados · NÃO bloqueia · NÃO decide NADA               ║
║                          │                                            ║
║                          ▼                                            ║
║   O GESTOR clica APROVAR ou RECUSAR, LIVREMENTE.                      ║
║   Pode APROVAR uma rota abaixo de 50 €.                               ║
║   Pode RECUSAR uma rota acima de 50 €.                                ║
╚═══════════════════════════════════════════════════════════════════════╝

❌ REVOGADO: algoritmo automático com limiar de 100 € (v1.0 da documentação)
✅ PREVALECENTE: especificacao_mvp.md §3.1 — Fase 4
```

### 7.2 Listagem — composição dos indicadores

```
GET admin-routes-list ──▶ RotaController::list
   └── RotaService::findRouteSummaries()
        ├── BookingRepository::findAmbulatoryGroups()  (AGREGAÇÃO)
        │     ⟶ uma linha por (dia, cidade) com:
        │        agendamentos, receita, pessoas
        ├── RotaRepository::listWithDetails()  ──▶ [rota_ambulante]
        │     ⟶ decisão já registada (se existir)
        ├── RotaRepository::getFuelCost()      ──▶ [matriz_deslocacao]
        │     ⟶ valor_estimado (custo de combustível)
        └── por cada grupo:
              custoTotal     = combustivel + 50.00
              rentabilidade  = receita − custoTotal
              meetsReference = rentabilidade >= 50.00   ← só indicador
```

### 7.3 Aspecto da lista (exemplo real)

```
┌──────────────────────────────────────────────────────────────────────┐
│ 🟢 Évora · 22/09/2026 · 3 agendamentos · 5 serviços                    │
│                                                                        │
│    Receita .......... 48,81 €                                          │
│    Combustível ......  3,20 €                                          │
│    Custo fixo ....... 50,00 €                                          │
│    Custo total ...... 53,20 €                                          │
│    Rentabilidade .... −4,39 €   ⚠ ABAIXO DA REFERÊNCIA (50 €)         │
│                                                                        │
│    [ ✔ APROVAR ]   [ ✘ RECUSAR ]   [ Observações… ]                  │
└──────────────────────────────────────────────────────────────────────┘
       │
       └── ⭐ O gestor PODE aprovar mesmo com −4,39 €
             (a decisão é livre; o aviso é apenas informativo)
```

### 7.4 Diagrama de sequência da decisão

```
  GESTOR         BROWSER (JS)              PHP                        MySQL
   │                │                       │                            │
   │ [✔ Aprovar]    │  ← MESMO com rentabilidade abaixo de 50 € ⭐⭐    │
   ├───────────────▶│ POST admin-route-decide                            │
   │                │  {cityId, date, decision:'aprovada', notes:''}     │
   │                ├──────────────────────▶│ RotaController             │
   │                │                       │  ::decideRoute             │
   │                │                       │  └ requireProfileApi(['gestor'])
   │                │                       │  └ RotaService::decideRoute ⭐
   │                │                       │ ┌── TRANSAÇÃO ───────────┐│
   │                │                       │ │ validate decision      ││
   │                │                       │ │  ✗ 422 se não for      ││
   │                │                       │ │    aprovada/recusada   ││
   │                │                       │ │ findDecidable ────────▶││[agendamento]
   │                │                       │ │  ✗ 404 se nenhum       ││
   │                │                       │ │                        ││
   │                │                       │ │ decision='aprovada'    ││
   │                │                       │ │    → 'confirmado'      ││
   │                │                       │ │ decision='recusada'    ││
   │                │                       │ │    → 'cancelado'       ││
   │                │                       │ │ updateEstadoMany ╌╌╌╌╌▶││[agendamento]
   │                │                       │ │                        ││
   │                │                       │ │ RotaRepository         ││
   │                │                       │ │  ::create|updateDecision││[rota_ambulante]
   │                │                       │ │   decidido_por = <user>││
   │                │                       │ │   decidido_em = NOW()  ││
   │                │                       │ │   observacoes_decisao  ││
   │                │                       │ └────────────────────────┘│
   │                │◀─ {decision, affected:3}                           │
   │◀── "Rota aprovada — 3 agendamentos confirmados"                     │
```

### 7.5 Cadeia de código

```
backoffice/js/components/routes.js
  └── API.admin.decideRoute({cityId, date, decision, notes})
        └── POST ?action=admin-route-decide
              └── RotaController::decideRoute()
                    ├── requireProfileApi(["gestor"])        ← 403 se não for gestor
                    └── RotaService::decideRoute()   ⭐⭐ TRANSAÇÃO
                          ├─ validate(decision ∈ {aprovada, recusada})   ✗ 422
                          ├─ findDecidableByCityAndDate()                 ✗ 404
                          │      └── [agendamento] 'carrinha_ambulante'
                          │          em 'pendente_aceitacao_funcionarios'
                          │          ou 'totalmente_aceite_funcionarios'
                          ├─ updateEstadoMany(ids, 'confirmado'|'cancelado') ──▶ [agendamento]
                          └─ RotaRepository::create|updateDecision()          ──▶ [rota_ambulante]
```

### 7.6 Prova de que a decisão é MANUAL (testes que o demonstram)

| #   | Cenário         | Rentabilidade        | Ação do gestor | Resultado obtido                              |
| --- | --------------- | -------------------- | -------------- | --------------------------------------------- |
| 1   | Évora, 3 agend. | **−4,39 €** (abaixo) | **APROVAR**    | ✅ `confirmado` (aprovado apesar do aviso)    |
| 2   | Outra cidade    | **+80,00 €** (acima) | **RECUSAR**    | ✅ `cancelado` (recusado apesar de favorável) |

```
Se existisse um algoritmo automático (como o revogado de 100 €):
  ✗ o cenário 1 seria RECUSADO automaticamente
  ✗ o cenário 2 seria APROVADO automaticamente

Como foi implementado (Fase 4 — manual):
  ✓ ambos os cenários respeitam a VONTADE DO GESTOR
  ✓ o valor de 50 € aparece APENAS como aviso visual
```

### 7.7 Estados afetados pela decisão

```
ANTES da decisão:
  (( pendente_aceitacao_funcionarios ))  ← aguarda aceitação
                  │
                  ▼ (todos os serviços aceites)
  (( totalmente_aceite_funcionarios ))   ← aguarda decisão de rota
                  │
        ┌─────────┴─────────┐
        │                   │
   [APROVAR]           [RECUSAR]
        │                   │
        ▼                   ▼
  (( confirmado ))     (( cancelado ))
```

## 8. FLUXO PONTA-A-PONTA: CALENDÁRIO FISCAL (FASE 4) ⭐

### 8.1 Diagrama de sequência

```
  GESTOR         BROWSER (JS)              PHP                        MySQL
   │                │                       │                           │
   │ /gestao/fiscal │                       │                           │
   ├───────────────▶│ GET admin-fiscal-calendar-list                    │
   │                ├──────────────────────▶│ FiscalController::calendar│
   │                │                       │  └ FiscalService          │
   │                │                       │     ::findCalendar        │
   │                │                       │ ┌── ON-DEMAND ───────────┐│
   │                │                       │ │ generateAlerts(today)  ││
   │                │                       │ │  ├ list obligations ──▶││[obrigacao_fiscal]
   │                │                       │ │  ├ daysLeft = prazo-today
   │                │                       │ │  ├ alertType = resolve ││
   │                │                       │ │  │   AlertLevel(daysLeft)
   │                │                       │ │  └ insertIfNotExists ╌▶││[alerta_fiscal]
   │                │                       │ │    (IDEMPOTENTE)      ││
   │                │                       │ └───────────────────────┘│
   │                │◀─ obrigações + resumo ┤                           │
   │                │                       │                           │
   │  ┌────────────────────────────────────────────────────────────┐    │
   │  │ RESUMO                                                     │    │
   │  │   Total ........... 8     Pendentes ...... 6               │    │
   │  │   Vencem em 7d .... 3     Em atraso ....... 1               │   │
   │  └────────────────────────────────────────────────────────────┘    │
   │                │                       │                           │
   │  ┌────────────────────────────────────────────────────────────┐    │
   │  │ 🔴 IVA Trimestral · prazo 15/07/2026 · 1 234,00 €          │    │
   │  │    Estado: pendente   ⏰ EM ATRASO há 68 dias (diário)     │     │
   │  │    [ Marcar como pago ]                                    │    │
   │  ├────────────────────────────────────────────────────────────┤    │
   │  │ 🟠 Segurança Social · prazo 20/10/2026 · 380,00 €          │    │
   │  │    Estado: pendente   ⚠ faltam 3 dias  (nível '3_dias')    │   │
   │  └────────────────────────────────────────────────────────────┘    │
   │                │                       │                           │
   │ [Marcar pago]  │                       │                           │
   ├───────────────▶│ POST admin-fiscal-obligation-paid                 │
   │                │  {obligationId, notes}                            │
   │                ├──────────────────────▶│ FiscalController          │
   │                │                       │  ::markPaid → FiscalService::markPaid
   │                │                       │ ┌── TRANSAÇÃO ───────────┐│
   │                │                       │ │ requireObligation     ││
   │                │                       │ │  ✗ 404 se não existe  ││
   │                │                       │ │ assertNotPaid         ││
   │                │                       │ │  ✗ 409 se já pago     ││
   │                │                       │ │ markPaid ╌╌╌╌╌╌╌╌╌╌╌╌▶││[obrigacao_fiscal]
   │                │                       │ │   estado='pago'       ││
   │                │                       │ │   data_pagamento=now  ││
   │                │                       │ │   notas (?? null) ⚠   ││
   │                │                       │ └───────────────────────┘│
   │                │◀─ obrigação atualizada ┤                          │
   │  ┌────────────────────────────────────────────────────────────┐    │
   │  │ ✅ IVA Trimestral ....... PAGO em 16/07/2026               │    │
   │  └────────────────────────────────────────────────────────────┘    │
```

### 8.2 Níveis de alerta progressivos — regra exata

```
FiscalService::resolveAlertLevel(daysLeft)
   │
   │  ⚠ A iteração é feita do MAIS URGENTE para o MAIS LARGO
   │    (do menor número de dias para o maior)
   │
   ├── daysLeft < 0  ──▶  'em_atraso'   (gerado diariamente)
   ├── daysLeft <= 1 ──▶  '1_dia'
   ├── daysLeft <= 3 ──▶  '3_dias'
   ├── daysLeft <= 7 ──▶  '7_dias'
   ├── daysLeft <= 15 ─▶  '15_dias'
   ├── daysLeft <= 30 ─▶  '30_dias'
   └── daysLeft > 30 ──▶  (nenhum alerta)

Exemplo: faltam 7 dias
   ✔ nível CORRETO: '7_dias'
   ✗ bug corrigido: reportava '30_dias' (iteração por ordem descendente)
```

### 8.3 Progressão dos alertas ao longo do tempo

```
Prazo                     30d    15d    7d    3d    1d   prazo  atraso
  │                        │      │     │     │     │      │       │
  │◀──────────────────────▶│      │     │     │     │      │       │
  │  (silêncio)            │      │     │     │     │      │       │
  │                        │      │     │     │     │      │       │
  ▼                        ▼      ▼     ▼     ▼     ▼      ▼       ▼
                          🟢     🟡    🟠    🟠    🔴    🔴      🔴🔴
                        30_dias 15_dias 7_dias 3_dias 1_dia  (prazo) em_atraso
                                                                    (diário)

✅ IDEMPOTÊNCIA: generateAlerts() pode correr N vezes no mesmo dia
   → o alerta do mesmo tipo NÃO é duplicado
   → apenas 'em_atraso' é reinserido uma vez por dia
```

### 8.4 Cadeia de código (nomes reais)

```
backoffice/js/components/fiscal.js
  ├── API.admin.fiscal.calendar({...})      → ?action=admin-fiscal-calendar-list
  ├── API.admin.fiscal.alerts()             → ?action=admin-fiscal-alert-list
  ├── API.admin.fiscal.createObligation(d)  → ?action=admin-fiscal-obligation-create
  ├── API.admin.fiscal.markPaid(id)         → ?action=admin-fiscal-obligation-paid
  └── API.admin.fiscal.markAlertsRead()     → ?action=admin-fiscal-alert-read

backoffice/js/components/greenReceipts.js
  ├── API.admin.greenReceipt.config()       → ?action=admin-green-receipt-config
  └── API.admin.greenReceipt.saveConfig(d)  → ?action=admin-green-receipt-config-save
      (existe também o endpoint admin-green-receipt-simulate, método
       FiscalController::greenReceiptSimulate)

┌─ FiscalController (métodos) ────────────────────────────────────────────┐
│  calendar()              → FiscalService::findCalendar()                 │
│       └── generateAlerts() + resolveAlertLevel()                         │
│  createObligation()      → FiscalService::createObligation()     ✗ 422  │
│  markPaid()              → FiscalService::markPaid()         ✗ 404/409  │
│  alerts()                → FiscalAlertRepository::list()                 │
│  markAlertsRead()        → FiscalAlertRepository::markRead()             │
│  greenReceiptConfig()    → GreenReceiptService::findActiveConfig()       │
│  saveGreenReceiptConfig()→ GreenReceiptService::saveConfig()      ✗ 422 │
│  greenReceiptSimulate()  → GreenReceiptService::simulate()               │
└─────────────────────────────────────────────────────────────────────────┘
```

### 8.5 Dados e tabelas

| Tabela             | Campos-chave                                       | Notas                                                     |
| ------------------ | -------------------------------------------------- | --------------------------------------------------------- |
| `obrigacao_fiscal` | `tipo`, `periodicidade`, `prazo`, `valor_estimado` | `tipo` ∈ IVA / IRC / Segurança Social / Seguros           |
|                    | `estado`                                           | `pendente` → `pago`                                       |
|                    | `data_pagamento`, `notas`                          | preenchidos no pagamento                                  |
| `alerta_fiscal`    | `obrigacao_fiscal_id`, `tipo_alerta`               | `30_dias`/`15_dias`/`7_dias`/`3_dias`/`1_dia`/`em_atraso` |
|                    | `mensagem`, `lido`                                 | `lido` alternado pelo endpoint `admin-fiscal-alert-read`  |

---

## 9. FLUXO PONTA-A-PONTA: EXECUÇÃO + FEEDBACK (FASES 2/4) ⭐

### 9.1 Cadeia de código

```
Gestor → backoffice/js/components/appointments.js
  └── API.admin.executeAppointment(bookingId)
        └── POST ?action=admin-appointment-execute
              └── ExecutionService::registerExecution()   ⭐ TRANSAÇÃO
                    ├─ requireBooking()        ✗ 404
                    ├─ assertExecutable()      ✗ 409 (cancelado / já executado)
                    ├─ ExecutionRepository::create()   ──▶ [execucao_agendamento]
                    └─ BookingRepository::updateEstado('executado')  ──▶ [agendamento]

Cliente → modules/main/js/components/appointments.js
  └── API.feedback.create({bookingId, rating, comment})
        └── POST ?action=feedback-create
              └── FeedbackController::create()
                    └── FeedbackService::createFeedback()   ⭐ TRANSAÇÃO
                          ├─ validate(bookingId, rating)       ✗ 422
                          ├─ rating fora de 1..5               ✗ 422
                          ├─ requireBooking()                  ✗ 404
                          ├─ customerId != dono                ✗ 403
                          ├─ status ∉ {executado, concluido}   ✗ 409
                          ├─ sem execução registada            ✗ 409
                          ├─ já tem feedback                   ✗ 409
                          └─ FeedbackRepository::create()    ──▶ [feedback_cliente]

Público → modules/main/js/components/testimonial.js
  └── API.feedback.list(limit)   (SEM sessão)
        └── GET ?action=feedback-list
              └── FeedbackController::publicList()
                    └── FeedbackService::findPublicFeedback()
```

### 9.2 Diagrama de sequência do ciclo completo

```
 GESTOR       CLIENTE/PÚBLICO         PHP                     MySQL
   │                │                   │                        │
   │ [registo de execução]              │                        │
   ├───────────────▶│ POST admin-appointment-execute             │
   │                ├──────────────────▶│ ExecutionService       │
   │                │                   │  ::registerExecution   │
   │                │                   │  ├ ✗ 404 / 409        │
   │                │                   │  ├ create ╌╌╌╌╌╌╌╌╌╌▶│[execucao_agendamento]
   │                │                   │  └ updateEstado ╌╌╌╌▶│[agendamento]
   │                │◀─ execução registada                      │
   │                │                   │                       │
   │      [CLIENTE abre /agendamentos]  │                       │
   │                │ GET booking-my ──▶│ BookingService        │
   │                │                   │  ::findCustomerBookings
   │                │                   │  ├ canLeaveFeedback ⭐ │
   │                │                   │  │   = estado ∈        │
   │                │                   │  │   {executado,       │
   │                │                   │  │    concluido}       │
   │                │                   │  │   && !hasFeedback   │
   │                │                   │  └ hasFeedback ──────▶│[feedback_cliente]
   │                │◀─ canLeaveFeedback=true                      │
   │  ┌───────────────────────────────────────────────────────┐    │
   │  │ Agendamento #12 · Carrinha · 09:00 · 16,27 €          │    │
   │  │ Estado: ✅ executado                                  │    │
   │  │ ⭐⭐⭐⭐⭐  [ Deixar avaliação ]                         │ │
   │  └───────────────────────────────────────────────────────┘    │
   │                │                   │                          │
   │      [CLIENTE avalia 5★]           │                         │
   │                │ POST feedback-create                         │
   │                │ {bookingId, rating:5, comment:"Excelente"}   │
   │                ├──────────────────▶│ FeedbackService          │
   │                │                   │  ::createFeedback        │
   │                │                   │  ├ validate ✗ 422       │
   │                │                   │  ├ dono ✗ 403           │
   │                │                   │  ├ estado ✗ 409         │
   │                │                   │  ├ execução ✗ 409       │
   │                │                   │  ├ duplicado ✗ 409      │
   │                │                   │  └ create ╌╌╌╌╌╌╌╌╌╌▶│[feedback_cliente]
   │                │◀─ Avaliação registada                      │
   │                │                   │                        │
   │      [PÚBLICO abre a HOME]         │                        │
   │                │ GET feedback-list ▶│ FeedbackService       │
   │                │  (SEM sessão)     │  ::findPublicFeedback  │
   │                │                   │  ├ recent() ─────────▶│[feedback_cliente]
   │                │                   │  │  + nome cliente    │[cliente]
   │                │                   │  │  + serviço         │[utilizador]
   │                │                   │  └ averageRating        │
   │                │◀─ testemunho real │                         │
   │  ┌───────────────────────────────────────────────────────┐   │
   │  │ ⭐⭐⭐⭐⭐ "Excelente"                                  │ │
   │  │ — João Cliente · Barba · 21/09/2026                   │   │
   │  │ (restante grelha = testemunhos ESTÁTICOS)              │  │
   │  └───────────────────────────────────────────────────────┘   │
```

### 9.3 Regras de elegibilidade do feedback

```
canLeaveFeedback(booking)  ⭐
   │
   ├── estado ∈ { 'executado', 'concluido' }    ──não──▶ false
   ├── execução registada em [execucao_agendamento] ──não──▶ false
   └── já existe feedback para este agendamento  ──sim──▶ false
                    │
                    └── todos OK ──▶ true  (formulário visível)

Feedback é PÚBLICO assim que registado (SEM moderação — simplificação
académica). A única condição no repositório é:
      WHERE f.classificacao_estrelas IS NOT NULL
não há filtro por nota mínima (avaliações de 1★ também aparecem).
```

> ⚠️ Verificação: `FeedbackRepository::recent()` filtra apenas
> `classificacao_estrelas IS NOT NULL` — **não existe** regra `rating >= 4`.

### 9.4 Cadeia de código (resumo)

| Ato               | Endpoint                    | Serviço                                      | Tabela                                                       |
| ----------------- | --------------------------- | -------------------------------------------- | ------------------------------------------------------------ |
| Registar execução | `admin-appointment-execute` | `ExecutionService::registerExecution`        | `execucao_agendamento` + `agendamento`                       |
| Avaliar           | `feedback-create`           | `FeedbackService::createFeedback`            | `feedback_cliente`                                           |
| Ver na home       | `feedback-list`             | `FeedbackService::findPublicFeedback`        | `feedback_cliente` (+`agendamento` +`cliente` +`utilizador`) |
| Estado do cliente | `booking-my`                | `FeedbackService::findCustomerFeedbackState` | `feedback_cliente`                                           |

## 10. MÁQUINA DE ESTADOS

### 10.0 Enum real de `agendamento.estado_reserva` (8 valores)

```
'pendente_aceitacao_funcionarios'   ← criado (carrinha)
'pendente_validacao_logistica_loja' ← criado (loja)
'totalmente_aceite_funcionarios'    ← consolidado (carrinha)
'confirmado'                        ← rota aprovada pelo gestor
'recusado'                          ← estado do enum, NÃO usado pelo fluxo atual ⚠
'cancelado'                         ← rota recusada OU cancelamento manual
'executado'                         ← execução registada
'concluido'                         ← terminal (aceite pelo feedback)
```

> ⚠️ `'recusado'` existe no ENUM mas o `RotaService` grava **`'cancelado'`**
> quando a rota é recusada (`$bookingState = $approved ? "confirmado" : "cancelado"`).
> Não é um bug; é um valor legado do schema.

### 10.1 `agendamento.estado_reserva` — LOJA FÍSICA

```
        [cliente confirma]
                 │
                 ▼
   (( pendente_validacao_logistica_loja ))
                 │
                 │  [gestor decide rota? NÃO se aplica à loja]
                 │
                 ├──[gestor cancela]──▶ (( cancelado ))
                 │
                 └──[gestor regista execução]──▶ (( executado ))
                                                        │
                                                        ▼
                                              [cliente pode avaliar]
                                                        │
                                                        ▼
                                                 [feedback_cliente]
```

### 10.2 `agendamento.estado_reserva` — CARRINHA AMBULANTE (ciclo completo)

```
        [cliente confirma + OTP válido]
                 │
                 ▼
   (( pendente_aceitacao_funcionarios ))
        └─ todos os agendamento_servico = 'pendente'
                 │
                 │  [funcionários aceitam individualmente]
                 │  ServiceAcceptanceService::acceptService
                 ▼
        (último serviço aceite)
                 │
                 ▼
   (( totalmente_aceite_funcionarios ))  ⭐ CONSOLIDAÇÃO
        └─ janela temporal BLOQUEADA
        └─ desfazer BLOQUEADO (409)
                 │
                 │  [gestor decide a rota — MANUAL]
                 │  RotaService::decideRoute
                 │
        ┌────────┴────────┐
        │                 │
   [APROVAR]         [RECUSAR]
        │                 │
        ▼                 ▼
  (( confirmado ))  (( cancelado ))
        │                 │
        │                 └──▶ ✗ não pode ser executado
        │
        └──[gestor regista execução]──▶ (( executado ))
                                              │
                                              ▼
                                    [cliente pode avaliar]
                                              │
                                              ▼
                                       [feedback_cliente]
```

### 10.3 Tabela de transições permitidas (verificada no código)

| Estado de origem                    | Ação              | Estado de destino                   | Quem        | Guarda (código)                                                           |
| ----------------------------------- | ----------------- | ----------------------------------- | ----------- | ------------------------------------------------------------------------- |
| —                                   | criar (loja)      | `pendente_validacao_logistica_loja` | cliente     | `validateBookingDate` + `validateStoreOpeningHours` + `countByDateWindow` |
| —                                   | criar (carrinha)  | `pendente_aceitacao_funcionarios`   | cliente     | + `OTPService::verify` + morada + pessoas                                 |
| `pendente_aceitacao_funcionarios`   | aceitar todos     | `totalmente_aceite_funcionarios`    | funcionário | `consolidateIfComplete` + `assertNoWindowConflict`                        |
| `totalmente_aceite_funcionarios`    | aprovar rota      | `confirmado`                        | **gestor**  | `decideRoute` — **decisão manual**                                        |
| `totalmente_aceite_funcionarios`    | recusar rota      | **`cancelado`**                     | **gestor**  | `decideRoute` — **decisão manual**                                        |
| ≠ {cancelado, executado, concluido} | cancelar          | `cancelado`                         | gestor      | `cancelBooking` — ✗ 409 nos 3 estados                                    |
| `confirmado`                        | registar execução | `executado`                         | gestor      | `ExecutionService::registerExecution`                                     |
| `executado`                         | avaliar           | (sem mudança)                       | cliente     | 1 por agendamento                                                         |

```
Aceitação (assertAcceptableBooking) — bloqueia se:
   ✗ local != 'carrinha_ambulante'                          → 409
   ✗ estado ∈ {cancelado, recusado, executado, concluido}   → 409

Cancelamento (cancelBooking) — bloqueia se:
   ✗ estado ∈ {cancelado, executado, concluido}             → 409

Execução (registerExecution) — bloqueia se:
   ✗ estado não executável / execução já registada           → 409
```

### 10.4 `agendamento_servico.estado_aceitacao`

```
  ┌──────────────────────────────────────────────────────┐
  │                                                       │
  ▼                                                       │
(( pendente )) ──[funcionário aceita]──▶ (( aceite ))     │
  ▲                                          │            │
  │                                          │            │
  └────────[funcionário desfaz]──────────────┘            │
                ✗ 409 se o AGENDAMENTO estiver           │
                  'totalmente_aceite_funcionarios' ──────┘
                  (bloqueio da janela temporal)

LOJA: criado diretamente como (( aceite )) — aceitação automática
```

### 10.5 `rota_ambulante` — campos e estados (valores reais do schema)

```
ENUM real de `estado_rota`:
   'planeada' | 'aprovada' | 'recusada' | 'em_execucao' | 'concluida'
                  ▲            ▲
                  └── escritos por RotaService::decideRoute
                      (recebe status = 'aprovada' | 'recusada')
```

| Coluna real (BD)             | Tipo     | Escrita por                 | Descrição                                    |
| ---------------------------- | -------- | --------------------------- | -------------------------------------------- |
| `estado_rota`                | ENUM     | `create` / `updateDecision` | `planeada` (default) → `aprovada`/`recusada` |
| `custo_estimado_combustivel` | decimal  | `create`                    | de `[matriz_deslocacao].valor_estimado`      |
| `quota_parte_cliente`        | decimal  | `create`                    | **0.00** (sem regra definida)                |
| `lucro_servicos`             | decimal  | `create`                    | receita dos serviços                         |
| `lucro_total`                | decimal  | `create`                    | receita − (combustível + 50 €)               |
| `decidido_por`               | int FK   | `create`/`updateDecision`   | `utilizador.id` do gestor                    |
| `decidido_em`                | datetime | `create`/`updateDecision`   | `NOW()`                                      |
| `observacoes_decisao`        | text     | `create`/`updateDecision`   | notas livres do gestor                       |

> Mapeamento no código (`RotaMapper`), BD → código:
> `data_rota`→`routeDate` · `base_partida_id`→`baseId` · `cidade_id`→`cityId`
> · `estado_rota`→`status` · `custo_estimado_combustivel`→`fuelCost`
> · `quota_parte_cliente`→`customerShare` · `lucro_servicos`→`servicesProfit`
> · `lucro_total`→`totalProfit` · `decidido_por`→`decidedBy`
> · `decidido_em`→`decidedAt` · `observacoes_decisao`→`decisionNotes`
>
> ⚠️ **Não existe coluna `decisao`** — a decisão é gravada diretamente
> em `estado_rota` como `aprovada` / `recusada`.

## 11. CICLO DE VIDA DE UM PEDIDO (REQUEST)

### 11.1 Entrada — `index.php` (Front Controller)

```
URL: /secade-beauty-tarde/api?action=admin-service-accept      (POST)
URL: /secade-beauty-tarde/gestao/rotas                         (GET)
                            │
                            ▼
┌──────────────────────────────────────────────────────────────────────┐
│ index.php                                                             │
│   1. require app/config/config.php   (BASE_URL, APP_PATH, ROOT_PATH)  │
│   2. require app/utils/Session.php   (Session::init() é lazy)         │
│   3. $path = resolve_request_path(BASE_URL)                           │
│        ├── parse_url(REQUEST_URI, PHP_URL_PATH)                       │
│        ├── str_replace(BASE_URL path, "")   → remove "/secade-…"      │
│        └── trim($path, "/")                                           │
│   4. if (is_api_request($path))                                       │
│        is_api_request = ($path === "api" || isset($_GET["action"]))   │
│        └──▶ require app/config/api.php  ── API ──▶ exit               │
│   5. else ──▶ TABELA DE ROTAS DE PÁGINA                               │
│        match_route_and_extract_params($path, $routes)                 │
│          ├── ":param" convertido em regex "([^/]+)"                   │
│          ├── correspondência → $_GET["param"] = urldecode(valor)      │
│          └── require_once $matchedFile                                │
│   6. sem correspondência ──▶ render_404_page()  (404)                 │
└──────────────────────────────────────────────────────────────────────┘
```

### 11.2 Tabela de rotas de página (real)

| Rota                    | Ficheiro                                         |
| ----------------------- | ------------------------------------------------ |
| `` (vazio)              | `modules/main/home.php`                          |
| `home`                  | `modules/main/home.php`                          |
| `sobre`                 | `modules/main/about.php`                         |
| `contacto`              | `modules/main/contact.php`                       |
| `servicos`              | `modules/main/serviceCategories.php`             |
| `servicos/:category` ⭐ | `modules/main/services.php` ← parâmetro dinâmico |
| `login`                 | `modules/main/login.php`                         |
| `registo`               | `modules/main/customerRegister.php`              |
| `recuperar-passe`       | `modules/main/recoverPassword.php`               |
| `perfil`                | `modules/main/profile.php`                       |
| `agendamentos`          | `modules/main/appointments.php`                  |
| `agendar`               | `modules/main/booking.php`                       |
| `agendamento-sucesso`   | `modules/main/bookingSuccess.php`                |
| `gestao`                | `modules/backoffice/appointments.php`            |
| `gestao/agendamentos`   | `modules/backoffice/appointments.php`            |
| `gestao/rotas`          | `modules/backoffice/routes.php`                  |
| `gestao/servicos`       | `modules/backoffice/services.php`                |
| `gestao/fiscal`         | `modules/backoffice/fiscal.php`                  |
| `gestao/recibos-verdes` | `modules/backoffice/greenReceipts.php`           |

### 11.3 Despacho da API — `app/config/api.php`

```
$action = $_GET["action"]              // ex.: "admin-service-accept"
$routes = [
   "auth-login"                => ["controller"=>"AuthController",  "method"=>"login",  "http"=>"POST"],
   "booking-create-store"      => ["controller"=>"BookingController","method"=>"createStoreBooking","http"=>"POST"],
   "booking-create-amb"        => ["controller"=>"BookingController","method"=>"createAmbulatoryBooking","http"=>"POST"],
   "admin-service-accept"      => ["controller"=>"ServiceController","method"=>"accept","http"=>"POST"],
   "admin-service-unaccept"    => ["controller"=>"ServiceController","method"=>"unaccept","http"=>"POST"],
   "admin-route-decide"        => ["controller"=>"RotaController",   "method"=>"decideRoute","http"=>"POST"],
   "admin-fiscal-calendar-list"=> ["controller"=>"FiscalController","method"=>"calendar","http"=>"GET"],
   "feedback-list"             => ["controller"=>"FeedbackController","method"=>"publicList","http"=>"GET"],
   …   (37 endpoints no total)
];

┌──────────────────────────────────────────────────────────────────────┐
│ 1. !isset($routes[$action])                                          │
│      ──▶ throw Exception("Invalid endpoint.", 404)                   │
│ 2. REQUEST_METHOD !== $route["http"]                                 │
│      ──▶ throw Exception("Invalid HTTP method.", 405)                │
│ 3. require_once controllers/{Controller}.php                         │
│ 4. class_exists  ──▶ não ──▶ Exception(500)                          │
│ 5. new $controllerName()                                             │
│ 6. method_exists ──▶ não ──▶ Exception("Method not found…", 500)     │
│ 7. $responsedata = $controllerInstance->$methodName()                │
└──────────────────────────────────────────────────────────────────────┘
```

### 11.4 Fluxo interno do Controller

```
ServiceController::accept()
   │
   ├── 1. AUTORIZAÇÃO
   │      ServiceController::requireEmployee()          ← helper privado
   │         └── Session::requireProfileApi(["funcionario"])
   │                ├── Session::requireLoginApi()  → 401 "Sessão não iniciada."
   │                └── perfil não está na lista      → 403 "Sem permissões…"
   │
   ├── 2. DADOS
   │      $data = $this->getRequestData()
   │         ├── $_POST não vazio (form)          ──▶ usa $_POST
   │         └── $_POST vazio                     ──▶ json_decode(php://input)
   │
   ├── 3. DELEGAÇÃO (o Controller NÃO tem regras de negócio)
   │      $result = $service->acceptService($employeeUserId, $data)
   │
   └── 4. RESPOSTA — devolve um ARRAY
          return ["consolidated" => …, "greenReceipt" => […]];
          (o api.php acrescenta "success" e faz o json_encode)
```

### 11.5 Camada de negócio — `BaseService`

```
BaseService::executeTransactional(callable $callback)
   │
   ├── transação ANINHÁVEL (beginTransaction só na mais externa)
   ├── try  { $result = $callback(); commit; return $result; }
   └── catch { rollBack; throw; }

BaseService::validate(array $data, callable $rules)
   │
   └── Validator (utilitário) → lança ValidationException
           └── api.php converte em HTTP 422
               com lista de erros por campo ("errors")
```

### 11.6 Camada de dados — `BaseRepository`

```
BaseRepository::fetch($sql, $params)      → 1 linha  + Mapper  → array camelCase | null
BaseRepository::fetchAll($sql, $params)   → N linhas + Mapper  → array de arrays
BaseRepository::fetchRaw($sql, $params)   → 1 linha  SEM Mapper → chaves snake_case
BaseRepository::fetchAllRaw($sql, $params)→ N linhas SEM Mapper

► fetch / fetchAll       → entidades que existem como tabela própria
► fetchRaw / fetchAllRaw → AGREGAÇÕES, lookups, COUNT/SUM e JOINs enriquecedores

Regra do projeto: Repositories evitam JOIN, EXCETO quando a query pertence
ao próprio contexto do repositório (ex.: findPending em BookingServiceRepository).
```

### 11.7 Tratamento de erros — mapa HTTP (real)

```
EXCEÇÃO                                     HTTP   RESPOSTA JSON
──────────────────────────────────────────────────────────────────────────
sessão ausente                               401   {"success":false,"message":"…"}
perfil sem permissão                         403   {"success":false,"message":"…"}
recurso inexistente                          404   {"success":false,"message":"…"}
método HTTP errado / endpoint inexistente    405   {"success":false,"message":"…"}
                                            404
ValidationException                          422   {"success":false,"message":"…",
                                                     "errors":{"campo":"msg"}}
regra de estado / conflito                   409   {"success":false,"message":"…"}
código fora de 400–599                       400   (normalizado para 400)
```

### 11.8 Formato de resposta (real, do `api.php`)

**Sucesso** — `array_merge(["success" => true], $responsedata)` (chaves no NÍVEL RAIZ):

```json
{
  "success": true,
  "consolidated": false,
  "greenReceipt": { "employeePercentage": 70, "employeeValue": 2.85 }
}
```

**Erro genérico** — chave `message`:

```json
{ "success": false, "message": "O agendamento já está totalmente aceite pelos funcionários." }
```

**Erro de validação** — acrescenta `errors`:

```json
{
  "success": false,
  "message": "Dados inválidos.",
  "errors": { "decision": "O valor tem de ser 'aprovada' ou 'recusada'." }
}
```

> ⚠️ Nota para o teste manual: as respostas **não** estão aninhadas em `data`.
> O `apiClient.js` resolve a *promise* com o **corpo JSON completo**, pelo que
> os componentes leem as chaves na RAIZ do objeto:
>
> ```js
> API.cities.getSupported().then(response => response.cities)        // ✅
> API.booking.createStore(p).then(response => response.bookingId)     // ✅
> // ❌ response.data.cities   ← NÃO existe
> ```
>
> Nos erros, o `jQuery` expõe `xhr.responseJSON.message` e
> `xhr.responseJSON.errors` (padrão usado em `login.js` e `customerRegister.js`).

## 12. MAPA DE TABELAS POR FUNCIONALIDADE

**Total: 24 tabelas** (`secade_beauty`)

```
┌─ NÚCLEO (utilizadores e perfis) ──────────────────────────────────────────┐
│ [utilizador]            base comum (nome, email, password_hash,           │
│                         telemovel, nif, tipo_perfil)                      │
│      ├── [cliente]       1:1  perfil cliente (tipo_perfil='cliente')      │
│      ├── [funcionario]   1:1  perfil funcionário (tipo_perfil=…           │
│      │                        'funcionario')                              │
│      └── ⚠ GESTOR: NÃO existe tabela própria. O gestor vive apenas em    │
│         [utilizador] com tipo_perfil='gestor' — ver `ManagerRepository`,  │
│         que consulta `utilizador` e não uma tabela `gestor`.              │
│ [cliente_morada]        N moradas por cliente                             │
│ ⚠ NÃO existe tabela de categorias de funcionário: as categorias são      │
│   apenas filtros visuais (categoria_profissional completa).               │
└───────────────────────────────────────────────────────────────────────────┘

┌─ CATÁLOGO ────────────────────────────────────────────────────────────────┐
│ [categoria_profissional]  categorias de serviços                           │
│ [servico]                 catálogo — colunas reais:                        │
│      ├── nome                    varchar                                   │
│      ├── descricao               text                                      │
│      ├── categoria_id            FK [categoria_profissional]               │
│      ├── duracao_estimada_minutos int                                      │
│      ├── preco_base              decimal(10,2)                             │
│      ├── requer_espaco_fisico    tinyint(1)  ⭐ (1 = só loja física)       │
│      └── ativo                   tinyint(1)  (criada na migração v3)       │
│      ▸ 35 serviços, todos ativos; apenas 1 com requer_espaco_fisico=1      │
│ [servico_local]           onde pode ser prestado (loja/carrinha)           │
│ [servico_foto]            galeria de imagens                               │
└───────────────────────────────────────────────────────────────────────────┘

┌─ GEOGRAFIA E LOGÍSTICA ───────────────────────────────────────────────────┐
│ [cidade]                 colunas reais: id, nome, distrito                 │
│                          ⚠️ NÃO tem coluna `suportada` — a área de         │
│                             cobertura é imposta pelos DADOS (existem       │
│                             apenas as 10 cidades do distrito de Évora)     │
│ [base_partida]           base de partida da carrinha                       │
│ [matriz_deslocacao]      base × cidade → distancia_km, valor_estimado ⭐   │
└───────────────────────────────────────────────────────────────────────────┘

┌─ AGENDAMENTO (núcleo do MVP) ⭐⭐ ──────────────────────────────────────────┐
│ [agendamento]            cabeçalho                                         │
│      ├── cliente_id              FK [cliente]                              │
│      ├── cliente_morada_id       FK [cliente_morada] (NULL na loja)        │
│      ├── local_prestacao         enum: loja_fisica | carrinha_ambulante    │
│      ├── data_hora_pretendida    datetime                                  │
│      ├── estado_reserva          enum (8 valores — ver secção 10)          │
│      ├── modo_urgencia           tinyint(1)                                │
│      ├── valor_total             decimal(10,2)                             │
│      ├── valor_sinal             decimal(10,2)                             │
│      ├── sinal_pago              tinyint(1)  (simulado, fica 0)            │
│      ├── validado_logistica_loja tinyint(1)                                │
│      └── criado_em               timestamp                                 │
│                                                                            │
│      ⚠️ NÃO existe cidade_id nem rota_ambulante_id nesta tabela.           │
│         A CIDADE é derivada pelo JOIN:                                     │
│             agendamento → cliente_morada → cidade                          │
│         (ver BookingRepository::findAmbulatoryGroups)                      │
│         A ligação à rota é feita por (data_rota, cidade_id) em             │
│         [rota_ambulante] — não por FK.                                     │
│                                                                            │
│      ├── [agendamento_pessoa]   N pessoas (carrinha)                       │
│      └── [agendamento_servico]  N serviços × pessoa                        │
│              ├── agendamento_pessoa_id  FK (NULL na loja)                  │
│              ├── servico_id             FK [servico]                       │
│              ├── funcionario_id         FK (NULL enquanto pendente)        │
│              ├── preco_praticado        decimal(10,2)                      │
│              ├── duracao_minutos        int                                │
│              ├── estado_aceitacao ⭐    enum: pendente | aceite            │
│              ├── aceito_em              datetime                           │
│              ├── percentagem_funcionario_aplicada     decimal(5,2)         │
│              ├── valor_recibo_verde_funcionario       decimal(10,2)        │
│              └── valor_recibo_verde_plataforma        decimal(10,2)        │
└───────────────────────────────────────────────────────────────────────────┘

┌─ OPERAÇÃO ────────────────────────────────────────────────────────────────┐
│ [execucao_agendamento]   registo da execução real (1 por agendamento)       │
│ [feedback_cliente]       avaliação do cliente (1 por execução) ⭐           │
│ [rota_ambulante]         decisão MANUAL da rota ⭐⭐                        │
│      ├── estado_rota: planeada|aprovada|recusada|em_execucao|concluida      │
│      ├── custo_estimado_combustivel, quota_parte_cliente,                   │
│      │   lucro_servicos, lucro_total                                        │
│      └── decidido_por, decidido_em, observacoes_decisao                     │
│ [rota_funcionario]       funcionários atribuídos à rota                     │
└───────────────────────────────────────────────────────────────────────────┘

┌─ FINANCEIRO E FISCAL ⭐ ───────────────────────────────────────────────────┐
│ [config_recibo_verde]    percentagens configuráveis (70/30) + vigência    │
│ [obrigacao_fiscal]       IVA, IRC, Segurança Social, Seguros              │
│ [alerta_fiscal]          30/15/7/3/1 dia + em_atraso (progressivos)       │
│ [transacao_financeira]   movimentos (incl. 'quota_parte_deslocacao')      │
│ [fecho_caixa_diario]     fecho de caixa                                   │
│ [gorjeta]                gorjetas                                         │
└───────────────────────────────────────────────────────────────────────────┘
```

## 13. MATRIZ: PÁGINA ↔ ENDPOINTS ↔ FICHEIROS

### 13.1 Páginas públicas e do cliente

| Página (rota)          | Ficheiro PHP                 | JS componente          | Endpoints                                                                                                |
| ---------------------- | ---------------------------- | ---------------------- | -------------------------------------------------------------------------------------------------------- |
| `/` `home`             | `main/home.php`              | `testimonial.js`       | `feedback-list`                                                                                          |
| `/sobre`               | `main/about.php`             | —                      | —                                                                                                        |
| `/contacto`            | `main/contact.php`           | —                      | —                                                                                                        |
| `/servicos`            | `main/serviceCategories.php` | `serviceCategories.js` | `category-all`                                                                                           |
| `/servicos/:category`  | `main/services.php`          | `services.js`          | `booking-services`, `category-all`                                                                       |
| `/login`               | `main/login.php`             | `login.js`             | `auth-login`                                                                                             |
| `/registo`             | `main/customerRegister.php`  | `customerRegister.js`  | `city-supported`, `auth-register`                                                                        |
| `/recuperar-passe`     | `main/recoverPassword.php`   | —                      | —                                                                                                        |
| `/perfil`              | `main/profile.php`           | `profile.js`           | `customer-profile`, `customer-address-list`, `customer-address-store`, `customer-address-set-principal`, |
|                        |                              |                        | `customer-address-delete`, `city-supported`                                                              |
| `/agendar`             | `main/booking.php`           | `bookingWizard.js`     | `booking-services`, `category-all`, `customer-address-list`, `city-supported`, `booking-availability`,   |
|                        |                              |                        | `booking-otp-request`, `booking-create-store`, `booking-create-amb`                                      |
| `/agendamentos`        | `main/appointments.php`      | `appointments.js`      | `booking-my`, `feedback-my`, `feedback-create`                                                           |
| `/agendamento-sucesso` | `main/bookingSuccess.php`    | —                      | — (usa `?id=` e `?local=`)                                                                               |
| 404                    | `main/404.php`               | —                      | —                                                                                                        |

### 13.2 Páginas do backoffice

| Página (rota)                    | Ficheiro PHP                   | JS componente      | Endpoints                                                                                         |
| -------------------------------- | ------------------------------ | ------------------ | ------------------------------------------------------------------------------------------------- |
| `/gestao` `/gestao/agendamentos` | `backoffice/appointments.php`  | `appointments.js`  | `admin-appointments-list`, `admin-appointment-details`, `admin-appointment-cancel`,               |
|                                  |                                |                    | `admin-appointment-execute`, `city-supported`                                                     |
| `/gestao/rotas` ⭐⭐             | `backoffice/routes.php`        | `routes.js`        | `admin-routes-list`, `admin-route-decide`                                                         |
| `/gestao/servicos` ⭐            | `backoffice/services.php`      | `services.js`      | `admin-service-pending-list`, `admin-service-accepted-list`, `admin-service-accept`,              |
|                                  |                                |                    | `admin-service-unaccept`                                                                          |
| `/gestao/fiscal` ⭐              | `backoffice/fiscal.php`        | `fiscal.js`        | `admin-fiscal-calendar-list`, `admin-fiscal-obligation-create`, `admin-fiscal-obligation-paid`,   |
|                                  |                                |                    | `admin-fiscal-alert-list`, `admin-fiscal-alert-read`                                              |
| `/gestao/recibos-verdes` ⭐      | `backoffice/greenReceipts.php` | `greenReceipts.js` | `admin-green-receipt-config`, `admin-green-receipt-config-save` (+ `admin-green-receipt-simulate` |
|                                  |                                |                    | disponível)                                                                                       |

### 13.3 Mapa de ficheiros por camada (listas reais)

```
PÁGINAS          modules/main/  (13)   404 · about · appointments · booking ·
                                      bookingSuccess · contact · customerRegister ·
                                      home · login · profile · recoverPassword ·
                                      serviceCategories · services
                 modules/backoffice/ (5) appointments · fiscal · greenReceipts ·
                                      routes · services

COMPONENTES PHP  modules/main/components/ (15) about · bookingWizard · contact ·
                 customerRegister · hero · login · mainFooter · menuUser ·
                 notFound · pageHeader · recoverPassword · serviceCategories ·
                 services · team · testimonial
                 modules/main/includes/       header · navbar · footer
                 modules/backoffice/includes/ boHeader · boNavbar · boFooter

JS COMPONENTES   modules/main/js/components/ (12) about · appointments ·
                 bookingWizard · contact · customerRegister · hero · login ·
                 menuUser · profile · serviceCategories · services · testimonial
                 modules/backoffice/js/components/ (5) appointments · fiscal ·
                 greenReceipts · routes · services
                 modules/backoffice/js/bo.utils.js

JS UTILS         modules/common/js/
                   api/         apiClient.js · api.js
                   utils/       form.utils.js · general.utils.js ·
                                addressAutocomplete.js
                   validators/  booking · customer · employee · login ·
                                manager · user  (.validator.js)
                   lib-our/     jq-preloader (overlays + skeletons)

CONTROLLERS (12) app/controllers/  AdminController · AuthController ·
                 BaseController · BookingController · CategoryController ·
                 CityController · CustomerAddressController · CustomerController ·
                 FeedbackController · FiscalController · RotaController ·
                 ServiceController

SERVICES (18)    app/services/  AuthService · BaseService · BookingService ·
                 CategoryService · CityService · CustomerAddressService ·
                 CustomerService · EmployeeService · ExecutionService ·
                 FeedbackService · FiscalService · GreenReceiptService ·
                 ManagerService · OTPService · RotaService ·
                 ServiceAcceptanceService · ServiceService · UserService

REPOSITORIES(18) app/repositories/ BaseRepository · BookingPersonRepository ·
                 BookingRepository · BookingServiceRepository ·
                 CategoryRepository · CityRepository ·
                 CustomerAddressRepository · CustomerRepository ·
                 EmployeeRepository · ExecutionRepository ·
                 FeedbackRepository · FiscalAlertRepository ·
                 FiscalObligationRepository · GreenReceiptConfigRepository ·
                 ManagerRepository · RotaRepository · ServiceRepository ·
                 UserRepository

MAPPERS (16)     app/mappers/  BaseMapper · BookingMapper ·
                 BookingServiceMapper · CategoryMapper · CityMapper ·
                 CustomerAddressMapper · CustomerMapper · EmployeeMapper ·
                 ExecutionMapper · FeedbackMapper · FiscalAlertMapper ·
                 FiscalObligationMapper · GreenReceiptConfigMapper ·
                 RotaMapper · ServiceMapper · UserMapper

CONFIG/UTILS     app/config/  config.php · connection.php · api.php
                 app/utils/   Session.php · Validator.php ·
                              ValidationException.php

ENTRADA          index.php  (front controller)
BD               DataBase.sql · DataBase_v2.sql · database_migration_v3.sql ·
                 database_seed.sql
TESTES (4)       tests/  functional_test.php · http_test.php ·
                 js_syntax_check.php · asset_test.php
```

## 14. REGRAS DE NEGÓCIO ↔ CÓDIGO

| #   | Regra de negócio                      | Constante / método                                          | Valor                                          | Ficheiro                         |
| --- | ------------------------------------- | ----------------------------------------------------------- | ---------------------------------------------- | -------------------------------- |
| 1   | Abertura da loja                      | `STORE_OPEN_HOUR`                                           | `9` (09:00)                                    | `BookingService`                 |
| 2   | Fecho da loja                         | `STORE_CLOSE_HOUR`                                          | `19` (19:00)                                   | `BookingService`                 |
| 3   | Grelha de horários                    | `SLOT_STEP_MINUTES`                                         | `30`                                           | `BookingService`                 |
| 4   | Sinal (loja)                          | `DEPOSIT_PERCENTAGE`                                        | `10` (%)                                       | `BookingService`                 |
| 5   | Dias permitidos                       | `validateBookingDate`                                       | Ter(2) → Sáb(6)                                | `BookingService`                 |
| 6   | Data futura                           | `validateBookingDate`                                       | `strtotime < time()` → 422                     | `BookingService`                 |
| 7   | Base de partida                       | `BASE_PARTIDA_ID`                                           | `1` (Évora)                                    | `RotaService`                    |
| 8   | Custo fixo operacional                | `FIXED_OPERATIONAL_COST`                                    | `50.0` €                                       | `RotaService`                    |
| 9   | **Indicador visual** de rentabilidade | `REFERENCE_PROFITABILITY`                                   | `50.0` € ⭐ **não decide**                     | `RotaService`                    |
| 10  | % funcionário (recibos verdes)        | `DEFAULT_EMPLOYEE_PERCENTAGE`                               | `70.0`                                         | `GreenReceiptService`            |
| 11  | % plataforma (recibos verdes)         | `DEFAULT_PLATFORM_PERCENTAGE`                               | `30.0`                                         | `GreenReceiptService`            |
| 12  | Alertas fiscais                       | `ALERT_OFFSETS`                                             | `30, 15, 7, 3, 1` dias                         | `FiscalService`                  |
| 13  | Alerta em atraso                      | `resolveAlertLevel`                                         | `daysLeft < 0` → `em_atraso`                   | `FiscalService`                  |
| 14  | OTP: nº de dígitos                    | `OTPService::request`                                       | `6`                                            | `OTPService`                     |
| 15  | OTP: validade                         | `OTPService::request`                                       | `600` s (10 min)                               | `OTPService`                     |
| 16  | OTP: uso único                        | `OTPService::verify`                                        | consumido no sucesso                           | `OTPService`                     |
| 17  | Feedback: 1 por agendamento           | `createFeedback` (verificação inline com `findByBooking`)   | 409                                            | `FeedbackService`                |
| 18  | Feedback: só após execução            | `createFeedback` (verificação inline do `status`)           | 409                                            | `FeedbackService`                |
| 19  | Feedback: só o dono                   | `createFeedback` (comparação `customerId`)                  | 403                                            | `FeedbackService`                |
| 19b | Feedback: execução registada          | `createFeedback` (`findByBooking` em `executionRepository`) | 409                                            | `FeedbackService`                |
| 20  | Área de cobertura                     | `CityService::findSupportedCities` → **todas** as linhas de | 10 cidades (a cobertura é imposta pelos dados) | `CityService` / `CityRepository` |
|     |                                       | `[cidade]`                                                  |                                                |                                  |
| 21  | Carrinha bloqueada                    | `requer_espaco_fisico = 1`                                  | serviço só na loja                             | `services.js`                    |
| 22  | Conflito de janela                    | `countByDateWindow`                                         | 409                                            | `BookingService`                 |
| 23  | Consolidação                          | `consolidateIfComplete` → `countPendingByBooking === 0`     | muda o estado ⭐                               | `ServiceAcceptanceService`       |
| 24  | Estado consolidado                    | `CONSOLIDATED_STATE`                                        | `'totalmente_aceite_funcionarios'`             | `ServiceAcceptanceService`       |
| 25  | Bloqueio pós-consolidação             | `unacceptService` (verifica `CONSOLIDATED_STATE`)           | 409                                            | `ServiceAcceptanceService`       |
| 26  | Duração total do agendamento          | `totalDurationByBooking`                                    | `SUM(duracao_minutos)`                         | `BookingServiceRepository`       |
| 27  | Soma das % = 100                      | `saveConfig`                                                | 422                                            | `GreenReceiptService`            |
| 28  | Sinal dispensado (carrinha)           | `createAmbulatoryBooking`                                   | `valor_sinal = 0`                              | `BookingService`                 |

### 14.1 Fórmulas explícitas

```
VALOR DO AGENDAMENTO
  loja     : valor_total = Σ preço dos serviços selecionados
             valor_sinal = valor_total × 10%            (10%, simulado)

  carrinha : valor_total = Σ (preço do serviço POR PESSOA)
             valor_sinal = 0                            (dispensado)
             duracao     = Σ (duração POR PESSOA, sem duplicar
                              dentro da mesma pessoa)

RENTABILIDADE DA ROTA  (RotaService::findRouteSummaries)
  custoTotal     = combustivel + 50,00
  rentabilidade  = receita − custoTotal
  meetsReference = rentabilidade >= 50,00     ← SÓ INDICADOR VISUAL

RECIBOS VERDES  (ServiceAcceptanceService::acceptService)
  valor_recibo_verde_funcionario = preço × (pct_func / 100)
  valor_recibo_verde_plataforma  = preço × (pct_plat / 100)

ALERTA FISCAL  (FiscalService::resolveAlertLevel)
  daysLeft = floor((prazo − hoje) / 86400)
  daysLeft < 0   → em_atraso
  senão o MENOR limiar que satisfaz daysLeft <= limiar
        (iteração do mais urgente para o mais largo)
```

### 14.2 Regras revogadas (NÃO implementadas de propósito)

| Regra (v1.0 da documentação)                 | Estado          | Substituto                           |
| -------------------------------------------- | --------------- | ------------------------------------ |
| Algoritmo automático com limiar de **100 €** | ❌ **REVOGADO** | `decideRoute` — decisão **manual**   |
| Bloqueio automático de rota não rentável     | ❌ **REVOGADO** | aviso visual `meetsReference`        |
| `RotaService::validateRoutes()`              | ❌ removido     | —                                    |
| `admin-validate-routes` (endpoint)           | ❌ removido     | `admin-route-decide`                 |
| Desconto/`valor_pago_sinal`                  | ❌ removido     | `valor_sinal` simulado               |
| CRON para validação de rotas                 | ❌ removido     | decisão manual + alertas *on-demand* |

## 15. SEGURANÇA: MATRIZ DE PERMISSÕES

### 15.1 Mecanismos

```
┌─ NÍVEL DE API (JSON) ───────────────────────────────────────────────────┐
│ Session::requireLoginApi()                                             │
│    └── !isLoggedIn()  ──▶ throw Exception("Sessão não iniciada.", 401) │
│                                                                        │
│ Session::requireProfileApi(["gestor"] | ["funcionario"] | ["cliente"]) │
│    ├── requireLoginApi()                       ──▶ 401                 │
│    └── perfil ∉ lista  ──▶ Exception("Sem permissões…", 403)           │
│                                                                        │
│ BaseController::requireCustomer()  = requireProfileApi(["cliente"])    │
│    └── devolve (int)Session::user()["id"]                              │
└────────────────────────────────────────────────────────────────────────┘

┌─ NÍVEL DE PÁGINA (HTML) ───────────────────────────────────────────────┐
│ Session::requireLogin()                                                 │
│    └── !isLoggedIn()  ──▶ header("Location: {BASE_URL}/login"); exit;   │
│                                                                         │
│ (o backoffice valida adicionalmente isManager()/isEmployee() e          │
│  redireciona em vez de devolver JSON)                                   │
└────────────────────────────────────────────────────────────────────────┘

┌─ NÍVEL DE DADOS ───────────────────────────────────────────────────────┐
│ • TODAS as queries usam Prepared Statements (PDO)                       │
│ • O cliente só recebe os SEUS registos: findByCustomer($customerId)     │
│ • O funcionário só opera em nome próprio: requireEmployee() → id        │
│ • O feedback valida a propriedade: customerId == booking.customerId     │
│   (verificação inline em FeedbackService::createFeedback → 403)         │
│ • password_hash(PASSWORD_BCRYPT) + password_verify()                    │
│ • hash_equals() no OTP (proteção contra timing attacks)                 │
└────────────────────────────────────────────────────────────────────────┘
```

### 15.2 Matriz de acesso por endpoint

| Endpoint                                                              | Sem sessão | cliente | funcionario | gestor |
| --------------------------------------------------------------------- | ---------- | ------- | ----------- | ------ |
| `category-all` · `city-supported`                                     | ✅         | ✅      | ✅          | ✅     |
| `booking-services`                                                    | ✅         | ✅      | ✅          | ✅     |
| `feedback-list`                                                       | ✅         | ✅      | ✅          | ✅     |
| `auth-login` · `auth-register`                                        | ✅         | —       | —           | —      |
| `auth-logout`                                                         | —          | ✅      | ✅          | ✅     |
| `booking-availability`                                                | ⚠️ *       | ✅      | ✅          | ✅     |
| `customer-profile` · `customer-address-*`                             | 401        | ✅      | 403         | 403    |
| `booking-otp-request` · `booking-create-store` · `booking-create-amb` | 401        | ✅      | 403         | 403    |
| `booking-my`                                                          | 401        | ✅      | 403         | 403    |
| `feedback-my` · `feedback-create`                                     | 401        | ✅      | 403         | 403    |
| `admin-service-pending-list` · `-accepted-list`                       | 401        | 403     | ✅          | 403    |
| `admin-service-accept` · `-unaccept`                                  | 401        | 403     | ✅          | 403    |
| `admin-appointments-list` · `-details` · `-cancel` · `-execute`       | 401        | 403     | 403         | ✅     |
| `admin-routes-list` · `admin-route-decide`                            | 401        | 403     | 403         | ✅     |
| `admin-fiscal-*` · `admin-green-receipt-*`                            | 401        | 403     | 403         | ✅     |

\* `booking-availability` é público na rota (não chama `requireCustomer`), mas é
**inofensivo**: devolve apenas grelha de horários ocupados/livres, sem dados pessoais.

> ⚠️ **Incoerência intencional a conhecer:** na **página** `/gestao/servicos` o gestor
> tem acesso (supervisão), mas nas **APIs** `admin-service-*` o gestor recebe **403**
> (só `funcionario` pode aceitar/desfazer). O gestor vê a lista, mas não consegue
> aceitar em nome de terceiros — comportamento pretendido.

### 15.3 Testes automáticos que cobrem segurança

| Ficheiro                    | Testes       | Cobertura                                                                          |
| --------------------------- | ------------ | ---------------------------------------------------------------------------------- |
| `tests/http_test.php`       | 119 pass     | 401/403 por perfil, 404/405/409/422, sessões reais, **registo e login end-to-end** |
| `tests/functional_test.php` | 105 pass     | regras de negócio + transações + algoritmo + **registo (server-side)**             |
| `tests/asset_test.php`      | 65 pass      | assets servidos, **contrato de nomes do formulário de registo**                    |
| `tests/js_syntax_check.php` | 15 ficheiros | sintaxe JS (`node --check` ou equivalente)                                         |

### 15.4 Páginas protegidas por perfil (valores reais)

| Página                   | Guarda                                                   | Comportamento                           |
| ------------------------ | -------------------------------------------------------- | --------------------------------------- |
| `/perfil`                | `Session::requireLogin()`                                | redireciona para `BASE_URL/login`       |
| `/agendar`               | `Session::requireLogin(BASE_URL . "/login")`             | redireciona para `/login`               |
| `/agendamentos`          | `Session::requireLogin()`                                | redireciona para `/login`               |
| `/gestao/agendamentos`   | `requireLogin()` + `isManager()`                         | não-gestor → `BASE_URL + "/"`           |
| `/gestao/rotas`          | `requireLogin()` + `isManager()`                         | não-gestor → `BASE_URL + "/"`           |
| `/gestao/fiscal`         | `requireLogin()` + `isManager()`                         | não-gestor → `BASE_URL + "/"`           |
| `/gestao/recibos-verdes` | `requireLogin()` + `isManager()`                         | não-gestor → `BASE_URL + "/"`           |
| `/gestao/servicos`       | `requireLogin()` + (`isEmployee()` **OU** `isManager()`) | ⚠️ **o gestor TEM acesso** (supervisão) |

> ⚠️ Correção: `/gestao/servicos` **não** bloqueia o gestor —
> a condição real é `if (!Session::isEmployee() && !Session::isManager())`.
> O gestor pode consultar a página de aceitação em modo de supervisão.

### 15.5 Menu do backoffice (boNavbar.php — valores reais)

```
NAVBAR do backoffice  ($boIsEmployee ? [...] : [...])

┌─ perfil == 'funcionario' ────────────────────────────────────────────┐
│  • Serviços        /gestao/servicos        (bi-list-check)           │
│  • Agendamentos    /gestao/agendamentos    (bi-calendar-check)       │
│  Home (logo) →     /gestao/servicos                                  │
└──────────────────────────────────────────────────────────────────────┘

┌─ perfil == 'gestor' (ou qualquer outro não-funcionário) ─────────────┐
│  • Agendamentos        /gestao/agendamentos   (bi-calendar-check)    │
│  • Rotas               /gestao/rotas          (bi-signpost-split)    │
│  • Calendário Fiscal   /gestao/fiscal         (bi-receipt-cutoff)    │
│  • Recibos Verdes      /gestao/recibos-verdes (bi-cash-stack)        │
│  Home (logo) →         /gestao/agendamentos                          │
└──────────────────────────────────────────────────────────────────────┘
```

> ⚠️ Correção: o funcionário vê **Serviços + Agendamentos** (não só Serviços).
> O badge da navbar mostra `$boIsManager ? "Gestor" : "Funcionário"`.

---

## CONCLUSÃO — O QUE ESTE MAPA PERMITE VERIFICAR

```
✅ Qualquer funcionalidade da UI pode ser rastreada até à tabela da BD
   (secção 13 cruza página → endpoint → ficheiros; secção 12 mapeia tabelas)

✅ O caráter MANUAL da decisão de rotas está demonstrado na secção 7
   (o indicador de 50 € nunca altera estados)

✅ O ciclo completo da carrinha está mapeado nas secções 5 → 6 → 7 → 10
   (criação → aceitação/consolidação → decisão → execução → feedback)

✅ Todas as constantes de negócio estão listadas na secção 14,
   prontas a confrontar com o comportamento observado nos testes manuais

✅ As regras REVOGADAS estão explicitamente marcadas (secção 14.2)
   para evitar reportar como bug algo que é intencional
```
