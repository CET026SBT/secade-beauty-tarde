# Especificação — Agendamento e rotas

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../especificacao_mvp.md).
> Capítulos: §8 · §9 · §12 · §20

## 8. AGENDAMENTO — LOJA FÍSICA

### 8.1 Wizard (Main) — 5 passos
1. **Seleção de serviços** — checkboxes múltiplos, mínimo 1; cálculo automático de duração e valor.
2. **Escolha do canal** — "Loja Física".
3. **Data e hora** — calendário Terça–Sábado; slots de 30 min entre 09:00 e 19:00;
   a API valida disponibilidade.
4. **Profissional** — passo **informativo** ("Sem preferência"); a equipa é atribuída por aceitação,
   porque a BD não associa funcionários a slots.
5. **Resumo e confirmação** — serviços, data/hora, local, valores, sinal de 10 %.

**Sem** passo de morada, **sem** estrutura por pessoa.

### 8.2 Regras de criação
- Todos os serviços ficam **imediatamente aceites** (`estado_aceitacao='aceite'`) — **sem**
  intervenção de funcionários.
- Estado após criação: **`pendente_validacao_logistica_loja`** (validação de capacidade/logística,
  visível no backoffice do gestor, que pode cancelar).
- **Sem** bloqueio por "totalmente aceite por funcionários" (não se aplica à loja).
- **Validação de conflito:** `countByDateWindow(..., 'loja_fisica')` considera a **duração total**
  e conta os estados `pendente_validacao_logistica_loja`, `totalmente_aceite_funcionarios` e
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

### 9.1 Wizard (Main) — 7 passos + OTP
1. **Seleção de serviços** — apenas serviços sem `requer_espaco_fisico`.
2. **Escolha do canal** — "Carrinha Ambulante" (bloqueado se algum serviço exigir espaço físico).
3. **2B — Morada:** cidade (dropdown das 10 cidades suportadas), rua, número, código postal.
   A **cidade deriva da morada** e define a rota.
4. **2C — OTP simulado:** código de 6 dígitos mostrado no ecrã; validado antes de prosseguir.
5. **3 — Data e hora** do slot (por **lista de horas disponíveis**, ver D-07).
6. **4 — Política de sinal:** informado que a 1.ª marcação é **dispensada**; aceitação de termos.
7. **5 — Estrutura por pessoa + Resumo:** Pessoa 1..N, cada uma com os seus serviços.
   → agendamento criado em **`pendente_aceitacao_funcionarios`**.

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

### 9.6 Limitações conhecidas
- A lista de horas disponíveis **não é recarregada** se o cliente alterar os serviços depois de
  escolher a data → **a melhorar** (D-07 / §24.1).
- O horário é hoje **rígido** 09:00–19:00 também para a carrinha → falta a **exceção** de D-09 (§24.4).

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

### 12.5 Rotas multicidades (a implementar — ver §24.4)
- Permitidas mas **não são a norma**; exigem **ordenação cronológica** e validação de
  **espaçamento temporal suficiente** para a deslocação entre cidades
  (usar `matriz_deslocacao.tempo_estimado_minutos`).
- A listagem do gestor deve permitir **adicionar agendamentos de outras cidades** ao grupo,
  desde que não colidam com os intervalos já selecionados.
- Padrão de exemplo: `Évora → Évora → [deslocação] → Arraiolos → Arraiolos → [regresso] → Évora`.

## 20. MÁQUINA DE ESTADOS

### 20.1 `agendamento.estado_reserva` — enum com 8 valores
```
'pendente_aceitacao_funcionarios'    ← criado (carrinha)
'pendente_validacao_logistica_loja'  ← criado (loja)
'totalmente_aceite_funcionarios'     ← consolidado (carrinha)
'confirmado'                         ← rota aprovada pelo gestor
'recusado'                           ← existe no enum, NÃO usado pelo fluxo atual ⚠️
'cancelado'                          ← rota recusada OU cancelamento (gestor/cliente/24h)
'executado'                          ← execução registada
'concluido'                          ← terminal
```
> ⚠️ `'recusado'` existe no ENUM mas o `RotaService` grava **`'cancelado'`** quando a rota é
> recusada. Não é defeito — é um valor legado do schema.

