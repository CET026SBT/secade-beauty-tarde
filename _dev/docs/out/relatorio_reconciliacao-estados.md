# RECONCILIAÇÃO DE ESTADOS — PLANO DE IMPLEMENTAÇÃO (não desenvolvimento)

<!-- md-wrap-tables:max=220 -->

**Data:** 2026-10-03 · **Base:** `secade_beauty` · **Natureza:** apoio (**não normativo** — plano)
**Artefacto:** `_dev/docs/out/relatorio_reconciliacao-estados.md` · **Autoridade:** `especificacao_mvp.md`
**Estado:** ⬜ **por decidir** — este documento **não implementa** nada; serve para fechar as dúvidas do §10.

> **Leitura:** §2–§4 traduzem os teus requisitos para o **vocabulário real** do schema (os `ENUM` não dizem
> `cancelado`/`concluido` em todo o lado). §5–§8 são o desenho técnico. **§10 é o mais importante: são as
> dúvidas que preciso que decidas antes de eu escrever uma linha de código.**

## 1. OBJETIVO

Garantir, no **servidor**, que o estado das entidades reflete a passagem do tempo — sem CRON, sem ação
humana:

1. **Cancelar** o que já passou do momento de execução e nunca chegou a ser executado.
2. **Concluir** o que já foi executado e passou o prazo de arrumação (**+4 h**).
3. **Propagar** isso para cima (**bottom-up**): primeiro os filhos, depois os pais — e um pai só muda se
   **todos** os filhos diretos já estiverem nesse estado.

## 2. LEVANTAMENTO — OS ESTADOS REAIS (o teu aviso confirma-se)

Li os `ENUM` do schema (`DataBase.sql`) e a máquina de estados da spec (**§20**). O mapeamento pedido
**"cancelado"/"concluído"** não é literal em todas as tabelas:

| Entidade                   | Coluna             | Estados reais (ENUM)                                           | "Cancelado" =            | "Concluído" =            | Observação                                    |
| :------------------------- | :----------------- | :------------------------------------------------------------- | :----------------------- | :----------------------- | :-------------------------------------------- |
| **`agendamento`**          | `estado_reserva`   | `pendente_aceitacao_funcionarios` ·                            | **`cancelado`** (existe) | **`concluido`** (existe) | `recusado` é **legado** (§20.1)               |
|                            |                    | `pendente_validacao_logistica_loja` ·                          |                          |                          |                                               |
|                            |                    | `totalmente_aceite_funcionarios` · `confirmado` · `recusado` · |                          |                          |                                               |
|                            |                    | `cancelado` · `executado` · `concluido`                        |                          |                          |                                               |
| **`rota_ambulante`**       | `estado_rota`      | `planeada` · `aprovada` · `recusada` · `em_execucao` ·         | **`recusada`** (D3)      | **`concluida`** (existe) | Não há `cancelada`; `em_execucao` não é usado |
|                            |                    | `concluida`                                                    |                          |                          |                                               |
| **`execucao_agendamento`** | `estado_execucao`  | `em_curso` · `concluido` · `no_show_cliente` ·                 | `cancelado_terreno`      | `concluido`              | 1:1 com o agendamento                         |
|                            |                    | `cancelado_terreno`                                            |                          |                          |                                               |
| **`agendamento_servico`**  | `estado_aceitacao` | `pendente` · `aceite`                                          | ❌ **não tem**           | ❌ **não tem**           | **Bloqueia a cascata literal (D2)**           |

> ⚠️ **Conclusão dura:** `agendamento_servico` **não tem estados terminais**. A regra «um pai só muda se
> todos os filhos estiverem nesse estado» **não é aplicável literalmente** a este nível — ver **D2**.

## 3. HIERARQUIA (bottom-up)

```text
NÍVEL 3 (pai)      rota_ambulante            (agrega por data + cidade)
                        ▲
                        │  (sem FK — ligação por data+cidade)
NÍVEL 2 (filho)    agendamento               (1:1 com execucao_agendamento)
                        ▲
                        │  (FK agendamento_id)
NÍVEL 1 (neto)     agendamento_servico       (não tem estados terminais — D2)
                        │
                   agendamento_pessoa        (agrupador, sem estado)
```

**Ordem de execução obrigatória:** **nível 1 → nível 2 → nível 3** (netos primeiro, depois filhos, depois
pai). É o mínimo para o pai olhar para filhos já reconciliados.

## 4. AS REGRAS, TRADUZIDAS

