# PLANEAMENTO GERAL — SECADE BEAUTY
> 📌 **CONSOLIDAÇÃO DOCUMENTAL (21/09/2026):** este ficheiro foi **centralizado em
> `especificacao_mvp.md`** (documento-mestre). Mantém-se como **referência detalhada**, mas em caso
> de contradição **prevalece o documento-mestre**. Mapa documental: `especificacao_mvp.md` §29.2.

**Versão:** 3.0 (Fusão definitiva) | **Data:** documento de referência
**Estado:** Documento de planeamento e requisitos — não contém código.

> ⚠️ **PREVALÊNCIA:** As **Regras de Ouro** (Secção 2) prevalecem sobre qualquer planeamento ou contradição anterior (fluxo_funcionalidades.md, CARRINHA_SPEC.md, plano_desenvolvimento.md, README.md). Onde haja conflito, vale o que está aqui.

---

## ÍNDICE
1. Visão Geral e Stack
2. Regras de Ouro e Definições Finais (PREVALECE)
3. Arquitetura Main vs. Backoffice Admin
4. Domínio: Utilizadores e Perfis
5. Catálogo de Serviços e Categorias
6. Agendamento em Loja
7. Agendamento em Ambulatório (Domicílio/Carrinha)
8. Dinâmica dos Funcionários (Backoffice)
9. Simulador de Recibos Verdes
10. Módulo do Gestor e Rotas
11. Calendário Fiscal
12. Modelo de Dados (mapeamento actual + extensões necessárias)
13. Arquitetura Técnica e Pastas (incluindo futura pasta `admin`)
14. Endpoints / API previstos
15. Estados e Máquina de Estados
16. Fases e Roadmap
17. Simplificações Académicas
18. Critérios de Aceitação

---

## 1. VISÃO GERAL E STACK

**Secade Beauty** é um sistema de agendamentos para um negócio híbrido de beleza:
- **Loja Física** em Évora (Terça a Sábado, 09:00–19:00)
- **Serviço Ambulatório** (domicílio / carrinha itinerante) em cidades do Alentejo

**Público-alvo:** Idosos com mobilidade reduzida, famílias rurais, clientes que procuram serviços de beleza e bem-estar acessíveis.

**Stack (já definida, mantém-se):**
- Backend: PHP puro (7.4+) + PDO + MySQL 8.4.3, MVC custom sem frameworks
- Frontend: HTML5, CSS3, JavaScript vanilla (ES6+), Bootstrap 5, jQuery
- Ambiente: Laragon (Apache + MySQL + PHP), HeidiSQL, VS Code
- Charset UTF-8, timezone Europe/Lisbon
- Código em inglês; base de dados em português; prepared statements obrigatórios

**Base de dados actual (17+ tabelas):** `agendamento`, `agendamento_pessoa`, `agendamento_servico`, `alerta_fiscal`, `base_partida`, `categoria_profissional`, `cidade`, `cliente`, `cliente_morada`, `config_recibo_verde`, `execucao_agendamento`, `fecho_caixa_diario`, `feedback_cliente`, `funcionario`, `gorjeta`, `matriz_deslocacao`, `obrigacao_fiscal`, `rota_ambulante`, `rota_funcionario`, `servico`, `servico_foto`, `servico_local`, `transacao_financeira`, `utilizador`.

> **NOTA (v3.0):** a tabela `funcionario_categoria` (N:N funcionário ↔ categoria) foi **removida** —
> não tinha consumidor (ver `relatorio_implementacao.md` §"Remoção da relação funcionário ↔ categoria").
> Os 24 nomes acima são a lista completa.

---

## 2. REGRAS DE OURO E DEFINIÇÕES FINAIS (PREVALECE SOBRE TUDO)

### A. Separação de Arquitetura (Main vs. Backoffice Admin futuro na pasta `admin`)
- **Site Principal (Main):** Exclusivo para **clientes**. Navegação, consulta de serviços/categorias, visualização de feedback e criação de agendamentos. O cliente **não tem acesso ao backoffice**.
- **Backoffice (Admin — futuro na pasta `admin`):** Área restrita onde serão alojadas as ferramentas de gestão para:
  - **Gestores:** Calendário Fiscal centralizado, gestão/confirmação de rotas, gestão de agendamentos.
  - **Funcionários:** Listagem, aceitação e troca de serviços.

