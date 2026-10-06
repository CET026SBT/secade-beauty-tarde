# Especificação — Agendamento e rotas

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../../especificacao_mvp.md).
> Capítulos: §8 · §9 · §12 · §20

## 8. AGENDAMENTO — LOJA FÍSICA

### 8.1 Wizard (Main) — 4 passos
1. **Seleção de serviços** — checkboxes múltiplos, mínimo 1; cálculo automático de duração e valor.
2. **Escolha do canal** — "Loja Física".
3. **Data e hora** — calendário Terça–Sábado; slots de 30 min entre 09:00 e 19:00;
   a API valida disponibilidade. Os slots **reavaliam-se** sempre que os serviços mudam (§9.2).
4. **Resumo e confirmação** — serviços, data/hora, local, valores, sinal de 10 %.

**Sem** passo de morada, **sem** estrutura por pessoa. O antigo passo **«Profissional» foi removido**
(G-01): a equipa é atribuída por aceitação/alocução no backoffice, não pela preferência do cliente.

**Regra das 24 h (RF-58 · RN-24 · F10):** nenhuma marcação é aceite com **menos de 24 h** de
antecedência (`validateBookingDate`) — a operação planeia rotas, não improvisa o dia de hoje.

### 8.2 Regras de criação
- Todos os serviços ficam **imediatamente aceites** (`estado_aceitacao='aceite'`) — **sem**
  intervenção de funcionários.
- Estado após criação: **`pendente_validacao_logistica_loja`** (validação de capacidade/logística,
  visível no backoffice do gestor, que pode cancelar).
- **Sem** bloqueio por "totalmente aceite por funcionários" (não se aplica à loja).
- **Validação de conflito:** `countByDateWindow(..., 'loja_fisica')` considera a **duração total**
  e conta os estados `pendente_validacao_logistica_loja`, `totalmente_alocado` e
  `confirmado` → **409** se houver sobreposição.
- **Sinal:** `valor_sinal = valor_total × 10 %` (simulado; `sinal_pago` permanece `0`).
- **Campos gravados:** `local_prestacao='loja_fisica'`, `cliente_morada_id = NULL`,
  `agendamento_servico.agendamento_pessoa_id = NULL`, sem registos em `agendamento_pessoa`.

### 8.3 Exemplo trabalhado (usado nos testes)
```
Barba (4,07 € · 20 min) + Design de Sobrancelha (8,13 € · 30 min)
  valor_total = 12,20 €   ·   valor_sinal = 1,22 € (10 %)
  duração     = 50 min    ·   data: Terça a Sábado, futura
  → 2 registos em agendamento_servico, ambos 'aceite'
```
> ⚠️ Na **loja** os serviços **não** são duplicados por pessoa (não existem pessoas).

## 9. AGENDAMENTO — CARRINHA AMBULANTE

### 9.1 Wizard (Main) — 6 passos
1. **Seleção de serviços** — apenas serviços sem `requer_espaco_fisico`.
2. **Escolha do canal** — "Carrinha Ambulante" (bloqueado se algum serviço exigir espaço físico).
3. **2B — Morada:** cidade (dropdown das 10 cidades suportadas), rua, número, código postal.
   A **cidade deriva da morada** e define a rota.
4. **3 — Data e hora** do slot (por **lista de horas disponíveis**, ver D-07), a **≥ 24 h** (RF-58).
5. **4 — Política de sinal:** informado que a 1.ª marcação é **dispensada**; aceitação de termos.
6. **5 — Estrutura por pessoa + Resumo:** Pessoa 1..N, cada uma com os seus serviços.
   → agendamento criado em **`pendente_alocacao`**.

> **O OTP saiu do fluxo de agendamento (D-04 · D-07.7 · F9):** deixou de haver passo «2C». O OTP passa a
> servir o **perfil** (alterar telemóvel/e-mail/palavra-passe). Ver §9.5.

