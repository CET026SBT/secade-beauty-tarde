# TEMPLATE — RELATÓRIO / ANÁLISE / AUDITORIA

> Molde. Copiar para `_dev/docs/out/relatorio_<slug>.md` (DOUTRINA 8 de `_dev/docs/README.md`:
> **regenerado no mesmo nome**; sem limite de linhas). Preencher; apagar o que não se aplica.
> Regra: **cada afirmação com prova** (ficheiro:linha ou comando). Nada inferido sem o dizer.
> **Para quem lê de fora:** diz o que é cada coisa antes de a julgar e escreve cada achado com
> *prova → consequência → proposta*.
> Cada achado marca-se com o seu ID (`<!-- id:A-01 -->`), que passa a ser referenciável
> (`git ref-open A-01`) e citável da especificação.

**Data:** <<AAAA-MM-DD>> · **Âmbito:** <<ficheiros analisados>> · **Autor:** agente
**Artefacto:** `_dev/docs/out/relatorio_<<slug>>.md` · **Natureza:** apoio (não normativo)
**Autoridade:** `especificacao_mvp.md` + `_dev/docs/spec/`

## 1. RESUMO

| #    | Achado    | Onde          | Gravidade   | Estado                |
| :--- | :-------- | :------------ | :---------- | :-------------------- |
| A-01 | <<facto>> | `<<f>>:<<L>>` | 🔴/🟠/🟡/⚪ | aberto/corrigido/n.a. |

**Gravidade:** 🔴 bloqueia · 🟠 decisão necessária · 🟡 incoerência · ⚪ verificado OK (não mexer)

## 2. DETALHE

### A-01 · <<título do achado>>  <!-- id:A-01 -->

**Afirmado em:** `<<ficheiro>>` §<<N>> — *"<<texto exato>>"*
**Prova:** `<<comando executado>>` → <<resultado>>
**Porque é problema:** <<consequência concreta>>
**Proposta:** <<ação>> ou **decisão necessária** <<alternativas>>
**IDs relacionados:** <<`Q-nn`/`C-nn` do artefacto de origem, `RF-nn`, `§N`>>

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

**Gate:** pipeline + `php _dev/tools/health-check.php` → `TUDO OK`