### B. Agendamento: Loja vs. Ambulatório

**B.1 — Agendamentos em Loja:**
- Exigem espaço físico.
- O cliente seleciona: **loja, data, hora (Terça a Sábado, 09:00–19:00)** e serviços pretendidos.
- **Sem morada** e **sem estrutura por pessoa**.
- **Sem aceitação por funcionários:** os serviços são assumidos **automaticamente como aceites no momento da criação**, ficando apenas **pendentes de validação logística da loja**.

**B.2 — Agendamentos em Ambulatório (Domicílio/Carrinha):**
- O cliente seleciona a **cidade** (derivada da morada), **data, hora** e **serviços**.
- **Estrutura por Pessoa e Serviços Partilhados:** os serviços são agrupados **obrigatoriamente por pessoa** (Pessoa 1, Pessoa 2, ...). Este agrupamento funciona como **agrupador logístico e visual**, permitindo estimar com precisão a **duração total do slot no terreno**.
- **Nota importante:** Várias pessoas podem usufruir dos **mesmos serviços** (ou de serviços diferentes) num único agendamento de ambulatório.

### C. Dinâmica dos Funcionários (no Backoffice)
- **Categorias como filtros:** as categorias (Cabeleireiro, Barbearia, Estética) funcionam **estritamente como agrupadores visuais/filtros**. Os funcionários têm **liberdade total** para ver e aceitar qualquer serviço, independentemente da categoria.
- **Aceitação individual:** o funcionário aceita serviços de ambulatório de forma **individual** (serviço a serviço, dentro do agrupamento por pessoa).
- **Troca/desfazer aceitação:** o funcionário pode **desfazer a aceitação ou trocar** serviços que já tinha aceitado, **desde que o agendamento global a que pertencem ainda não esteja totalmente aceite** por completo.
- **Consolidação ("Totalmente Aceite por Funcionários"):** assim que o **último serviço** de um agendamento é aceite, o agendamento fica com o estado **"Totalmente Aceite por Funcionários"**, consolidando-se e **bloqueando agendamentos concorrentes** na mesma janela temporal.
- **Simulador de Recibos Verdes:** integrado no **momento da aceitação** de serviços de ambulatório, com **percentagem configurável** (ex.: 70% funcionário / 30% plataforma).

### D. Módulo do Gestor e Rotas (no Backoffice)
- **Gestão de Agendamentos:** listagem **por defeito filtrada** pelos agendamentos de **ambulatório totalmente aceites pelos funcionários**, com opção de ver os **pendentes** e consultar os **detalhes** (quais funcionários associados a cada serviço).
- **Decisão de Rotas:** agrupamento por **dia e cidade**. O sistema mostra **custos de combustível, quota-parte do cliente, lucro dos serviços e lucro total**.
- **Sem limiar automático:** o valor de **50€** deixa de ser um bloqueio automático e passa a ser **apenas um indicador visual de referência**. A decisão de aprovar/recusar a rota é sempre **manual e livre** por parte do gestor.
- **Calendário Fiscal:** centralizado no backoffice, cobrindo **IVA, IRC, Segurança Social e Seguros**, com **alertas automáticos progressivos (30, 15, 7, 3, 1 dia e diários em atraso)**.

---

## 3. ARQUITETURA MAIN vs. BACKOFFICE ADMIN

### 3.1 Site Principal (`modules/main/`)
Acesso: público + clientes autenticados.

| Área | Conteúdo |
|---|---|
| Home / Sobre / Contacto | Páginas institucionais (já existentes) |
| Catálogo de serviços | Grid de serviços com filtros por categoria, preço, duração; badge "Apenas Loja" quando `requer_espaco_fisico=1` |
| Feedback | Visualização de avaliações de clientes |
| Login / Registo | Autenticação e wizard de registo (já implementados) |
| Perfil | Dados pessoais + gestão de moradas |
| Wizard Loja | Agendamento em loja (ver secção 6) |
| Wizard Ambulatório | Agendamento domicílio/carrinha (ver secção 7) |
| Meus Agendamentos | Histórico e estado das marcações do cliente |

