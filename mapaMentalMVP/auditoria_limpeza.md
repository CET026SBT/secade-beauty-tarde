# AUDITORIA DE LIMPEZA — OBSOLETOS, PRAZOS PASSADOS E HISTÓRICO

**Ficheiro de trabalho (não normativo)** · branch **`agent-workspace`** · **24/09/2026**
**Âmbito:** `especificacao_mvp.md` · `.clinerules` · `mapaMentalMVP/` · `README.md` · `tests/README.md`

> ⚠️ **REGRA DE OURO RESPEITADA: nada foi apagado, alterado ou movido.** Este relatório é uma
> **proposta** de limpeza. Cada linha está marcada para **validação manual** antes de qualquer corte.

**Método:** varrimento por 3 eixos (referências temporais · marcadores de revogação/eliminação ·
notas embrionárias), seguido de leitura direta de cada zona sinalizada. Os números de linha são os
**atuais à data de 24/09/2026** (mudam se os ficheiros forem editados).

**Contexto do corte:** o MVP (Fases 1–5) está concluído e validado; o desenvolvimento está na
**Fase 6** (§24 — requisitos adicionais). Tudo o que só servia para *entregar o MVP* já cumpriu a
função.

---

## 1. RESUMO — 23 ACHADOS

| #    | Ficheiro / secção                   | Tipo                           | Veredicto                        |
| :--- | :---------------------------------- | :----------------------------- | :------------------------------- |
| A-01 | `README.md` L12                     | Prazo vencido                  | 🔴 **CORTAR**                    |
| A-02 | `README.md` L344                    | Prazo vencido                  | 🔴 **CORTAR**                    |
| A-03 | `README.md` L191 + L346             | Blocos de "entrega"            | 🟠 **CONDENSAR**                 |
| A-04 | `README.md` L368                    | Rodapé desatualizado           | 🔴 **CORRIGIR**                  |
| A-05 | `especificacao_mvp.md` L1870        | Rodapé desatualizado           | 🔴 **CORRIGIR**                  |
| A-06 | `especificacao_mvp.md` L2           | Data contraditória ao rodapé   | 🔴 **CORRIGIR**                  |
| A-07 | `guia_teste_manual.md` L3           | Data desatualizada             | 🟡 **CORRIGIR**                  |
| A-08 | `mapa_fluxo_dados.md` L3            | Data desatualizada             | 🟡 **CORRIGIR**                  |
| B-01 | `especificacao_mvp.md` §23          | Histórico técnico (66 linhas)  | 🟠 **CONDENSAR**                 |
| B-02 | `especificacao_mvp.md` L11-17       | Lista de ficheiros eliminados  | 🔴 **CORTAR** (duplica §29.2)    |
| B-03 | `especificacao_mvp.md` L1819-1826   | Comandos `git` de recuperação  | 🔴 **CORTAR**                    |
| B-04 | `especificacao_mvp.md` L1828-1829   | Nota sobre os `.pdf`           | 🟠 **CONDENSAR** (repetida)      |
| B-05 | `especificacao_mvp.md` §3.13        | Conflitos internos resolvidos  | 🟠 **CONDENSAR** (duplica §5.2)  |
| B-06 | `especificacao_mvp.md` L1777        | Data da consolidação           | 🟡 **CORTAR** (data)             |
| B-07 | `mapa_fluxo_dados.md` §14.2         | Regras revogadas repetidas     | 🟠 **CONDENSAR**                 |
| B-08 | `README.md` L331-333                | Nota sobre cronograma revogado | 🟡 **CORTAR**                    |
| C-01 | `especificacao_mvp.md` §5.2         | Regras revogadas               | ⚪ **MANTER** (protegido)        |
| D-01 | `especificacao_mvp.md` §3.12        | Nota embrionária transversal   | 🔴 **CORTAR**                    |
| E-01 | `mapa_fluxo_dados.md` (5 sítios)    | Datas em diagramas de exemplo  | ⚪ **ACEITÁVEL**                 |
| F-01 | `mensagem_teams.txt`                | Perguntas já respondidas       | 🟠 **PODAR**                     |
| F-02 | `analise_backoffice_gestor.md` §2.0 | Estado das iterações           | 🟡 **MANTER** (rastreio)         |
| F-03 | `auditoria_artefactos.md` §3        | Atestado de verificação        | ⚪ **MANTER** (evita retrabalho) |
| G-01 | `.clinerules` (todo)                | —                              | ✅ **LIMPO**                     |