### 9.2 Estrutura por pessoa (regra central)
- Os serviços são agrupados **obrigatoriamente por pessoa** (Pessoa 1, Pessoa 2, …) — funciona como
  **agrupador logístico e visual** para estimar a **duração total do slot no terreno**.
- **Várias pessoas podem usufruir dos mesmos serviços** (ou de serviços diferentes).
- **Pessoa 1** vem pré-preenchida com o nome do cliente autenticado; podem ser adicionados
  familiares **sem conta**. Validação: nome + ≥ 1 serviço por pessoa. Sem limite imposto.
- **Modelo de dados:** **um registo por (agendamento, pessoa, serviço)**.

### 9.3 Fórmulas (críticas)
```
duração_total = Σ (duração dos serviços POR PESSOA,
                   sem duplicar DENTRO da mesma pessoa)
valor_total   = Σ (preço de cada serviço POR PESSOA)

Exemplo de referência (usado nos testes):
  Pessoa 1: [Barba 20 min · 4,07 €]                →  20 min ·  4,07 €
  Pessoa 2: [Barba 20 min · 4,07 € | Design 30 min · 8,13 €] → 50 min · 12,20 €
  ────────────────────────────────────────────────────────────────────────
  TOTAL                                              70 min · 16,27 €
  valor_sinal = 0,00 € (dispensado)
```
> ⚠️ **Ponto crítico:** o mesmo serviço pedido por duas pessoas conta **duas vezes**.
> (Foi corrigido um defeito em que a deduplicação entre pessoas subestimava valor e duração.)

### 9.4 Regras pós-criação
- Cada serviço (por pessoa) fica **individualmente disponível para aceitação** no backoffice (§10).
- **Sem aprovação automática de rentabilidade** na criação.
- **Validação de conflito:** `countByDateWindow(..., 'carrinha_ambulante')` com a duração total.

### 9.5 OTP — ciclo de vida

| Passo              | Comportamento                                                                                      |
| :----------------- | :------------------------------------------------------------------------------------------------- |
| Pedido             | `OTPService::request()` gera 6 dígitos, guarda na **sessão** (`expiresAt = now + 600 s`) e devolve |
| Validação cliente  | `booking.validator.js` confirma formato 6 dígitos imediatamente                                    |
| Validação servidor | `OTPService::verify()` — compara cliente da sessão, expiração e código (`hash_equals`)             |
| Consumo            | No sucesso o código é **removido da sessão** → **uso único**                                       |
| Erro               | Código inválido/expirado → **422**; sem pedido prévio → **422**                                    |
| **Âmbito (F9)**    | **Saiu do agendamento** (D-04 · D-07.7): `booking-create-amb` já **não** exige `otpCode`. O OTP    |
|                    | passa a servir o **perfil** (alterar telemóvel/e-mail/palavra-passe).                              |

### 9.6 Limitações conhecidas
- A lista de horas disponíveis **recarrega-se** quando os serviços mudam (§24.1 · ✅ resolvido: F3/F9b).
- O horário é hoje **rígido** 09:00–19:00 também para a carrinha → falta a **exceção** de D-09 (§24.4).

### 9.7 Área Cliente (F9 · C-05 · C-06 · C-07)
Nova página **`/area-cliente`** (cliente autenticado) com **três secções**:
1. **Perfil** — dados pessoais (nome, telemóvel, NIF editáveis em **modal**), **foto** com recorte
   quadrado (`cropper` local + re-codificação GD, §4.6) e as **moradas** (criar / principal / remover).
2. **Agendamentos** — as marcações do cliente, com filtros por estado, **cancelamento sem penalização**
   (RF-12) e o **editor de agendamento**.
3. **Lembretes** — os avisos do cliente (`notificacao`): marcação confirmada, recusa por logística e
   **lembrete 24 h** com alternativas (RF-13).

**Substitui** as antigas páginas `profile.php` e `appointments.php` (C-05): as rotas `/perfil` e
`/agendamentos` passam a **atalhos** para a Área Cliente. A **staff perde a página de perfil** — o gestor
é encaminhado para `/gestao` e o funcionário para `/gestao/agenda`.