**Regra:** nenhum fluxo do Main expõe dados de gestão, funcionários, rotas, fiscalidade ou recibos verdes.

### 3.2 Backoffice Admin (futura pasta `admin/`)
Acesso: restrito, por perfil (`gestor` / `funcionario`). Não existe hoje; toda a estrutura está definida desde já.

| Módulo | Perfis | Conteúdo |
|---|---|---|
| Dashboard | Gestor, Funcionário | Resumo do dia, alertas |
| **Meus Serviços (Funcionário)** | Funcionário | Listagem de serviços de ambulatório por aceitar/aceites; aceitação individual; desfazer/trocar (regras da secção 8); filtros por categoria (apenas visuais) |
| **Gestão de Agendamentos (Gestor)** | Gestor | Listagem por defeito = ambulatório "Totalmente Aceite por Funcionários"; toggle para pendentes; detalhe com funcionários por serviço |
| **Rotas (Gestor)** | Gestor | Agrupamento dia+cidade; custos de combustível, quota-parte do cliente, lucro dos serviços, lucro total; indicador visual de referência 50€; **aprovação/recusa manual e livre** |
| **Calendário Fiscal (Gestor)** | Gestor | IVA, IRC, Segurança Social, Seguros; alertas 30/15/7/3/1 dia + diários em atraso |
| Simulador Recibos Verdes | Funcionário (na aceitação), Gestor (configuração %) | Percentagem configurável (ex.: 70/30) |

Autenticação do backoffice: sessão separada da do cliente, com middleware de verificação de perfil em todos os endpoints `admin-*`.

---

## 4. DOMÍNIO: UTILIZADORES E PERFIS

- **`utilizador`** (tabela mãe): id, nome, email, password_hash, telemovel, nif, `tipo_perfil` → `cliente`, `funcionario`, `gestor`.
- **`cliente`**: herdade de utilizador; moradas em `cliente_morada`; `telemovel_validado_otp`.
- **`funcionario`**: herdade de utilizador; tipo_contrato, salario_base, cc. **Sem relação com categorias** — as categorias de serviço são apenas filtros/agrupadores visuais da listagem (obtidos de `categoria_profissional`) e nunca restringem a aceitação.
- **`gestor`**: perfil de backoffice com acesso total ao módulo de gestão (rotas, agendamentos, calendário fiscal, configuração do simulador).

---

## 5. CATÁLOGO DE SERVIÇOS E CATEGORIAS

- **35 serviços** em 3 categorias: Cabeleireiro, Barbearia, Estética.
- Campos-chave: `preco_base`, `duracao_estimada_minutos`, `requer_espaco_fisico`.
- `requer_espaco_fisico=1` → disponível **apenas em loja**.
- `servico_local` parametriza disponibilidade por canal (loja_fisica / carrinha_ambulante).
- **Categorias = filtros/agrupadores visuais** (na loja para o cliente, no backoffice para os funcionários). Nunca condicionam quem pode executar o quê.

---

## 6. AGENDAMENTO EM LOJA

### Wizard (Main)
1. **Seleção de serviços** (checkboxes múltiplos; mínimo 1; cálculo automático de duração e valor).
2. **Data e hora**: calendário Terça–Sábado; slots de 30min entre 09:00–19:00; API valida disponibilidade de funcionários.
3. **Seleção de loja** (quando houver mais do que uma; hoje: Évora).
4. **Resumo e confirmação**: serviços, data/hora, local, valores; sinal de 10% (pagamento simulado) + restante no dia.

**Nota:** sem passo de morada, sem passo de profissional obrigatório, **sem estrutura por pessoa**.

### Regras de criação
- Ao criar o agendamento, **todos os serviços ficam imediatamente marcados como "aceites"** (aceitação automática), **sem intervenção de funcionários**.
- Estado posterior à criação: **pendente de validação logística da loja** (a loja valida a logística — espaço, encaixe no calendário interno — no backoffice).
- Sem bloqueio por "totalmente aceite por funcionários" (não se aplica).

