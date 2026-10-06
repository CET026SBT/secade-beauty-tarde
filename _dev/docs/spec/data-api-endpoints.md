# SECADE BEAUTY — API / ENDPOINTS (§19)
**Especificação — ficheiro de domínio** · branch **`agent-workspace`**
**Âmbito:** §19 — catálogo de endpoints por perfil, matriz de códigos HTTP e o que **falta** implementar.
**Router:** `especificacao_mvp.md` (mapa `§ → ficheiro`) · **Modelo de dados:** `data-api.md` §17 ·
**Arquitetura:** `data-api.md` §18.

## 19. API / ENDPOINTS

**Padrão:** `?action=<dominio>-<acao>` · **49 endpoints** registados em `app/config/api.php`.
Resposta de sucesso: `{"success":true, …chaves na raiz}`; erro: `{"success":false,"message":"…"}`
(+ `errors` por campo em **422**).

### 19.1 Públicos e de cliente
| Endpoint                         | Método | Descrição                                                                           | Acesso         |
| :------------------------------- | :----- | :---------------------------------------------------------------------------------- | :------------- |
| `auth-register`                  | POST   | Registo (cliente público; gestor pode criar perfis)                                 | público        |
| `auth-login` / `auth-logout`     | POST   | Gestão de sessão                                                                    | público / auth |
| `city-supported`                 | GET    | Listagem das 10 cidades                                                             | público        |
| `category-all`                   | GET    | Listagem das 3 categorias profissionais                                             | público        |
| `booking-services`               | GET    | Catálogo de serviços ativos (inclui `photoUrl` da foto principal)                   | público        |
| `service-photos`                 | GET    | Galeria de fotos de um serviço (modal de detalhes) — F3.1                           | público        |
| `feedback-list`                  | GET    | Feedback público (testemunhos)                                                      | público        |
| `site-stats`                     | GET    | Contadores do site (serviços, áreas, cidades, equipa, clientes, avaliações)         | público        |
| `booking-availability`           | GET    | Consulta de slots (data + duração + canal)                                          | público*       |
| `booking-otp-request`            | POST   | Pedido de OTP (devolve código no ecrã)                                              | cliente        |
| `booking-create-store`           | POST   | Cria agendamento de loja                                                            | cliente        |
| `booking-create-amb`             | POST   | Cria agendamento de ambulatório (com validação OTP)                                 | cliente        |
| `booking-my`                     | GET    | Consulta de agendamentos do cliente                                                 | cliente        |
| `customer-booking-cancel`        | POST   | Cancelamento pelo cliente, **sem penalização** (RF-12 · §24.6)                      | cliente        |
| `customer-booking-update`        | POST   | Cliente reprograma o agendamento (serviços/pessoas, morada, data/hora) — F9b · §4.5 | cliente        |
| `customer-profile-update`        | POST   | Cliente edita nome/telemóvel/NIF — F9                                               | cliente        |
| `user-photo-upload`              | POST   | Foto do próprio utilizador (`uploads/users/<id>`) — F9 · §4.6                       | autenticado    |
| `user-photo-remove`              | POST   | Remover a própria foto — F9                                                         | autenticado    |
| `customer-alerts-list`           | GET    | Avisos/lembretes do cliente — F9 · C-07                                             | cliente        |
| `customer-alerts-summary`        | GET    | Contador de avisos do cliente (badge do menu) — F9                                  | cliente        |
| `customer-alerts-read`           | POST   | Marcar os avisos do cliente como lidos — F9                                         | cliente        |
| `customer-profile`               | GET    | Dados de perfil e moradas do cliente                                                | cliente        |
| `customer-address-list`          | GET    | Listagem de moradas                                                                 | cliente        |
| `customer-address-store`         | POST   | Registo de nova morada                                                              | cliente        |
| `customer-address-set-principal` | POST   | Definição de morada principal                                                       | cliente        |
| `customer-address-delete`        | POST   | Remoção de morada                                                                   | cliente        |
| `feedback-my`                    | GET    | Estado do feedback do cliente                                                       | cliente        |
| `feedback-create`                | POST   | Submissão de nova avaliação                                                         | cliente        |