**Editor de agendamento (C-06 · §4.5):** o cliente muda **serviços/pessoas**, **morada** (carrinha) e
**data/hora**; o **canal é imutável** (`local_prestacao`) e o **OTP saiu** do fluxo (D-04 · D-07.7).
Os slots são **re-avaliados** ao mudar os serviços; a alteração faz recomeçar a folha de serviços
(as percentagens da aceitação anterior não se arrastam) e gera um **aviso** novo (C-07).
Implementado em `bookingEditor.js` + `customerArea.js` (o wizard não foi modularizado: tem stepper, OTP e
resumo que no editor não fazem sentido — partilham a API e os utilitários comuns, que é o reutilizável).

## 12. MÓDULO DO GESTOR — ROTAS

### 12.1 Listagem
- **Agrupamento:** **dia + cidade**.
- **Filtro por defeito** na gestão de agendamentos: ambulatório **"Totalmente Aceite por
  Funcionários"**; opção de ver **pendentes**.
- **Detalhe do agendamento:** que funcionários estão associados a **cada serviço** (por pessoa),
  progresso de aceitação e registo de execução.

### 12.2 Indicadores por grupo (rota)

| Indicador                   | Origem / fórmula                                                                            |
| :-------------------------- | :------------------------------------------------------------------------------------------ |
| **Receita prevista**        | `SUM(agendamento.valor_total)` dos agendamentos do grupo                                    |
| **Custo de combustível**    | `matriz_deslocacao.custo_estimado_combustivel` (base 1 = Évora) — **o único custo da rota** |
| **Lucro/rentabilidade**     | receita − combustível                                                                       |
| **Quota-parte do cliente**  | `rota_ambulante.quota_parte_cliente` — 0 € (sem regra definida; **não** cobrada)            |
| **Indicador de referência** | `meetsReference = rentabilidade ≥ 50 €` → **apenas visual** (RN-05)                         |

### 12.3 Decisão manual (núcleo do módulo)
- **Aprovar** → agendamentos do grupo passam a **`confirmado`**; rota gravada com
  `estado_rota='aprovada'`.
- **Recusar** → **todos** os agendamentos do grupo passam a **`cancelado`**; rota com
  `estado_rota='recusada'`; mensagem de **notificação simulada** ao cliente com alternativas.
- **Auditoria gravada:** `decidido_por`, `decidido_em`, `observacoes_decisao`
  (nota automática com a rentabilidade quando o gestor não escreve nada).
- **Sem CRON** e **sem limiar automático**: o gestor decide livremente, mesmo abaixo dos 50 €.
- **Prova de manualidade (testes):** aprovar uma rota **abaixo** dos 50 € → fica `confirmado`;
  recusar uma rota **acima** → fica `cancelado`.

### 12.4 Convenção da zona de alertas (listagens do backoffice) — D-09
Convenção **obrigatória** para uniformizar alertas em todas as listagens (rotas, agendamentos,
fiscal, serviços):

| Nível           | Classe Bootstrap | Uso                                                                                       | Bloqueia ação?   |
| :-------------- | :--------------- | :---------------------------------------------------------------------------------------- | :--------------- |
| **Informativo** | `alert-info`     | Contexto neutro (ex.: "rota de 3 agendamentos")                                           | Não              |
| **Atenção**     | `alert-warning`  | Rentabilidade abaixo da referência; **multicidades** (custos acrescidos); perto do limite | **Não**          |
| **Crítico**     | `alert-danger`   | Conflito de janela, dados inválidos, obrigação fiscal em atraso                           | Não (só informa) |

**Regras de composição:**
- Posição: **acima** da listagem/tabela; nas rotas, **imediatamente junto** ao indicador de 50 €.
- Estrutura: `ícone + título curto + (detalhe opcional) + (ação opcional)`.
- **Nunca** bloqueiam a decisão manual do gestor (RN-05) — são apoio à decisão.
- Reutilizar o mesmo componente/markup em todos os módulos (não criar variações por página).

