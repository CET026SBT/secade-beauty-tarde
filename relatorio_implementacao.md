# RELATÓRIO DE IMPLEMENTAÇÃO — MVP SECADE BEAUTY
> 📌 **CONSOLIDAÇÃO DOCUMENTAL (21/09/2026):** este ficheiro foi **centralizado em
> `especificacao_mvp.md`** (documento-mestre). Mantém-se como **referência detalhada**, mas em caso
> de contradição **prevalece o documento-mestre**. Mapa documental: `especificacao_mvp.md` §29.2.

**Data:** 21/09/2026 | **Âmbito:** implementação do MVP + correção de defeitos + testes

> Pasta `admin` ignorada conforme instrução. Toda a entrega foi feita em `app/`, `modules/` e raiz.

---

## 1. LEVANTAMENTO INICIAL

Foi lida toda a documentação `.md` e a estrutura de código existente. Conclusões:

**Já existia e funcionava:** MVC custom (Controller → Service → Repository → Mapper), autenticação, registo de clientes, catálogo em BD (35 serviços, 10 cidades, 3 categorias), `BookingService`/`BookingRepository`/`BookingPersonRepository`/`OTPService`, rotas/API base, preloader, `form.utils.js` e validators.

**Faltava implementar (MVP):**
1. Página e JS do catálogo de serviços (`services.js` estava **vazio**)
2. Wizard de agendamento (loja + carrinha) — frontend inexistente
3. Módulo de Rotas (Mapper/Repository/Service/Controller) + algoritmo de viabilidade
4. Backoffice (agendamentos + rotas)
5. APIs de perfil/moradas do cliente; páginas `perfil` e `agendamentos` eram placeholders
6. Página de sucesso do agendamento
7. Colunas/seed em falta na base de dados

---

## 2. DEFEITOS CRÍTICOS CORRIGIDOS (impediam o MVP)

| # | Ficheiro | Defeito | Impacto | Correção |
|---|---|---|---|---|
| 1 | `app/services/OTPService.php` | `validate(int, string): bool` incompatível com `BaseService::validate(array, callable): void` | **Fatal error em qualquer endpoint de agendamento** (contrato de assinatura violado) | Renomeado para `verify()` e atualizada a chamada em `BookingService` |
| 2 | `app/services/BookingService.php` | `validateStoreOpeningHours()` comparava *epoch* (data+hora) com *segundos desde a meia-noite* | **100% dos agendamentos de loja rejeitados** ("horário das 09:00 às 19:00") | Cálculo por *offset* face à meia-noite da data escolhida |
| 3 | `app/utils/Session.php` | `createLoginSession()` lia chaves da BD (`nome`, `tipo_perfil`) mas recebia chaves mapeadas (`name`, `profileType`) | Sessão sem perfil, *warnings* PHP que **corrompiam o JSON do login**; 403 em todas as APIs de cliente e impossibilidade de entrar no backoffice | Leitura das chaves mapeadas com *fallback* para as chaves originais |
| 4 | `app/config/connection.php` | Espaço antes de `<?php` | Saída de conteúdo antes dos *headers*: `session_start()`/`header()` falhavam | Removido o espaço inicial |
| 5 | `app/repositories/CustomerAddressRepository.php` | `SELECT cm.obs_localizacao` — coluna inexistente | Erro SQL ao listar/guardar moradas | Coluna removida da query |
| 6 | `app/services/CustomerService.php` | Lia `$customer["phoneVerified"]` (o mapper devolve `isMobileValidated`) | Campo sempre `false` no perfil | Chave corrigida |
| 7 | `app/services/BookingService.php` | `resolveServicesForPeople()` deduplicava serviços entre pessoas | Valor/duração do ambulatório subestimados (serviços partilhados não contabilizados) | Contabilização por pessoa (modelo "1 registo por pessoa+serviço") |
| 8 | `app/repositories/BookingRepository.php` | `countByDateWindow()` ignorava `pendente_validacao_logistica_loja` | Duplo agendamento no mesmo *slot* de loja | Estado incluído na verificação de conflito |
| 9 | `ServiceRepository::findActive()` / BD | `s.ativo` usado no código mas **ausente** no schema | Catálogo/`booking-services` quebrava (coluna desconhecida) | Coluna adicionada (`database_migration_v3.sql` + `DataBase.sql`/`DataBase_v2.sql`) |
| 10 | BD `cliente.morada` (`NOT NULL`, legado v1) | `CustomerRepository::create()` não envia `morada` | Erro `Field 'morada' doesn't have a default value` no registo de clientes | Coluna tornada opcional (moradas residem em `cliente_morada` desde a v3.0) |

