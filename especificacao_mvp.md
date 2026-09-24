# SECADE BEAUTY — ESPECIFICAÇÃO (ROUTER)
**Documento-mestre (fonte única)** · Versão 3.0 · 24/09/2026 · branch **`agent-workspace`**
**Âmbito:** requisitos, decisões, regras, dados, API e entrega. O detalhe está **dividido por domínio** em `docs/spec/`.

> ⚠️ **PREVALÊNCIA:** em conflito, prevalece a especificação. O que é **operação** vive no `.clinerules`;
> as **ferramentas** em `tools/README.md`; os **testes** em `tests/README.md`. Não se duplica: **aponta-se**.

## MAPA — ler só o domínio necessário

| §              | Domínio                                                  | Ficheiro                    |
| :------------- | :------------------------------------------------------- | :-------------------------- |
| 1–3            | Núcleo, Regras de Ouro e prevalência, decisões D-01…D-11 | `docs/spec/core.md`         |
| 4–5            | Requisitos (RF) e regras de negócio (RN)                 | `docs/spec/requirements.md` |
| 6, 7, 10, 16   | Perfis, catálogo, equipa, feedback                       | `docs/spec/operations.md`   |
| 8, 9, 12, 20   | Loja, carrinha, rotas, máquina de estados                | `docs/spec/booking.md`      |
| 11, 13, 14, 15 | Recibos verdes, fiscal, pagamentos, cancelamentos        | `docs/spec/finance.md`      |
| 17, 18, 19     | Modelo de dados, arquitetura, API                        | `docs/spec/data-api.md`     |
| 21, 22, 26–28  | Roadmap, limitações, testes, instalação, critérios       | `docs/spec/delivery.md`     |
| 24, 25         | Gap (§24) e trabalho futuro (§25)                        | `docs/spec/backlog.md`      |
| 23, 29         | Anexos: glossário, mapa documental, manutenção           | `docs/spec/annex.md`        |

**Regra de leitura:** identificar o domínio → abrir **UM** ficheiro. Nunca carregar `docs/spec/` inteiro.
**Convenção de referências:** `§N` = secção **da especificação** (onde quer que esteja em `docs/spec/`);
as secções do próprio `.clinerules` escrevem-se `rules §N`.

## COMO USAR

| Se quer…                                    | Vá a                    |
| :------------------------------------------ | :---------------------- |
| Saber **o que o sistema faz**               | §4 (RF) · §5 (RN)       |
| Saber **que decisão foi tomada e porquê**   | §3                      |
| Escrever **código**                         | §18 · §19 · `rules §2`  |
| **Commits / branches**                      | `rules §4`              |
| **Editar ficheiros** (encoding seguro)      | `tools/README.md` §1    |
| **Estados** de um agendamento               | §20                     |
| Saber **o que falta fazer**                 | §24 · §25               |
| **Instalar / importar**                     | §27                     |
| **Testar**                                  | `tests/README.md` · §28 |
| **Gerar documentação / mapas / relatórios** | `docs/README.md`        |

**Identificadores:** `RF-nn` requisito · `RN-nn` regra · `D-nn` decisão. Referências cruzadas usam estes IDs.

## FONTE ÚNICA POR ASSUNTO

| Assunto                                 | Fonte                                | Âncora          |
| :-------------------------------------- | :----------------------------------- | :-------------- |
| Requisitos e regras de negócio          | especificação                        | §4 · §5         |
| Decisões finais e conflitos resolvidos  | especificação                        | §3              |
| Dados, API e máquina de estados         | especificação                        | §17 · §19 · §20 |
| Arquitetura por camadas                 | especificação                        | §18.1–§18.4     |
| Convenções de código **aplicadas**      | `.clinerules`                        | §2              |
| Git e commits                           | `.clinerules`                        | §4              |
| Ferramentas: uso e catálogo             | `tools/README.md`                    | §1 · §2 · §3    |
| Testes: suites e execução               | `tests/README.md`                    | —               |
| Regras de documentação on-demand        | `docs/README.md` · `docs/rules/`     | —               |
| Guia de teste manual                    | `mapaMentalMVP/guia_teste_manual.md` | —               |
| Análise e auditoria (**não normativo**) | `mapaMentalMVP/*.md`                 | —               |

## HISTÓRICO

Os `.pdf` iniciais e os `.md` de planeamento anteriores foram **eliminados**: o arquivo é o **histórico do
Git** — regras revogadas e decisões antigas **não se acumulam** neste documento.
`git log --diff-filter=D --oneline -- "*.md"` para localizar; mapa documental em **§29.2**.