### 20.2 Fluxo **Carrinha Ambulante**
```
[cliente confirma + OTP válido]
        │
        ▼
(( pendente_aceitacao_funcionarios ))   ── todos os agendamento_servico = 'pendente'
        │   [funcionários aceitam individualmente]
        ▼  (último serviço aceite)
(( totalmente_aceite_funcionarios ))    ── janela BLOQUEADA · desfazer BLOQUEADO (409)
        │   [gestor decide a rota — MANUAL]
        ├──[APROVAR]──▶ (( confirmado )) ──[registar execução]──▶ (( executado )) ──▶ feedback
        └──[RECUSAR]──▶ (( cancelado ))  ✗ não executável
```

### 20.3 Fluxo **Loja Física**
```
[cliente confirma]  ── serviços AUTO-ACEITES
        │
        ▼
(( pendente_validacao_logistica_loja ))
        ├──[gestor cancela]───────────▶ (( cancelado ))
        └──[gestor registra execução]─▶ (( executado )) ──▶ feedback
```

### 20.4 Transições permitidas
| Origem                              | Ação                             | Destino                             | Quem        | Guarda (código / regras)                                     |
| :---------------------------------- | :------------------------------- | :---------------------------------- | :---------- | :----------------------------------------------------------- |
| —                                   | criar (loja)                     | `pendente_validacao_logistica_loja` | cliente     | `validateBookingDate` + `validateStoreOpeningHours` +        |
|                                     |                                  |                                     |             | `countByDateWindow`                                          |
| —                                   | criar (carrinha)                 | `pendente_aceitacao_funcionarios`   | cliente     | + `OTPService::verify` + morada + pessoas                    |
| `pendente_aceitacao_funcionarios`   | aceitar todos                    | `totalmente_aceite_funcionarios`    | funcionário | `consolidateIfComplete` + `assertNoWindowConflict`           |
| `totalmente_aceite_funcionarios`    | aprovar rota                     | `confirmado`                        | **gestor**  | `decideRoute` — **manual**                                   |
| `totalmente_aceite_funcionarios`    | recusar rota                     | **`cancelado`**                     | **gestor**  | `decideRoute` — **manual**                                   |
| ≠ {cancelado, executado, concluido} | cancelar                         | `cancelado`                         | gestor      | `cancelBooking` — erro 409 se já estiver num estado terminal |
| —                                   | **cancelar**                     | `cancelado`                         | **cliente** | **⬜ a implementar** (§24.6)                                 |
| —                                   | **auto-cancelar (24h sem rota)** | `cancelado`                         | sistema     | **⬜ a implementar** (§24.6)                                 |
| `confirmado`                        | registar execução                | `executado`                         | gestor      | `ExecutionService::registerExecution` (idempotente)          |
| `executado`                         | avaliar                          | (sem mudança)                       | cliente     | Limite de 1 avaliação por agendamento                        |

### 20.5 `agendamento_servico.estado_aceitacao`
```
(( pendente )) ──[funcionário aceita]──▶ (( aceite ))
      ▲                                      │
      └────────[desfazer]────────────────────┘
               ✗ 409 se o AGENDAMENTO estiver 'totalmente_aceite_funcionarios'

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
| Operação          | Condição de Bloqueio                                                                    | Código HTTP       |
| :---------------- | :-------------------------------------------------------------------------------------- | :---------------- |
| Aceitar serviço   | Local diferente de carrinha, ou estado em `{cancelado, recusado, executado, concluido}` | **409**           |
| Desfazer / trocar | Agendamento consolidado; ou não foi o próprio funcionário que aceitou                   | **409** / **403** |
| Consolidar        | Conflito de janela temporal                                                             | **409**           |
| Cancelar (gestor) | Estado em `{cancelado, executado, concluido}`                                           | **409**           |
| Registar execução | Estado não executável / já registado (tratado como idempotente com aviso)               | **409**           |
| Criar agendamento | Conflito de janela temporal; data no passado; fora do horário de Terça a Sábado         | **409** / **422** |