**Notas de arquitetura decorrentes das convenções do projeto:**
- `BaseRepository` ganhou `fetchRaw()`/`fetchAllRaw()` para consultas **agregadas/lookups**, que não devem ser transformadas pelo *mapper* da entidade (o *mapper* descartava `custo_estimado_combustivel` e as chaves agregadas da viabilidade).
- `BookingMapper` passou a mapear também os campos enriquecidos da listagem do backoffice (`cliente_nome`, `cidade_nome`, `cidade_id`).

---

## 3. IMPLEMENTADO (por camada)

### Backend — novo
| Camada | Ficheiros |
|---|---|
| Mapper | `RotaMapper`, `ExecutionMapper`, `FiscalObligationMapper`, `FiscalAlertMapper`, `FeedbackMapper`, `GreenReceiptConfigMapper` |
| Repository | `RotaRepository`, `ExecutionRepository`, `FiscalObligationRepository`, `FiscalAlertRepository`, `FeedbackRepository` |
| Service | `RotaService`, `ServiceAcceptanceService` (Fase 3), `GreenReceiptService` (Fase 3), `FiscalService` (Fase 4), `ExecutionService`, `FeedbackService` (Fase 2) |
| Controller | `RotaController`, `AdminController`, `CustomerController`, `CustomerAddressController`, `ServiceController` (Fase 3), `FiscalController` (Fase 4), `FeedbackController` (Fase 2) |

### Backend — estendido
- `BookingRepository`: listagem do backoffice (filtros data/local/estado/cidade + paginação), agrupamento de pendentes por dia+cidade, `updateEstadoMany()`
- `BookingService`: `listBookings()`, `cancelBooking()` (com guardas 404/409), `listActiveServices()` devolve `services`
- `api.php`: 9 novos endpoints (`customer-*`, `admin-*`)
- `index.php`: novas rotas `perfil`, `agendamentos`, `agendar`, `agendamento-sucesso`, `gestao/agendamentos`, `gestao/rotas`

### Frontend — Main (cliente)
- `modules/main/services.php` + `components/services.php` (novo) — catálogo com filtros, modal e *skeleton* via preloader
- `modules/main/js/components/services.js` (era vazio) — filtros por categoria/preço/duração/pesquisa, modal, integração com `/agendar?services=`
- `modules/main/booking.php` + `components/bookingWizard.php` — wizard com os passos `services`, `channel`, `address`, `otp`, `datetime`, `professional` (loja), `policy` (carrinha) e `summary`
- `modules/main/js/components/bookingWizard.js` — fluxos por canal (5 vs 7 passos), disponibilidade dinâmica de *slots*, OTP, construtor de pessoas, resumo e submissão
- `modules/common/js/validators/booking.validator.js` (novo)
- `modules/main/bookingSuccess.php` (novo)
- `modules/main/profile.php` + `js/components/profile.js` — perfil e CRUD de moradas
- `modules/main/appointments.php` + `js/components/appointments.js` — histórico com filtros
- `modules/common/js/api/api.js` — namespaces `booking`, `customer`, `admin`
- `modules/common/js/utils/general.utils.js` — `formatCurrency`, `formatDuration`, `formatDateTime`
- Navbar com entrada "Agendar"; CSS do catálogo/wizard/backoffice

### Frontend — Backoffice (gestor + funcionário)
- `modules/backoffice/includes/{boHeader,boNavbar,boFooter}.php` + `js/bo.js`, `js/bo.utils.js`
  - **menu dinâmico por perfil**: funcionário (Serviços, Agendamentos) vs. gestor (Agendamentos, Rotas, Fiscal, Recibos Verdes)
- `modules/backoffice/appointments.php` + `js/components/appointments.js` (tabela, filtros, paginação, **detalhe por serviço/funcionário**, execução, cancelamento)
- `modules/backoffice/routes.php` + `js/components/routes.js` (**decisão manual** aprovar/recusar, indicador de 50 €)
- `modules/backoffice/services.php` + `js/components/services.js` (**Fase 3**: aceitação individual, desfazer, recibo verde simulado)
- `modules/backoffice/fiscal.php` + `js/components/fiscal.js` (**Fase 4**: calendário, alertas progressivos, marcar pago)
- `modules/backoffice/greenReceipts.php` + `js/components/greenReceipts.js` (**Fase 3**: configuração do simulador)

