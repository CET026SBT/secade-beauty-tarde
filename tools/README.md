# TOOLS — UTILITÁRIOS DE MANUTENÇÃO (dev-only)

Pasta de ferramentas de apoio ao desenvolvimento. **Não faz parte da aplicação** e não é
servida pela web (ver `.htaccess`).

> 📍 **Onde vive esta pasta.** Existe **apenas na branch `agent-workspace`** e está no `.gitignore`
> (com `/especificacao_mvp.md`, `/mapaMentalMVP/` e `/.clinerules`). Nas branches de produto
> (`main`, `dev`, …) os ficheiros ficam **no disco mas invisíveis para o Git** (`git status` limpo),
> pelo que as ferramentas continuam utilizáveis. Para os versionar é obrigatório `git add -f`.
> Detalhe: `.clinerules` §11.5 e `especificacao_mvp.md` §18.12.

<!-- encoding-check:ignore-mojibake -->
> Este ficheiro **documenta** mojibake como exemplo (na secção 1), pelo que contém
> intencionalmente essas sequências. A linha `encoding-check:ignore-mojibake` acima
> (pragma) faz com que o `encoding-check.php` não as reporte como problema.

> Existe sobretudo para resolver, de forma permanente e reutilizável, dois problemas
> recorrentes neste projeto: **corrupção de encoding** ao editar ficheiros pela shell e
> **desalinhamento das tabelas** nos `.md`.

---

## 1. PORQUE EXISTE — causa raiz do BOM e do mojibake

**Confirmado experimentalmente** neste ambiente (Windows PowerShell **5.1** / *Desktop*):

| Passo                                    | O que acontece                                                                                                                                           |
| ---------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Get-Content ficheiro.md`                | **Sem** `-Encoding`, decodifica os bytes UTF-8 como **CP1252 (ANSI)**. Os acentos, travessões e euros entram em memória **já em mojibake** (`—` → `â€”`) |
| `Set-Content ficheiro.md -Encoding UTF8` | No PS 5.1 o `UTF8` significa **UTF-8 COM BOM**. Os encodings disponíveis são apenas: `Unknown, String, Unicode, Byte, BigEndianUnicode, UTF8,            |
|                                          | UTF7, UTF32, Ascii, Default, Oem, BigEndianUTF32` — **`utf8NoBOM` não existe**                                                                           |

**Resultado medido num teste controlado** (ficheiro de 158 bytes com acentos, travessão, euro,
aspas curvas e emoji):

```
ANTES  (UTF-8 sem BOM)  158 bytes   bom=nao  utf8=OK   mojibake=0
DEPOIS (Get-Content + Set-Content -Encoding UTF8)
                        218 bytes   bom=SIM  utf8=OK   mojibake=10
        travessao — -> mojibake : SIM
        euro €     -> mojibake : SIM
```

O ficheiro cresce **+60 bytes** (BOM de 3 bytes + cada carácter acentuado que passa de 1 byte
em CP1252 para 2–3 bytes em UTF-8), fica **válido em UTF-8** mas com o **conteúdo corrompido** —
o que torna o erro silencioso e fácil de não notar.

### Como evitar

```powershell
# NUNCA fazer isto (corrompe):
$lines = Get-Content ficheiro.md
Set-Content -Path ficheiro.md -Value $lines -Encoding UTF8

# OPCAO A — usar os utilitarios desta pasta (recomendado):
php tools/file-edit.php show ficheiro.md 100 40
php tools/file-edit.php replace ficheiro.md spec.json --write

