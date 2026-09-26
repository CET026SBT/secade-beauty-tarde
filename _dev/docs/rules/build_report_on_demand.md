# RULE — build_report_on_demand

**Objetivo:** produzir **relatórios, auditorias e análises** quando pedidos, com evidência e sem inchar.
**Saída:** `_dev/docs/out/relatorio_<assunto>.md` (ou `auditoria_<assunto>.md` numa varredura —
DOUTRINA 8 de `_dev/docs/README.md`: **nome fixo**, regenerado em cima; a versão é o Git).
**Molde:** `_dev/docs/templates/report.md` — a estrutura do output não se inventa aqui.
**Limite:** o artefacto **não tem máximo de linhas** — é lido por humanos e vale pelo detalhe. Escreve
para quem **não tem o contexto**: diz o que é cada coisa antes de a julgar, define os termos próprios e,
por cada achado, dá **prova + consequência + proposta**. Marca cada achado com `<!-- id:A-01 -->` na
linha que o define: passa a ser referenciável (`git ref-open A-01`) e citável da especificação.

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
6. **Varrer com contexto multi-linha.** Um padrão de linha única
   (`throw new Exception\(.*,\s*\d{3}\s*\)`) deu **3 falsos positivos** numa auditoria: as exceções
   escrevem o código na **linha seguinte**. → `Select-String -Context 0,3`, ou padrão sobre o bloco.
   Um falso positivo num relatório custa uma conclusão errada.
7. **Confirmar o número de ficheiros de um teste antes de confiar no verde.** O `js_syntax_check` tem
   **lista fixa**: deu `OK` sem validar 2 ficheiros novos. → contar as linhas `PASS` e comparar com o
   esperado, ou verificar se os ficheiros criados aparecem na saída.
8. **Verificar o uso efectivo, não a existência.** Um método pode existir e nunca ser chamado
   (`findManager`, `findByIdAndBooking`): varrer `Nome->metodo`/`Nome::metodo` antes de contar com ele.
   O mesmo para chaves de resposta: `booking.people` era produzido e **ninguém** o lia.
9. **As fontes de conversa não são citáveis linha a linha.** `_dev/mapaMentalMVP/mensagem_teams.txt` é
   texto do grupo: os números de linha **deslocam-se** e partes do conteúdo **não existem no repositório**
   (perguntas nascidas de conversa — `.clinerules` §0 regra 3). Citar por **ficheiro + pergunta/ID**
   (`mensagem_teams.txt` pergunta 36 · **Q-66**), nunca por linha; e dizer explicitamente quando não há
   fonte no repositório.
10. **Artefactos regeneram-se, não se corrigem.** `_dev/docs/out/**` não é fonte única de nada: se um
    relatório ficou desatualizado, **volta-se a gerar no mesmo nome** (DOUTRINA 8). Corrigir à mão um
    artefacto derivado é trabalho perdido na próxima geração.
11. **Ler os `.txt`/`.docx` do cliente com as ferramentas.** O `mensagem_teams.txt` é UTF-8 válido, mas
    `Get-Content` (PS 5.1) mostra-o em mojibake — é o bug de leitura do `_dev/tools/README.md` §1, não
    corrupção do ficheiro. Ler com `php _dev/tools/file-edit.php show <f> <inicio> <n>`.

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

> Os números são **esforço de escrita**, não um tecto: o artefacto **não tem limite de linhas** — se o
> assunto pedir detalhe (provas, alternativas, passos), escreve-se. O que não se faz é encher.
