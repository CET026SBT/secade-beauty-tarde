# RULE — build_history_on_demand

**Objetivo:** reconstruir, **por data**, a história de **qualquer parte** da especificação — **incluindo o
que foi revogado** — mais o contexto que o humano pedir.
**Saída:** `dev/docs/out/historico_<assunto>.md` (descartável; regenerável a qualquer momento).
**Princípio:** o histórico **não se mantém** — o arquivo é o **Git** (`.clinerules` §5). Este ficheiro não
cria arquivo paralelo: **lê** o que já lá está. Corpus atual: **107 commits**, 2026-08-03 → 2026-09-24.

## 1. QUANDO

| Gatilho                                     | Âmbito do documento          |
| :------------------------------------------ | :--------------------------- |
| "de onde veio esta regra / porque é assim?" | o `§`/`RF`/`RN`/`D` em causa |
| "o que já foi tentado e porque falhou?"     | tema, não secção             |
| "o que revogámos e o que ficou no lugar?"   | todos os revogados do tema   |
| "reconstitui a evolução da spec"            | camadas 1–3 (§3 abaixo)      |

**NUNCA** gerar preventivamente nem guardar o resultado: é **derivado** do Git. Regenerar é mais barato
que corrigir um histórico desatualizado.

## 2. ARMADILHA CENTRAL — ancorar por IDENTIFICADOR, nunca por caminho nem linha

O conteúdo **mudou de ficheiro e de linha** (ver §3). Referências por caminho/linha mentem:

| Âncora                                                                 | Serve para                                          |
| :--------------------------------------------------------------------- | :-------------------------------------------------- |
| `§N` · `RF-nn` · `RN-nn` · `D-nn`                                      | **é a chave estável** — o número vai com o conteúdo |
| nome de constante (`FIXED_OPERATIONAL_COST`), tabela, coluna, endpoint | rasto pontual seguro                                |
| caminho de ficheiro / número de linha                                  | ❌ inútil fora da revisão em que foi escrito        |

**Ferramenta principal — pickaxe (`-S`), que atravessa movimentos:**
`git log -S '<identificador>' --date=short --pretty=format:'%h|%ad|%s' --all -- '*.md' '*.php'`
Dá os commits que **introduziram** ou **removeram** aquele texto — logo as duas metades da história.

**Recuperar o que foi revogado (o método que interessa):**
`git show <rev>^:<ficheiro>` — o estado **antes** do commit que apagou. Exemplos reais já executados:

```bash
git show 0321ce7^:especificacao_mvp.md      # 1872 linhas; contém "3.13", "5.2 revogadas", "23. defeitos"
git show ef8f689^:app/services/RotaService.php   # FIXED_OPERATIONAL_COST ainda presente
```

**Ficheiros apagados (arquivo morto):**
`git log --diff-filter=D --date=short --pretty=format:'%h|%ad|%s' --name-only -- '*.md' '*.txt'`

## 3. AS TRÊS CAMADAS (o que esperar)

| #   | Camada                                                                                                                                                  | Onde vive hoje                |
| :-- | :------------------------------------------------------------------------------------------------------------------------------------------------------ | :---------------------------- |
| 1   | planeamento inicial (`.md` apagados: `plano_desenvolvimento`, `CARRINHA_SPEC`, `ALTERACOES_PRIORIDADES`, `LOGIN_PROFILE_TODO`, `fluxo_funcionalidades`, | **só no Git** (`e16e14b`)     |
|     | `tecnologias_projeto`)                                                                                                                                  |                               |
| 2   | mestre consolidado (`especificacao_mvp.md` v1.x/v2.x, até 1872 linhas)                                                                                  | no Git **antes de `f75bb8d`** |
| 3   | atual: router + `dev/docs/spec/` **+** `dev/docs/rules`/`dev/docs/templates`                                                                            | disco                         |

Um documento completo cita **a camada** de cada facto: *"§12.2 veio da camada 2; a regra dos 50 € morreu
em `ef8f689`"*.

## 4. ARMADILHAS MEDIDAS (não repetir)

1. `--follow` **exige um só** pathspec e **para** quando o ficheiro é novo: em `dev/docs/spec/booking.md`
   devolveu apenas `f75bb8d`. → usar `-S`, não `--follow`.
2. `--all` traz **ruído**: **23** dos **199** commits são checkpoints automáticos (`untracked files on
   cline checkpoint`). → filtrar: `--invert-grep --grep='untracked files'`. Em `HEAD` o ruído é **0** —
   se o âmbito for a branch atual, **não usar `--all`**.
3. **Falso apagamento:** `8230698` (*"dev: remove do controlo de versao"*) aparece como `D` de
   `especificacao_mvp.md`, mas o ficheiro **não foi apagado** — só saiu do índice. → confirmar sempre com
   `git show <rev>^:<f>` antes de escrever "apagado".
4. Mensagens de commit deste projeto **trazem o porquê** no corpo. Lê-las inteiras
   (`git show <rev> --stat --format='%B'`) é a fonte do "porquê" — não se inventa causa.

## 5. GATE

Pipeline + `php dev/tools/health-check.php` → **TUDO OK** (`dev/docs/README.md`). Sem BOM, CRLF.
O histórico **não** altera a especificação: se revelar contradição, isso é um **achado** a reportar, não
uma edição a fazer.

## 6. CUSTO ESPERADO

| Âmbito              | Comandos                    | Escrever        |
| :------------------ | :-------------------------- | :-------------- |
| uma secção / um ID  | 1 pickaxe + 1 `show <rev>^` | ~30-60 linhas   |
| um tema transversal | 2-3 pickaxes + 3-5 `show`   | ~80-150 linhas  |
| evolução completa   | §3 inteiro + ~10 `show`     | ~200-400 linhas |
