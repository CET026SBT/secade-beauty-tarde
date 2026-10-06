# HISTÓRICO — O CUSTO FIXO DE 50 € NAS ROTAS

**Assunto:** o "custo operacional" de 50 € que existiu em `RotaService` e na especificação §12.2
**Contexto pedido:** os **dois** significados do mesmo 50 € e as regras revogadas que se ligam a ele
**Data:** 24/09/2026 · **Reconstruído com:** `_dev/docs/rules/build_history_on_demand.md`
**Natureza:** derivado do Git — **descartável** · **Âmbito:** `HEAD` (ruído de `--all` descartado)
**Revisões cobertas:** `e8c3139`..`263692f`

## 1. CRONOLOGIA

| Data       | Rev       | O que mudou                                                                     | Porquê (da mensagem de commit)                                         |
| :--------- | :-------- | :------------------------------------------------------------------------------ | :--------------------------------------------------------------------- |
| 2026-09-22 | `e8c3139` | Nascem **duas** constantes, ambas `50.0`: `FIXED_OPERATIONAL_COST` (L22) e      | Backoffice: agendamentos, rotas, servicos e aceitacao por funcionario  |
|            |           | `REFERENCE_PROFITABILITY` (L23)                                                 |                                                                        |
| 2026-09-22 | `2bceb81` | O mestre e as regras entram no controlo de versão                               | Agent workspace: documento-mestre, mapa de apoio, utilitarios e regras |
| 2026-09-22 | `8230698` | *(ruído)* os ficheiros do agente saem do índice — **não** foram apagados        | dev: remove do controlo de versao os ficheiros de trabalho do agente   |
| 2026-09-22 | `81f0007` | *(ruído)* voltam ao índice                                                      | agent-workspace: repor os ficheiros do agente removidos pelo merge     |
| 2026-09-24 | `66ebc90` | A auditoria regista o **F-06**: um 50 € com **dois** significados               | docs: adiciona o ficheiro da auditoria de artefactos                   |
| 2026-09-24 | `b242e49` | Relatório de limpeza assinala o histórico acumulado nos `.md`                   | docs: relatorio de auditoria de limpeza de obsoletos                   |
| 2026-09-24 | `0321ce7` | O mestre vira 2.0 e **perde os registos de revogados** (§3.13 e §5.2)           | docs: compacta as regras do projeto e remove historico                 |
| 2026-09-24 | `ef8f689` | **`FIXED_OPERATIONAL_COST` eliminada**; `rentabilidade = receita − combustível` | fix: remove o custo fixo de 50 euros das rotas                         |
| 2026-09-24 | `263692f` | Re-check da auditoria **documenta** a remoção (re-verificação)                  | docs: remove guia manual e mapa de fluxo                               |

**Registo de decisão de 24/09 (resposta do gestor, transcrita na auditoria):** *"este valor de 50€ deve ser
somente visual! (...) creio que os 50€ eram uma estimativa muito primordial como placeholder para esse
custo [intra-cidade]"*.

## 2. ESTADO ATUAL

**Onde vive hoje:** `app/services/RotaService.php` L22 — só **uma** constante sobreviveu:
`REFERENCE_PROFITABILITY = 50.0` (**indicador visual**, nunca gatilho). `rentabilidade` calcula-se em L79
e L159 (`receita − $fuelCost`).

| #   | Regra em vigor                                       | Fonte                            | Âncora |
| :-- | :--------------------------------------------------- | :------------------------------- | :----- |
| 1   | Decisão de rota é **manual e livre** (sem limiar)    | `_dev/docs/spec/booking.md`      | §12.3  |
| 2   | O 50 € é **apenas indicador visual**                 | `_dev/docs/spec/requirements.md` | RN-05  |
| 3   | Custo de rota = **combustível** (nada de custo fixo) | `_dev/docs/spec/booking.md`      | §12.2  |
| 4   | **Sem** alerta de custos por limiar                  | `_dev/docs/spec/backlog.md`      | §25.4  |
## 3. REVOGADO E SUBSTITUÍDO

| O que foi revogado                                                                            | Quando       | Rev       | Substituto                                            |
| :-------------------------------------------------------------------------------------------- | :----------- | :-------- | :---------------------------------------------------- |
| `FIXED_OPERATIONAL_COST = 50.0` somado ao custo (L80: `fuelCost + FIXED_OPERATIONAL_COST`)    | 2026-09-24   | `ef8f689` | combinação direta `receita − combustível`             |
| Campos de retorno `fixedCost` / `totalCost` (4 estruturas)                                    | 2026-09-24   | `ef8f689` | removidos; a página passa a mostrar **Combustível**   |
| Coluna *Custo total* em `routes.php` + `state.fixedCost` em `routes.js`                       | 2026-09-24   | `ef8f689` | coluna fora; `colspan` 9→8                            |
| ~~`RN05 (v1)`~~ *"Rentabilidade mínima de rotas: **100 €** (aprova/cancela automaticamente)"* | ≤ 2026-09-22 | `0321ce7` | decisão **manual**; o limiar automático nunca voltou  |
| *"Limiar automático de 100 €"* — conflito #1 de §3.13, vindo de `fluxo_funcionalidades.md`    | ≤ 2026-09-22 | `0321ce7` | idem                                                  |
| Estado `pendente_aprovacao_viabilidade`                                                       | ≤ 2026-09-22 | `0321ce7` | não existe; a rota é `planeada`/`aprovada`/`recusada` |

**Lacunas que a revogação deixou:** o **custo intra-cidade** — a `matriz_deslocacao` só cobre
**base → cidade**. Os 50 € tapavam essa ausência com um *placeholder*. Registado em `_dev/docs/spec/backlog.md`
**§25.3**, com as duas condições impostas na resposta: estimativa **real** e, se reutilizar o
geolocalização, **centralizá-lo** primeiro (padrão `apiClient.js`/`api.js`).

## 4. COMO RECUPERAR (comandos exatos, já executados)

```bash
# o código como estava ANTES de a constante morrer
git show ef8f689^:app/services/RotaService.php
#   → L22 FIXED_OPERATIONAL_COST = 50.0 | L80 $totalCost = fuelCost + FIXED_OPERATIONAL_COST
#   → L126/185/209/227 "fixedCost" | L210 "totalCost"

# o registo de revogados que o mestre perdeu na compactação
git show 0321ce7^:especificacao_mvp.md
#   → 1872 linhas; L303 "### 3.13 — Conflitos internos" (#1: limiar automático de 100 €)
#   → L451 "### 5.2 Regras **revogadas** (não implementar)" (~~RN05 (v1)~~ 100 €)

# rasto do identificador (atravessa a mudança de ficheiro)
git log -S 'FIXED_OPERATIONAL_COST' --date=short --pretty=format:'%h|%ad|%s' \
  -- 'app/services/RotaService.php' '*.md'
```

**Camada de origem:** o código nasceu na **camada 3** (`e8c3139`); os revogados dos 100 € vivem na
**camada 2** e estão **só no Git**.

## 5. LIMITES

- **Não recuperável:** a conversa de Teams onde o 100 € terá sido pedido — nunca entrou no repositório.
  O que existe é a sua **pegada** em `fluxo_funcionalidades.md` (apagado, camada 1).
- **Ruído descartado:** os commits que só movem ficheiros dentro/fora do índice (`8230698` · `81f0007`) e
  os 23 checkpoints automáticos que o `--all` traz (§4.2–4.3 da regra).
- **Não altera a especificação:** este documento é derivado; a autoridade continua em `_dev/docs/spec/`.