### 12.5 Multicidades — **REVOGADO** (D-01 · F4)
- Já **não** faz parte do produto: a rota é de **uma só cidade** por dia. O que existe é o **inverso** —
  o impedimento de o funcionário estar em duas cidades no mesmo dia (**R-ALOC** · F4).
- Regra em vigor (§4.4 · F4):
  - **R-ALOC** (alocar serviço) — valida só «o funcionário já está noutra cidade no mesmo dia?»
    (`countEmployeeInOtherCitySameDay`; cidade `NULL` não conta).
  - **R-CONF** (confirmar rota) — valida «mesma cidade + janela sobreposta?» → **409**
    (`findCityWindowConflicts`).
  - **R-24H** (decidir rota) — só com **≥ 24 h** de antecedência → **409** (`findIdsWithin24Hours`).
- O **bloqueio de janela deixou de acontecer na consolidação** (C-02/D-01): o recurso finito é a **rota
  confirmada**, não a mera alocação.

## 20. MÁQUINA DE ESTADOS

### 20.1 `agendamento.estado_reserva` — enum com 8 valores
```
'pendente_alocacao'    ← criado (carrinha)
'pendente_validacao_logistica_loja'  ← criado (loja)
'totalmente_alocado'     ← consolidado (carrinha)
'confirmado'                         ← rota aprovada pelo gestor (cliente avisado — C-11)
'recusado'                           ← tudo o que a STAFF/SISTEMA decide (6.1 · D-07.4)
'cancelado'                          ← EXCLUSIVO do cliente (6.1 · D-07.4)
'executado'                          ← execução registada
'concluido'                          ← terminal (+4 h após a execução — R2 · F6)
```
> **Divisor fixo (6.1 · D-07.4):** `cancelado` = **só o cliente cancela** · `recusado` = **tudo o que a
> staff ou o sistema decide** (rota recusada, auto-recusa às 24 h, cancelamento do gestor).

### 20.2 Fluxo **Carrinha Ambulante**
```
[cliente confirma — sem OTP (D-04)]
        │
        ▼
(( pendente_alocacao ))   ── todos os agendamento_servico = 'pendente'
        │   [gestor ALOCA (employeeId) e/ou funcionário ACEITA]
        ▼  (último serviço aceite)
(( totalmente_alocado ))    ── deixa de bloquear a janela (F4); desfazer segue bloqueado (409)
        │   [gestor decide a rota — MANUAL, a ≥ 24 h (R-24H)]
        ├──[APROVAR]──▶ (( confirmado )) ──[registar execução]──▶ (( executado )) ──▶ +4 h ▶ (( concluido )) ──▶ feedback
        └──[RECUSAR]──▶ (( recusado ))  ✗ não executável

R1a (F6 · 24 h sem rota) → (( recusado )) + aviso ao cliente (C-14)
R1b (F6 · confirmado já começado) → aviso ao funcionário
R3/R4 (F6 · cascata) → rota 'concluida' / 'recusada' pelos filhos
```

### 20.3 Fluxo **Loja Física**
```
[cliente confirma]  ── serviços AUTO-ACEITES
        │
        ▼
(( pendente_validacao_logistica_loja ))
        ├──[gestor recusa]────────────▶ (( recusado ))
        └──[gestor registra execução]─▶ (( executado )) ──▶ +4 h ▶ (( concluido )) ──▶ feedback
```

