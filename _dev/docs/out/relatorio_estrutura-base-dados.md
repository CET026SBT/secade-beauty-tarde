# ESTRUTURA DA BASE DE DADOS — SECADE BEAUTY

<!-- md-wrap-tables:max=220 -->

**Data:** 2026-10-03 · **Base:** `secade_beauty` (MySQL 8.4 · InnoDB · utf8mb4/unicode_ci)
**Fonte:** `DataBase.sql` (dump canónico — schema + dados) · **Natureza:** apoio (**não normativo**)
**Autoridade:** `especificacao_mvp.md` + `_dev/docs/spec/data-api.md` §17 · **Artefacto:** `_dev/docs/out/relatorio_estrutura-base-dados.md`

> **Para quem cai de paraquedas.** Leia de cima para baixo: em ~5 minutos fica com o modelo todo.
> Este relatório é o **mapa mental** da BD. O diagrama ER completo (mermaid) está na spec §17.8; o que a
> aplicação **escreve/lê** de cada tabela está em `relatorio_uso-base-dados.md`; as melhorias propostas, em
> `relatorio_melhorias-base-dados.md`.

## 1. O MODELO EM 6 PONTOS

- **25 tabelas.** Um utilizador tem **um só perfil** (`cliente` · `funcionario` · `gestor`), guardado em
  `utilizador.tipo_perfil`; não existe tabela `gestor`.
- **Dois canais de prestação:** `loja_fisica` (Évora) e `carrinha_ambulante` (itinerante por 10 cidades).
- **O núcleo é o `agendamento`:** cabeçalho (cliente, data, valor, estado) + linhas em `agendamento_servico`
  (um serviço por linha) e, no ambulatório, `agendamento_pessoa` (as pessoas atendidas).
- **A cidade nunca está no agendamento.** Deriva-se por `agendamento → cliente_morada → cidade`. A **rota** de
  uma cidade num dia (`rota_ambulante`) agrega agendamentos por **(data, cidade)** — é um cálculo, não uma FK.
- **O dinheiro** passa por `transacao_financeira` (movimentos) e `gorjeta`; o fiscal por `obrigacao_fiscal`
  (+ `alerta_fiscal`) e as comissões por `config_recibo_verde`.
- **Material de apoio** (catálogo, cidades, matriz de deslocação) é **dados de referência** — entra pelo dump
  e a aplicação **só lê**. Os **fornecedores** também vêm no dump, mas têm ecrã de gestão (`/gestao/fornecedores`).

## 2. CONVENÇÕES (o que se repete em todas as tabelas)

| Convenção               | Detalhe                                                                                                  |
| :---------------------- | :------------------------------------------------------------------------------------------------------- |
| **Nomes**               | Tabelas e colunas em **português, snake_case**; o código (PHP/JS) usa inglês camelCase.                  |
| **Chave primária**      | `id` surrogate `AUTO_INCREMENT`, exceto as tabelas 1:1 e as de associação.                               |
| **Perfis 1:1**          | `cliente` e `funcionario` **partilham o `id`** de `utilizador` (PK = FK, `ON DELETE CASCADE`).           |
| **Estados**             | `ENUM` com os valores do domínio (ex.: `agendamento.estado_reserva`, `rota_ambulante.estado_rota`).      |
| **Chaves estrangeiras** | `RESTRICT` protege histórico; `CASCADE` apaga dependentes; `SET NULL` liberta referências opcionais.     |
| **Datas**               | `datetime` para instantes de negócio, `date` para prazos/dias, `timestamp` para auditoria (`criado_em`). |
| **Dinheiro**            | `decimal(10,2)` (ou `12,2` no fiscal). O catálogo guarda o **líquido** (sem IVA) — D-16.                 |

## 3. AS 25 TABELAS POR GRUPO

### 3.1 Núcleo — pessoas e perfis