### Base de dados
- `database_migration_v3.sql` (novo, **idempotente**): coluna `servico.ativo`; `cliente.morada` opcional; **remoção de `funcionario_categoria`** (passo 3)
- `database_seed.sql` (novo, **idempotente**): gestor, funcionário, cliente + morada em Évora (passwords *bcrypt*)
- `DataBase.sql` e `DataBase_v2.sql`: alinhados com a coluna `ativo`; `DataBase_v2.sql` deixou de criar `funcionario_categoria` (**24 tabelas**)

---

## 4. RESOLUÇÃO DO CONFLITO: ROTAS — ALGORITMO vs. DECISÃO MANUAL

Existiam dois requisitos contraditórios na documentação:

| Fonte | Regra |
|---|---|
| `.clinerules`, `CARRINHA_SPEC.md`, `fluxo_funcionalidades.md`, `plano_desenvolvimento.md` (v1.0) | **Algoritmo automático**: `rentabilidade ≥ 100 €` → aprova sozinho; `< 100 €` → cancela sozinho (RN05) |
| `planeamento_geral.md` v3.0 + `relatorio_alteracoes.md` (documento de **prevalência**) | **Decisão MANUAL e LIVRE** do gestor; **50 € apenas indicador visual**, sem limiar de bloqueio |

**Resolução aplicada (Fase 4, conforme decidido):** prevalece o `planeamento_geral.md` v3.0.

- ❌ **Removido:** limiar automático de 100 € (`RotaService::validateRoutes()` e o endpoint `admin-validate-routes`).
- ✅ **Implementado:** `RotaService::decideRoute()` + endpoint `POST ?action=admin-route-decide` — o gestor aprova/recusa livremente uma rota (dia+cidade).
  - `aprovada` → rota `aprovada` + agendamentos `confirmado`
  - `recusada` → rota `recusada` + agendamentos `cancelado`
  - Auditoria em `rota_ambulante`: `decidido_por`, `decidido_em`, `observacoes_decisao`
- ✅ O valor de **50 €** passou a ser o `REFERENCE_PROFITABILITY`: devolvido como `meetsReference` e mostrado na UI
  como ícone/badge de apoio à decisão — **nunca altera estados**.
- ✅ Documentos v1.0 atualizados com marca de **REVOGADO** para não induzir em erro em futuras leituras.

**Testes que provam a inversão:** a suite funcional aprova uma cidade **abaixo** da referência de 50 € (e verifica
que o agendamento fica `confirmado`) e recusa uma cidade **acima** da referência (ficando `cancelado`) — demonstrando
que o valor é puramente indicativo.

---

## 5. FASES 2, 3 e 4 — O QUE FOI IMPLEMENTADO

### Fase 3 — Backoffice do Funcionário (`/gestao/servicos`)
- **Aceitação individual** serviço a serviço (`ServiceAcceptanceService::acceptService`), com categorias apenas como filtros visuais.
- **Desfazer** (`unacceptService`) permitido **enquanto o agendamento não estiver consolidado** (409 depois disso).
- **Troca**: aceitar um serviço já aceite por outro funcionário transfere-o (`isSwap` na resposta).
- **Consolidação**: ao aceitar o último serviço, o agendamento passa a `totalmente_aceite_funcionarios`.
- **Bloqueio da janela temporal**: a consolidação verifica sobreposição com agendamentos já consolidados/confirmados e falha com 409 se houver conflito.
- **Simulador de Recibos Verdes**: percentagem em vigor aplicada no momento da aceitação, com valores gravados em `agendamento_servico` (70/30 por omissão).

### Fase 4 — Backoffice do Gestor
- **Decisão manual de rotas** (acima).
- **Detalhe por serviço/funcionário** (`admin-appointment-details`): estado de aceitação de cada serviço, funcionário atribuído, recibos verdes e progresso (`X aceite(s) / Y pendente(s)`).
- **Registo de execução** (`admin-appointment-execute`): cria `execucao_agendamento` e passa o agendamento a `executado` (idempotente).
- **Calendário Fiscal** (`/gestao/fiscal`): IVA, IRC, Segurança Social e Seguros; estado pendente/pago; marcar como pago (com validação 404/409).
- **Alertas progressivos** (`alerta_fiscal`): 30, 15, 7, 3 e 1 dia antes do prazo + **alerta diário em atraso**. Geração **on-demand** e **idempotente** (chave única obrigação+tipo+data).
- **Configuração do simulador** (`/gestao/recibos-verdes`): percentagens com vigência por data; validação de soma = 100.

### Fase 2 — Feedback do Cliente
- Avaliação (1–5 estrelas + comentário) **apenas** após serviço executado e **uma única vez** por agendamento.
- Listagem **pública** (`feedback-list`) que alimenta os testemunhos da página inicial, com média de satisfação (fallback para testemunhos estáticos quando não há feedback).

