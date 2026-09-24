# TEMPLATE — RELATÓRIO / ANÁLISE / AUDITORIA

> Molde. Copiar para `docs/out/<assunto>.md`. Preencher; apagar o que não se aplica.
> Regra: **cada afirmação com prova** (ficheiro:linha ou comando). Nada inferido sem o dizer.

**Data:** <<AAAA-MM-DD>> · **Âmbito:** <<ficheiros analisados>> · **Autor:** agente
**Natureza:** apoio (não normativo) — autoridade: `especificacao_mvp.md` + `docs/spec/`

## 1. RESUMO

| #    | Achado    | Onde          | Gravidade   | Estado                |
| :--- | :-------- | :------------ | :---------- | :-------------------- |
| A-01 | <<facto>> | `<<f>>:<<L>>` | 🔴/🟠/🟡/⚪ | aberto/corrigido/n.a. |

**Gravidade:** 🔴 bloqueia · 🟠 decisão necessária · 🟡 incoerência · ⚪ verificado OK (não mexer)

## 2. DETALHE

### A-01 · <<título do achado>>

**Afirmado em:** `<<ficheiro>>` §<<N>> — *"<<texto exato>>"*
**Prova:** `<<comando executado>>` → <<resultado>>
**Porque é problema:** <<consequência concreta>>
**Proposta:** <<ação>> ou **decisão necessária** <<alternativas>>

## 3. VERIFICADO E CORRETO (caderneta "não voltar a rever")

| Afirmação            | Prova                    | Medido em      |
| :------------------- | :----------------------- | :------------- |
| <<facto confirmado>> | `<<f>>:<<L>>` ou comando | <<AAAA-MM-DD>> |

## 4. LIMITES DO TRABALHO

- O que **não** foi verificado e porquê (ex.: requer Apache/MySQL, texto que não está no repositório).
- O que **não** foi tocado (fora de âmbito por decisão do humano).

## 5. PRÓXIMOS PASSOS

| #   | Ação     | Depende de       | Risco       |
| :-- | :------- | :--------------- | :---------- |
| 1   | <<ação>> | <<pré-condição>> | <<impacto>> |

**Gate:** pipeline + `php tools/health-check.php` → `TUDO OK`
