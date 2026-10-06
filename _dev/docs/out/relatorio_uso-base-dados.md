# USO DA BASE DE DADOS PELA APLICAÇÃO — tabela a tabela

<!-- md-wrap-tables:max=220 -->

**Data:** 2026-10-03 · **Base:** `secade_beauty` · **Fonte:** código de `app/` + `modules/` (2026-10-03)
**Natureza:** apoio (**não normativo**) · **Autoridade:** `especificacao_mvp.md` + `_dev/docs/spec/`
**Artefacto:** `_dev/docs/out/relatorio_uso-base-dados.md`

> **O que responde:** para cada uma das 25 tabelas, **onde** a plataforma escreve (INSERT/UPDATE/DELETE) e
> **onde** lê — e, com o mesmo rigor, **quais não são tocadas**. Toda a linha sai de ficheiro lido
> (caminho + método); nada é inferido.

## 1. COMO LER

| Marca       | Significado                                                                                        |
| :---------- | :------------------------------------------------------------------------------------------------- |
| **Escreve** | Existe `INSERT`/`UPDATE`/`DELETE` na tabela, a partir de um ecrã real da aplicação.                |
| **Lê**      | Existe `SELECT` (direto, JOIN ou agregação) que alimenta um ecrã/indicador.                        |
| **Só lê**   | Tabela de **referência**: só tem leitura no código; os dados entram pelo dump.                     |
| **NÃO usa** | **Zero referências** no código da aplicação (`app/**`, `modules/**`). Continua com dados ou vazia. |

**Cadeia típica de uma escrita** (arquitetura em camadas, §18):
`ecrã (`.php`) → componente JS → endpoint `?action=` → Controller → Service → Repository → tabela`.
Nas tabelas abaixo, a coluna «onde» indica o **ecrã + endpoint + método do Repository**.

## 2. RESUMO — TRÊS CATEGORIAS

| Categoria                           | Tabelas | Quais                                                                                                                                                             |
| :---------------------------------- | :------ | :---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **A — A aplicação escreve e lê**    | 14      | `utilizador`, `cliente`, `funcionario`, `cliente_morada`, `agendamento`, `agendamento_pessoa`, `agendamento_servico`, `execucao_agendamento`, `feedback_cliente`, |
|                                     |         | `alerta_fiscal`, `obrigacao_fiscal`, `config_recibo_verde`, `rota_ambulante`, `fornecedor`                                                                        |
| **B — Só lê (dados de referência)** | 5       | `servico`, `categoria_profissional`, `cidade`, `base_partida`, `matriz_deslocacao`                                                                                |
| **C — NÃO usa (intocadas)**         | 6       | `servico_foto`, `servico_local`, `rota_funcionario`, `transacao_financeira`, `fecho_caixa_diario`, `gorjeta`                                                      |

> **Leitura rápida:** a plataforma vive de **14 tabelas**; 5 são catálogo/apoio que só se consulta;
> **6 estão criadas mas nenhuma linha de código lhes toca** (§5).

## 3. MAPA POR TABELA (categoria A — escreve e lê)

