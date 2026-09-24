# Especificação — Núcleo, decisões e prevalência

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../../especificacao_mvp.md).
> Capítulos: §1 · §2 · §3

## 1. VISÃO GERAL, CONTEXTO E STACK

**Secade Beauty** é um sistema de agendamentos para um negócio híbrido de beleza:
- **Loja Física** em Évora — Terça a Sábado, 09:00–19:00
- **Serviço Ambulatório** (domicílio / carrinha itinerante) — cidades do distrito de Évora

**Público-alvo:** idosos com mobilidade reduzida, famílias rurais e clientes que procuram
serviços de beleza e bem-estar acessíveis.

**Problema que resolve:** levar o serviço ao cliente (em vez de exigir deslocação),
mantendo uma loja física como base e oferecendo gestão de rotas, equipa e obrigações fiscais
num backoffice único.

### 1.1 Stack tecnológica (fixa — sem frameworks externos)

| Camada         | Tecnologia                                                             |
| :------------- | :--------------------------------------------------------------------- |
| Backend        | **PHP puro** 7.4+ (executado em 8.3) + **PDO** com prepared statements |
| Base de dados  | **MySQL 8.4.3** (Community Server)                                     |
| Arquitetura    | **MVC custom** (Controller → Service → Repository + Mapper)            |
| Frontend       | HTML5, CSS3, **JavaScript vanilla ES6+**, jQuery 3.x                   |
| UI             | **Bootstrap 5.0.0**, Bootstrap Icons / Font Awesome                    |
| Bibliotecas UI | Owl Carousel 2, WOW.js + Animate.css, Lightbox, Isotope                |
| Ambiente       | Laragon (Apache 2.4 + MySQL + PHP), HeidiSQL, VS Code                  |
| Charset / TZ   | UTF-8 · `Europe/Lisbon`                                                |
| Idioma         | **Código em inglês · Base de dados em português**                      |

**Base URL local:** `http://localhost/secade-beauty-tarde`

## 2. REGRAS DE OURO E PREVALÊNCIA

### 2.0 Fluxo das regras — o que prevalece sobre o quê

| #   | Camada                                         | Papel                                                                             | Fonte          |
| :-- | :--------------------------------------------- | :-------------------------------------------------------------------------------- | :------------- |
| 1   | Regras de operação                             | Como o trabalho é executado (restrições, convenções **aplicadas**, Git)           | `.clinerules`  |
| 2   | **Regras de Ouro (§2.A–§2.E)**                 | Invariantes de produto — nenhuma decisão as contraria                             | este documento |
| 3   | **Decisões finais (§3 · D-01…D-11)**           | Resolvem cada conflito de fontes; prevalecem sobre as fontes originais            | este documento |
| 4   | **Regras de negócio (§5 · RN-01…RN-29)**       | Regra operativa e testável; **no detalhe, a RN vence a §2** (a §2 dá o princípio) | este documento |
| 5   | Requisitos e módulos (§4 · §6–§16 · §19 · §20) | O que o sistema faz e como; **conforma-se** às camadas 2–4                        | este documento |
| 6   | Gap, futuro e critérios (§24 · §25 · §28)      | O que falta, por que ordem, e como se aceita                                      | este documento |
| 7   | Manutenção (§29.3)                             | Meta-regras deste documento                                                       | este documento |

**Em conflito:** prevalece a camada de **número menor**; dentro da mesma camada, a regra **mais
específica**. Quem deteta a colisão corrige-a na mesma alteração (§29.3.7).

**Fora da hierarquia (não normativos):** `_dev/mapaMentalMVP/*` (análise, auditorias e guias) e `README.md`
(instalação). O `_dev/tests/README.md` é a autoridade sobre **como testar** e o `_dev/tools/README.md` sobre o
**comportamento das ferramentas**. Em qualquer conflito de **produto**, prevalece **este documento**.

### A. Separação Main vs. Backoffice
`modules/main/` é **exclusivo de clientes**; o backoffice (`modules/backoffice/`) é restrito por
perfil (**gestor** · **funcionário**) e o cliente **não tem acesso**. Nenhum fluxo do Main expõe
dados de gestão, equipa, rotas, fiscalidade ou recibos verdes. Matriz de acesso: §18.6.

### B. Dois canais com regras distintas
**Loja** exige espaço físico, não pede morada nem OTP, os serviços são aceites **automaticamente** na
criação e há sinal de 10 %. **Carrinha** exige morada (que define a cidade/rota) e **estrutura por
pessoa**, usa OTP, a aceitação é **manual serviço a serviço** e a 1.ª marcação é **isenta de sinal**.
Detalhe operativo: §8 · §9 e RN-01 · RN-02 · RN-03 · RN-14 · RN-16.

### C. Categorias são apenas filtros visuais
Nunca condicionam quem executa o quê — qualquer funcionário aceita qualquer serviço (RN-04).

### D. A equipa é atribuída por aceitação, não por alocação
Sem motorista dedicado e sem controlo logístico de condução; **nada disso deve ser implementado**
(D-08 · §22.3).