---

## 2. EIXO A — PRAZOS PASSADOS E DATAS DESATUALIZADAS

> **Hoje é 24/09/2026.** O prazo académico era **21/09/2026** e o projeto continua na Fase 6. Tudo o
> que anuncia "entrega" ou fixa a data de 21/09 é **resíduo do ciclo anterior**.

### A-01 · `README.md` L12 — prazo vencido no cabeçalho

```markdown
## Projeto Académico | Entrega: 21/09/2026
```

**Porque já não é relevante:** o prazo está **vencido** há 3 dias e o projeto passou a Fase 6
(desenvolvimento ativo). Um leitor novo conclui que o projeto terminou. A data não acrescenta nada —
o estado real está na tabela de fases (L335-342).
**Proposta:** remover a linha ou substituir por `## Projeto Académico — Fase 6`.

### A-02 · `README.md` L344 — prazo vencido no roadmap

```markdown
**Data de entrega:** 21/09/2026
```

**Porque já não é relevante:** idem A-01, e está **dentro** da secção de roadmap, a seguir à fase 6
"⬜ a iniciar" — o que se contradiz: um roadmap em curso não tem data de entrega no passado.
**Proposta:** cortar a linha.

### A-03 · `README.md` L191 e L346 — blocos de "entrega" do MVP

```markdown
### ✅ MVP Completo (entrega)          ← L191
### 🎯 Funcionalidades OBRIGATÓRIAS para Entrega   ← L346
```

**Porque já não é relevante:** são **duas listas** de funcionalidades já entregues (L193-211 e
L347-350), sobrepostas entre si e com a tabela de fases (L335-342) e com a §21.1 da especificação.
Três listas a dizer o mesmo é o risco que §29.3.7 quer eliminar.
**Proposta:** manter **uma** (a tabela de fases) e cortar os dois blocos, ou reduzir a L346-350 a
2 linhas de resumo.

### A-04 · `README.md` L368 — rodapé com data e estado errados

```markdown
**Versão:** 2.0 | **Data:** 21/09/2026 | **Status:** 🟢 MVP COMPLETO E TESTADO
```

**Porque já não é relevante:** o `README.md` foi **alterado hoje** (deduplicação de convenções) e o
estado real é "MVP entregue, Fase 6 por iniciar" — declarar "COMPLETO" contradiz a própria tabela de
fases (L342: `⬜ a iniciar`).
**Proposta:** atualizar data e estado (não cortar).

### A-05 · `especificacao_mvp.md` L1870-1871 — rodapé do documento-mestre

```markdown
**Versão:** 1.1 · **Data:** 21/09/2026 · **Estado:** ✅ MVP implementado e validado (289 verificações) ·
⬜ Fase 6 (requisitos adicionais — §24) por iniciar
```

**Porque já não é relevante:** a `Versão 1.1` e a data **21/09** deixaram de corresponder ao
conteúdo: desde então o documento recebeu §1.12 (Módulo F), §2.5.1, §2.8.2, §29.2-§29.4 reescritos e
a deduplicação de hoje. O rodapé é a **primeira coisa** que um auditor lê para datar o documento —
está a mentir.
**Proposta:** atualizar `Versão` e `Data` (hoje: 24/09/2026).

### A-06 · `especificacao_mvp.md` L2 — data contraditória

```markdown
**Documento-mestre (single source of truth)** · Versão 1.1 · 22/09/2026 · branch **`agent-workspace`**
```

**Porque já não é relevante:** o cabeçalho diz **22/09** e o rodapé diz **21/09** — duas datas
diferentes no mesmo documento, ambas obsoletas.
**Proposta:** alinhar cabeçalho e rodapé numa única data.

### A-07 · `guia_teste_manual.md` L3 — data antiga

```markdown
**Data:** 21/09/2026 · **Versão do guia:** 1.0
```

**Porque já não é relevante:** o guia já foi revisto (contém a nota dos lembretes ao cliente e
referências a §23/§22.1 atualizadas hoje). A data sugere um guia não revisto.
**Proposta:** atualizar a data (o conteúdo merece `1.1`).

### A-08 · `mapa_fluxo_dados.md` L3 — data antiga