| Tabela                 | Onde ESCREVE (INSERT/UPDATE/DELETE)                                                            | Onde LÊ                                                                                       |
| :--------------------- | :--------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------------------------- |
| `utilizador`           | **INSERT** `/registo` → `auth-register` → `AuthService::register` →                            | `auth-login` (`find` por email) · `/perfil` → `customer-profile` · JOINs em comissões, rotas, |
|                        | (Customer/Employee/Manager)Service → `UserService::createUser` → `UserRepository::create`. Sem | agenda, agendamentos e feedback.                                                              |
|                        | UPDATE nem DELETE.                                                                             |                                                                                               |
| `cliente`              | **INSERT** `/registo` → `auth-register` → `CustomerService::createCustomer` →                  | `site-stats` e dashboard (`countAll`) · JOINs do nome/telemóvel em agendamentos, rotas e      |
|                        | `CustomerRepository::create`. Sem UPDATE nem DELETE.                                           | feedback.                                                                                     |
| `funcionario`          | **INSERT** `auth-register` com `profileType=funcionario` → `EmployeeService::createEmployee` → | `site-stats` (`countActive`) · `find` (contas) · JOINs em `agendamento_servico`, comissões e  |
|                        | `EmployeeRepository::create`. Sem UPDATE nem DELETE.                                           | agenda.                                                                                       |
| `cliente_morada`       | **INSERT** `/perfil` e wizard → `customer-address-store` (`create`) · **UPDATE**               | `/perfil` (`customer-address-list`) · wizard de agendamento · JOINs em agendamentos, rotas e  |
|                        | `customer-address-set-principal` (`updatePrincipal`) · **DELETE** `customer-address-delete`    | agenda.                                                                                       |
|                        | (`delete`).                                                                                    |                                                                                               |
| `agendamento`          | **INSERT** wizard → `booking-create-store` / `booking-create-amb` (`create`) · **UPDATE**      | `/agendamentos` → `booking-my` · BO agendamentos → `admin-appointments-list`/`-details` ·     |
|                        | `customer-booking-cancel`, `admin-appointment-cancel`, `admin-appointment-execute`             | dashboard · agrupamento de rotas.                                                             |
|                        | (`updateEstado`) e `admin-route-decide` (`updateEstadoMany`).                                  |                                                                                               |
| `agendamento_pessoa`   | **INSERT** wizard ambulatório → `BookingPersonRepository::create`. Sem UPDATE.                 | Detalhe do agendamento no BO · agenda do funcionário.                                         |
| `agendamento_servico`  | **INSERT** wizard (`BookingServiceRepository::create`) · **UPDATE** BO serviços →              | Listas Por aceitar/Aceites, detalhe, comissões, agenda e dashboard.                           |
|                        | `admin-service-accept` / `admin-service-unaccept` (`accept` / `unaccept`).                     |                                                                                               |
| `execucao_agendamento` | **INSERT** BO agendamentos → `admin-appointment-execute` (`ExecutionRepository::create`) ·     | Detalhe do agendamento (estado da execução) · base do feedback.                               |
|                        | **UPDATE** `updateEstado`.                                                                     |                                                                                               |
| `feedback_cliente`     | **INSERT** `/agendamentos` → `feedback-create` (`FeedbackRepository::create`).                 | Home (testemunhos) → `feedback-list` · `/agendamentos` → `feedback-my` · `site-stats`         |
|                        |                                                                                                | (`countAll`).                                                                                 |
| `alerta_fiscal`        | **INSERT IGNORE** gerado no calendário fiscal → `admin-fiscal-calendar-list`                   | Sino do backoffice (`admin-alert-list`/`-summary`) · página fiscal.                           |
|                        | (`FiscalAlertRepository::createIfAbsent`) · **UPDATE** `admin-alert-read` /                    |                                                                                               |
|                        | `admin-fiscal-alert-read` (`markAllAsRead`).                                                   |                                                                                               |
| `obrigacao_fiscal`     | **INSERT** `/gestao/fiscal` → `admin-fiscal-obligation-create`                                 | `admin-fiscal-calendar-list` · dashboard (`sumFiscalByType`) · `findOverdue` (gera alertas em |
|                        | (`FiscalObligationRepository::create`) · **UPDATE** `admin-fiscal-obligation-paid`             | atraso).                                                                                      |
|                        | (`markAsPaid`).                                                                                |                                                                                               |
| `config_recibo_verde`  | **INSERT** `/gestao/recibos-verdes` → `admin-green-receipt-config-save`                        | `admin-green-receipt-config` · `findActive` usado no cálculo da aceitação                     |
|                        | (`GreenReceiptConfigRepository::create`).                                                      | (`ServiceAcceptanceService`).                                                                 |
| `rota_ambulante`       | **INSERT** `/gestao/rotas` → `admin-route-decide` (`RotaRepository::create`) · **UPDATE**      | `admin-routes-list` · dashboard (`countRoutesAwaitingDecision`).                              |
|                        | `updateDecision`.                                                                              |                                                                                               |
| `fornecedor`           | **INSERT** `/gestao/fornecedores` → `admin-supplier-store` (`SupplierRepository::create`) ·    | `admin-supplier-list` (`find`/`search`/`summary`) · dashboard (`countActiveSuppliers`).       |
|                        | **UPDATE** `admin-supplier-update` (`update`) e `admin-supplier-set-active` (`setActive`).     |                                                                                               |

> **Sem DELETE de entidades de negócio:** não há eliminação de agendamentos, clientes, fornecedores nem
> obrigações — só de moradas (`cliente_morada`). O resto é **desativado** (`ativo=0`) ou muda de estado.

## 4. MAPA POR TABELA (categoria B — só lê)