---

## 7. AGENDAMENTO EM AMBULATÓRIO (DOMICÍLIO/CARRINHA)

### Wizard (Main)
1. **Seleção de serviços.**
2. **Escolha do canal:** "Ambulatório / Domicílio".
3. **Morada:** cidade (dropdown das cidades suportadas pela `matriz_deslocacao`), rua, número, código postal. A **cidade deriva da morada** e define a rota.
4. **Validação OTP simulada** (código de 6 dígitos mostrado no ecrã).
5. **Estrutura por pessoa:** o cliente cria **Pessoa 1, Pessoa 2, ...** e associa serviços a cada uma. **Várias pessoas podem partilhar os mesmos serviços**; também podem ter serviços diferentes. Este agrupamento determina a **duração total do slot no terreno** (soma/sequenciamento das durações dos serviços de todas as pessoas).
6. **Data e hora** do slot.
7. **Resumo e confirmação** → agendamento criado em **pendente de aceitação por funcionários**.

### Regras pós-criação
- Cada serviço (por pessoa) fica **individualmente disponível para aceitação por funcionários** no backoffice (secção 8).
- A duração total estimada do slot considera o agrupamento por pessoa (serviços partilhados entre pessoas podem ser executados em simultâneo ou sequência conforme logística — ver Dúvidas, secção 19.3).
- Sem aprovação automática de rentabilidade na criação.

---

## 8. DINÂMICA DOS FUNCIONÁRIOS (BACKOFFICE)

### 8.1 Listagem e filtros
- O funcionário vê os serviços de ambulatório pendentes de aceitação, agrupados por agendamento → pessoa → serviço.
- **Categorias funcionam apenas como filtros visuais.** Qualquer funcionário pode ver e aceitar qualquer serviço.

### 8.2 Aceitação individual
- A aceitação é **por serviço individual** (não por agendamento inteiro nem por pessoa).
- No momento da aceitação de serviços de ambulatório, é apresentado o **Simulador de Recibos Verdes** (secção 9) com a simulação do valor liquidável segundo a percentagem configurada.

### 8.3 Desfazer / trocar
- O funcionário pode **desfazer a aceitação** ou **trocar** serviços que já tinha aceitado **enquanto o agendamento global ainda não esteja "Totalmente Aceite por Funcionários"**.
- Quando o **último serviço** do agendamento é aceite, o agendamento transita para **"Totalmente Aceite por Funcionários"** e **bloqueia**: (a) novas trocas/desistências dos funcionários e (b) **agendamentos concorrentes na mesma janela temporal**.

### 8.4 Concorrência de janela temporal
- Ao consolidar um agendamento, o sistema reserva o slot (data/hora + cidade/rota) e recusa agendamentos novos ou aceitações que colidam com essa janela, considerando a duração total estimada no terreno.

---

## 9. SIMULADOR DE RECIBOS VERDES

- **Gatilho:** apresentado **no momento da aceitação** de cada serviço de ambulatório.
- **Percentagem configurável** pelo gestor (por defeito: **70% funcionário / 30% plataforma**).
- Cálculo simulado: `valor_serviço × percentagem_funcionário` (para o funcionário) e `valor_serviço × percentagem_plataforma` (para a plataforma).
- Registo associado ao serviço aceito (para futura integração com `transacao_financeira` ou tabela própria de recibos verdes — ver Dúvidas).
- **Âmbito:** apenas ambulatório (na loja não há aceitação por funcionários).

---

## 10. MÓDULO DO GESTOR E ROTAS

### 10.1 Gestão de Agendamentos
- **Filtro por defeito:** agendamentos de **ambulatório "Totalmente Aceite por Funcionários"**.
- **Opção de ver pendentes** (ainda não totalmente aceites).
- **Detalhe do agendamento:** quais funcionários estão associados a **cada serviço** (por pessoa).

### 10.2 Decisão de Rotas
- Agrupamento: **dia + cidade**.
- Por cada grupo, o sistema apresenta:
  - **Custo de combustível** (da `matriz_deslocacao`, base Évora)
  - **Quota-parte do cliente** (componente de custo de deslocação partilhado com o cliente)
  - **Lucro dos serviços** (receita dos serviços aceites do grupo)
  - **Lucro total** (lucro serviços − custos ± quota-parte)
