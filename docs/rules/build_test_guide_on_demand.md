# RULE — build_test_guide_on_demand

**Objetivo:** gerar o **guia de teste manual passo-a-passo** quando pedido (file-based ou UI).
**Saída:** `docs/out/guia_teste_manual.md` (descartável; regenerável a qualquer momento).
**Não confundir com:** testes automatizados — esses são `tests/` (`tests/README.md`).

## 1. QUANDO

| Gatilho                                | Ação                                           |
| :------------------------------------- | :--------------------------------------------- |
| "preciso de testar à mão o fluxo X"    | guia **só** do fluxo X                         |
| "guia de teste completo"               | guia integral (todas as fases)                 |
| "provar a entrega ao formador/cliente" | guia integral + secção de problemas conhecidos |

**NUNCA** gerar preventivamente, nem manter um guia desatualizado em disco: se o código muda, o guia
antigo é **mentira** — apagar e regenerar é mais barato que corrigir.

## 2. FONTES A LER (por esta ordem, só o necessário)

| Preciso de…           | Ler                                                               |
| :-------------------- | :---------------------------------------------------------------- |
| o que o sistema faz   | `docs/spec/requirements.md` (§4) · `operations.md` · `booking.md` |
| regras e estados      | `docs/spec/booking.md` (§8, §9, §20) · `finance.md`               |
| o que já está testado | `tests/README.md` · §26 (`delivery.md`)                           |
| arrancar do zero      | `docs/spec/delivery.md` §27 (instalação/importação)               |
| verdade do código     | ler o **Controller/Service/JS** do fluxo em causa                 |
| dados de apoio        | `DataBase_v3.sql` (contagens) — **contar, não presumir**          |

## 3. ESTRUTURA OBRIGATÓRIA

Seguir `docs/templates/test-guide.md`. Um guia é aceitável quando:

1. **Pré-requisitos** verificáveis (Apache/MySQL, internet, browser com DevTools).
2. **Passos numerados** com Ação → **Esperado** → ☐ OK, um por vez.
3. **Prova de API**: o endpoint e o código HTTP esperado em cada passo que o toque.
4. **Prova de BD**: `SELECT` de verificação (antes → depois), colado como SQL legível.
5. **Estados e IDs reais** lidos do schema — nunca inventados.
6. **Problemas conhecidos** no fim (com o *porquê*, para não parecerem avarias).
7. **Zero promessas**: o que não existe no código não entra no guia.

## 4. GATE

| Passo | Comando                                              |
| :---- | :--------------------------------------------------- |
| 1     | `php tools/ascii-align.php <f> --write` (SQL/blocos) |
| 2     | `php tools/md-align-tables.php <f> --write`          |
| 3     | `php tools/health-check.php` → **TUDO OK**           |

UTF-8 sem BOM · CRLF · escrever por `tools/file-edit.php` ou via .NET.

## 5. CUSTO ESPERADO

| Guia     | Ler                                                  | Escrever        |
| :------- | :--------------------------------------------------- | :-------------- |
| um fluxo | 1 domínio + o Controller/Service                     | ~80-150 linhas  |
| integral | `requirements.md` + `booking.md` + `delivery.md` §27 | ~500-900 linhas |
