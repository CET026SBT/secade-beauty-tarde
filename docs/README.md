# DOCS — MAPA E REGRAS DE DOCUMENTAÇÃO (on-demand)

**Para quem é:** para o agente. Não é documentação de produto — é o **controlo de quando e como** se gera
documentação. Ler **apenas quando** o humano pedir documentação, mapa, relatório ou auditoria.

## DOUTRINA (vale para todo o `docs/`)

1. **Sob demanda.** Nada é criado, atualizado ou reescrito sem pedido explícito. Sem pedido → não mexer.
2. **Zero redundância.** Cada facto tem **um** sítio. Documento novo que repita outro conteúdo é erro:
   apontar, não copiar.
3. **Normativo vs apoio.** A autoridade é `especificacao_mvp.md` + `docs/spec/`. Tudo o que se gere em
   `docs/rules/` é **apoio** — nunca contraria nem substitui a especificação.
4. **Hierarquia de leitura.** N1 `.clinerules` (sempre) → N2 spec por domínio (um ficheiro de cada vez)
   → N3 `docs/` (só por pedido). Nunca carregar os três níveis por rotina.
5. **Ficheiros de controlo são densos; o output é legível.** Estes `.md` de regras são telegráficos de
   propósito. O documento que produzem segue o molde de `docs/templates/` e passa pelo pipeline.

## ÁRVORE

```text
N1  .clinerules                    regras de operação (stack, restrições, convenções, Git, ponteiros)
N2  especificacao_mvp.md           ROUTER: mapa §→ficheiro + prevalência + fonte única por assunto
    docs/spec/                     especificação dividida por domínio (9 ficheiros, §1–§29)
N3  docs/README.md                 este ficheiro — mapa e índice de regras
    docs/rules/                    COMO gerar (receitas por tipo de pedido)
    docs/templates/                O QUE o output tem de conter (moldes)
    docs/out/                      saída gerada (descartável; só existe se o humano pedir para guardar)

—— fora de docs/ ——
    README.md                      instalação e uso (público do repositório)
    tests/README.md                suites, execução, pré-requisitos        [normativo: testes]
    tools/README.md                catálogo e comportamento das ferramentas [normativo: ferramentas]
    mapaMentalMVP/                 apoio NÃO normativo: análise do backoffice, auditoria,
                                   mensagem Teams e templates do cliente (.docx/.xlsx)
```

## N2 — DOMÍNIOS DA ESPECIFICAÇÃO

| §           | Ficheiro                    | Conteúdo                               |
| :---------- | :-------------------------- | :------------------------------------- |
| 1–3         | `docs/spec/core.md`         | núcleo, Regras de Ouro, decisões       |
| 4–5         | `docs/spec/requirements.md` | RF e RN                                |
| 6,7,10,16   | `docs/spec/operations.md`   | perfis, catálogo, equipa, feedback     |
| 8,9,12,20   | `docs/spec/booking.md`      | loja, carrinha, rotas, estados         |
| 11,13,14,15 | `docs/spec/finance.md`      | recibos, fiscal, pagamentos            |
| 17,18,19    | `docs/spec/data-api.md`     | dados, arquitetura, API                |
| 21,22,26–28 | `docs/spec/delivery.md`     | roadmap, limitações, testes, critérios |
| 24,25       | `docs/spec/backlog.md`      | gap e futuro                           |
| 23,29       | `docs/spec/annex.md`        | glossário, mapa documental, manutenção |

## N3 — ÍNDICE DE REGRAS (qual usar)

| O humano pede…                            | Regra                                        | Saída            |
| :---------------------------------------- | :------------------------------------------- | :--------------- |
| atualizar / criar secção da especificação | `rules/build_spec_on_demand.md`              | `docs/spec/*.md` |
| relatório de estado, auditoria, análise   | `rules/build_report_on_demand.md` (a criar)  | `docs/out/…`     |
| mapa mental / diagrama de fluxo           | `rules/build_mindmap_on_demand.md` (a criar) | `docs/out/…`     |
| guia de teste manual passo-a-passo        | `rules/build_test_guide_on_demand.md`        | `docs/out/…`     |
| verificar integridade da documentação     | *(não é regra: correr as ferramentas)*       | —                |

> Uma regra que ainda não existe **cria-se quando for pedida** — no mesmo molde da que existe. Não se
> criam regras preventivamente (seria documentação sem procura).

## GATE OBRIGATÓRIO (antes de dar qualquer documento por concluído)

```bash
php tools/md-join-tables.php <f> --write
php tools/md-wrap-tables.php <f> --write
php tools/md-align-tables.php <f> --write
php tools/ascii-align.php <f> --write
php tools/health-check.php          # tem de dar TUDO OK
php tests/js_syntax_check.php       # se tocar em JS
```

- `md-verify` valida as referências `§NN` **entre ficheiros**: uma âncora partida é detetada aqui.
- Numeração `§` **preserva-se sempre** (é a API de referência do projeto). Ao mover conteúdo, o número vai com ele.
- UTF-8 sem BOM e CRLF: escrever por `tools/file-edit.php` ou via .NET — nunca `Get-Content`/`Set-Content`.
