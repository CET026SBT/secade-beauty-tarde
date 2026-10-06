# DECISÕES — ATUALIZAÇÃO 28/09/2026 (documento **LOCAL**, não versionado)

> **Só neste computador.** `_dev/` está em `.git/info/exclude` → este ficheiro **não é versionado nem
> publicado**; **não** usar `git add -f` nele. Complementa o dossier
> `_dev/docs/out/relatorio_decisoes-pendentes.md` (26/09), que tem as propostas **P-01…P-23**.
> **Natureza:** apoio à decisão (não normativo). **Autoridade:** `_dev/docs/spec/`.

## 1. Onde estão as 36 perguntas, em detalhe

| Queres ver                                        | Ficheiro                                            | Onde                                              |
| :------------------------------------------------ | :-------------------------------------------------- | :------------------------------------------------ |
| As 36 perguntas **como foram enviadas**           | `_dev/mapaMentalMVP/mensagem_teams.txt`             | §1 estrutura do backoffice · §3 dúvidas           |
| As 36 **com referências** e o mapa `Q-nn`         | `_dev/mapaMentalMVP/mensagem_teams_referencias.txt` | tabela final (pergunta → `Q-nn` → ficheiro:linha) |
| O **catálogo completo** das dúvidas (`Q-01…Q-66`) | `_dev/mapaMentalMVP/analise_backoffice_gestor.md`   | §2 (2.1 a 2.10) · §1.12 (Módulo F)                |
| **Onde cada pergunta foi decidida**               | `_dev/docs/out/relatorio_decisoes-pendentes.md`     | **§3.1** (36 perguntas → `P-nn`)                  |
| Os **conflitos** e o destino de cada um           | idem                                                | **§3.2** (`C-nn` → decisão)                       |
| A **proposta** de cada decisão                    | idem                                                | **§2** (`P-01` … `P-23`)                          |
| As **decisões pendentes** (checklist)             | `analise_backoffice_gestor.md`                      | **§3.1 checklist** (37 itens, `C-nn`)             |

**Abrir no ponto exato** (ferramenta do clone): `git ref-open Q-25` · `git ref-open P-05` ·
`git ref-open C-26`. Voltar atrás: `git ref-back` / `git ref-forward`.

## 2. Decidido em 28/09/2026 (já refletido na especificação)

| Tema                  | Decisão                                                                                                                            | Onde ficou                              |
| :-------------------- | :--------------------------------------------------------------------------------------------------------------------------------- | :-------------------------------------- |
| Forma de apresentação | **Gráficos** nos ecrãs contabilísticos/financeiros (Chart.js **local** em `modules/common/lib/chartjs`); **tabelas e calendários** | `core.md` §1.2 (E-3) · §3.13 (**D-13**) |
|                       | na operação                                                                                                                        |                                         |
| Entrada do backoffice | `/gestao` = **dashboard do gestor**; funcionário entra na **agenda**; cada página = 1 contexto/perfil                              | `core.md` §3.14 (**D-14**)              |
| Notificações          | Sino usa `alerta_fiscal` (leitura **global**); lembretes não fiscais exigem ➕ `notificacao`; página de avisos por perfil          | `core.md` §3.15 (**D-15**) · **RF-81**  |
| Painel/agenda         | **RF-77** (dashboard + gráficos + sininho) · **RF-78** (agenda só com rotas confirmadas) · **RF-79** (gráficos no financeiro)      | `requirements.md` §4.7                  |
| Rotas                 | **RN-31** (rota só com **todos** os serviços aceites) · **RN-33** (agenda só `confirmado`) · **RN-32** (saem da lista quando       | `requirements.md` §5.1                  |
|                       | entram em rota confirmada)                                                                                                         |                                         |
| Reverter/excluir      | Só **antes** de a rota ser confirmada; volta a *qualificado*, **nunca** `cancelado`; em rota confirmada **não se altera**          | **RN-34** · **RF-80**                   |
| Botões de ação        | Ícone + `title`, fundo transparente; dourado=detalhes · azul=editar · vermelho=remover; **retrofit** a todas as listagens          | §24.7 (item 8)                          |
| **Promoções**         | **Fora da Fase 6** → **Fase 7**; risco de **retro-atualização** de histórico a avaliar                                             | `delivery.md` §21 · C-33                |
| Fases                 | Fase 6 = §24 + módulos do backoffice + **sidebar** + **comissões**; **Fase 7** = promoções                                         | `delivery.md` §21                       |