| Tabela           | Para que serve                                                                                                                         |
| :--------------- | :------------------------------------------------------------------------------------------------------------------------------------- |
| `utilizador`     | **Tabela-mãe de pessoas.** Credenciais e identidade: `nome`, `email` (único), `password_hash`, `telemovel`, `nif`, `tipo_perfil`.      |
| `cliente`        | 1:1 com `utilizador`; acrescenta `telemovel_validado_otp`.                                                                             |
| `funcionario`    | 1:1 com `utilizador`; acrescenta `tipo_contrato`, `salario_base`, `cc`, `ativo`.                                                       |
| `cliente_morada` | Moradas do cliente (**N por cliente**): `cidade_id`, `designacao`, `rua`, `numero_porta`, `andar_bloco`, `codigo_postal`, `principal`. |

### 3.2 Catálogo de serviços

| Tabela                   | Para que serve                                                                                                               |
| :----------------------- | :--------------------------------------------------------------------------------------------------------------------------- |
| `categoria_profissional` | 3 categorias (Cabeleireiro · Barbearia · Estética). Filtro visual.                                                           |
| `servico`                | 35 serviços: `nome`, `descricao`, `categoria_id`, `duracao_estimada_minutos`, `preco_base`, `requer_espaco_fisico`, `ativo`. |
| `servico_local`          | Disponibilidade/preço de um serviço por canal. **Vazia e sem uso** (ver relatório 2).                                        |
| `servico_foto`           | Galeria de imagens do serviço. **Vazia e sem uso.**                                                                          |

### 3.3 Geografia e logística

| Tabela              | Para que serve                                                                              |
| :------------------ | :------------------------------------------------------------------------------------------ |
| `cidade`            | 10 cidades do distrito de Évora (`nome`, `distrito`).                                       |
| `base_partida`      | Ponto de partida da carrinha (1 linha: Évora).                                              |
| `matriz_deslocacao` | Matriz base×cidade: `distancia_km`, `tempo_estimado_minutos`, `custo_estimado_combustivel`. |

### 3.4 Agendamento (o coração do sistema)

| Tabela                | Para que serve                                                                                                                                                                                 |
| :-------------------- | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `agendamento`         | **Cabeçalho.** `cliente_id`, `cliente_morada_id` (NULL na loja), `local_prestacao`, `data_hora_pretendida`, `estado_reserva`, `valor_total`, `valor_sinal`, `sinal_pago`,                      |
|                       | `validado_logistica_loja`, `modo_urgencia`, `criado_em`.                                                                                                                                       |
| `agendamento_pessoa`  | Pessoas de um agendamento de ambulatório (`nome_pessoa`, `observacoes`). **Não existe na loja.**                                                                                               |
| `agendamento_servico` | **Uma linha por (agendamento, pessoa, serviço).** Preço e duração praticados, `estado_aceitacao`, `funcionario_id` atribuído e os valores do recibo verde (`percentagem_funcionario_aplicada`, |
|                       | `valor_recibo_verde_funcionario`, `valor_recibo_verde_plataforma`).                                                                                                                            |

### 3.5 Operação e rotas

| Tabela                 | Para que serve                                                                                                                                                |
| :--------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `execucao_agendamento` | Registo da execução real (1 por agendamento): início/fim reais, `estado_execucao`, notas do técnico.                                                          |
| `feedback_cliente`     | Avaliação do cliente (1 por execução): `classificacao_estrelas` (1–5), `comentario`.                                                                          |
| `rota_ambulante`       | Rota (data × cidade): custo, quota do cliente, lucros e a **decisão manual** do gestor (`estado_rota`, `decidido_por`, `decidido_em`, `observacoes_decisao`). |
| `rota_funcionario`     | Equipa alocada a uma rota (N:N). **Vazia e sem uso.**                                                                                                         |

### 3.6 Financeiro, fiscal e pessoal