\* `booking-availability` não exige sessão, mas devolve apenas grelha de horários (sem dados pessoais).

> **`site-stats` — regra do número publicado:** cada contador vale o **valor contado na BD**; se a contagem
> for **0** publica-se o **valor documental** (`SITE_STATS_FALLBACK` em `app/config/config.php`) e, para as
> chaves marcadas em **`SITE_STATS_DOCUMENTAL`**, o documental mantém-se **mesmo com contagem > 0** — é o que
> evita publicar um número sabidamente incompleto (§24.9 · §24.11). Nenhum componente inventa números nem
> chama a API diretamente: o preenchimento é do `siteStats.utils.js`.
### 19.2 Backoffice — funcionário
| Endpoint                      | Método | Descrição                                                            | Acesso               |
| :---------------------------- | :----- | :------------------------------------------------------------------- | :------------------- |
| `admin-service-pending-list`  | GET    | Serviços de ambulatório por alocar + funcionários (F4)               | gestor + funcionário |
| `admin-service-accepted-list` | GET    | Serviços alocados + totais (gestor: todos; funcionário: os seus)     | gestor + funcionário |
| `admin-service-accept`        | POST   | Alocar serviço (gestor escolhe `employeeId`) / aceitar (funcionário) | gestor + funcionário |
| `admin-service-unaccept`      | POST   | Desfazer/trocar (bloqueia 409 se consolidado) — F4/C-08              | gestor + funcionário |

### 19.3 Backoffice — gestor
| Endpoint                          | Método | Descrição                                                               |
| :-------------------------------- | :----- | :---------------------------------------------------------------------- |
| `admin-appointments-list`         | GET    | Agendamentos (com filtros e paginação)                                  |
| `admin-appointment-details`       | GET    | Detalhe por serviço/funcionário, progresso e registo de execução        |
| `admin-appointment-cancel`        | POST   | Cancelar agendamento (retorna erro 409 se em estados terminais)         |
| `admin-appointment-execute`       | POST   | Registrar execução do agendamento (operação idempotente)                |
| `admin-routes-list`               | GET    | Rotas por dia e cidade com custos, lucros e indicador `meetsReference`  |
| `admin-route-decide`              | POST   | Aprovar ou recusar rota de forma manual                                 |
| `admin-fiscal-calendar-list`      | GET    | Calendário fiscal com geração on-demand de alertas                      |
| `admin-fiscal-alert-list`         | GET    | Listagem de alertas fiscais progressivos                                |
| `admin-fiscal-obligation-create`  | POST   | Criar nova obrigação fiscal (retorna erro 422 se dados inválidos)       |
| `admin-fiscal-obligation-paid`    | POST   | Marcar obrigação como paga (validações 404 e 409)                       |
| `admin-fiscal-alert-read`         | POST   | Marcar alertas fiscais como lidos                                       |
| `admin-green-receipt-config`      | GET    | Consultar configuração de recibos verdes em vigor                       |
| `admin-green-receipt-config-save` | POST   | Guardar configuração de recibos verdes (erro 422 se a soma não for 100) |
| `admin-green-receipt-simulate`    | GET    | Simular distribuição de valores de recibos verdes para um dado montante |

**Fase 6 — entrada do backoffice, fornecedores e comissões (28/09/2026):**