### Segurança por perfil (verificada em testes)
| Perfil | Acesso |
|---|---|
| Cliente | Main + `customer-*`, `booking-*`, `feedback-create/my`. **401** sem sessão, **403** nas APIs `admin-*`; **redirect** nas páginas `/gestao/*` |
| Funcionário | `admin-service-*` (só em nome próprio); **403** em rotas e fiscal |
| Gestor | Todas as `admin-*` (rotas, fiscal, recibos verdes, agendamentos) |

### Remoção da relação funcionário ↔ categoria (v3.0)

A tabela `funcionario_categoria` (N:N entre `funcionario` e `categoria_profissional`) foi **removida**
por não ter qualquer consumidor na aplicação. Justificação verificada no código:

| Verificação | Resultado |
|---|---|
| Alguma query/repositório **lê** a tabela? | ❌ Nenhum. `EmployeeRepository` só tinha escrita (`createCategory`/`deleteCategories`) |
| O filtro do backoffice usa a relação? | ❌ Usa `categoria_profissional` **completa** (`CategoryRepository::find()`) |
| A filtragem de serviços pendentes usa a relação? | ❌ Usa `servico.categoria_id` (`BookingServiceRepository::findPending`) |
| A relação restringe a aceitação? | ❌ Não — e o planeamento v3.0 **proíbe** (categorias = filtros visuais) |
| O caminho de escrita tem UI? | ❌ Nenhum ecrã envia `profileType` = `funcionario`/`gestor`; não existe página de funcionários |
| Alguma FK aponta para a tabela? | ❌ Nenhuma (era folha) |

**Alterações:**

- `DataBase_v2.sql` — tabela removida (**24 tabelas** agora); a `servico.ativo` e a ausência do legado
  `cliente.morada` mantêm-se.
- `database_seed.sql` — removido o `INSERT IGNORE` correspondente.
- `database_migration_v3.sql` — **passo 3** novo: `DROP TABLE IF EXISTS funcionario_categoria`
  (idempotente; converge BDs v1/v2 existentes sem reimportar).
- `EmployeeRepository` — removidos `createCategory()` e `deleteCategories()`.
- `EmployeeService` — removido o ciclo sobre `categories` e a dependência de `CategoryRepository`.
- `ServiceAcceptanceService` — removida a dependência `EmployeeRepository` (estava declarada e
  instanciada mas **nunca usada**).

**Ficheiros históricos deixados intactos (deliberado):** `DataBase.sql` (dump v1) e
`DataBase_backup_pre_v2.sql` (arquivo de segurança) ainda contêm a tabela — são artefactos
históricos, marcados no `README.md` como **não usar**, e o passo 3 da migração v3 remove-a se
algum deles for restaurado.

**Testes de regressão** (`tests/functional_test.php` §11): confirma que a tabela já não existe, que
os métodos órfãos desapareceram, que `EmployeeService` perdeu a dependência de categorias e que o
filtro visual continua alimentado pelas 3 categorias de `categoria_profissional`.

### Registo de clientes — defeitos corrigidos e convenção de nomes

**Defeitos encontrados ao escrever os testes end-to-end** (nenhum estava coberto por testes):

| # | Defeito | Efeito | Correção |
|---|---|---|---|
| 1 | `UserService::validateInput` invertia a unicidade do email (`Validator::custom()` falha quando o callable devolve `true`) | **Rejeitava emails novos** e aceitaria duplicados | `fn($email) => !empty($this->userRepository->find(null, $email))` |
| 2 | O formulário enviava `termosCondicoes`; a API valida `termsAccepted` | Registo falhava sempre (422) | Campo renomeado para `termsAccepted` |
| 3 | Nomes divergentes: `nome`/`telemovel` vs `name`/`phone`; `morada`/`numPorta`/`andarBloco`/`codigoPostal`/`cidade` vs `street`/`doorNumber`/`floor`/`zipCode`/`cityName`. Faltavam os campos `zipCode` e `cityName` no formulário | Registo falhava sempre | Formulário, validadores e `AddressAutocomplete` alinhados com os mappers |
| 4 | `UserRepository::create` lia `nome`/`telemovel`/`tipoPerfil` enquanto os Services escreviam `name`/`phone`/`profileType` | Gravação inconsistente (o perfil caía sempre em `cliente`) | Chaves unificadas com o nome dos mappers |

**Defeitos colaterais corrigidos no mesmo passo:**

- `customerValidators(supportedCities)` era chamado com um **array**, mas a função desestrutura um
  **objeto** → a lista de cidades suportadas nunca era preenchida (e o validador comparava com `undefined`).
