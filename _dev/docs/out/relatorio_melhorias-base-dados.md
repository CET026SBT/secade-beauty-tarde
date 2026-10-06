# MELHORIAS PROPOSTAS À BASE DE DADOS — redundâncias, conflitos e higiene

<!-- md-wrap-tables:max=220 -->

**Data:** 2026-10-03 · **Base:** `secade_beauty` · **Natureza:** apoio (**não normativo**)
**Autoridade:** `especificacao_mvp.md` + `_dev/docs/spec/` · **Artefacto:** `_dev/docs/out/relatorio_melhorias-base-dados.md`

> **Como usar:** cada linha é uma observação com **evidência** (ver `relatorio_uso-base-dados.md`) e uma
> **proposta**. Nada aqui está implementado — é uma lista para decisão. Alterações de schema exigem
> **justificação de BD registada** (`.clinerules` §1 · `data-api.md` §17.9).

## 1. REDUNDÂNCIAS E CONFLITOS DE FONTE DE VERDADE

| #   | Onde                                                              | Problema                                                            | Proposta                                                             |
| :-- | :---------------------------------------------------------------- | :------------------------------------------------------------------ | :------------------------------------------------------------------- |
| R-1 | `agendamento.valor_total` vs.                                     | **Dois totais** para a mesma coisa. Se um serviço mudar, o total    | Manter `valor_total` como *cache* (aceitável por performance) mas    |
|     | `SUM(agendamento_servico.preco_praticado)`                        | pode divergir do somatório das linhas.                              | **documentar** que é derivado, ou recalculá-lo sempre na escrita das |
|     |                                                                   |                                                                     | linhas.                                                              |
| R-2 | `agendamento.sinal_pago`/`valor_sinal` vs. `transacao_financeira` | O sinal vive em **dois sítios**: no cabeçalho e (futuramente) no    | Eleger **uma** fonte. Sugestão: `transacao_financeira` como livro    |
|     |                                                                   | livro de movimentos — fonte de verdade ambígua.                     | real e `sinal_pago` como flag derivada/estado.                       |
| R-3 | `servico_local.preco_especifico`/`ajuste_logistico` vs.           | Preço do serviço pode existir em **dois sítios** — risco de         | Fixar a regra: o preço efetivo = `preco_base` **+** ajuste, com      |
|     | `servico.preco_base`                                              | incoerência entre loja e carrinha.                                  | `preco_especifico` só como *override* documentado.                   |
| R-4 | `utilizador` (sem `ativo`) vs. `funcionario.ativo` /              | **Soft-delete só parcial.** Não há forma de desativar um cliente ou | ➕ `utilizador.ativo` e passar a desativar na tabela-mãe (as filhas  |
|     | `fornecedor.ativo`                                                | gestor.                                                             | herdam).                                                             |
| R-5 | `rota_ambulante.quota_parte_cliente`                              | Coluna **escrita sempre a `0.0`** (`RotaService` L151) — nunca      | Ou implementar o cálculo (partilha de custo entre clientes), ou      |
|     |                                                                   | calculada.                                                          | remover a coluna.                                                    |
| R-6 | Estados em `ENUM` vs. lógica de estados no código                 | Os valores de estado estão **duplicados** (schema + PHP/JS), sem    | Documentar o `ENUM` como contrato (§8/§20) **ou** criar tabela de    |
|     |                                                                   | tabela de lookup. Divergem em silêncio.                             | estados — não deixar implícito.                                      |
| R-7 | `cliente_morada.principal`                                        | A regra «**uma só** morada principal por cliente»                   | ➕ índice único parcial (`UNIQUE(cliente_id) WHERE principal=1`) —   |
|     |                                                                   | **não é garantida pela BD** (só pelo código).                       | exige MySQL 8 com *functional index* ou *trigger*.                   |
| R-8 | `config_recibo_verde.data_vigencia`                               | Várias vigências podem sobrepor-se; `findActive` resolve «a mais    | ➕ `UNIQUE(data_vigencia)` para garantir uma configuração por data.  |
|     |                                                                   | recente», mas a BD não impede duplicados.                           |                                                                      |

## 2. COLUNAS MORTAS OU MEIAS-IMPLEMENTADAS (higiene)