### 4.1 Nível 2 — `agendamento`

Designações: `inicio = data_hora_pretendida` (momento de execução) ·
`fim = inicio + Σ agendamento_servico.duracao_minutos` · `fim_real = execucao_agendamento.data_hora_fim_real`.

| #   | Condição                                                             | De                                                                                                                        | Para        |
| :-- | :------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------------------------ | :---------- |
| R1  | `NOW() > inicio` e o estado é **anterior à execução** e não terminal | `pendente_aceitacao_funcionarios` · `pendente_validacao_logistica_loja` · `totalmente_aceite_funcionarios` · `confirmado` | `cancelado` |
| R2  | `NOW() > (fim/prazo + 4 h)` e o estado é **na/posterior à execução** | `executado`                                                                                                               | `concluido` |

- **"Não terminal"** = não é `cancelado` nem `concluido` (e, por proposta **D5**, também não `recusado`).
- **"Hora de fim/prazo"** → **D1** (planeada vs. real). Proposta: `GREATEST(fim, COALESCE(fim_real, fim))`.

### 4.2 Nível 3 — `rota_ambulante` (pai)

Filhos diretos = **agendamentos de `carrinha_ambulante`** cuja `cidade` (via `cliente_morada`) e cuja data
(`DATE(data_hora_pretendida)`) coincidem com `rota_ambulante.data_rota` + `cidade_id`.

| #   | Condição                                                          | De                           | Para        |
| :-- | :---------------------------------------------------------------- | :--------------------------- | :---------- |
| R3  | **Todos** os filhos diretos em `concluido` **e** existe ≥ 1 filho | `aprovada` · `em_execucao`   | `concluida` |
| R4  | **Todos** os filhos diretos em `cancelado` **e** existe ≥ 1 filho | `planeada` · `aprovada` (D4) | `recusada`  |

- O guard «existe ≥ 1 filho» evita concluir/cancelar uma rota **vazia**.
- **R4 sobre `planeada`** é discutível (uma rota ainda por decidir) → **D4**.

## 5. ONDE EXECUTAR (os "hooks")

**Decisão de desenho:** um **serviço transacional idempotente** (`ReconciliationService::reconcile()`),
chamado **no início** das leituras que mostram estados ao utilizador — nunca espalhado pela lógica.

| Ponto de entrada (Service)               | Endpoint                     | Porquê reconciliar antes              |
| :--------------------------------------- | :--------------------------- | :------------------------------------ |
| `BookingService::listBookings()`         | `admin-appointments-list`    | gestor vê estados atualizados         |
| `BookingService::findCustomerBookings()` | `booking-my`                 | cliente vê o seu histórico correto    |
| `RotaService::findRouteSummaries()`      | `admin-routes-list`          | decisão da rota usa filhos certos     |
| `EmployeeAgendaService::findMonth()`     | `admin-employee-agenda-list` | agenda não mostra marcações fantasma  |
| `DashboardService` (KPIs)                | `admin-dashboard-summary`    | contadores coerentes com as listagens |

**Recomendações de segurança e custo:**

1. **Correr uma vez por pedido** (guard `static` no serviço) — evita repetir nas várias leituras do mesmo pedido.
2. **Nunca** nos endpoints públicos de métricas (`site-stats`, `feedback-list`) — leitura pura, sem escrita.
3. **Sempre** dentro de transação (`BaseService::executeTransactional`), para o conjunto ser atómico.
4. **Só as listagens do backoffice/cliente** — como na tabela acima.

## 6. DESENHO TÉCNICO (respeita a arquitetura §18)

```text
Controller (autoriza, delega)
   └── Service: BookingService / RotaService / ...   ← chama reconcile() antes de ler
           └── ReconciliationService (NOVO)          ← orquestra a ORDEM bottom-up + transação
                   └── ReconciliationRepository (NOVO)  ← SQL set-based, 1 tabela por UPDATE
```

**`app/services/ReconciliationService.php`** (esqueleto):

```php
class ReconciliationService extends BaseService {
    private const HOURS_AFTER_END = 4;
    private static bool $done = false;   // 1x por pedido

    public function reconcile(): array {
        if (self::$done) return ["skipped" => true];
        self::$done = true;

        return $this->executeTransactional(function () {
            $r = new ReconciliationRepository();
            // NÍVEL 1 — netos (agendamento_servico): NADA (ver D2)
            // NÍVEL 2 — filhos
            $out["bookingsCancelled"] = $r->cancelExpiredBookings();
            $out["bookingsCompleted"] = $r->completeExecutedBookings(self::HOURS_AFTER_END);
            // NÍVEL 3 — pai (só depois de os filhos estarem estáveis)
            $out["routesCompleted"] = $r->completeRoutesWithAllChildrenDone();
            $out["routesCancelled"] = $r->cancelRoutesWithAllChildrenCancelled();
            return $out;
        });
    }
}
```

