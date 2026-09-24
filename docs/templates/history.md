# TEMPLATE — HISTÓRICO POR DATA (com revogados)

> Molde. Copiar para `docs/out/historico_<assunto>.md`. Preencher com o que o Git devolver; apagar o que
> não se aplica. **Legibilidade é prioridade** (lê-se para decidir).
> Regra: cada linha da cronologia tem **prova** (revisão). Nada inferido: o "porquê" sai da mensagem de
> commit, não da memória.

**Assunto:** <<§/RF/RN/D ou tema>> · **Contexto pedido:** <<âmbito extra>> · **Data:** <<AAAA-MM-DD>>
**Reconstruído com:** `docs/rules/build_history_on_demand.md` · **Natureza:** derivado do Git — **descartável**
**Âmbito analisado:** `<<HEAD | --all>>` · **Revisões cobertas:** `<<primeira>>..<<última>>`

## 1. CRONOLOGIA

| Data           | Rev        | O que mudou        | Porquê (da mensagem de commit) |
| :------------- | :--------- | :----------------- | :----------------------------- |
| <<AAAA-MM-DD>> | `<<hash>>` | <<facto concreto>> | <<razão>>                      |

## 2. ESTADO ATUAL

**Onde vive hoje:** `<<docs/spec/xx.md §N>>` — *"<<texto atual, curto>>"*

| #   | Regra em vigor | Fonte          | Âncora |
| :-- | :------------- | :------------- | :----- |
| 1   | <<regra>>      | `<<ficheiro>>` | §<<N>> |

## 3. REVOGADO E SUBSTITUÍDO

> O que morreu, quando, e **o que ficou no lugar**. Se não houve substituto, dizê-lo explicitamente —
> "sem substituto: o requisito desapareceu" é informação, não lacuna.

| O que foi revogado | Quando   | Rev        | Substituto                                    |
| :----------------- | :------- | :--------- | :-------------------------------------------- |
| <<regra antiga>>   | <<data>> | `<<hash>>` | <<regra nova + onde vive>> / *sem substituto* |

**Lacunas que a revogação deixou:** <<ex.: o custo intra-cidade ficou por estimar — §25.3>>

## 4. COMO RECUPERAR (comandos exatos)

```bash
# texto revogado: estado antes de o apagar
git show <<hash>>^:<<ficheiro>>            # → <<o que se encontra lá>>

# rasto do identificador através dos movimentos de ficheiro
git log -S '<<identificador>>' --date=short --pretty=format:'%h|%ad|%s' -- '*.md' '*.php'

# ficheiros apagados no tema
git log --diff-filter=D --name-only -- '*.md'
```

**Camada de origem:** <<1 (planeamento apagado) | 2 (mestre pré-`f75bb8d`) | 3 (router + spec atual)>>

## 5. LIMITES

- **Não verificado / não recuperável:** <<ex.: texto que chegou por Teams e nunca entrou no repositório>>
- **Ruído descartado:** <<nº>> checkpoints automáticos e <<nº>> eventos de des-versionamento (ver §4.2–4.3 da regra).
- **Não altera a especificação:** se algo aqui contradiz o estado atual, é **achado** a reportar.

**Gate:** `md-align-tables` → `ascii-align` → `php tools/health-check.php` = **TUDO OK**