### 20.4 Transições permitidas
| Origem                                        | Ação                    | Destino                             | Quem        | Guarda (código / regras)                                 |
| :-------------------------------------------- | :---------------------- | :---------------------------------- | :---------- | :------------------------------------------------------- |
| —                                             | criar (loja)            | `pendente_validacao_logistica_loja` | cliente     | `validateBookingDate` + `validateStoreOpeningHours` +    |
|                                               |                         |                                     |             | `countByDateWindow`                                      |
| —                                             | criar (carrinha)        | `pendente_alocacao`                 | cliente     | morada + pessoas + `validateBookingDate` (≥ 24 h)        |
| `pendente_alocacao`                           | alocar serviço          | (sem mudança)                       | **gestor**  | **R-ALOC** (`countEmployeeInOtherCitySameDay`)           |
| `pendente_alocacao`                           | aceitar todos           | `totalmente_alocado`                | funcionário | `consolidateIfComplete`                                  |
| `totalmente_alocado`                          | aprovar rota            | `confirmado`                        | **gestor**  | `decideRoute` — **manual**; **R-CONF** + **R-24H**       |
| `totalmente_alocado`                          | recusar rota            | **`recusado`**                      | **gestor**  | `decideRoute` — **manual**; aviso ao cliente (C-14)      |
| ≠ {recusado, cancelado, executado, concluido} | recusar (staff)         | `recusado`                          | gestor      | `cancelBooking` — 409 se terminal (D-07.4)               |
| ≠ {recusado, cancelado, executado, concluido} | **cancelar** (cliente)  | `cancelado`                         | **cliente** | `cancelCustomerBooking` — **sem penalização** (RF-12)    |
| não terminal e a < 24 h                       | **auto-recusar (24 h)** | `recusado`                          | sistema     | **R1a** · `MaintenanceService` + aviso ao cliente (C-14) |
| `confirmado`                                  | registar execução       | `executado`                         | gestor      | `ExecutionService::registerExecution` (idempotente)      |
| `executado`                                   | +4 h após a janela      | `concluido`                         | sistema     | **R2** · `MaintenanceService`                            |
| `executado`                                   | avaliar                 | (sem mudança)                       | cliente     | Limite de 1 avaliação por agendamento                    |

### 20.5 `agendamento_servico.estado_aceitacao`
```
(( pendente )) ──[funcionário aceita]──▶ (( aceite ))
      ▲                                      │
      └────────[desfazer]────────────────────┘
               ✗ 409 se o AGENDAMENTO estiver 'totalmente_alocado'

TROCA: aceitar um serviço já 'aceite' por OUTRO funcionário → transfere (isSwap)
LOJA:  criado diretamente como (( aceite )) — aceitação automática
```

### 20.6 `rota_ambulante.estado_rota`
```
'planeada' | 'aprovada' | 'recusada' | 'em_execucao' | 'concluida'
                 ▲            ▲
                 └── gravados por RotaService::decideRoute
```

### 20.7 Guardas de bloqueio (resumo)
| Operação           | Condição de Bloqueio                                                                    | Código HTTP                 |
| :----------------- | :-------------------------------------------------------------------------------------- | :-------------------------- |
| Aceitar serviço    | Local diferente de carrinha, ou estado em `{recusado, cancelado, executado, concluido}` | **409**                     |
| Alocar serviço     | **R-ALOC** — funcionário já noutra cidade no mesmo dia                                  | **409**                     |
| Desfazer / trocar  | Agendamento consolidado; ou não foi o próprio funcionário que aceitou                   | **409** / **403**           |
| Aprovar rota       | **R-CONF** (mesma cidade + janela sobreposta) ou **R-24H** (< 24 h)                     | **409**                     |
| Recusar (gestor)   | Estado em `{recusado, cancelado, executado, concluido}`                                 | **409**                     |
| Cancelar (cliente) | Estado em `{recusado, cancelado, executado, concluido}`; ou não é o dono                | **409** / **403**           |
| Alterar (cliente)  | Estado terminal; ou não é o dono; ou < 24 h; ou conflito de janela                      | **409** / **403** / **422** |
| Registar execução  | Estado não executável / já registado (tratado como idempotente com aviso)               | **409**                     |
| Criar agendamento  | Conflito de janela temporal; data no passado; fora do horário de Terça a Sábado; < 24 h | **409** / **422**           |