```markdown
Data: 21/09/2026 · Comprovar com: `guia_teste_manual.md`
```

**Porque já não é relevante:** idem A-07 — o mapa foi tocado hoje (referências `.clinerules`).
**Proposta:** atualizar a data ou **remover a data** e deixar só a remissão para o guia (a data de um
documento de apoio não acrescenta informação — o Git tem-na).

---

## 3. EIXO B — HISTÓRICO TÉCNICO

### B-01 · `especificacao_mvp.md` §23 (L1352-1417) — 66 linhas de defeitos corrigidos

| Subsecção | Linhas     | Conteúdo                                                   |
| :-------- | :--------- | :--------------------------------------------------------- |
| §23.1     | L1356-1375 | 10 defeitos críticos (colunas "Impacto" e "Correção")      |
| §23.2     | L1377-1389 | 4 defeitos do registo + "colaterais"                       |
| §23.3     | L1391-1398 | 5 defeitos menores                                         |
| §23.4     | L1400-1415 | Auditoria da remoção de `funcionario_categoria` (6 linhas) |

**Porque já não é relevante:** o próprio título assume o estatuto — *"HISTÓRICO TÉCNICO"*, com a
justificação *"para memória futura e para evitar reintrodução"*. Essa função **já é cumprida por
outro mecanismo**: os defeitos foram corrigidos em código hoje coberto pelas **289 verificações** —
se algum regredir, o teste falha antes de chegar a produção. O texto é um segundo guarda, mais fraco
(ninguém o lê antes de commitar) e mais caro (66 linhas mantidas).
**Proposta:** cortar §23.1–§23.3 na íntegra e **manter §23.4** (ou um resumo) — é a única que explica
o *porquê* de uma decisão de schema vigente (v3 sem `funcionario_categoria`).

### B-02 · `especificacao_mvp.md` L11-17 — lista de ficheiros eliminados

```markdown
> **Fontes consolidadas** (ficheiros **entretanto eliminados** na consolidação documental — mapa em §29.2):
> `planeamento_geral.md` · `rectificacoes.md` · `fluxo_funcionalidades.md` · `CARRINHA_SPEC.md` ·
> `plano_desenvolvimento.md` · `ALTERACOES_PRIORIDADES.md` · `duvidas_planeamento.md` ·
> `relatorio_alteracoes.md` · `tecnologias_projeto.md` · `LOGIN_PROFILE_TODO.md` ·
> `README.md` · `relatorio_implementacao.md`
```

**Porque já não é relevante:** são 13 ficheiros que **já não existem** — nenhum leitor os pode abrir.
A lista completa **já está** em §29.2 (L1800-1811), com papel e destino de cada um. Aqui é uma
segunda cópia, e no **topo do documento**, onde ocupa o espaço de abertura com lixo.
**Proposta:** cortar as L12-17 e deixar uma linha: `> Fontes consolidadas — mapa em §29.2.`

### B-03 · `especificacao_mvp.md` L1819-1826 — comandos `git` para ressuscitar ficheiros

```powershell
git show HEAD:<ficheiro>          # ver o conteúdo original
git checkout HEAD -- <ficheiro>   # restaurar o ficheiro
# depois de a remoção ser commitada, usar a revisão anterior:
git log --oneline --diff-filter=D -- "*.md"   # localizar a revisão
git show <revisão>^:<ficheiro>                # ver
git checkout <revisão>^ -- <ficheiro>         # restaurar
```

**Porque já não é relevante:** é um **manual de recuperação** de documentos de planeamento v1
(`plano_desenvolvimento.md`, `ALTERACOES_PRIORIDADES.md`, …) eliminados por estarem consolidados
neste documento. Ninguém os vai restaurar: o conteúdo está aqui e na §5.2.
**Proposta:** cortar o bloco e, para manter a rastreabilidade, uma linha:
*"recuperáveis pelo histórico do Git (`git log --diff-filter=D`)"*.

### B-04 · `especificacao_mvp.md` L1828-1829 — nota sobre os `.pdf`

**Porque já não é relevante:** a nota está **repetida em três sítios** — §3 (L133-136), aqui (§29.2) e
no rodapé (L1872). Um dos três chega.
**Proposta:** manter a do §3 e cortar esta.

### B-05 · `especificacao_mvp.md` §3.13 (L303-326) — 11 conflitos internos resolvidos