## 3. O que falta decidir — propostas (bloqueiam a Fase 6)

| #   | Tema                                       | Minha proposta                                                                  | Onde está           | O que preciso de ti   |
| :-- | :----------------------------------------- | :------------------------------------------------------------------------------ | :------------------ | :-------------------- |
| 1   | **IVA** (onde vive a taxa)                 | BD fica **líquida**; bruto calculado; taxa em configuração                      | **P-10** · C-17     | Sim/não               |
| 2   | **Âmbito da contabilidade**                | 1.º ecrã (resumo + rácios) na Fase 6; restante por marcos                       | **P-12** · C-29     | Sim/não               |
| 3   | **Fonte de cada número**                   | Receita **sempre interna**; ficheiro externo só para o que o sistema não produz | **P-13** · C-20     | Sim/não               |
| 4   | **RH** (custo de pessoal)                  | Σ `valor_recibo_verde_funcionario` + `salario_base`; **sem** novo lançamento    | **P-14** · C-10     | Sim/não               |
| 5   | **Rácios/dívidas**                         | Ler do mapa de células; **não** recalcular                                      | **P-15** · C-27     | Sim/não               |
| 6   | **Fiscal** (IVA apurado vs obrigação, IRC) | IVA da obrigação ≠ apurado; IRC **configurável**                                | **P-20** · C-14     | Confirmar taxa        |
| 7   | **Cancelamento / 24 h / lembrete**         | Entram (Tier 1)                                                                 | **P-02** … **P-04** | Sim/não               |
| 8   | **Sinal + 90 %/método**                    | Entra; configuração generaliza `/gestao/recibos-verdes`                         | **P-05**            | Sim/não               |
| 9   | **Multicidades**                           | **Alerta visual** + decisão manual (coerente com D-01)                          | **P-07**            | Sim/não               |
| 10  | **Painel: conteúdo**                       | KPIs no topo + gráficos por baixo + sininho                                     | **P-09** · RF-77    | **Imagem do exemplo** |

> Os números de esforço e a alternativa de cada proposta estão no dossier (§2). Nenhuma destas decisões
> exige o grupo de contabilidade: a base são os **ficheiros do cliente** (já no repositório) e a
> especificação.

## 4. O que preciso que me forneças

1. **Imagem do dashboard de exemplo** (outro projeto) — para fixar o layout dos KPIs e dos gráficos.
2. **Sim/não** às propostas do §3 (basta o número + resposta; o resto fica registado por mim).
3. **Valores que só tu tens**: taxa de **IRC** para a simulação, **IVA** (taxa e regime) e **dados de RH**
   (salário base, subsídios) se os quiseres no cálculo.
4. **Decisão de confidencialidade** (ver §5).

## 5. Confidencialidade — estado atual e opções

**Já publicado no remoto** (branch `agent-workspace`, que **nunca** é integrada em `dev`/`main`):
`_dev/docs/out/relatorio_decisoes-pendentes.md` (contém valores do ficheiro do cliente),
`_dev/docs/out/relatorio_mensagem-teams.md` e `_dev/mapaMentalMVP/*` (inclui o `.xlsx` e o `.docx`).

| #   | O que fazer                                                | Efeito                                                                               |
| :-- | :--------------------------------------------------------- | :----------------------------------------------------------------------------------- |
| A   | **Nada** — a branch `agent-workspace` fica fora do produto | Mais simples; o conteúdo continua no remoto                                          |
| B   | `git rm --cached` dos ficheiros sensíveis (ficam no disco) | Deixa de ser publicado **daqui para a frente**; o **histórico** já enviado mantém-se |
| C   | Reescrever o histórico da branch e forçar push             | Remove do remoto, mas reescreve commits (só se for mesmo necessário)                 |

**A partir de agora:** tudo o que eu produzir com matéria sensível fica em `_dev/docs/out/*.md`
(**ignorado pelo Git** — este ficheiro é o exemplo) e **sem** `git add -f`.

---

**Versão:** 1 · **Data:** 28/09/2026 · **Estado:** apoio à decisão, local, não normativo
