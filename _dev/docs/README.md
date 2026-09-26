# DOCS — MAPA E REGRAS DE DOCUMENTAÇÃO (on-demand)

**Para quem é:** para o agente. Não é documentação de produto — é o **controlo de quando e como** se gera
documentação. Ler **apenas quando** o humano pedir documentação, mapa, relatório ou auditoria.

## DOUTRINA (vale para todo o `_dev/docs/`)

1. **Sob demanda.** Nada é criado, atualizado ou reescrito sem pedido explícito. Sem pedido → não mexer.
2. **Zero redundância.** Cada facto tem **um** sítio. Documento novo que repita outro conteúdo é erro:
   apontar, não copiar.
3. **Normativo vs apoio.** A autoridade é `especificacao_mvp.md` + `_dev/docs/spec/`. Tudo o que se gere em
   `_dev/docs/rules/` é **apoio** — nunca contraria nem substitui a especificação.
4. **Hierarquia de leitura.** N1 `.clinerules` (sempre) → N2 spec por domínio (um ficheiro de cada vez)
   → N3 `_dev/docs/` (só por pedido). Nunca carregar os três níveis por rotina.
5. **Ficheiros de controlo são densos; o output é legível.** Estes `.md` de regras são telegráficos de
   propósito. O documento que produzem segue o molde de `_dev/docs/templates/` e passa pelo pipeline.
6. **Limite de linhas — dois regimes, sem meio-termo.** O que é **mantido** é enxuto porque é lido a
   cada sessão e é normativo: **máx. 400 linhas**. O que é **gerado sob demanda** é lido por humanos e
   vale pelo detalhe: **sem limite**.

| Regime        | O que é                                                                                  | Limite       | Porquê                                                               |
| :------------ | :--------------------------------------------------------------------------------------- | :----------- | :------------------------------------------------------------------- |
| **MANTIDO**   | `.clinerules` · `especificacao_mvp.md` · `_dev/docs/README.md` · `_dev/docs/spec/*.md` · | **≤ 400**    | normativo, lido a cada sessão — denso por necessidade                |
|               | `_dev/docs/rules/*.md` · `_dev/docs/templates/*.md`                                      |              |                                                                      |
|               | `_dev/tools/README.md` · `_dev/tests/README.md` · `README.md`                            |              |                                                                      |
| **ON-DEMAND** | `_dev/docs/out/**` (artefactos — ver DOUTRINA 8) e as entradas do cliente + a análise em | **sem máx.** | é lido por humanos, com prova; **regenera-se** em vez de se corrigir |
|               | `_dev/mapaMentalMVP/`                                                                    |              |                                                                      |

   Um ficheiro **mantido** que passe de **400** linhas → **modularizar antes de acrescentar** (o pai passa
   a router, corte **por contexto**). Um **on-demand** não se divide à força: o detalhe é o produto.
   Mede-se com o `md-verify` do gate, que imprime `linhas=N` por ficheiro.
   **Como pensar a divisão (regime mantido):**
   - **Vários domínios no mesmo ficheiro** → cortar **por domínio** (é o que a spec fez).
   - **Um só assunto longo** (um guia, um fluxo) → cortar **por fase/etapa**, com índice no pai.
   **Forma obrigatória da divisão** (padrão já provado neste projeto):
   - o ficheiro-mãe **mantém o nome** e passa a **router**: mapa `§ → ficheiro` + prevalência + ponteiros;
   - corte **nas fronteiras de `§N`**, nunca a meio de uma secção — a numeração é a API de referência do
     projeto (`md-verify`) e **o número vai com o conteúdo**;
   - cada módulo declara, no cabeçalho, **a que pai pertence** e o seu âmbito (layout de `_dev/docs/spec/*.md`);
   - **mover, nunca copiar**; o que já exista noutro sítio **aponta-se**;
   - no **mesmo** commit: atualizar o mapa e correr o GATE.
   **Não partir por partir:** nada de ficheiros abaixo de ~40 linhas, nem fragmentar um documento que se
   lê em sequência por parágrafos — o corte segue a estrutura, não a aritmética.
7. **Histórico não se acumula — reconstrói-se.** O que foi revogado não vive em rodapés nem em anexos:
   vive no **Git** e é **reconstruído** quando pedido (`rules/build_history_on_demand.md`). Corolário da
   doutrina 2 — um arquivo paralelo seria redundância, e ficaria desatualizado.
8. **Artefactos on-demand: um sítio, um nome, um ID.** Todo o gerado sob demanda vive em
   `_dev/docs/out/` — **é esta convenção que permite às ferramentas encontrar o artefacto sem lhe
   dizerem o nome**:

| Tipo              | Nome do ficheiro      | Exemplo                       |
| :---------------- | :-------------------- | :---------------------------- |
| relatório/análise | `relatorio_<slug>.md` | `relatorio_mensagem-teams.md` |
| auditoria         | `auditoria_<slug>.md` | `auditoria_plataforma.md`     |
| mapa mental       | `mindmap_<slug>.md`   | `mindmap_rotas.md`            |
| guia de teste     | `guia_<slug>.md`      | `guia_teste-manual.md`        |
| histórico         | `historico_<slug>.md` | `historico_d-05.md`           |

   - `<slug>`: minúsculas, `[a-z0-9]`, `-` como separador, **≤ 40 caracteres**, **sem data e sem versão**:
     o artefacto é **regenerado no mesmo nome** e a versão é o Git. O mesmo `<slug>` em tipos diferentes
     é o mesmo assunto (`relatorio_x.md` ↔ `guia_x.md`).
   - O ficheiro declara-se no próprio cabeçalho (*Artefacto:* …) e substitui-se **em bloco**.
   - **IDs do artefacto** — `A-nn` (achados), `Q-nn` (dúvidas), `C-nn` (conflitos) — marcam-se na linha
     que os define: `<!-- id:A-01 -->`. Passam a ser **referenciáveis** (`git ref-open A-01`) e podem ser
     citados da especificação (`§N` + `A-01`), como qualquer `RF-nn`/`D-nn`
     (`_dev/tools/README.md` §2.2). Quando o artefacto é regenerado, os IDs **mantêm-se**: são o contrato.