**Porque já não é relevante:** a tabela cruza os **mesmos 11 itens** que §5.2 (L451-468) já lista
como "regras revogadas", e cita ficheiros que foram eliminados. A diferença é só a forma: §5.2 diz
*"não implementar"*, §3.13 diz *"de onde vinha o conflito"*. Itens como *"Repositories sem JOINs"*
(L322) e *"Rotas sem prazo-limite"* (L324) continuam a induzir erro em quem lê §3.13 fora de contexto
— na auditoria de hoje o item 8 exigiu verificação extra para se perceber que a regra estava
**revista** em §18.2.
**Proposta:** cortar a tabela e manter 1-2 linhas de nota (conflitos PDF ↔ `.md` resolvidos em
§3.1–§3.11; internos em §5.2).

### B-06 · `especificacao_mvp.md` L1777 — data da consolidação documental

```markdown
> **Consolidação documental (21/09/2026).** Para eliminar redundância e o risco de divergência, toda
```

**Porque já não é relevante:** a data não ajuda — o que interessa é *"os ficheiros antigos foram
eliminados, veja-se a tabela"*. É mais um foco de desatualização (ver A-05/A-06).
**Proposta:** cortar a data e o parêntese.

### B-07 · `mapa_fluxo_dados.md` §14.2 + 3 repetições — regras revogadas em 4 sítios

| Sítio           | Linhas     | Forma                                                           |
| :-------------- | :--------- | :-------------------------------------------------------------- |
| §14.2 (tabela)  | L1776-1785 | 6 regras revogadas com substituto                               |
| §7.1 (diagrama) | L769-770   | `❌ REVOGADO: algoritmo automático com limiar de 100 €`         |
| §7 (prova)      | L871-873   | *"Se existisse um algoritmo automático (como o revogado…)"*     |
| §16 (conclusão) | L1917-1918 | *"As regras REVOGADAS estão explicitamente marcadas (secção…)"* |

**Porque já não é relevante:** 4 repetições do mesmo conteúdo dentro de **um** ficheiro. A tabela
§14.2 é a fonte; as outras três são reforço narrativo de um conflito que já não está vivo.
⚠️ **Ressalva:** L871-873 tem valor de **prova do comportamento manual** (2 cenários com resultado) —
cortar apenas a frase sobre o algoritmo revogado, **não** os cenários.
**Proposta:** manter §14.2 e cortar as 3 repetições (ou reduzir a remissões *"ver §14.2"*).

### B-08 · `README.md` L331-333 — nota sobre o cronograma revogado

```markdown
> O cronograma de 7 dias do planeamento inicial foi **revogado** (continha o algoritmo automático de
> 100 €, entretanto substituído por decisão manual). Detalhe em `especificacao_mvp.md` §21
> (branch `agent-workspace`).
```

**Porque já não é relevante:** explica a **ausência** de algo que já não existe em ficheiro nenhum —
o `plano_desenvolvimento.md` foi eliminado. Informação útil para quem viveu o problema; ruído para
quem chega agora (a tabela de fases, logo abaixo, é autoexplicativa).
**Proposta:** cortar as L331-332 e manter só a remissão para §21.

---

## 4. EIXO C — REGRAS DE NEGÓCIO REVOGADAS

### C-01 · `especificacao_mvp.md` §5.2 (L451-468) — ⚪ **MANTER** (protegido por regra própria)

```markdown
### 5.2 Regras **revogadas** (não implementar)
| ID (antigo)   | Regra revogada                                       | Substituto |
| ~~RN04 (v1)~~ | "Funcionário deve cobrir todas as categorias..."     | RN-04      |
| ~~RN05 (v1)~~ | "Rentabilidade mínima de rotas: 100 € (aprova/cancela automaticamente)" | RN-05 |
... (18 linhas: sinal 50 %, 3 carrinhas, JOINs proibidos, cronograma de 7 dias, etc.)
```

**Porque parece lixo:** é uma tabela de regras que **não se devem implementar** — à primeira vista,
o candidato mais óbvio a corte deste relatório.

**Porque NÃO deve ser cortada:** a §29.3.4 deste mesmo documento **exige-a**:
> *"Nada é removido em silêncio: regras revogadas são movidas para §5.2 (com o substituto), para
> preservar o histórico de decisões."*