### E. Decisões de gestão são manuais
Aprovar ou recusar uma rota é **inteiramente do gestor**; o valor de referência é **apenas visual** e
nunca bloqueia. Alertas fiscais gerados *on-demand*, sem CRON (D-01 · RN-05 · §12.3).

## 3. DECISÕES FINAIS (D-01 … D-11)

> Decisões de produto que resolveram os conflitos entre fontes de planeamento. **As fontes originais
> estão revogadas** e não se acumulam aqui — o histórico está no Git (§29.2). Cada decisão aponta as
> regras operativas (§5) e o estado; o que falta está no gap (§24).

### 3.1 — D-01 · Decisão de rotas e limiar financeiro
**Decisão:** aprovar ou recusar é **inteiramente do gestor**; o valor de referência serve **apenas** de
apoio visual, **sem bloqueio automático**.
**Regras:** RN-05 · RN-10 · RN-18 · RN-19 · **Estado:** ✅ (`RotaService::decideRoute`).

### 3.2 — D-02 · Funcionários ↔ categorias profissionais
**Decisão:** categorias são **apenas filtros e agrupadores visuais**; qualquer profissional aceita
qualquer serviço.
**Regras:** RN-04 · **Estado:** ✅ — tabela `funcionario_categoria` **removida** (schema com 24 tabelas).

### 3.3 — D-03 · Política salarial vs. recibos verdes
**Decisão:** efetivos atuam **predominantemente na loja**; recibos verdes na vertente **ambulante**,
sujeitos ao simulador por serviço.
**Regras:** RN-09 · RN-22 · **Estado:** 🟡 simulador ✅ (§11); encaminhamento por `tipo_contrato` na UI
⬜ (§24.3).

### 3.4 — D-04 · Estrutura da frota móvel
**Decisão:** **uma única carrinha polivalente**, que transporta a equipa independentemente das
especialidades originais.
**Estado:** ✅ — `base_partida` única (Évora) + `matriz_deslocacao` base→cidade.

### 3.5 — D-05 · Percentagem do sinal de reserva
**Decisão:** **10 % exclusivamente na loja**, configurável no backoffice; a **1.ª marcação em
ambulatório é isenta**.
**Regras:** RN-03 · **Estado:** 🟡 os 10 % existem como constante de código; a configuração no
backoffice ⬜ (§24.5).

### 3.6 — D-06 · Interface e apresentação do catálogo
**Decisão:** ignorar o AdminLTE; serviços em **cards** e **página de detalhes dedicada por serviço**
(não apenas um modal), encimada por **carousel** de imagens, com descrição e tempo estimado.
**Estado:** 🟡 existe só o modal; a página dedicada ⬜ (§24.2). `servico_foto` existe, sem conteúdo.

### 3.7 — D-07 · Escolha da hora e dinâmica de tempos
**Decisão:** o cliente escolhe a **hora inicial** de uma lista de horas disponíveis; o tempo estimado
é **re-avaliado** a cada alteração de serviços; a ordem *serviços → canal → data/hora* é garantida
estruturalmente.
**Estado:** 🟡 ordem ✅ e validação server-side ✅ (409); **alterar serviços não recarrega os slots**
⬜ (§24.1).

### 3.8 — D-08 · Logística de condução e papel dos funcionários
**Decisão:** **não existe** motorista dedicado nem controlo de condução, e **nada disso deve ser
implementado**. A carrinha é transporte; os serviços são executados polivalentemente por quem estiver
presente.
**Estado:** ✅ conforme — nada a fazer (§22.3).

### 3.9 — D-09 · Flexibilidade horária e rotas multicidades
**Decisão:** horários da carrinha tendencialmente flexíveis, com o fim **após as 19:00 permitido como
exceção**; **multicidades permitidas** se os agendamentos estiverem cronologicamente ordenados e
houver **espaçamento validado** para a deslocação; **alerta padronizado de custos** junto ao indicador
de referência (§12.4).
**Regras:** RN-27 · RN-28 · **Estado:** ⬜ por implementar (§24.4).

### 3.10 — D-10 · Pagamentos, sinal (10/90) e falhas de internet
**Decisão:** sinal de 10 % **configurável no backoffice**; **90 % cobrados no término**; todos os
métodos (**Dinheiro · Multibanco · MB Way**) **simulados de forma realista**; em **falha de internet**
no terreno, apenas **numerário**; recibo manual como trabalho futuro.
**Regras:** RN-23 · RN-29 · **Estado:** 🟡 só o sinal está implementado; o resto ⬜ (§24.5).

### 3.11 — D-11 · Cancelamentos, janela de 24 h e notificações
**Decisão:** não se criam rotas com agendamentos a **menos de 24 h**; aos 24 h sem rota, o agendamento
é **auto-cancelado** das listagens mas **retido na BD**; o cliente recebe **lembrete** (≤ 24 h) a
sugerir loja física ou reagendamento; o cliente **pode cancelar** sem penalização financeira.
**Regras:** RN-24 · RN-25 · RN-26 · **Estado:** ⬜ **por implementar integralmente** (§24.6 · §15).
