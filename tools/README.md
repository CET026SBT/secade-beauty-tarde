# TOOLS — UTILITÁRIOS DE MANUTENÇÃO (dev-only)

Pasta de ferramentas de apoio ao desenvolvimento. **Não faz parte da aplicação** e não é
servida pela web (ver `.htaccess`).

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

| Ficheiro              | Para que serve                                                                                                                    |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| `_common.php`         | Módulo comum: I/O UTF-8 seguro, CLI, largura de texto/emoji, deteção de encoding. **Não executar directamente**                   |
| `encoding-check.php`  | Deteta BOM, mojibake, UTF-8 inválido, **UTF-16** e fins de linha mistos. Aceita `--ignore=` (exceções conhecidas)                 |
| `encoding-fix.php`    | **Repara** BOM e mojibake (mapa CP1252 explícito + verificação *round-trip*) e **converte UTF-16 → UTF-8**                        |
| `md-align-tables.php` | Alinha as tabelas markdown (largura de ecrã, preserva emoji; ignora *code fences*)                                                |
| `md-verify.php`       | Verifica encoding, code fences, referências `§NN` (com **resolução cruzada** no documento-mestre), tabelas e marcadores residuais |
| `file-edit.php`       | `show` / `write` / `replace` / `lines` / `grep` — leitura e escrita UTF-8 **segura**                                              |
| `health-check.php`    | Corre tudo de uma vez e dá um resumo                                                                                              |

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

# Alinhar tabelas de um .md
php tools/md-align-tables.php especificacao_mvp.md
php tools/md-align-tables.php especificacao_mvp.md --write

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