| Tabela                   | Onde LÊ (todas as escritas de dados entram pelo `DataBase.sql`)                                                                                                      |
| :----------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `servico`                | Catálogo público → `booking-services` · categorias → `category-all` · `site-stats`/dashboard (`countActive`, `findMinPrice`) · JOINs em todas as listas de serviços. |
| `categoria_profissional` | `category-all` · JOINs de agrupamento no catálogo e nas listas de serviços.                                                                                          |
| `cidade`                 | `city-supported` (wizard, rotas, perfil) · JOINs em moradas, agendamentos e rotas.                                                                                   |
| `base_partida`           | JOINs na listagem de rotas (`RotaRepository::find`/`listWithDetails`).                                                                                               |
| `matriz_deslocacao`      | `RotaRepository::getFuelCost` — custo de combustível da rota.                                                                                                        |

> **Porque é que isto importa:** como são **só de leitura**, qualquer alteração ao catálogo, às cidades ou à
> matriz de custos faz-se **por SQL na BD** — não há ecrã de administração para elas.

## 5. TABELAS QUE A APLICAÇÃO **NÃO USA** (categoria C)

> Confirmado por varrimento de `app/**` e `modules/**`: **0 referências** a cada nome (nem PHP, nem JS).
> Ficam com os dados que o dump lhes deu (ou vazias) e nenhum ecrã as mostra ou altera.

| Tabela                 | O que é (no modelo)                                     | Estado hoje        | Para que serviria                                       |
| :--------------------- | :------------------------------------------------------ | :----------------- | :------------------------------------------------------ |
| `servico_foto`         | Galeria de imagens do serviço                           | **Vazia**, sem uso | Página de detalhe por serviço com carousel (§24.2).     |
| `servico_local`        | Disponibilidade/preço de um serviço por canal           | **Vazia**, sem uso | Diferenciar preço loja vs. carrinha (não implementado). |
| `rota_funcionario`     | Equipa (N:N) alocada a uma rota                         | **Vazia**, sem uso | Alocar funcionários à rota da carrinha.                 |
| `transacao_financeira` | Movimentos financeiros (sinal, restante, quota, método) | **Vazia**, sem uso | Registo real de pagamentos (hoje são **simulados**).    |
| `fecho_caixa_diario`   | Auditoria de caixa por dia/funcionário                  | **Vazia**, sem uso | Fecho de caixa (sem UI no MVP — §22.1).                 |
| `gorjeta`              | Gorjetas registadas                                     | **Vazia**, sem uso | Registar gorjetas (sem UI no MVP — §22.1).              |

> ⚠️ **Consequência importante:** como `transacao_financeira` nunca é escrita, os **pagamentos e o sinal não
> ficam registados** em tabela nenhuma — vivem só nos campos `agendamento.sinal_pago` / `agendamento.valor_sinal`.
> As receitas do painel do gestor saem de `agendamento.valor_total`, não do livro de movimentos.

## 6. COLUNAS CRIADAS MAS NUNCA ESCRITAS (ou nunca usadas)

| Coluna                                      | Situação                                              | Efeito prático                                              |
| :------------------------------------------ | :---------------------------------------------------- | :---------------------------------------------------------- |
| `agendamento.modo_urgencia`                 | **0 referências** no código                           | Coluna morta — sempre no valor por omissão (`0`).           |
| `agendamento.validado_logistica_loja`       | **Lida** (SELECT/detalhe), **nunca escrita**          | Fica sempre `0`; nenhum fluxo valida a logística da loja.   |
| `cliente.telemovel_validado_otp`            | Escrita **só a `0`** no registo; **nunca atualizada** | Fica sempre `0` (o OTP é validado em sessão, não persiste). |
| `transacao_financeira.estado_offline`       | **0 referências**                                     | Coluna morta (na tabela intocada).                          |
| `transacao_financeira.recibo_manual_numero` | **0 referências**                                     | Coluna morta (na tabela intocada).                          |

## 7. NOTA SOBRE OS TESTES

As suites de `_dev/tests/` escrevem/lêem na BD por **SQL direto** (não pela API) para montar e limpar cenários
(`functional_test.php`, `http_test.php`). **Isto não é uso de produto**: as 6 tabelas intocadas continuam a ter
**0 referências também nos testes** — a conclusão de §5 mantém-se.

## 8. MÉTODO (para reproduzir)

```powershell
# escritas por tabela
Get-ChildItem -Recurse -File -Include '*.php' 'app' |
  Select-String -Pattern '(INSERT INTO|INSERT IGNORE INTO|UPDATE|DELETE FROM)\s+`?<tabela>\b'
# leituras por tabela
Get-ChildItem -Recurse -File -Include '*.php' 'app' |
  Select-String -Pattern '\b(FROM|JOIN)\s+`?<tabela>\b'
# uso no front-end
Get-ChildItem -Recurse -File -Include '*.js' 'modules' | Select-String -Pattern '<tabela>'
```