- **Indicador visual de referência: 50€** — apenas **referência visual** (ex.: destaque verde/vermelho), **nunca bloqueio automático**.
- **Decisão manual e livre:** o gestor **aprova ou recusa** cada rota, independentemente do valor.
  - **Aprovar** → agendamentos da rota confirmados; rota registada.
  - **Recusar** → agendamentos da rota recusados/cancelados; notificação simulada ao cliente com alternativas.
- Sem CRON: decisão sempre accionada manualmente no backoffice.

### 10.3 Calendário Fiscal (centralizado no backoffice)
- **Obrigações:** IVA, IRC, Segurança Social, Seguros.
- **Alertas automáticos progressivos:** 30, 15, 7, 3 e 1 dia antes do prazo, e **diários em atraso**.
- Cada obrigação: designação, tipo, valor estimado, periodicidade, data de prazo, estado (pendente/pago), histórico.
- Necessita de novas tabelas (ex.: `obrigacao_fiscal`, `alerta_fiscal`) — ver secção 12.

---

## 11. CALENDÁRIO FISCAL — DETALHE (resumo operacional)
(ver secção 10.3; mantido como apontador para as regras de alerta e entidades envolvidas)

---

## 12. MODELO DE DADOS — MAPEAMENTO E EXTENSÕES

### Existente e aproveitado
| Tabela | Uso no novo planeamento |
|---|---|
| `agendamento` | Registo principal; `local_prestacao` = `loja` / `ambulatorio`; `estado_reserva` (ver secção 15) |
| `agendamento_servico` | Serviços por agendamento; **novo campo `pessoa_id`** para estrutura por pessoa; campo de aceitação/funcionário |
| `agendamento_pessoa` (NOVA) | Agrupador "Pessoa 1, 2, ..." de um agendamento de ambulatório (nome, notas) |
| `servico` / `servico_local` | Catálogo e disponibilidade por canal |
| `cidade` / `matriz_deslocacao` / `base_partida` | Cidades, custos de combustível (Évora) |
| `rota_ambulante` | Rota por dia+cidade; `estado_rota` = aprovada/recusada; campos de custos e lucros |
| `rota_funcionario` | Alocação de funcionários à rota |
| `funcionario` | Perfis da equipa (contrato, salário, CC) — **sem categorias** |
| `transacao_financeira` | Sinal de loja, pagamentos simulados, quotas de deslocação |
| `feedback_cliente` | Feedback no Main |
| `utilizador` / `cliente` / `cliente_morada` | Domínio de utilizadores |

### Novas entidades previstas
1. **`agendamento_pessoa`** — pessoas de um agendamento de ambulatório.
2. **Configuração do simulador de recibos verdes** — tabela de configuração (`percentagem_funcionario`, `percentagem_plataforma`, data de vigência) ou registo por aceitação.
3. **`obrigacao_fiscal`** — calendário fiscal (tipo: IVA/IRC/SS/Seguros; periodicidade; prazo; valor; estado).
4. **`alerta_fiscal`** (ou geração dinâmica) — alertas 30/15/7/3/1/atrados.
5. Campo de **estado de aceitação por serviço** em `agendamento_servico` (`funcionario_id`, `estado_aceitacao`, `valor_recibo_verde`).

---

## 13. ARQUITETURA TÉCNICA E PASTAS

```
secade-beauty-tarde/
├── app/                       # Backend (partilhado, MVC)
│   ├── config/               # Config + routing da API
│   ├── controllers/          # Controllers (Main + admin-*)
│   ├── services/             # Lógica de negócio (Booking, Rota, OTP, Fiscal, GreenReceipt…)
│   ├── repositories/         # Acesso a dados (sem JOINs cross-context; transações no Service)
│   └── utils/                # Validator, Session
├── modules/
│   ├── common/               # Recursos partilhados (libs, js utils/validators/api)
│   ├── main/                 # SITE PRINCIPAL (clientes) — já existente
│   └── admin/ → ver pasta raiz abaixo
├── admin/                    # BACKOFFICE FUTURO (não tocar agora; planeado desde já)
│   ├── index.php / login.php
│   ├── components/           # layouts, navbar admin
│   ├── pages/                # dashboard, services (funcionário), appointments, routes, fiscal
│   └── js/                   # lógica admin (services.js, routes.js, fiscal.js)
├── index.php                 # Front Controller (Main)
├── .htaccess                 # Rewrite (incluindo futura rota /admin)
└── DataBase.sql              # Schema
```

