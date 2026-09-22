# DÚVIDAS, AMBIGUIDADES E PONTOS DE ATENÇÃO — SECADE BEAUTY
> 📌 **CONSOLIDAÇÃO DOCUMENTAL (21/09/2026):** o conteúdo relevante deste ficheiro foi
> **centralizado em `especificacao_mvp.md`** (documento-mestre, que **prevalece**).
> Este ficheiro mantém-se **apenas como registo histórico** — **não** deve ser usado como fonte de
> requisitos. Mapa documental completo: `especificacao_mvp.md` §29.2.

**Documento de apoio ao planeamento v3.0 | histórico**

> ✅ **ESTADO (21/09/2026): dúvidas resolvidas e implementadas** — ver as decisões ("RESOLUÇÃO")
> e o detalhe em `planeamento_geral.md` §2/§10/§18 e `relatorio_implementacao.md`.
> Este ficheiro mantém-se como **registo histórico** das questões levantadas.

---

## 1. Agendamento em Loja

### 1.1 Sinal de 10% — aplica-se?
**RESOLUÇÃO:** mantém-se na **loja** (10%, simulado na criação; `valor_sinal` gravado) e é
**dispensado** na 1ª marcação em ambulatório (nota no passo "Política de Sinal").

### 1.2 "Validação logística da loja" — quem e onde?
**RESOLUÇÃO:** é uma validação de **capacidade** (conflito de janela horária) feita
automaticamente na criação; o agendamento fica em `pendente_validacao_logistica_loja` e o
gestor vê-o no backoffice (filtro por estado), podendo cancelar.

### 1.3 Seleção de loja
**RESOLUÇÃO:** uma loja fixa (Évora). O wizard não pede escolha de loja.

---

## 2. Agendamento em Ambulatório

### 2.1 Duração do slot com serviços partilhados
**RESOLUÇÃO:** (a) **soma simples por pessoa** — cada serviço conta por pessoa que o recebe.
Implementado em `BookingService::resolveServicesForPeople()` / `bookingDuration()`.

### 2.2 Uma pessoa do agendamento = sempre o cliente logado?
**RESOLUÇÃO:** a **Pessoa 1** vem pré-preenchida com o nome do cliente logado; podem ser
adicionados familiares sem conta. Sem limite imposto; valida-se nome + ≥1 serviço por pessoa.

### 2.3 Morada única por agendamento
**RESOLUÇÃO:** confirmado — **uma morada por agendamento** (`agendamento.cliente_morada_id`).

### 2.4 OTP
**RESOLUÇÃO:** OTP **simulada** (6 dígitos no ecrã), validada contra a sessão, expira em 10 min,
aplicada em **todas** as marcações de ambulatório.

### 2.5 Quota-parte do cliente
**RESOLUÇÃO:** o campo `rota_ambulante.quota_parte_cliente` existe e é devolvido, mas fica a 0
no MVP — não há regra definida para o seu cálculo (não é cobrada ao cliente).

---

## 3. Dinâmica dos Funcionários

### 3.1 Troca de funcionário num serviço aceite
**RESOLUÇÃO:** aceitar um serviço já aceite por outro funcionário **transfere-o** (`isSwap=true`).

### 3.2 Concorrência no último serviço
**RESOLUÇÃO:** confirmado — a consolidação é transacional e bloqueia desfazer/trocar a partir
do momento em que o último serviço é aceite (409).

### 3.3 Bloqueio de janela temporal
**RESOLUÇÃO:** (b) — na consolidação verifica-se se existe **outro agendamento de ambulatório
consolidado/confirmado sobreposto** (`countByDateWindow` com a duração total); se existir, 409.

### 3.4 Aceitação de serviços de loja
**RESOLUÇÃO:** os serviços de loja são **automaticamente aceites**; aceitá-los no backoffice de
funcionário é rejeitado (409 — "apenas serviços de ambulatório").

### 3.5 Capacidade de funcionários por slot
**RESOLUÇÃO:** a disponibilidade baseia-se na **ausência de conflito de janela** (não no número
de funcionários, pois a equipa é atribuída por aceitação).

---