# OPCAO B — via .NET, explicitamente SEM BOM (verificado: 158 -> 158 bytes, intacto):
$enc = New-Object System.Text.UTF8Encoding($false)
$txt = [System.IO.File]::ReadAllText($path, $enc)
[System.IO.File]::WriteAllText($path, $txt, $enc)
```

---

## 2. FERRAMENTAS

| Ficheiro              | Para que serve                                                                                                                                                                                   |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `_common.php`         | Módulo comum: I/O UTF-8 seguro, CLI, largura de texto/emoji, deteção de encoding. **Não executar directamente**                                                                                  |
| `encoding-check.php`  | Deteta BOM, mojibake, UTF-8 inválido, **UTF-16** e fins de linha mistos. Aceita `--ignore=` (exceções conhecidas)                                                                                |
| `encoding-fix.php`    | **Repara** BOM e mojibake (mapa CP1252 explícito + verificação *round-trip*) e **converte UTF-16 → UTF-8**                                                                                       |
| `md-align-tables.php` | Alinha as tabelas markdown (largura de ecrã; emoji = 2 colunas; ignora *code fences*; aceita separadores com 1+ hífenes (GFM) e pipes escapados `\|`)                                            |
| `md-verify.php`       | Verifica encoding, code fences, referências `§NN` (com **resolução cruzada** entre documentos e allowlist `<!-- md-verify:allow-refs=… -->`), tabelas (pipes escapados não                       |
|                       | contam) e marcadores residuais                                                                                                                                                                   |
| `file-edit.php`       | `show` / `write` / `replace` / `lines` / `grep` — leitura e escrita UTF-8 **segura**                                                                                                             |
| `ascii-align.php`     | Nivela **tabelas ASCII** desenhadas à mão dentro de *code fences*: boxes `+---+` e a coluna de referência `│` dos diagramas de fluxo (`--boxes-only` limita aos boxes)                           |
| `md-wrap-tables.php`  | **Quebra o texto das células** para que nenhuma linha de tabela markdown exceda `--max` colunas (200 por omissão; aceita o pragma `<!-- md-wrap-tables:max=N -->` do ficheiro).                  |
|                       | **Nunca parte palavras a meio**                                                                                                                                                                  |
| `widthcheck.php`      | Verifica a **uniformidade** das tabelas: todas as linhas do mesmo bloco têm de ter a mesma **largura de ecrã** e caber no limite. Puro diagnóstico (só lê)                                       |
| `health-check.php`    | Corre as **6** verificações de uma vez (encoding · `md-verify` · `md-align-tables` · `ascii-align` · `md-wrap-tables` · `md-widths`, as quatro últimas em *dry-run* por ficheiro) e dá um resumo |
|                       | com `[OK]`/`[!!]`                                                                                                                                                                                |

**Convenções comuns**
- Todos assumem que são corridos **a partir da raiz do projeto**.
- Operações de escrita são **dry-run por omissão**; só gravam com `--write`.
- **Exit code:** `0` = ok · `1` = problema/erro.
- Recusam gravar se o resultado não for UTF-8 válido.

---

## 3. USO RÁPIDO

```bash
# Saude geral do projeto (encoding + .md + alinhamento)
php tools/health-check.php

# Detetar problemas de encoding (aceita exceções conhecidas)
php tools/encoding-check.php
php tools/encoding-check.php especificacao_mvp.md
php tools/encoding-check.php --ext=php,sql
php tools/encoding-check.php --ignore=ficheiro_legado.sql

# Reparar BOM / mojibake  +  converter UTF-16 -> UTF-8 (dry-run -> gravar)
php tools/encoding-fix.php especificacao_mvp.md
php tools/encoding-fix.php especificacao_mvp.md --write
php tools/encoding-fix.php . --write
php tools/encoding-fix.php dump_legado.sql        # deteta UTF-16 e propõe conversão

# --- PIPELINE .md: quebrar linhas longas -> nivelar tabelas -> nivelar ASCII ---
# (por esta ordem: o wrap pode voltar a desalinhar, e o align fecha a formatação)
php tools/md-wrap-tables.php especificacao_mvp.md            # dry-run (diz quantas tabelas excedem 200 colunas)
php tools/md-wrap-tables.php especificacao_mvp.md --write
php tools/md-wrap-tables.php relatorio.md --write --max=160 --min=10 --verbose

# Alinhar tabelas markdown de um .md
php tools/md-align-tables.php especificacao_mvp.md
php tools/md-align-tables.php especificacao_mvp.md --write