**Convenções (atualizadas com base no código implementado — ver 13.1):**
- Endpoints `kebab-case` na API (`?action=...`), métodos camelCase, classes PascalCase.
- Código em inglês, BD em português; prepared statements obrigatórios; sem frameworks/pacotes novos.
- Demais convenções detalhadas na secção 13.1 (Repository + Mapper + Service).

---

### 13.1 Convenções de Repository, Mapper e Service (com base no código implementado)

> Revê e substitui a regra antiga "repository limitado à própria tabela, sem JOINs". O código atual (`ServiceRepository`, `CustomerAddressRepository`) já adota `LEFT JOIN` com **tabelas de suporte/descrição**, e a camada de `mappers/` é parte oficial da arquitetura.

#### Repository (`app/repositories/`)
- Estende `BaseRepository`, que expõe `fetch`, `fetchAll`, `execute`, `exists` e `lastInsertId` (todos com prepared statements) e aplica automaticamente o Mapper associado (`protected ?string $mapper = XMapper::class;`) ao resultado.
- **Queries de leitura (SELECT) podem usar JOINs** — permitido e já em prática — desde que respeitem estas condições:
  - O JOIN é para **enriquecer a linha da tabela principal** com dados descritivos/ de suporte (ex.: `servico LEFT JOIN categoria_profissional` para `categoria_nome`; `cliente_morada LEFT JOIN cidade` para `cidade_nome`/`distrito`). Ou seja: joins **"pertence a" (N:1) de lookup**, nunca escrita nem lógica de outra entidade agregada.
  - A **tabela principal** do repository continua a ser a sua própria; colunas de outras tabelas entram apenas como campos de leitura mapeados (ex.: `c.nome AS categoria_nome`).
  - **Não usar JOINs para composição de coleções filhas** (1:N — ex.: agendamento + seus serviços). Isso continua a ser composição no Service, consultando os repositories respetivos.
- Escrita (INSERT/UPDATE/DELETE) permanece estritamente limitada à própria tabela.
- Filtros dinâmicos construídos com `WHERE 1=1` + concatenação condicional de `:params` nomeados (padrão `search(array $filters)`).
- Convenção de métodos: `find(?int $id = null, ...)`, `search(array $filters = [])`, `create(...)`, `update*()`, `delete(...)` — o `find()` sem id devolve lista, com id devolve um registo (`LIMIT 1`).

#### Mapper (`app/mappers/`)
- Camada dedicada à tradução **linha BD (snake_case PT) → payload de saída (camelCase EN)** e ao casting de tipos (`int`, `float`, `string`, `bool`).
- Cada mapper estende `BaseMapper` e declara os casts em `mapRow()` com `->cast(coluna_bd, chave_saida, tipo)`. Campos vindos de JOINs (ex.: `categoria_nome` → `categoryName`) são mapeados aqui também.
- `BaseMapper::map($data)` trata automaticamente linha única, lista de linhas e vazio/null.
- O `BaseRepository` aplica o mapper automaticamente; quando um Service recebe dados crus de outro caminho, pode invocar `XMapper::map($raw)` diretamente (padrão atual em `ServiceService::listServices`).
- Repositories sem mapper definido (`$mapper = null`) devolvem as linhas tal como vêm da BD.

