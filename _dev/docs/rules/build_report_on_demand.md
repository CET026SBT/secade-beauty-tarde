# RULE — build_report_on_demand

**Objetivo:** produzir **relatórios, auditorias e análises** quando pedidos, com evidência e sem inchar.
**Saída:** `_dev/docs/out/<assunto>.md` (descartável; **não versionar**).
**Molde:** `_dev/docs/templates/report.md` — a estrutura do output não se inventa aqui.

## 1. QUANDO

| Gatilho                                | Âmbito                                 |
| :------------------------------------- | :------------------------------------- |
| "audita X" / "procura inconsistências" | varredura + achados + propostas        |
| "onde está Y e o que está errado?"     | localizar + provar + propor            |
| "estado do projeto em Z"               | medição (contagens, testes, ficheiros) |
| "confirma se isto é verdade"           | verificação pontual, resposta curta    |

**NUNCA** gerar preventivamente, nem guardar em `docs/` versionado: é **derivado**, regenera-se.

## 2. FONTES E MÉTODO

| Preciso de…             | Ler / correr                                 |
| :---------------------- | :------------------------------------------- |
| o molde                 | `_dev/docs/templates/report.md`              |
| a convenção a verificar | **só** a § da `_dev/docs/spec/` que a define |
| a verdade do código     | varredura + **leitura** do ficheiro citado   |

**Regra de ouro:** cada achado tem **ficheiro:linha**. Sem prova, não é achado — é opinião.

## 3. ARMADILHAS MEDIDAS (neste projeto — não repetir)

1. **`search_codebase` não indexa `app/`.** Medido: `$_SESSION` existe em `app/utils/Session.php` e a
   busca devolveu *0 resultados*. → usar `Select-String` (`-SimpleMatch` quando o padrão tem `$`).
2. **`md-verify` a um ficheiro isolado dá falsos inválidos.** As `§NN` vivem noutros ficheiros: validar
   `auditoria_*.md` sozinho acusou `18.5·18.6·18.2·25.3`. → correr **sem alvo** (índice global).
3. **Inserir por número de linha parte blocos a meio.** Medido: um insert antes de um cabeçalho deixou
   uma secção órfã no fim do ficheiro. → usar `old_text` como **âncora** de conteúdo, não `insert_line`.
4. **Ficheiros do cliente são binários** (`.docx`/`.xlsx`): não são citáveis linha a linha.
5. **Não tocar em `admin/`** (instrução permanente) nem em `_dev/mapaMentalMVP/*` (apoio não normativo).

## 4. ESTRUTURA OBRIGATÓRIA (do molde)

1. **Resumo** com tabela `# · achado · onde · gravidade · estado` (🔴🟠🟡⚪).
2. **Detalhe** por achado: *prova* → *consequência* → *proposta* (com código, quando aplicável).
3. **Verificado e correcto** — não é decorativo: evita que a próxima ronda re-audite o que já está bem.
4. **Limites do trabalho** — o que **não** foi verificado e porquê (ex.: `http_test` exige Apache).
5. **Próximos passos** ordenados, com dependências e **risco**.

## 5. GATE

Pipeline `.md` + `php _dev/tools/md-verify.php` (**sem alvo**) + `php _dev/tools/health-check.php`.
O relatório **não altera** o produto: se revelar uma quebra, isso é achado a reportar, não edição a fazer.

## 6. CUSTO ESPERADO

| Âmbito                | Varredura + leitura           | Escrever        |
| :-------------------- | :---------------------------- | :-------------- |
| uma pergunta          | 1 padrão + 1-2 leituras       | ~10-20 linhas   |
| um eixo (ex.: sessão) | 2-3 padrões + 4-6 leituras    | ~50-80 linhas   |
| projeto inteiro       | 8-12 padrões + 10-20 leituras | ~250-350 linhas |