- `updateCitiesTooltip()` fazia `join()` sobre **objetos** (`{id, name, district}`) e instanciava um
  `bootstrap.Tooltip` sem verificar se o elemento existe (erro de consola no arranque da página).
- A primeira morada do cliente era gravada com `principal = 0`; o wizard pré-seleciona a morada por
  `isMain` → o registo passa a marcá-la como principal.
- `CustomerAddressRepository` aceitava a chave `streetRaw`, que nenhum formulário envia — removida.

**Convenção adotada (verificada por teste):**

> Os atributos `name` dos campos de formulário refletem, de forma transparente, as chaves que a API
> espera — as mesmas que os mappers do servidor produzem (`name`, `phone`, `street`, `doorNumber`,
> `floor`, `zipCode`, `cityName`, `termsAccepted`, `profileType`). O `asset_test.php` falha se
> reaparecer uma chave legada em português nestes formulários.

---

## 6. TESTES EXECUTADOS

Ficheiros em `tests/` (executar com `php tests/<ficheiro>.php`):

| Suite | Cobertura | Resultado |
|---|---|---|
| `functional_test.php` | Camadas Service/Repository: catálogo, disponibilidade, conflitos de slot, loja/ambulatório, OTP, **decisão manual de rotas (aprovar/recusar, 422/409)**, backoffice, perfil/moradas, **Fase 3** (aceitação, desfazer, consolidação, bloqueio, recibos verdes), **Fase 4** (fiscal, alertas progressivos, idempotência, pagamento), **Fase 2** (execução, feedback, duplicados), **registo de cliente server-side** (transação, unicidade, morada principal, rollback) e **remoção da relação funcionário ↔ categoria** | **105/105 PASS** |
| `http_test.php` | Stack real (Apache + routing + sessões): login dos 3 perfis, páginas main, APIs (200/401/403/404/405/422), fluxo end-to-end carrinha+OTP+decisão manual, **Fase 3/4** (aceitação, fiscal, recibos verdes, feedback), páginas de backoffice por perfil, **registo e login end-to-end** (§8–§12: registo + validações + login dos 3 perfis + área reservada + logout) | **119/119 PASS** |
| `js_syntax_check.php` | Estrutura/sintaxe dos 15 ficheiros JS criados/alterados (sem Node disponível) | **OK** |
| `asset_test.php` | Assets servidos (200 + conteúdo), injeção de scripts por página via `register_script()` em 8 páginas e **contrato de nomes do formulário de registo** (campos e validadores alinhados com os mappers) | **65/65 PASS** |

**Total: 289 verificações automatizadas, todas a passar.**

Também validados: `php -l` em **todos os ficheiros PHP** (0 erros) e as migrações/seed aplicadas em MySQL 8.4.3 sem erros.

> As duas suites com dependência de BD limpam os seus próprios dados de teste no início de cada execução,
> pelo que podem ser corridas repetidamente sem resultados flutuantes.

---

## 7. COMO REPRODUZIR A VALIDAÇÃO

```powershell
# Base de dados (MySQL do Laragon)
mysql -u root --default-character-set=utf8mb4 -e "source c:/laragon/www/secade-beauty-tarde/database_migration_v3.sql;"
mysql -u root --default-character-set=utf8mb4 -e "source c:/laragon/www/secade-beauty-tarde/database_seed.sql;"

# Testes
php tests/js_syntax_check.php
php tests/functional_test.php
php tests/http_test.php      # requer Apache+MySQL ativos
php tests/asset_test.php     # requer Apache ativo
```

---

## 8. LIMITAÇÕES / SIMPLIFICAÇÕES (conforme âmbito académico)

- OTP, pagamentos/sinal e notificações (SMS/e-mail) são **simulados** — sem gateway real
- Alertas fiscais são gerados **on-demand** (ao abrir o calendário), sem CRON
- A seleção de profissional no wizard de loja é informativa: a equipa é atribuída pela aceitação/consolidação, pois a BD não associa funcionários a *slots*
- `cliente.morada` (coluna legada v1) mantém-se por compatibilidade; as moradas operacionais estão em `cliente_morada`
- O backoffice vive em `modules/backoffice/` (e não na pasta raiz `admin/` prevista no planeamento v3.0), por instrução explícita de não tocar em `/admin`
- `transacao_financeira` e `fechamento de caixa` existem na BD mas não têm UI no MVP (o sinal é apenas simulado na criação)

---

**Status:** ✅ Todas as fases (1 a 5) implementadas, defeitos críticos corrigidos e validado por 289 verificações automatizadas.