| Endpoint                       | Método | Descrição                                                                        | Acesso               |
| :----------------------------- | :----- | :------------------------------------------------------------------------------- | :------------------- |
| `admin-dashboard-summary`      | GET    | Painel do gestor: KPIs (dia e 7 dias) e séries dos gráficos (RF-77 · §24.7)      | gestor               |
| `admin-alert-summary`          | GET    | Contador de avisos por tratar (sino, RF-81)                                      | gestor + funcionário |
| `admin-alert-list`             | GET    | Avisos do perfil autenticado, agrupados por origem (fiscal, operação, rotas)     | gestor + funcionário |
| `admin-alert-read`             | POST   | Marcar os alertas fiscais como lidos (leitura **global** — §22.2 · C-03)         | gestor               |
| `admin-employee-agenda-list`   | GET    | Agenda do funcionário num mês (`month=YYYY-MM`), só rotas `confirmado` (RN-33)   | funcionário          |
| `admin-supplier-list`          | GET    | Fornecedores com pesquisa/estado + resumo (total, ativos, sem NIF) (RF-85)       | gestor               |
| `admin-supplier-store`         | POST   | Criar fornecedor (422 com `errors` por campo)                                    | gestor               |
| `admin-supplier-update`        | POST   | Editar fornecedor (404 se não existir; `active` ausente **não** reativa)         | gestor               |
| `admin-supplier-set-active`    | POST   | Ativar/desativar (não há eliminação física)                                      | gestor               |
| `admin-commission-list`        | GET    | Comissões gravadas na aceitação, por mês: gestor vê todos, funcionário o próprio | gestor + funcionário |
| `admin-service-photo-list`     | GET    | Fotos de um serviço do catálogo (F3.1)                                           | gestor               |
| `admin-service-photo-upload`   | POST   | Carregar foto (multipart; JPG/PNG/WEBP até 2 MB) — F3.1                          | gestor               |
| `admin-service-photo-featured` | POST   | Definir a foto principal (imagem do card) — F3.1                                 | gestor               |
| `admin-service-photo-remove`   | POST   | Remover foto (linha + ficheiro) — F3.1                                           | gestor               |
| `admin-employee-list`          | GET    | Funcionários + indicadores + percentagens por omissão — F7                       | gestor               |
| `admin-employee-create`        | POST   | Criar funcionário (RV nasce sem salário e com a % do contrato) — F7              | gestor               |
| `admin-employee-update`        | POST   | Editar funcionário — F7                                                          | gestor               |
| `admin-employee-impact`        | GET    | Impacto da desativação (serviços por executar) — F7 · §7.5                       | gestor               |
| `admin-employee-deactivate`    | POST   | Desativar (soft delete; devolve serviços a pendente) — F7 · §7.5                 | gestor               |
| `admin-employee-activate`      | POST   | Reativar funcionário — F7                                                        | gestor               |
| `admin-employee-photo-upload`  | POST   | Foto do funcionário (`uploads/users/<id>`) — F7 · §4.6                           | gestor               |
| `admin-employee-photo-remove`  | POST   | Remover foto do funcionário — F7                                                 | gestor               |

> **`admin-routes-list` (§24.7 item 7):** cada linha passou a expor `bookingIds` e `bookingsDetail`
> (agendamentos qualificados com serviços aceites) para o **diálogo de detalhes** da rota, e
> `decideBlockReason` quando há serviços por aceitar (RN-31). `admin-route-decide` aceita
> `bookingIds` opcional — a decisão aplica-se ao **conjunto incluído** (RN-34).

### 19.4 Matriz de códigos HTTP
| Situação                               | Código HTTP       |
| :------------------------------------- | :---------------- |
| Sessão ausente / não autenticado       | **401**           |
| Perfil sem permissão (não autorizado)  | **403**           |
| Recurso inexistente                    | **404**           |
| Endpoint inexistente / método errado   | **404** / **405** |
| Conflito / violação de regra de estado | **409**           |
| Erro de validação (com lista `errors`) | **422**           |

### 19.5 Endpoints **previstos e não implementados** (futuro — §25)
- `client-alert-*` (lembretes ao cliente — §24.6)
- `admin-deposit-config` (configuração do sinal — §24.5)
- `admin-payment-*` (cobrança dos 90 % + método — §24.5)
- `admin-accounting-*` (importação dos ficheiros e gráficos contabilísticos — §17.9 · RF-75/76/79)
- `admin-payroll-*` (RH: líquido a pagar calculado — §24.9 · RF-82)
- `admin-fiscal-*` das 8 famílias (SAF-T, DMR, retenções, IES/DA — §24.10 · RF-83)
- `servico/:slug` (página de detalhes de serviço com carousel — §24.2 · RF-07)

> **Já implementados (deixaram esta lista em 28/09/2026):** `customer-booking-cancel` (§24.6) ·
> `admin-supplier-*` (§25.1) · `admin-dashboard-summary`, `admin-employee-agenda-list`,
> `admin-alert-summary|list|read` (§24.7) · `admin-commission-list` (§25.5).