#### Service (`app/services/`)
- Estende `BaseService` (validação via `Validator` com `validate($data, fn($v) => ...)` e transações via `executeTransactional(callable)`, que suporta aninhamento).
- Instancia os seus `Repository` no construtor e pode **invocar outros Services** para composição (padrão do registo de cliente: `CustomerService` → `UserService` + `CustomerRepository` + `CustomerAddressService`).
- Responsabilidades do Service: validação de input, orquestração/transações entre múltiplos repositories/services, regras de negócio e formatação da resposta final (ex.: `"message"`, contagens). Sem SQL inline.
- Fluxo padrão: **Controller → Service → (outros Services) → Repository (+ Mapper) → saída mapeada**.

## 14. ENDPOINTS / API PREVISTOS

### Main (cliente)
| Endpoint | Método | Descrição |
|---|---|---|
| `service-list`, `service-details` | GET | Catálogo |
| `booking-availability` | GET | Slots (loja e ambulatório, com duração do agrupamento por pessoa) |
| `booking-create-loja` | POST | Cria agendamento loja (aceitação automática dos serviços) |
| `booking-create-ambulatorio` | POST | Cria agendamento ambulatório com pessoas/serviços; estado pendente |
| `otp-request` / `otp-validate` | POST | OTP simulado (domicílio) |
| `feedback-*` | GET/POST | Feedback |
| `city-supported`, `auth-*`, `customer-*` | GET/POST | Já existentes |

### Backoffice admin (`admin-*`) — acesso por perfil
| Endpoint | Método | Descrição |
|---|---|---|
| `admin-auth-*` | POST | Login backoffice por perfil |
| `admin-service-pending-list` | GET | Serviços de ambulatório por aceitar (filtros por categoria) |
| `admin-service-accept` | POST | Aceitação individual de serviço (+ recibo verde) |
| `admin-service-unaccept` / `admin-service-swap` | POST | Desfazer/trocar (bloqueado se agendamento consolidado) |
| `admin-appointments-list` | GET | Gestão de agendamentos (default: totalmente aceites; opção pendentes) |
| `admin-appointment-details` | GET | Funcionários por serviço |
| `admin-routes-list` | GET | Rotas agrupadas por dia+cidade com custos/lucros |
| `admin-route-decide` | POST | Aprovar/recusar rota (**manual**) |
| `admin-fiscal-calendar-list` | GET | Calendário fiscal |
| `admin-fiscal-alert-list` | GET | Alertas 30/15/7/3/1/atrados |
| `admin-green-receipt-config` | GET/POST | Percentagem do simulador (70/30) |

---

## 15. ESTADOS E MÁQUINA DE ESTADOS

### Agendamento (ambulatório)
```
pendente_aceitacao_funcionarios
  → (último serviço aceite) → totalmente_aceite_funcionarios
  → (gestor recusa rota) → recusado/cancelado
totalmente_aceite_funcionarios
  → (gestor aprova rota) → confirmado
  → (gestor recusa rota) → cancelado
confirmado → executado → concluído
```

### Agendamento (loja)
```
criado (serviços automaticamente aceites)
  → pendente_validacao_logistica_loja
  → validado → confirmado → executado → concluído
  → (problema logístico) → cancelado
```

### Serviço individual (ambulatório)
```
pendente → aceite (funcionário) → [desfeito se agendamento não consolidado] → pendente
aceite → (troca de funcionário enquanto agendamento não consolidado)
```

### Rota
```
proposta (agrupamento dia+cidade) → aprovada (manual) | recusada (manual)
```

---

## 16. FASES E ROADMAP

> ✅ **ESTADO (21/09/2026): todas as fases implementadas e testadas.** Ver `relatorio_implementacao.md`.

### Fase 1 — Base ✅ CONCLUÍDA
- Autenticação, registo, perfil, moradas, catálogo de serviços.

### Fase 2 — Site Principal (Main) ✅ CONCLUÍDA
- Wizard Loja (seleção → loja → data/hora → resumo → criação com aceitação automática + validação logística).
- Wizard Ambulatório (morada → OTP → **estrutura por pessoa** → data/hora → pendente).
- Feedback (avaliação após execução, listagem pública) e meus agendamentos.

### Fase 3 — Backoffice Funcionário ✅ CONCLUÍDA
- Listagem com filtros por categoria (visuais), aceitação individual, desfazer/trocar, consolidação "Totalmente Aceite", bloqueio de janela temporal, simulador de recibos verdes na aceitação.