**`app/repositories/ReconciliationRepository.php`** — SQL puro, `UPDATE` de **uma só tabela** por statement
(respeita «escrita sempre na própria tabela», §18.2); a condição sobre os filhos é um `EXISTS`/`NOT EXISTS`
dentro do `UPDATE`, não um JOIN de escrita.

## 7. AS QUERIES (set-based, sem loops)

> **Princípio de otimização:** **4 `UPDATE` constantes** (independentes do número de linhas) + **uma só
> fonte de tempo** (`NOW()` do MySQL, sem ir e vir ao PHP). Nada de `foreach` linha-a-linha.

```sql
-- NÍVEL 2 · R1 — cancelar o que passou e nunca executou
UPDATE agendamento
   SET estado_reserva = 'cancelado'
 WHERE estado_reserva IN ('pendente_aceitacao_funcionarios','pendente_validacao_logistica_loja',
                          'totalmente_aceite_funcionarios','confirmado')
   AND data_hora_pretendida < NOW();

-- NÍVEL 2 · R2 — concluir o que foi executado e já passou o prazo (+4h)
UPDATE agendamento a
  JOIN (SELECT agendamento_id, COALESCE(SUM(duracao_minutos),0) AS dur
          FROM agendamento_servico GROUP BY agendamento_id) d ON d.agendamento_id = a.id
  LEFT JOIN execucao_agendamento e ON e.agendamento_id = a.id
   SET a.estado_reserva = 'concluido'
 WHERE a.estado_reserva = 'executado'
   AND DATE_ADD(
         GREATEST(DATE_ADD(a.data_hora_pretendida, INTERVAL d.dur MINUTE),
                  COALESCE(e.data_hora_fim_real, a.data_hora_pretendida)),
         INTERVAL 4 HOUR) < NOW();
```

```sql
-- NÍVEL 3 · R3 — concluir rota se TODOS os filhos estiverem concluídos
UPDATE rota_ambulante r
   SET r.estado_rota = 'concluida'
 WHERE r.estado_rota IN ('aprovada','em_execucao')
   AND EXISTS (SELECT 1 FROM agendamento a JOIN cliente_morada cm ON cm.id = a.cliente_morada_id
                WHERE a.local_prestacao='carrinha_ambulante'
                  AND DATE(a.data_hora_pretendida)=r.data_rota AND cm.cidade_id=r.cidade_id)
   AND NOT EXISTS (SELECT 1 FROM agendamento a JOIN cliente_morada cm ON cm.id = a.cliente_morada_id
                    WHERE a.local_prestacao='carrinha_ambulante'
                      AND DATE(a.data_hora_pretendida)=r.data_rota AND cm.cidade_id=r.cidade_id
                      AND a.estado_reserva <> 'concluido');

-- NÍVEL 3 · R4 — cancelar rota se TODOS os filhos estiverem cancelados
UPDATE rota_ambulante r
   SET r.estado_rota = 'recusada'
 WHERE r.estado_rota IN ('planeada','aprovada')          -- D4
   AND EXISTS     ( ...idem, ≥ 1 filho... )
   AND NOT EXISTS ( ...idem, nenhum filho <> 'cancelado'... );
```

**Índices que isto precisa** (sem eles, o `EXISTS` da rota varre a tabela):

| Índice                                                        | Serve                                 |
| :------------------------------------------------------------ | :------------------------------------ |
| `agendamento (data_hora_pretendida, estado_reserva)`          | R1 e R2 (filtro temporal + estado)    |
| `agendamento (estado_reserva)` + `cliente_morada (cidade_id)` | R3/R4 (o `EXISTS` por data+cidade)    |
| `agendamento_servico (agendamento_id)`                        | subquery da duração (coberto pela FK) |

> Estes índices já constam do `relatorio_melhorias-base-dados.md` (**M-1**).

## 8. TRANSAÇÃO, IDEMPOTÊNCIA E CUSTO