| Coluna                                      | Situação                | Decisão a tomar                                                                 |
| :------------------------------------------ | :---------------------- | :------------------------------------------------------------------------------ |
| `agendamento.modo_urgencia`                 | **0 referências**       | **Remover** ou implementar (marcação urgente com taxa).                         |
| `agendamento.validado_logistica_loja`       | Lida, **nunca escrita** | **Remover** ou implementar a validação logística da loja (hoje não existe).     |
| `cliente.telemovel_validado_otp`            | Escrita só a `0`        | **Remover** ou persistir a validação OTP (hoje fica em sessão).                 |
| `transacao_financeira.estado_offline`       | **0 referências**       | Decidir com a R-2 (se a tabela for usada, dar-lhe uso; senão, remover colunas). |
| `transacao_financeira.recibo_manual_numero` | **0 referências**       | Idem.                                                                           |

> **Regra prática:** coluna que ninguém lê nem escreve é *dívida*: ou ganha uso, ou sai do schema. Cada uma
> que sai reduz o ruído de quem lê o modelo.

## 3. MELHORIAS ESTRUTURAIS

| #   | Área                  | Proposta                                                                                                               | Ganho                                                           |
| :-- | :-------------------- | :--------------------------------------------------------------------------------------------------------------------- | :-------------------------------------------------------------- |
| M-1 | **Índices**           | Criar índices para os filtros reais: `agendamento(data_hora_pretendida)`, `agendamento(estado_reserva)`,               | Listagens e painéis deixam de varrer tabelas inteiras.          |
|     |                       | `agendamento_servico(estado_aceitacao, agendamento_id)`, `feedback_cliente(data_feedback)`.                            |                                                                 |
| M-2 | **Integridade**       | `cliente_morada.rua` é `NOT NULL` mas aceita `''`. Trocar por regra de negócio explícita (`NULL` = por preencher).     | Consistência dos dados importados.                              |
| M-3 | **Integridade**       | `feedback_cliente.classificacao_estrelas` tem CHECK 1–5 ✅ (bom exemplo a replicar em `agendamento.valor_total >= 0`). | Mais regras no schema, menos no código.                         |
| M-4 | **Decisão de âmbito** | As 6 tabelas intocadas (`servico_foto`, `servico_local`, `rota_funcionario`, `transacao_financeira`,                   | Evita que pareçam avaria ou esquecimento.                       |
|     |                       | `fecho_caixa_diario`, `gorjeta`): **implementar** (§24.2/simulador de pagamentos) ou **marcar explicitamente** como    |                                                                 |
|     |                       | «previstas, sem uso».                                                                                                  |                                                                 |
| M-5 | **Contadores**        | `alerta_fiscal.visualizado` é **global** (sem `utilizador_id`) — o sino mostra o mesmo a todos.                        | Ligar os avisos ao utilizador (C-03, já registado).             |
| M-6 | **Manutenção**        | Manter `DataBase.sql` e `DataBase_clean.sql` **sincronizados** (o `_clean` é gerado; hoje verificou-se fiel por        | Evita que o ficheiro de leitura minta.                          |
|     |                       | `CHECKSUM`).                                                                                                           |                                                                 |
| M-7 | **Dados**             | Rever os *placeholders* importados: e-mails `@cliente.secade.local`, `telemovel=''`, `password_hash='*'` e moradas só  | Decidir se se mantêm ou se o cliente fornece os reais (§24.11). |
|     |                       | com cidade.                                                                                                            |                                                                 |

## 4. PRIORIDADE SUGERIDA

| Prioridade | Itens                                                       | Porquê                                                 |
| :--------- | :---------------------------------------------------------- | :----------------------------------------------------- |
| **P1**     | R-1, R-2 (fonte de verdade do dinheiro) · M-1 (índices)     | Corretude financeira e desempenho — mais barato agora. |
| **P2**     | R-4 (soft-delete uniforme) · R-7, R-8 (unicidade) · M-2/M-3 | Integridade garantida pela BD, não pelo código.        |
| **P3**     | §2 colunas mortas · R-3, R-5 · M-4/M-5                      | Higiene e clareza do modelo; sem risco imediato.       |

> **Nota final:** nenhuma proposta altera o comportamento atual da aplicação; todas passam por **decisão
> registada** e por novo dump (`DataBase.sql`). Este relatório não substitui a especificação — aponta-a
> (§17 · §22 · §24) para onde a decisão deve ser escrita.
