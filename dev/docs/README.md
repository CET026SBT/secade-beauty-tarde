# DOCS — MAPA E REGRAS DE DOCUMENTAÇÃO (on-demand)

**Para quem é:** para o agente. Não é documentação de produto — é o **controlo de quando e como** se gera
documentação. Ler **apenas quando** o humano pedir documentação, mapa, relatório ou auditoria.

## DOUTRINA (vale para todo o `dev/docs/`)

1. **Sob demanda.** Nada é criado, atualizado ou reescrito sem pedido explícito. Sem pedido → não mexer.
2. **Zero redundância.** Cada facto tem **um** sítio. Documento novo que repita outro conteúdo é erro:
   apontar, não copiar.
3. **Normativo vs apoio.** A autoridade é `especificacao_mvp.md` + `dev/docs/spec/`. Tudo o que se gere em
   `dev/docs/rules/` é **apoio** — nunca contraria nem substitui a especificação.
4. **Hierarquia de leitura.** N1 `.clinerules` (sempre) → N2 spec por domínio (um ficheiro de cada vez)
   → N3 `dev/docs/` (só por pedido). Nunca carregar os três níveis por rotina.
5. **Ficheiros de controlo são densos; o output é legível.** Estes `.md` de regras são telegráficos de
   propósito. O documento que produzem segue o molde de `dev/docs/templates/` e passa pelo pipeline.
6. **Limiar de 400 linhas — modularizar antes de crescer.** Um documento que passa de **400 linhas**
   deixa de poder ser carregado de uma vez: **divide-se antes de lhe acrescentar conteúdo**. Não é
   preciso medir à mão — o `md-verify` do gate imprime `linhas=N` por ficheiro.
   **Âmbito:** documentos que **mantemos** (`dev/docs/**`, `especificacao_mvp.md`, `.clinerules`, `README`s).
   Entradas do cliente (`.docx`/`.xlsx`) e `dev/docs/out/` (descartável) estão fora.
   **Como pensar a divisão:**
   - **Vários domínios no mesmo ficheiro** → cortar **por domínio** (é o que a spec fez).
   - **Um só assunto longo** (um guia, um fluxo) → cortar **por fase/etapa**, com índice no pai.
   **Forma obrigatória da divisão** (padrão já provado neste projeto):
   - o ficheiro-mãe **mantém o nome** e passa a **router**: mapa `§ → ficheiro` + prevalência + ponteiros;
   - corte **nas fronteiras de `§N`**, nunca a meio de uma secção — a numeração é a API de referência do
     projeto (`md-verify`) e **o número vai com o conteúdo**;
   - cada módulo declara, no cabeçalho, **a que pai pertence** e o seu âmbito (layout de `dev/docs/spec/*.md`);
   - **mover, nunca copiar**; o que já exista noutro sítio **aponta-se**;
   - no **mesmo** commit: atualizar o mapa e correr o GATE.
   **Não partir por partir:** nada de ficheiros abaixo de ~40 linhas, nem fragmentar um documento que se
   lê em sequência por parágrafos — o corte segue a estrutura, não a aritmética.
7. **Histórico não se acumula — reconstrói-se.** O que foi revogado não vive em rodapés nem em anexos:
   vive no **Git** e é **reconstruído** quando pedido (`rules/build_history_on_demand.md`). Corolário da
   doutrina 2 — um arquivo paralelo seria redundância, e ficaria desatualizado.

## ÁRVORE

```text
N1  .clinerules                     regras de operação (stack, restrições, convenções, Git, ponteiros)
N2  especificacao_mvp.md            ROUTER: mapa §→ficheiro + prevalência + fonte única por assunto

    dev/                            umbrella do que NÃO é produto (existe só na branch agent-workspace)
    ├── docs/spec/                  especificação por domínio (9 ficheiros, §1–§29)
    ├── docs/README.md              este ficheiro — mapa e índice de regras
    ├── docs/rules/                 COMO gerar (receitas por tipo de pedido)
    ├── docs/templates/             O QUE o output tem de conter (moldes)
    ├── docs/out/                   saída gerada (descartável)
    ├── tools/                      catálogo e comportamento das ferramentas [normativo: ferramentas]
    ├── tests/                      suites, execução, pré-requisitos       [normativo: testes]
    └── mapaMentalMVP/              apoio NÃO normativo: análise, auditoria, mensagem
                                    Teams e templates do cliente (.docx/.xlsx)

    fora de dev/ ——
    README.md                       instalação e uso (público do repositório)
```

## N2 — DOMÍNIOS DA ESPECIFICAÇÃO

| §           | Ficheiro                        | Conteúdo                               |
| :---------- | :------------------------------ | :------------------------------------- |
| 1–3         | `dev/docs/spec/core.md`         | núcleo, Regras de Ouro, decisões       |
| 4–5         | `dev/docs/spec/requirements.md` | RF e RN                                |
| 6,7,10,16   | `dev/docs/spec/operations.md`   | perfis, catálogo, equipa, feedback     |
| 8,9,12,20   | `dev/docs/spec/booking.md`      | loja, carrinha, rotas, estados         |
| 11,13,14,15 | `dev/docs/spec/finance.md`      | recibos, fiscal, pagamentos            |
| 17,18,19    | `dev/docs/spec/data-api.md`     | dados, arquitetura, API                |
| 21,22,26–28 | `dev/docs/spec/delivery.md`     | roadmap, limitações, testes, critérios |
| 24,25       | `dev/docs/spec/backlog.md`      | gap e futuro                           |
| 23,29       | `dev/docs/spec/annex.md`        | glossário, mapa documental, manutenção |

## N3 — ÍNDICE DE REGRAS (qual usar)

| O humano pede…                            | Regra                                        | Saída                |
| :---------------------------------------- | :------------------------------------------- | :------------------- |
| atualizar / criar secção da especificação | `rules/build_spec_on_demand.md`              | `dev/docs/spec/*.md` |
| relatório de estado, auditoria, análise   | `rules/build_report_on_demand.md` (a criar)  | `dev/docs/out/…`     |
| mapa mental / diagrama de fluxo           | `rules/build_mindmap_on_demand.md` (a criar) | `dev/docs/out/…`     |
| guia de teste manual passo-a-passo        | `rules/build_test_guide_on_demand.md`        | `dev/docs/out/…`     |
| histórico por data (incl. o revogado)     | `rules/build_history_on_demand.md`           | `dev/docs/out/…`     |
| verificar integridade da documentação     | *(não é regra: correr as ferramentas)*       | —                    |

> Uma regra que ainda não existe **cria-se quando for pedida** — no mesmo molde da que existe. Não se
> criam regras preventivamente (seria documentação sem procura).

## GATE OBRIGATÓRIO (antes de dar qualquer documento por concluído)

```bash
php dev/tools/md-join-tables.php <f> --write
php dev/tools/md-wrap-tables.php <f> --write
php dev/tools/md-align-tables.php <f> --write
php dev/tools/ascii-align.php <f> --write
php dev/tools/health-check.php          # tem de dar TUDO OK
php dev/tests/js_syntax_check.php       # se tocar em JS
```

- `md-verify` valida as referências `§NN` **entre ficheiros**: uma âncora partida é detetada aqui.
- Numeração `§` **preserva-se sempre** (é a API de referência do projeto). Ao mover conteúdo, o número vai com ele.
- UTF-8 sem BOM e CRLF: escrever por `dev/tools/file-edit.php` ou via .NET — nunca `Get-Content`/`Set-Content`.