| Garantia            | Como                                                                              |
| :------------------ | :-------------------------------------------------------------------------------- |
| **Atomicidade**     | tudo dentro de `executeTransactional` → ou muda tudo, ou nada                     |
| **Idempotência**    | cada `UPDATE` só toca linhas ainda em estado não terminal → repetir não muda nada |
| **1x por pedido**   | guard `static` no serviço                                                         |
| **Custo constante** | 4 `UPDATE` (set-based), independentes de N; `NOW()` só no servidor                |
| **Sem N+1**         | nenhum `foreach` sobre resultados; zero leituras por linha                        |
| **Compatibilidade** | não usa `ON DUPLICATE`/`DELETE`; só transições de estado já previstas na §20      |
| **Fuso**            | `NOW()` do MySQL (ver **D9**) — uma única fonte de verdade temporal               |

## 9. EXCEÇÕES EXPLÍCITAS (o que a rotina **não** toca)

| Tabela / domínio                                          | Decisão     | Motivo                                               |
| :-------------------------------------------------------- | :---------- | :--------------------------------------------------- |
| `alerta_fiscal`                                           | **Ignorar** | Requisito: alertas fora do âmbito                    |
| `obrigacao_fiscal`                                        | **Ignorar** | Requisito: prazos fiscais fora do âmbito             |
| *Lembretes*                                               | **Ignorar** | Não existe tabela de lembretes (não há nada a tocar) |
| `feedback_cliente`                                        | Ignorar     | Não tem estados                                      |
| `transacao_financeira` · `fecho_caixa_diario` · `gorjeta` | Ignorar     | Sem uso pela app (relatório 2)                       |
| `agendamento_servico`                                     | **D2**      | Não tem estados terminais                            |

## 10. DÚVIDAS / DECISÕES EM ABERTO ⚠️ (decidir antes de codificar)

> Cada item tem uma **proposta** para poderes só aceitar/rejeitar. Sem estas respostas, qualquer
> implementação seria uma **suposição** — e o `.clinerules` §0 proíbe inferir requisito em falta.

| #       | Dúvida                                                                                             | Proposta (aceitar / rejeitar)                                                                        |
| :------ | :------------------------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------------------------- |
| **D1**  | **"Hora de fim/prazo"** do agendamento: **planeada** (`data_hora_pretendida + Σ durações`) ou      | Usar **`GREATEST(fim_planeado, COALESCE(fim_real, fim_planeado))`** — cobre os dois casos.           |
|         | **real** (`execucao_agendamento.data_hora_fim_real`)?                                              |                                                                                                      |
| **D2**  | **`agendamento_servico` não tem estados terminais** (`pendente`/`aceite`). A cascata literal é     | **Não cascatear** por `agendamento_servico`; a cascata efetiva é `agendamento → rota`. (Alternativa: |
|         | impossível.                                                                                        | ➕ alargar o enum — exige justificação de BD.)                                                       |
| **D3**  | **`rota_ambulante` não tem `cancelada`** — tem `recusada`.                                         | "Cancelado" da rota = **`recusada`**.                                                                |
| **D4**  | **R4 sobre rota `planeada`** (ainda por decidir): cancelar quando todos os filhos já estão         | **Sim**, também `planeada` → `recusada` (a rota deixou de fazer sentido).                            |
|         | cancelados?                                                                                        |                                                                                                      |
| **D5**  | **`recusado`** (agendamento) é legado e não é usado pelo fluxo. Tratá-lo como terminal?            | **Sim** — ignorar (`recusado` nunca é tocado).                                                       |
| **D6**  | **`execucao_agendamento`**: é filho 1:1, mas escrito pelo `ExecutionService`. A reconciliação deve | **Não** — só **lê** o `data_hora_fim_real` (para D1).                                                |
|         | mexer-lhe?                                                                                         |                                                                                                      |
| **D7**  | **Onde executar**: hook nas listagens (como §5) ou também num endpoint dedicado (ex.:              | Hook nas listagens (§5) **+** (opcional) endpoint manual para o gestor forçar a rotina.              |
|         | `admin-reconcile`)?                                                                                |                                                                                                      |
| **D8**  | **Interação com a regra das 24 h** (§15 · §24.6, auto-cancelamento por falta de rota). Fundir ou   | **Manter separadas** — são regras diferentes; esta não substitui a das 24 h.                         |
|         | manter separadas?                                                                                  |                                                                                                      |
| **D9**  | **Fuso horário**: `NOW()` do **MySQL** (fuso do servidor) ou hora do **PHP** (`Europe/Lisbon`)?    | **`NOW()` do MySQL** — uma só fonte; garantir que o servidor está no fuso certo.                     |
| **D10** | Aplica-se a **loja física** (`pendente_validacao_logistica_loja`), que não tem rota?               | **Sim** — a regra R1/R2 é transversal aos dois canais.                                               |
| **D11** | **Dados de teste antigos**: a BD de trabalho tem marcações com datas já passadas (ex.: 2026-09-23) | Correr primeiro **em seco** (contagem sem `UPDATE`) e confirmar o impacto antes de ligar.            |
|         | que **seriam alteradas em massa** na 1.ª execução.                                                 |                                                                                                      |
| **D12** | **`em_execucao`** (rota) e **`modo_urgencia`** (agendamento) não são usados por ninguém.           | Ignorar ambos; considerar limpeza separada (relatório de melhorias).                                 |