## ÁRVORE

```text
N1  .clinerules                     regras de operação (stack, restrições, convenções, Git, ponteiros)
N2  especificacao_mvp.md            ROUTER: mapa §→ficheiro + prevalência + fonte única por assunto

    _dev/                            umbrella do que NÃO é produto (existe só na branch agent-workspace)
    ├── docs/spec/                  especificação por domínio (9 ficheiros, §1–§29)
    ├── docs/README.md              este ficheiro — mapa e índice de regras
    ├── docs/rules/                 COMO gerar (receitas por tipo de pedido)
    ├── docs/templates/             O QUE o output tem de conter (moldes)
    ├── docs/out/                   ARTEFACTOS on-demand (relatorio_ · auditoria_ · mindmap_ ·
    │                               guia_ · historico_) — sem limite de linhas; regenera-se em cima
    ├── tools/                      catálogo e comportamento das ferramentas [normativo: ferramentas]
    ├── tests/                      suites, execução, pré-requisitos       [normativo: testes]
    └── mapaMentalMVP/              apoio NÃO normativo: análise, auditoria, mensagem
                                    Teams e templates do cliente (.docx/.xlsx)

    fora de _dev/ ——
    README.md                       instalação e uso (público do repositório)
```

## N2 — DOMÍNIOS DA ESPECIFICAÇÃO

| §           | Ficheiro                         | Conteúdo                               |
| :---------- | :------------------------------- | :------------------------------------- |
| 1–3         | `_dev/docs/spec/core.md`         | núcleo, Regras de Ouro, decisões       |
| 4–5         | `_dev/docs/spec/requirements.md` | RF e RN                                |
| 6,7,10,16   | `_dev/docs/spec/operations.md`   | perfis, catálogo, equipa, feedback     |
| 8,9,12,20   | `_dev/docs/spec/booking.md`      | loja, carrinha, rotas, estados         |
| 11,13,14,15 | `_dev/docs/spec/finance.md`      | recibos, fiscal, pagamentos            |
| 17,18,19    | `_dev/docs/spec/data-api.md`     | dados, arquitetura, API                |
| 21,22,26–28 | `_dev/docs/spec/delivery.md`     | roadmap, limitações, testes, critérios |
| 24,25       | `_dev/docs/spec/backlog.md`      | gap e futuro                           |
| 23,29       | `_dev/docs/spec/annex.md`        | glossário, mapa documental, manutenção |

## N3 — ÍNDICE DE REGRAS (qual usar)

| O humano pede…                            | Regra                                        | Artefacto (DOUTRINA 8)                 |
| :---------------------------------------- | :------------------------------------------- | :------------------------------------- |
| atualizar / criar secção da especificação | `rules/build_spec_on_demand.md`              | `_dev/docs/spec/*.md` (mantido, ≤ 400) |
| relatório de estado, auditoria, análise   | `rules/build_report_on_demand.md`            | `out/relatorio_<slug>.md`              |
| analisar uma mensagem/doc do cliente      | `rules/build_report_on_demand.md`            | `out/relatorio_<assunto>.md`           |
| auditoria de código vs. especificação     | `rules/build_report_on_demand.md`            | `out/auditoria_<slug>.md`              |
| mapa mental / diagrama de fluxo           | `rules/build_mindmap_on_demand.md` (a criar) | `out/mindmap_<slug>.md`                |
| guia de teste manual passo-a-passo        | `rules/build_test_guide_on_demand.md`        | `out/guia_<slug>.md`                   |
| histórico por data (incl. o revogado)     | `rules/build_history_on_demand.md`           | `out/historico_<slug>.md`              |
| verificar integridade da documentação     | *(não é regra: correr as ferramentas)*       | —                                      |

> Uma regra que ainda não existe **cria-se quando for pedida** — no mesmo molde da que existe. Não se
> criam regras preventivamente (seria documentação sem procura).

## GATE OBRIGATÓRIO (antes de dar qualquer documento por concluído)

```bash
php _dev/tools/md-join-tables.php <f> --write
php _dev/tools/md-wrap-tables.php <f> --write
php _dev/tools/md-align-tables.php <f> --write
php _dev/tools/ascii-align.php <f> --write
php _dev/tools/health-check.php          # tem de dar TUDO OK
php _dev/tests/js_syntax_check.php       # se tocar em JS
```

- `md-verify` valida as referências `§NN` **entre ficheiros**: uma âncora partida é detetada aqui.
- Numeração `§` **preserva-se sempre** (é a API de referência do projeto). Ao mover conteúdo, o número vai com ele.
- UTF-8 sem BOM e CRLF: escrever por `_dev/tools/file-edit.php` ou via .NET — nunca `Get-Content`/`Set-Content`.