# Verificar a uniformidade das tabelas (todas as linhas do bloco com a mesma largura)
php tools/widthcheck.php especificacao_mvp.md
php tools/widthcheck.php relatorio.md 260      # limite explicito (ignora o pragma do ficheiro)

# Nivelar tabelas ASCII / diagramas dentro de code fences
php tools/ascii-align.php mapaMentalMVP/mapa_fluxo_dados.md
php tools/ascii-align.php mapaMentalMVP/mapa_fluxo_dados.md --write
php tools/ascii-align.php diagrama.md --write --boxes-only    # só boxes '+---+'
php tools/ascii-align.php diagrama.md --verbose               # diagnostico por linha

# Verificar estrutura dos .md
php tools/md-verify.php
php tools/md-verify.php especificacao_mvp.md

# Ler / escrever com seguranca
php tools/file-edit.php show especificacao_mvp.md 1105 40
php tools/file-edit.php grep "funcionario_categoria"
php tools/file-edit.php grep "admin-service-" --ext=php --ignore-case
php tools/file-edit.php replace doc.md spec.json --write
php tools/file-edit.php lines doc.md 12 18 novo.txt --write
```

### Formato do `spec.json` (para `file-edit.php replace`)

```json
{
  "comment": "metadados opcionais (o ficheiro pode ser directamente o array abaixo)",
  "jobs": [
    { "search": "texto antigo", "replace": "texto novo" },
    { "search": "so na 1a ocorrencia", "replace": "novo", "count": 1 },
    { "search": "§(\\d+)", "replace": "§$1", "regex": true }
  ]
}
```
- `search` — texto literal (ou expressão regular, se `regex: true`).
- `count` — `0`/ausente = todas as ocorrências; `1`+ = limita.
- Aceita tanto `[{...}, {...}]` como `{"comment":"...", "jobs":[...]}`.
- Se um `search` não for encontrado, o utilitário **informa-o** (não falha em silêncio).
- Este formato evita o *escaping* problemático do PowerShell.

---

### 3.1 Garantias de segurança do pipeline `.md`

Os três formatadores partilham a mesma promessa: **nunca alteram texto** — só mexem em *espaços*,
no comprimento dos separadores (`-`, `+`, `│`), nos *pipes* e nas *quebras de linha*.

| Ferramenta            | O que garante antes de gravar                                                                                           |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| `md-align-tables.php` | Compara o texto normalizado antes/depois e **aborta** se diferir; recusa resultado não-UTF-8                            |
| `md-wrap-tables.php`  | Compara o texto de cada coluna antes/depois (por tabela); se diferir, **mantém a tabela intacta** e assinala o problema |
| `ascii-align.php`     | Re-emite apenas *padding* e a largura dos traços; recusa resultado não-UTF-8; ignora o que não seja box/diagrama        |
| `widthcheck.php`      | Só lê: reporta blocos com larguras diferentes ou acima do limite. Não grava nada                                        |

**Regra de ouro da largura da célula divisória.** A célula divisória tem **exatamente a largura da
coluna** (`max(1, largura − marcadores)`). Um piso fixo de 3 caracteres (`:---`) numa coluna de
largura 3 dá uma célula com 4 — o `align` alargava a linha e as duas ferramentas ficavam em
desacordo (cada uma «corrigia» a outra). Está alinhado com a passagem final do `md-wrap-tables`.

**As três armadilhas de formatação (encontradas e corrigidas na v1.4)**

| Armadilha                                | Sintoma                                                                                     | Resolução                                                                      |
| ---------------------------------------- | ------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| Linha em branco **dentro** de uma tabela | A tabela é lida como **dois blocos**; o segundo não tem divisor no cabeçalho → é ignorado e | Não deixar linhas vazias dentro de tabelas                                     |
|                                          | fica todo desformatado                                                                      |                                                                                |
| Tabela que **já cabe** no limite         | O `wrap` deixa-a intacta (correto), mas o `align` re-renderiza-a a seguir — incluindo a     | Executar sempre `wrap` **e depois** `align`; confirmar com `widthcheck`        |
|                                          | divisória larga «a mais»                                                                    |                                                                                |
| Pisos de coluna relaxados pelo `shrink`  | Coluna mais estreita que o átomo mais longo → a palavra era **partida ao meio**             | Os pisos **nunca** são relaxados: a tabela fica mais larga e o relatório di-lo |
|                                          | (`edi`/`táveis`) → o guard detetava e a tabela ficava **meio formatada**                    |                                                                                |

**Tabelas que não cabem sem partir palavras.** Uma tabela densa (muitas colunas com *spans* de
código longos) pode exigir mais do que 200 colunas com as palavras intactas. O `md-wrap-tables`
formata-a na mesma e reporta:

```text
TABELA(S) ACIMA DO LIMITE MESMO COM PALAVRAS INTACTAS:
  - L425 (5 colunas, precisa de 217 colunas)