| Tabela                 | Para que serve                                                                                                   |
| :--------------------- | :--------------------------------------------------------------------------------------------------------------- |
| `config_recibo_verde`  | Percentagens do recibo verde (funcionário/plataforma, soma = 100) e `data_vigencia`.                             |
| `obrigacao_fiscal`     | Obrigações (IVA, IRC, SS, Seguros): `periodicidade`, `valor_estimado`, `data_prazo`, `estado`, `data_pagamento`. |
| `alerta_fiscal`        | Alertas progressivos de uma obrigação (30/15/7/3/1 dia, `em_atraso`) e `visualizado`.                            |
| `transacao_financeira` | Movimentos (sinal, restante, integral, quota de deslocação), método e nº de recibo. **Sem UI.**                  |
| `fecho_caixa_diario`   | Auditoria de caixa por dia/funcionário. **Sem UI.**                                                              |
| `gorjeta`              | Gorjetas registadas. **Sem UI.**                                                                                 |

### 3.7 Fornecedores

| Tabela       | Para que serve                                                                                                                     |
| :----------- | :--------------------------------------------------------------------------------------------------------------------------------- |
| `fornecedor` | Fornecedores do espaço (produtos, rendas, serviços externos): `nome`, `nif`, contactos, `ativo`, `observacoes`. 43 registos reais. |

## 4. AS RELAÇÕES QUE IMPORTAM (31 chaves estrangeiras)

```text
utilizador --1:1--> cliente --N--> cliente_morada --N--> cidade
     |                   |                                 |
     |                   +-------N--> agendamento         |
     +--1:1--> funcionario                  |             |
                                            |             |
                        agendamento --N--> agendamento_pessoa
                             |  +---N--> agendamento_servico ---N--> servico --N--> categoria_profissional
                             |                  |                       |
                             |                  +---N--> funcionario <--+
                             +--1:1--> execucao_agendamento --1:1--> feedback_cliente
                             +--N--> transacao_financeira
                             +--(data,cidade)--> rota_ambulante --N--> rota_funcionario --N--> funcionario
                                                      |--- base_partida
                                                      |--- cidade
cidade --N--> matriz_deslocacao --N--> base_partida    obrigacao_fiscal --N--> alerta_fiscal
fornecedor (isolada)  ·  config_recibo_verde.configurado_por --> utilizador
```

**Os quatro caminhos que explicam o sistema:**

1. **Quem marca:** `utilizador → cliente → agendamento → agendamento_servico → servico` (e, no domicílio,
   `cliente_morada → cidade`).
2. **Quem executa:** `agendamento_servico.funcionario_id → funcionario → utilizador`; a execução fecha em
   `execucao_agendamento` e a avaliação em `feedback_cliente`.
3. **A carrinha:** os agendamentos agrupam-se por **(data, cidade)** numa `rota_ambulante`, decidida pelo
   gestor; o custo vem de `matriz_deslocacao`.
4. **O dinheiro/fisco:** movimentos em `transacao_financeira`, comissões por `config_recibo_verde`, prazos em
   `obrigacao_fiscal` com lembretes em `alerta_fiscal`.

## 5. PARTICULARIDADES A NÃO ESQUECER

- **`agendamento` não tem `cidade_id` nem `rota_ambulante_id`.** A cidade sai por `cliente_morada`; a ligação à
  rota é feita por **(data, cidade)** — não há FK (a rota é um agregado calculado).
- **`rota_ambulante` não tem filhos de agendamento.** Os agendamentos que a compõem leem-se por data+cidade.
- **Os perfis 1:1 partilham o id:** o `cliente` #3 é o `utilizador` #3 (não há coluna `utilizador_id`).
- **`cliente_morada.rua` é `NOT NULL`** mas aceita string vazia — nos clientes importados vem `''` (só a
  cidade preenchida).
- **Estados vivem em `ENUM`,** não em tabela de lookup — mudar um estado implica alterar o schema.
- **6 tabelas estão criadas mas a aplicação não lhes toca** (`servico_local`, `servico_foto`,
  `rota_funcionario`, `transacao_financeira`, `fecho_caixa_diario`, `gorjeta`) — prova no
  `relatorio_uso-base-dados.md`. São estrutura prevista no domínio, ainda sem interface.