### Fase 4 — Backoffice Gestor ✅ CONCLUÍDA
- Gestão de agendamentos (filtros, detalhe por serviço/funcionário, execução, cancelamento).
- Rotas: agrupamento dia+cidade, custos/lucros, indicador 50€ (visual), **decisão manual** (aprovar/recusar).
- Calendário fiscal + alertas progressivos (30/15/7/3/1/atraso).
- Configuração do simulador de recibos verdes (percentagens com vigência por data).

### Fase 5 — Integração e testes ✅ CONCLUÍDA
- Fluxos end-to-end (cliente → funcionário → gestor), responsividade, estados, notificações simuladas.
- 4 suites de teste automatizado em `tests/` (289 verificações: funcional, HTTP, assets, sintaxe JS).

> Nota: o cronograma de 7 dias dos documentos anteriores é substituído por este roadmap por fases, alinhado com as novas regras.

---

## 17. SIMPLIFICAÇÕES ACADÉMICAS (mantêm-se)
- Pagamentos simulados (sem gateway real).
- SMS/Email/OTP simulados (log ou alert).
- Decisão de rotas manual (sem CRON).
- Recibos verdes: **simulador** (sem emissão real na Segurança Social).

---

## 18. CRITÉRIOS DE ACEITAÇÃO — ESTADO

1. ✅ Cliente faz agendamento em loja sem morada e sem pessoas; serviços ficam automaticamente aceites e pendentes de validação logística.
2. ✅ Cliente faz agendamento de ambulatório com morada, OTP, e estrutura por pessoa com serviços partilhados; duração do slot reflete o agrupamento.
3. ✅ Funcionário aceita serviços individualmente, desfaz/troca enquanto o agendamento não está consolidado, e vê o simulador de recibos verdes na aceitação.
4. ✅ Ao ser aceite o último serviço, o agendamento fica "Totalmente Aceite por Funcionários" e bloqueia concorrência na janela temporal.
5. ✅ Gestor consulta agendamentos com filtros (pendentes, aceites, local, data, cidade) e detalhe por serviço/funcionário.
6. ✅ Gestor analisa rotas por dia+cidade com custos/quota-parte/lucros, vê o indicador de 50€ **apenas como referência** e decide manualmente (aprovar/recusar).
7. ✅ Calendário fiscal centralizado com alertas 30/15/7/3/1/atraso para IVA, IRC, SS e Seguros.
8. ✅ Nenhum acesso do cliente ao backoffice; perfis respeitados em todos os endpoints `admin-*` (401 sem sessão, 403 perfil errado, redirect nas páginas).
9. ✅ Feedback do cliente após execução do serviço, com reflexo público nos testemunhos.
10. ✅ Todas as validações aplicadas em client e server; testes automatizados a passar.

---

## 19. PONTOS DE ATENÇÃO — RESOLVIDOS

> ✅ Todos os pontos abaixo foram **resolvidos e implementados**. Detalhe em `relatorio_implementacao.md`.

- ✅ **Rotas:** o limiar automático de 100 € foi **revogado**; a decisão é **manual** com 50 € apenas como indicador visual.
- ✅ **Estrutura por pessoa:** implementada com `agendamento_pessoa` + `agendamento_servico.agendamento_pessoa_id`.
- ✅ **Bloqueio de janela temporal:** verificado no momento da consolidação (`totalmente_aceite_funcionarios`).
- ✅ **OWT / sinal:** OTP simulada (código no ecrã) para o canal ambulatório; sinal de 10% simulado na loja.
- ✅ **Serviços partilhados:** um registo por (agendamento, pessoa, serviço) e contabilização por pessoa.
- ✅ **Categorias de funcionário:** são apenas filtros visuais (não restringem a aceitação).
- ✅ **Alertas fiscais:** geração on-demand (sem CRON), idempotente.
- ✅ **Perfis:** `gestor` existe em `utilizador.tipo_perfil` e o backoffice é segregado por perfil.

---

**Versão:** 3.1 | **Status:** ✅ IMPLEMENTAÇÃO CONCLUÍDA | Prevalência: este documento > documentos anteriores