## 4. Recibos Verdes (Simulador)

### 4.1 Base de cálculo
**RESOLUÇÃO:** a percentagem aplica-se ao **`preco_praticado` do serviço individual**, no momento da aceitação.
Percentagens configuráveis pelo gestor com vigência por data (70/30 por omissão).

### 4.2 Recalculável?
**RESOLUÇÃO:** alterar a percentagem afeta **apenas aceitações futuras** (vigência por data);
as aceitações já registadas mantêm os valores gravados em `agendamento_servico`.

### 4.3 Persistência
**RESOLUÇÃO:** os valores são gravados **no próprio `agendamento_servico`**
(`percentagem_funcionario_aplicada`, `valor_recibo_verde_funcionario`, `valor_recibo_verde_plataforma`).
As percentagens vivem em `config_recibo_verde`.

---

## 5. Rotas e Gestor

### 5.1 Indicador de 50€
**RESOLUÇÃO:** referência de **lucro/rentabilidade da rota (dia+cidade)** e é **apenas visual**
(`meetsReference`) — não bloqueia nem decide nada.

### 5.2 Recusa de rota
**RESOLUÇÃO:** ao recusar, **todos** os agendamentos dessa cidade+dia passam a `cancelado`
(mensagem indica notificação simulada com alternativas).

### 5.3 Rotas parciais
**RESOLUÇÃO:** a decisão é sempre a **rota inteira** (dia+cidade).

### 5.4 Quando se decide a rota
**RESOLUÇÃO:** em qualquer momento (sem prazo-limite); a listagem permite escolher a data.

### 5.5 Rota vs. janela temporal
**RESOLUÇÃO:** a aprovação passa os agendamentos a `confirmado`, estado que conta para o
bloqueio de janela em consolidações futuras.

---

## 6. Calendário Fiscal

### 6.1 Fonte dos valores
**RESOLUÇÃO:** valores **introduzidos manualmente pelo gestor** (`valor_estimado`).

### 6.2 Periodicidades
**RESOLUÇÃO:** campo `periodicidade` escolhido por obrigação (mensal / trimestral / anual).

### 6.3 Alertas
**RESOLUÇÃO:** gerados **on-demand** (ao abrir o calendário/alertas), sem CRON, e **idempotentes**.

### 6.4 Estado e histórico
**RESOLUÇÃO:** basta **marcar como pago** (grava `data_pagamento` + observações); sem anexo.

---

## 7. Técnicas e Arquitetura

### 7.1 Nova pasta `admin/`
**RESOLUÇÃO (temporária):** backoffice implementado em `modules/backoffice/` (por instrução de não
tocar em `/admin`). A migração para `admin/` fica como trabalho futuro.

### 7.2 Sessões Main vs. Admin
**RESOLUÇÃO:** **sessão única** com validação de perfil por endpoint (`requireProfileApi`),
suficiente para o âmbito; a separação total de sessões não foi implementada.

### 7.3 Migrações de BD
**RESOLUÇÃO:** `DataBase_v2.sql` + migrações incrementais idempotentes
(`database_migration_v2.sql`, `database_migration_v3.sql`) + `database_seed.sql`.

### 7.4 Estados na BD
**RESOLUÇÃO:** lista final — agendamentos: `pendente_aceitacao_funcionarios`,
`pendente_validacao_logistica_loja`, `totalmente_aceite_funcionarios`, `confirmado`, `recusado`,
`cancelado`, `executado`, `concluido`. Rotas: `planeada`, `aprovada`, `recusada`, `em_execucao`, `concluida`.

### 7.5 Perfis
**RESOLUÇÃO:** `gestor` já existe em `utilizador.tipo_perfil`; perfis em uso: cliente, funcionário, gestor.

### 7.6 Serviços partilhados e agendamento_servico
**RESOLUÇÃO:** confirmado — **um registo por (agendamento, pessoa, serviço)**.

### 7.7 Feedback
**RESOLUÇÃO:** é **público** (sem moderação), alimentando os testemunhos da home; exige serviço
executado e é único por agendamento.

---

**Status:** ✅ Resolvido e implementado — mantido como registo histórico.