## 11. PLANO DE IMPLEMENTAÇÃO (por passos, quando decidires)

| #   | Passo                                                                                | Ficheiros                                                                       |
| :-- | :----------------------------------------------------------------------------------- | :------------------------------------------------------------------------------ |
| 1   | Criar `ReconciliationRepository` (4 `UPDATE` + 1 `countCandidates` para o modo seco) | `app/repositories/ReconciliationRepository.php`                                 |
| 2   | Criar `ReconciliationService` (ordem bottom-up + transação + guard 1x/pedido)        | `app/services/ReconciliationService.php`                                        |
| 3   | Ligar os hooks nos 5 pontos de leitura (§5)                                          | `BookingService` · `RotaService` · `EmployeeAgendaService` · `DashboardService` |
| 4   | (Opcional) endpoint `admin-reconcile` (gestor) + JS da página de agendamentos        | `api.php` · `AdminController` · `appointments.js`                               |
| 5   | ➕ Índices de suporte (M-1) — exige justificação de BD registada                     | `DataBase.sql` + spec §17.9                                                     |
| 6   | Registar as regras na especificação (§5 RN · §20 estados · §24)                      | `_dev/docs/spec/`                                                               |
| 7   | Testes: `functional_test.php` (R1–R4 com datas forjadas) e `http_test.php` (hook)    | `_dev/tests/` · `tests/`                                                        |

**Testes mínimos a prever** (datas forjadas nos dados de teste, nunca a hora real do PC):

| Cenário                                                   | Esperado                  |
| :-------------------------------------------------------- | :------------------------ |
| `confirmado` com `data_hora_pretendida` no passado        | → `cancelado`             |
| `executado` com fim + 4 h no passado                      | → `concluido`             |
| `executado` com fim + 4 h no futuro                       | inalterado                |
| `cancelado` / `concluido`                                 | inalterado (idempotência) |
| Rota com 1 filho `confirmado` e 1 `concluido`             | rota **não** conclui      |
| Rota com todos os filhos `concluido`                      | → `concluida`             |
| Rota sem filhos                                           | inalterada                |
| `obrigacao_fiscal` / `alerta_fiscal` com prazo no passado | **inalterados** (exceção) |
| 2.ª execução seguida da rotina                            | 0 linhas alteradas        |

## 12. RISCOS E MITIGAÇÃO

| Risco                                              | Mitigação                                                        |
| :------------------------------------------------- | :--------------------------------------------------------------- |
| Escrita em `GET` (efeito colateral em leitura)     | Só nas listagens privadas · `static` 1x/pedido · transação curta |
| Impacto em dados antigos de demonstração (**D11**) | Modo seco antes de ligar                                         |
| Custo se a BD crescer                              | Índices M-1 + `UPDATE` filtrado por estado (poucas linhas)       |
| Divergência de fuso PHP ↔ MySQL (**D9**)           | Fonte única (`NOW()`) + confirmar fuso do servidor               |
| Concluir uma rota errada                           | Guard «≥ 1 filho» + só rotas decididas (`aprovada`)              |
| Conflito com a spec §20 (estados)                  | Registar as regras na especificação (passo 6) antes de ativar    |

---

## 13. RESUMO PARA DECISÃO

1. **Se concordares** com todas as propostas de §10 (D1–D12), basta dizeres **"avança"** e implemento pelos
   passos de §11.
2. **Se alguma proposta estiver errada**, indica o número (**ex.: «D1 é a real, não a planeada»**) e ajusto.
3. **Antes de mexer no produto**, corro o **modo seco** (**D11**) e mostro-te o impacto na BD de trabalho.
4. **Não escrevi código** — nem PHP, nem alterações ao schema.
