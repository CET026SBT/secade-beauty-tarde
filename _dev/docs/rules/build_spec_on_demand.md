# RULE — build_spec_on_demand

**Objetivo:** alterar a especificação **só quando pedido**, gastando o mínimo de contexto.
**Âmbito:** `especificacao_mvp.md` (router) e `_dev/docs/spec/*.md` (N2).
**Não usar para:** relatórios, auditorias, mapas — têm regra própria (`_dev/docs/README.md`).

## 1. GATILHOS (sem um destes, NÃO escrever)

| Gatilho                           | Ação                                                                  |
| :-------------------------------- | :-------------------------------------------------------------------- |
| "adiciona/atualiza o requisito X" | novo `RF-nn` ou `RN-nn` (+ `D-nn` se decisão)                         |
| "regista esta decisão"            | nova `D-nn` em §3 + regras associadas                                 |
| "isto deixou de valer"            | **substituir** a regra; o histórico é o Git — não acumular revogações |
| "muda o comportamento de Y"       | atualizar a RN (§5) **e** o módulo (§6–§16)                           |
| "corrige o texto da secção Z"     | edição local, sem renumerar                                           |

**NUNCA:** criar documento novo sem pedido · duplicar conteúdo de outro ficheiro · alterar
`_dev/mapaMentalMVP/` (é apoio não normativo) · mexer em `_dev/docs/` fora do domínio pedido.

## 2. LOCALIZAR (mínimo de leitura)

1. Abrir `especificacao_mvp.md` (**67 linhas**) → tabela **MAPA** → identificar o **domínio**.
2. Ler **apenas** `_dev/docs/spec/<dominio>.md`. Nunca abrir os 9.
3. Se o assunto toca noutro domínio: ler só a secção `§N` desse ficheiro (grep antes de ler):
   `php _dev/tools/file-edit.php grep "§18" --ext=md`

> `§N` = número do capítulo, preservado. Ficheiros podem ter capítulos não sequenciais (ex.: `booking.md`
> tem §8, §9, §12, §20) — é intencional: **o número identifica o conteúdo, não a posição**.

## 3. ESCREVER

| Passo | Regra                                                                                                                                                                                   |
| :---- | :-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1     | **Número primeiro:** `RF-nn` / `RN-nn` / `D-nn` conforme §29.3 (§29 = `_dev/docs/spec/annex.md`). Nunca reutilizar um número extinto.                                                   |
| 2     | **Estado explícito:** ✅ feito · 🟡 parcial · ⬜ por fazer. Nenhum requisito sem estado.                                                                                                |
| 3     | **Evidência:** ao marcar gap, indicar o ficheiro/linha onde se verificou.                                                                                                               |
| 4     | **Uma fonte por facto.** Se já existe no `.clinerules`, `_dev/tests/README.md` ou `_dev/tools/README.md` → **apontar**, não copiar.                                                     |
| 5     | **Escrever seguro:** `php _dev/tools/file-edit.php replace <f> <jobs.json> --write` (evita BOM/mojibake).                                                                               |
| 6     | **Limiar:** se o ficheiro alvo passar de **3000 linhas**, **modularizar antes de escrever** (DOUTRINA 6 de `_dev/docs/README.md`).                                                      |
| 7     | **Para quem chega de fora:** cada módulo abre com uma frase de contexto (o que resolve e a quem se aplica) e define os termos próprios que usa — o detalhe é bem-vindo, o implícito não |
|       | (DOUTRINA 6).                                                                                                                                                                           |

## 4. FECHAR O CICLO (mesma alteração, sempre)

Implementação concluída da §24/§25 obriga a atualizar **os quatro**:

| O quê    | Onde                   |
| :------- | :--------------------- |
| estado   | §4 → `requirements.md` |
| gap      | §24 → `backlog.md`     |
| critério | §28 → `delivery.md`    |
| testes   | §26 → `delivery.md`    |

Se a mudança toca **convenções aplicadas** → também `.clinerules` §2. Se cria **endpoint** → §19
(`data-api.md`). Se muda **tabela/coluna** → §17 (`data-api.md`) + justificação registada.

## 5. GATE

Correr o GATE de `_dev/docs/README.md` (pipeline + `health-check` → `TUDO OK`).
Falha típica: mover conteúdo e partir `§NN` → o `md-verify` di-lo-á pelo nome do ficheiro.

## 6. CUSTO ESPERADO (orçamento de contexto)

| Operação                 | Ler                          | Escrever           |
| :----------------------- | :--------------------------- | :----------------- |
| acrescentar um requisito | router + 1 domínio (~150 l.) | 1 linha + estado   |
| registar uma decisão     | router + `core.md` (~150 l.) | 1 bloco em §3 + RN |
| substituir uma regra     | router + 1 domínio           | 1 linha            |
| corrigir texto           | grep + 1 secção              | 1 linha            |

> Se uma alteração exigir ler **mais de dois** ficheiros da spec, o assunto está mal localizado:
> corrigir primeiro o **MAPA** do router, depois a alteração.
