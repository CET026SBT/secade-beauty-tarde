# TEMPLATE — MAPA MENTAL / DIAGRAMA DE FLUXO

> Molde. Copiar para `docs/out/<assunto>.md`. Um diagrama **por ficheiro**, ligado ao código real.
> Regra: cada caixa/seta tem de existir no código; o que não existe marca-se ⬜ (a construir), não se inventa.

**Assunto:** <<camada/fluxo>> · **Data:** <<AAAA-MM-DD>>
**Fontes lidas:** `<<ficheiro:linha>>` (listar) · **Natureza:** apoio (não normativo)

## 1. DIAGRAMA

```text
┌─ NÍVEL ─────────────────────────────────────────────────────────────┐
│ <<elemento>> ──▶ [<<tabela_Bd>>]                                    │
│      │                                                              │
│      └── <<condição / guarda>> ──▶ <<estado / efeito>>              │
└─────────────────────────────────────────────────────────────────────┘
```

**Legenda:** `──▶` fluxo · `[x]` tabela BD · `⬜` ainda não existe · `⚠️` divergência detetada

## 2. TABELA DE RASTREIO (página → endpoint → ficheiros → tabela)

| Ecrã / gatilho | Endpoint        | Camadas                                      | Tabela       |
| :------------- | :-------------- | :------------------------------------------- | :----------- |
| <<UI>>         | `<<?action=…>>` | `<<Controller>>::<<método>>` → `<<Service>>` | `<<tabela>>` |

## 3. CONSTANTES E LIMIARES

| #   | Regra      | Onde                      | Valor     |
| :-- | :--------- | :------------------------ | :-------- |
| 1   | <<limiar>> | `<<Ficheiro>>::<<CONST>>` | <<valor>> |

## 4. DIVERGÊNCIAS (código ↔ especificação)

| #   | Especificação diz | Código faz | Ficheiro/linha | Ação |
| :-- | :---------------- | :--------- | :------------- | :--- |
| —   | (nenhuma)         | —          | —              | —    |

**Gate:** `php tools/ascii-align.php <f> --write` → `php tools/health-check.php` → `TUDO OK`