Cortar §5.2 obrigaria a revogar §29.3.4 e a perder a defesa contra reintrodução do algoritmo de
100 € — o erro mais caro do projeto. **Veredicto: manter integralmente.**

**Alternativa, se o volume incomodar:** mover §5.2 para um anexo `mapaMentalMVP/regras_revogadas.md`
e deixar aqui um ponteiro. Só faz sentido se §5.2 continuar a crescer.

**Nota de coerência:** se §5.2 se mantém, então B-05 (§3.13) **deve** sair — é a duplicação dela.

---

## 5. EIXO D — NOTAS EMBRIONÁRIAS (reuniões/esclarecimentos)

### D-01 · `especificacao_mvp.md` §3.12 (L290-301) — "Reforços transversais" 🔴

```markdown
### 3.12 — Reforços transversais (dos esclarecimentos de retificações)
- **AdminLTE:** ignorar (reforço de D-06).
- **Módulos abrangentes do dashboard:** definir bem neste documento, mas **partir do que já existe**…
- **Configuração do sinal:** deve existir uma **secção no backoffice** … (§24.5).
- **Cancelamento pelo cliente:** confirmar se existe; **deve existir** (§3.11 / §24.6).
- **Catálogo multimédia:** … **página de detalhes** com **carousel** … (reforço de D-06 / §24.2).
```