```

Para esses ficheiros declara-se o limite real num **pragma** (mesma convenção do
`encoding-check:ignore-mojibake` e do `md-verify:allow-refs`), que passa a ser o default do
ficheiro e é respeitado automaticamente pelo `health-check` (o `--max` da linha de comandos
continua a sobrepor-se):

```html
<!-- md-wrap-tables:max=220 -->
```

**Cuidado com o `|` dentro de células.** Num `.md`, um `|` literal dentro de uma célula de tabela tem de ser
**escapado** (`\|`) — **mesmo dentro de inline code**. Sem escape a linha ganha colunas a mais e o
`md-verify` assinala a tabela como inconsistente. É por isso que os três formatadores contam apenas os
*pipes* **não escapados**.

**Ordem correta de execução** (o `wrap` altera larguras, logo tem de vir antes do `align`):

```text
md-wrap-tables  →  md-align-tables  →  ascii-align  →  widthcheck  →  health-check.php
```

**Validação empírica feita sobre os `.md` reais do projeto** (além das verificações internas das
ferramentas): multiconjunto de palavras invariante (sem perda/duplicação/palavra partida) ·
`0` linhas de tabela acima do limite efetivo · `0` tabelas com larguras diferentes entre linhas ·
`0` *code spans* partidos (paridade de `` ` `` por linha) · `0` tabelas com colunas inconsistentes ·
segunda passagem idempotente nas **seis** ferramentas.

> ⚠️ **Efeito do `md-wrap-tables`:** uma célula longa passa a ocupar **várias linhas físicas** da
> tabela (as continuações ficam na mesma coluna). O conteúdo é idêntico e a tabela continua válida,
> mas deixa de haver "uma linha = um registo" no ficheiro. É a troca assumida para os `.md` deste
> projeto caberem em ecrãs estreitos.

---

## 4. SEGURANÇA DO `encoding-fix.php`

Reverter mojibake com `mb_convert_encoding($raw, 'ISO-8859-1', 'UTF-8')` **perde dados**:
caracteres CP1252 como `—` (U+2014), `€` (U+20AC), `"…"` (U+201C/201D) e `…` (U+2026)
**não existem** em ISO-8859-1 e seriam substituídos por `?`.

Este utilitário usa antes um **mapa CP1252 explícito** (28 posições `0x80–0x9F`, com as restantes
a serem Latin-1 directo) e **verifica por *round-trip***: re-codifica o resultado e compara-o
**byte a byte** com o original. Se não corresponder, **aborta sem escrever**.

Caracteres que **não** vieram deste bug são preservados tal como estão (contados como
`fora-do-mapa`), pelo que a ferramenta é segura em ficheiros parcialmente corrompidos.

---

## 5. MANUTENÇÃO DESTES UTILITÁRIOS

Sinta-se livre para os editar. Se mudar o comportamento:
1. Manter a convenção de **dry-run por omissão** e o `--write` explícito.
2. Manter as verificações de segurança antes de gravar.
3. Atualizar este README (seções 2 e 3).
4. Validar com `php -l` e correr `php tools/health-check.php` no projeto.

> Nota: estes utilitários são **independentes da aplicação** — não usam `app/`, `modules/` nem a BD.
> Podem ser corridos a qualquer momento, mesmo com o MySQL parado.