**Porque já não é relevante:** é a **nota de uma reunião de esclarecimento** ("dos esclarecimentos de
retificações"), escrita numa fase em que as decisões ainda não estavam fixadas. Hoje:

| Item da §3.12         | Onde está resolvido agora                         |
| :-------------------- | :------------------------------------------------ |
| AdminLTE ignorar      | resolvido em D-06 (§3.6) — o reforço é redundante |
| Módulos do dashboard  | substituído por §25.5 (estrutura de menus) + §21  |
| Configuração do sinal | é o requisito **RF-25** (§4.2) e a §24.5          |
| Cancelamento cliente  | é o requisito **RF-12** (§4.1) e a §24.6          |
| Catálogo multimédia   | é o requisito **RF-07** (§4.1) e a §24.2          |

Os cinco itens estão **promovidos a requisitos numerados** — a nota embrionária perdeu função.
**Proposta:** 🔴 cortar as 12 linhas na íntegra. É o achado com melhor relação
*benefício/risco* deste relatório: nada se perde (tudo está em `RF-nn`) e sai uma secção inteira do §3.

---

## 6. EIXO E — DATAS EM EXEMPLOS (aceitável, mas registado)

### E-01 · `mapa_fluxo_dados.md` — datas dentro de diagramas ⚪

| Linha | Texto                                      | Natureza                           |
| :---- | :----------------------------------------- | :--------------------------------- |
| L795  | `🟢 Évora · 22/09/2026 · 3 agendamentos`   | Exemplo de ecrã (data ilustrativa) |
| L927  | `🔴 IVA Trimestral · prazo 15/07/2026`     | Exemplo de alerta fiscal           |
| L931  | `🟠 Segurança Social · prazo 20/10/2026`   | Exemplo de alerta fiscal           |
| L952  | `✅ IVA Trimestral ... PAGO em 16/07/2026` | Exemplo de estado pago             |
| L1124 | `— João Cliente · Barba · 21/09/2026`      | Exemplo de linha de agendamento    |

**Veredicto: ⚪ ACEITÁVEL — não cortar.** São **dados de exemplo** em diagramas ilustrativos (mostram
o formato de apresentação, não um prazo do projeto). Confundi-los com prazos seria falso positivo.
**Nota menor:** L795 e L1124 usam datas que já passaram — se o mapa for usado numa demonstração ao
vivo, vale a pena atualizar as datas dos exemplos para o mês corrente.

---

## 7. EIXO F — FICHEIROS NÃO NORMATIVOS

### F-01 · `mapaMentalMVP/mensagem_teams.txt` — 🟠 podar perguntas já respondidas

**Estado:** 281 linhas, "3.ª versão — 23/09/2026", com **40 perguntas** acumuladas ao longo de 3
iterações e várias respostas já registadas no próprio ficheiro.

**Porque parte é lixo:** as perguntas foram sendo **acrescentadas**, nunca podadas — e há duplicação
de assunto entre iterações (ex.: a 2.ª redação da pergunta 17 e a 18/19 tratavam o mesmo tema dos
avisos, o que já obrigou a uma nota de correção).
**Proposta:** numa passagem de limpeza, remover as perguntas cuja resposta **já está na
`especificacao_mvp.md`** (ou marcar cada uma como `✅ respondida em §X`) — o ficheiro deve conter só o
que **ainda falta perguntar** ao cliente. Não cortar o que continua em aberto.

### F-02 · `analise_backoffice_gestor.md` §2.0 (L694-718) — 🟡 manter

```markdown
### 2.0 Estado das dúvidas das iterações anteriores (o que esta iteração fechou)
| **Q-01** — os preços são com IVA? | ✅ RESOLVIDA por verificação (§E.2)…
| **Q-41 / pergunta 17 (Teams)** — o cliente recebe lembretes? | ✅ JÁ DECIDIDO na especificação…
```

**Porque parece lixo:** é um registo de "o que esta iteração fechou" — histórico de iterações.
**Porque se mantém:** tem **função de rastreio** (evita que a mesma dúvida seja reaberta uma 3.ª vez
— foi exatamente o que aconteceu com Q-41) e o ficheiro é **não normativo** e de apoio à decisão.
**Proposta:** manter. Se o ficheiro crescer muito, mover as iterações fechadas para um anexo.

### F-03 · `auditoria_artefactos.md` §3 (L119-162) — ⚪ manter

**Porque parece lixo:** uma tabela de 25 linhas a atestar que coisas **estão certas** (35 serviços, 24
tabelas, `servico_foto` dormente, 105/65 testes…).
**Porque se mantém:** é uma **caderneta de "não voltar a rever"** — evita re-verificações caras em
rondas futuras. Foi escrita hoje e é o oposto de obsoleto.
**Proposta:** manter, e **datá-la** (já tem "medido hoje") para se saber quando expira.

---

## 8. GUARDA-CORPOS — O QUE **NÃO** DEVE SER CORTADO

> Esta secção existe para evitar o erro inverso: cortar informação que **parece** histórica mas é
> vinculativa. Cada linha foi verificada.

| Elemento                                          | Porque parece obsoleto            | Porque se mantém                                          |
| :------------------------------------------------ | :-------------------------------- | :-------------------------------------------------------- |
| `especificacao_mvp.md` **§5.2** (revogadas)       | regras que não se implementam     | exigido por **§29.3.4** (ver C-01)                        |
| `especificacao_mvp.md` **§22.2 / §22.3**          | parecem "desculpas" de limitações | **limitações declaradas** que explicam 403, 0 €, `/admin` |
| `especificacao_mvp.md` **§24 / §25 / §28.2**      | parecem "trabalho antigo"         | é o **trabalho em curso** (Fase 6) — o motor do roadmap   |
| `especificacao_mvp.md` **§3.1–§3.11 (D-01…D-11)** | parecem atas de reunião           | são as **decisões vigentes** de produto                   |
| `especificacao_mvp.md` **§23.4**                  | histórico da v3.0                 | explica o **schema atual** (sem `funcionario_categoria`)  |
| `especificacao_mvp.md` **§17.7**                  | fala de `DataBase.sql` (v1)       | evita que alguém importe o dump **errado**                |
| `mapa_fluxo_dados.md` §14.2                       | tabela de regras revogadas        | é a fonte única dessas regras (B-07)                      |
| `mapa_fluxo_dados.md` §15                         | repete a §18.6                    | é **ferramenta de verificação** manual (não normativa)    |
| `auditoria_artefactos.md` §3                      | atesta coisas que já estão certas | evita **re-verificação** cara (F-03)                      |
| `.clinerules` (todo)                              | foi reescrito hoje                | ✅ **já limpo** — sem prazos nem histórico (G-01)         |

### G-01 · `.clinerules` — verificação de limpeza ✅

Varrimento para prazos, datas e revogações: **0 achados**. O ficheiro foi reescrito hoje e o antigo
prazo de 7 dias, o exemplo em português e a regra de JOINs desatualizada **já tinham sido removidos**
na v2.0. Único elemento temporal: `**Data:** 24/09/2026` no rodapé — **correto**.

---

## 9. IMPACTO ESTIMADO DA LIMPEZA

| Ação                         | Ficheiro               | Linhas a remover |
| :--------------------------- | :--------------------- | :--------------- |
| B-01 (§23.1–§23.3)           | `especificacao_mvp.md` | ~45              |
| B-05 (§3.13)                 | `especificacao_mvp.md` | ~24              |
| D-01 (§3.12)                 | `especificacao_mvp.md` | ~12              |
| B-02, B-03, B-04, B-06       | `especificacao_mvp.md` | ~18              |
| A-03, A-04, B-08 (e A-01/02) | `README.md`            | ~20              |
| B-07 (3 repetições)          | `mapa_fluxo_dados.md`  | ~6               |
| **Total estimado**           | —                      | **~125 linhas**  |

**Ganho:** menos ~125 linhas de ruído (≈ 6 % do documento-mestre) e, mais importante, **menos
falsos-positivos em auditorias futuras** — §3.13 e §5.2 a dizer o mesmo foi o que já obrigou a uma
verificação extra hoje.

---

## 10. ORDEM DE EXECUÇÃO PROPOSTA (após validação manual)

1. **Correções de data** (A-04, A-05, A-06, A-07) — risco zero, resolvem a incoerência
   cabeçalho↔rodapé.
2. **`README.md`** — cortar A-01, A-02 e B-08; decidir A-03 (duas listas de entrega sobrepostas).
3. **`especificacao_mvp.md`** — cortar D-01 (§3.12), depois B-05 (§3.13), depois B-01 (§23.1–§23.3),
   por fim B-02/B-03/B-04/B-06.
4. **`mapa_fluxo_dados.md`** — B-07 (manter §14.2, cortar 3 repetições) + decidir E-01.
5. **`mensagem_teams.txt`** — F-01 (podar respondidas) — pode ficar para quando o cliente responder.
6. **Fechar o ciclo** (§29.3.6): correr o pipeline `.md` + `php tools/health-check.php` e confirmar
   que o `md-verify` continua **`TUDO OK`** — as remoções mexem em referências `§NN`.

> ⚠️ **Atenção às âncoras:** cortar §23.1–§23.3 **mantendo** §23.4 obriga a decidir a numeração.
> Preferir deixar §23.4 com o número 23 (ou registar a renomeação em §29.2) para não partir
> referências. O `md-verify` valida ao nível do **capítulo**: uma subsecção partida **não** é
> detetada automaticamente.

---

## 11. CHECKLIST DE VALIDAÇÃO (preencher antes de cortar)

| #    | Achado                    | Cortar? | Decisão |
| :--- | :------------------------ | :-----: | :------ |
| A-01 | Prazo no cabeçalho        | [ ]     |         |
| A-02 | Data de entrega           | [ ]     |         |
| A-03 | Blocos de entrega         | [ ]     |         |
| A-04 | Rodapé `README.md`        | [ ]     |         |
| A-05 | Rodapé da especificação   | [ ]     |         |
| A-06 | Data contraditória (L2)   | [ ]     |         |
| A-07 | Data do guia de teste     | [ ]     |         |
| A-08 | Data do mapa de fluxo     | [ ]     |         |
| B-01 | §23.1–§23.3 (defeitos)    | [ ]     |         |
| B-02 | Lista de eliminados (L11) | [ ]     |         |
| B-03 | Comandos `git` (§29.2)    | [ ]     |         |
| B-04 | Nota dos `.pdf` (3.ª vez) | [ ]     |         |
| B-05 | §3.13 (conflitos)         | [ ]     |         |
| B-06 | Data da consolidação      | [ ]     |         |
| B-07 | Regras revogadas (4×)     | [ ]     |         |
| B-08 | Nota do cronograma        | [ ]     |         |
| C-01 | §5.2 — **protegida**      | n/a     | manter  |
| D-01 | §3.12 (embrionária)       | [ ]     |         |
| E-01 | Datas em exemplos         | n/a     | manter  |
| F-01 | Perguntas respondidas     | [ ]     |         |

---

**Ficheiro:** `mapaMentalMVP/auditoria_limpeza.md` · **branch:** `agent-workspace` · **24/09/2026** ·
**Método:** varrimento por 3 eixos + leitura direta de cada zona sinalizada ·
**Regra de ouro:** **nada foi apagado**; **23 achados propostos**, 6 deles protegidos por regra própria ·
**Nota:** documento de apoio à decisão, **não normativo**; a autoridade é `especificacao_mvp.md`.
