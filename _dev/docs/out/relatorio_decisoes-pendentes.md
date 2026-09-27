# DOSSIER DE DECISÕES PENDENTES — fechar a Fase 6 antes da entrega

<!-- md-wrap-tables:max=220 -->

**Data:** 2026-09-26 · **Contexto:** faltam **menos de 24 horas** para a entrega. A mensagem ao Teams
**deixa de ser enviada** (já não há tempo de esperar resposta da contabilidade) — as dúvidas passam a ser
**decididas internamente**, com proposta por escrito para revisão do gestor.
**Âmbito:** as 36 dúvidas + os conflitos `C-nn` de `_dev/mapaMentalMVP/analise_backoffice_gestor.md`, e os
pontos de estrutura da mensagem (painel, Contabilidade, RH, Promoções, Fornecedores).
**Autor:** agente · **Artefacto:** `_dev/docs/out/relatorio_decisoes-pendentes.md` · **Natureza:** apoio
(**não normativo**) · **Autoridade:** `especificacao_mvp.md` + `_dev/docs/spec/`

> **Como usar (ler primeiro).** Cada decisão tem ID próprio — **`P-nn`** (proposta) — e uma frase
> **DECISÃO PROPOSTA** que se aceita ou rejeita. Abre no editor: `git ref-open P-05`. Cada `P-nn` termina
> em **IDs relacionados** (`Q-nn` dúvida · `C-nn` conflito · `§N` · `RF-nn` · `RN-nn`) para se poder saltar
> à prova. O que é implementável em 24 h diz **ENTRA**; o que não cabe diz **ADIAR** com o registo
> obrigatório em `delivery.md` §22.1 (simplificação) ou §24 (gap) — nunca fica sem destino.
>
> **O que este dossier NÃO é:** não altera a especificação e não implementa nada. É a **lista de decisões**
> que o gestor aceita/rejeita; a execução vem depois (o plano está na §5).

## 1. RESUMO EXECUTIVO

### 1.1 Veredicto de âmbito — a Fase 6 inteira não cabe em 24 h

A Fase 6 tem **7 critérios de aceitação** (§28.2 #11-#17) e está **por iniciar** (`delivery.md` §21:
*"⬜ A INICIAR"*). Somando os módulos novos da mensagem, o esforço honesto é **~32 h de trabalho**
(ver §5), contra <24 h disponíveis. Proposta de corte:

| Tier  | O que entra                                                                                                                                          | Esforço | Fecha                                        |
| :---- | :--------------------------------------------------------------------------------------------------------------------------------------------------- | :------ | :------------------------------------------- |
| **1** | Cancelamento pelo cliente · Regra das 24 h + auto-cancelamento · Slots revalidados · Lembrete ≤ 24 h · Sinal configurável + 90 %/método · Importação | ~16 h   | §28.2 **#11 #12 #13 #14 #17** + RF-75        |
|       | (CSV/XLSX)                                                                                                                                           |         |                                              |
| **2** | Detalhes do serviço + carousel · Contabilidade (1.º ecrã) · Painel `/gestao`                                                                         | ~10 h   | §28.2 **#15** + RF-76 + RF-63                |
| **3** | Multicidades com validação dura · RH completo · Promoções · Fornecedores · Anexos fiscais · Seletor de período                                       | —       | Vai para §22.1/§24/§25 com decisão explícita |

### 1.2 As decisões que mudam o resultado (ordem de urgência)

| #    | Decisão a tomar hoje                                                 | Proposta (1 linha)                                                                                                                 | Custo |
| :--- | :------------------------------------------------------------------- | :--------------------------------------------------------------------------------------------------------------------------------- | :---- |
| P-01 | Âmbito: aceitar o corte Tier 1/2/3?                                  | Sim — Tier 1 integral + Tier 2 pela ordem dada; Tier 3 declarado adiado                                                            | —     |
| P-05 | Sinal configurável + 90 % no término: como persistir a configuração? | **Uma** tabela `config_sistema` (chave/valor) — serve sinal, IVA e IRC                                                             | 4 h   |
| P-11 | Importação: que formatos e que tabelas?                              | CSV **e** XLSX (PhpSpreadsheet) → `importacao` + `importacao_linha` (§17.9)                                                        | 5 h   |
| P-09 | Painel de entrada: que rota?                                         | `/gestao` = painel; a lista fica em `/gestao/agendamentos` (rota já existe)                                                        | 2 h   |
| P-13 | Cada indicador: ficheiro importado ou cálculo da plataforma?         | **Receita/custos internos sempre primários**; o importado só preenche o que a plataforma não conhece; **nunca somar** (D-12 p.5) e | 1 h   |
|      |                                                                      | cada widget mostra a origem                                                                                                        |       |
| P-10 | Preços com IVA incluído: onde vive a taxa?                           | BD continua **líquida**; o bruto é calculado (`líquido × taxa`) com a taxa na `config_sistema`                                     | 1 h   |
| P-19 | Sino do backoffice e avisos ao cliente: um ou dois?                  | **Dois** mecanismos; sino mantém-se **global** (1 gestor) e regista-se em §22.2                                                    | 1 h   |
| P-16 | Promoções: módulo novo em 24 h?                                      | **Não** — conteúdo estático no site; gestão vai para §25                                                                           | —     |
| P-17 | Fornecedores: iniciar agora?                                         | **Não** — mantém-se prioridade 1 do futuro (§25.1); o card lê o importado                                                          | —     |
| P-07 | Multicidades: validação dura do espaçamento?                         | **Alerta visual** (padronizado) + decisão manual do gestor (coerente com D-01)                                                     | 2 h   |

## 2. DECISÕES PROPOSTAS

Cada item: **Pergunta** (o que está em aberto) → **DECISÃO PROPOSTA** → **Porquê** (prova) → **Impacto**
(o que se toca) → **Esforço** → **Se decidires o contrário** (o que custa a alternativa).

### 2.1 Entrega — o que fecha critérios da §28.2

#### P-01 · Âmbito das próximas 24 horas  <!-- id:P-01 -->

**Pergunta:** a Fase 6 tem 7 critérios e não caberá toda. O que se entrega, o que é parcial e o que se
declara adiado?
**DECISÃO PROPOSTA:** **Tier 1 integral** (§28.2 #11 #12 #13 #14 #17 + RF-75), **Tier 2** pela ordem
*Detalhes+carousel → Contabilidade 1.º ecrã → Painel*, e **Tier 3 declarado** em `delivery.md` §22.1
(simplificação) ou §24/§25 (gap), **no mesmo commit** de cada item.
**Porquê:** `delivery.md` §21 marca a Fase 6 como *"⬜ A INICIAR"* e §28.2 lista #11-#17 por cumprir;
§26.4 diz que os testes E2E destes fluxos *"a acrescentar com a Fase 6"*. Não há folga para os módulos
novos da mensagem (Contabilidade completa, RH, Promoções) **em simultâneo** com os 7 critérios.
**Impacto:** nenhum ficheiro de produto por si só — é a decisão que encomenda a §5.
**Esforço:** — (decisão) · **Se decidires o contrário:** tentar tudo significa, na prática, **não fechar
nenhum** critério por inteiro — pior resultado de avaliação do que entregar 5 sólidos.

#### P-02 · Cancelamento do agendamento pelo cliente  <!-- id:P-02 -->

**Pergunta:** #11 de §28.2 · RF-12 está ⬜ e RN-26 diz *"sem penalização financeira"*.
**DECISÃO PROPOSTA:** **ENTRA.** Endpoint `customer-booking-cancel` (POST) + botão em
*Meus Agendamentos*, com as guardas: só o **dono**, só em estados não terminais, **sem penalização** —
alinhado com RN-26; após associação a rota, o cancelamento **não apaga** a rota (a decisão da rota é do
gestor, D-01).
**Porquê:** `agendamento.estado_reserva` já tem `cancelado` e o gestor já cancela (`admin-appointment-cancel`,
✅ RF-52) — ou seja, **a operação existe e está testada**; falta só expô-la ao cliente com a autorização
certa. É o critério com melhor relação valor/esforço da lista.
**Impacto:** `app/config/api.php` (1 endpoint) · `BookingController`/`BookingService`/`BookingRepository`
(método novo, reaproveita a transição de estado) · `modules/main/appointments.php` + `bookingMy.js` (botão
e confirmação) · §19.1 da API (nova linha) · RF-12 → ✅ · §28.2 #11 → ✅ · testes.
**Esforço:** ~2 h · **Se decidires o contrário:** o cliente continua a ter de telefonar; o critério #11
fica por cumprir e há que o declarar em §22.2 — mais visível na entrega do que o custo de o fazer.

#### P-03 · Regra das 24 h: bloqueio na rota + auto-cancelamento  <!-- id:P-03 -->

**Pergunta:** #12 de §28.2 · RF-58/59 ⬜ · RN-24 (nenhuma rota com <24 h) · RN-25 (24 h sem rota →
auto-cancelado **das listagens**, **retido na BD**).
**DECISÃO PROPOSTA:** **ENTRA, sem alterar o schema:** RN-24 como **validação server-side** na criação da
rota (rejeita com 409 e diz o porquê) e RN-25 como **estado derivado na leitura** — o agendamento a <24 h
sem rota **sai das listagens** (cliente e gestor) por filtro, mantendo-se intacto na BD.
**Porquê:** D-11 escreve exatamente *"auto-cancelado das listagens mas retido na BD"* — é uma **regra de
apresentação**, não uma escrita. Fazer o contrário (gravar `cancelado` a cada 24 h) exigiria uma coluna de
motivo e um processo periódico — que a §22.1 já rejeita (*"sem CRON"*). Reaproveita o padrão de RN-25 e não
invalida registos históricos.
**Impacto:** `RotaService`/`BookingService` (guarda + filtro) · `RotaRepository`/`BookingRepository`
(condição nas *queries* de listagem) · §5 RN-24/RN-25 (de *"a implementar"* para ✅) · §28.2 #12 → ✅ ·
testes (já existe base para conflitos de janela).
**Esforço:** ~2 h · **Se decidires o contrário:** gravar o cancelamento em BD obriga a justificar **uma
tabela/coluna nova** (E-4) e a recuperar os cancelamentos indevidos — mais trabalho e mais risco de
apagar dados de demonstração.

#### P-04 · Lembrete ao cliente (≤ 24 h, sem rota)  <!-- id:P-04 -->

**Pergunta:** #13 de §28.2 · RF-13 ⬜ · D-11 fixa que o cliente **recebe** lembrete, com sugestão de loja
física ou reagendamento · `§15.3` diz que a notificação é **simulada**.
**DECISÃO PROPOSTA:** **ENTRA, simulado e visível:** faixa de aviso em *Meus Agendamentos* (e um *badge* na
lista do gestor) para os agendamentos a ≤ 24 h **sem rota**, com o texto de §15.3 (impossibilidade +
sugestão de loja ou reagendamento). **Sem** SMS/e-mail (não há integração).
**Porquê:** §22.1 já aceita *"SMS/Email simulados (através de log ou `alert()`)"* — logo **não é preciso
infraestrutura**; e a condição («≤ 24 h e sem rota») é a **mesma** que o **P-03** calcula, pelo que os dois
partilham o mesmo predicado (custo marginal ≈ 0 depois do P-03).
**Impacto:** `BookingService` (predicado partilhado com P-03) · `modules/main/appointments.php` + JS
(faixa) · `§15.3`/§24.6 (de *"a implementar"* para ✅) · §28.2 #13 → ✅ · RF-13 → ✅ · testes.
**Esforço:** ~2 h (1 h se o P-03 estiver feito) · **Se decidires o contrário:** o critério #13 fica por
cumprir e a §3.11 (D-11) passa a ter uma decisão **não implementada** — o mais visível dos gaps, porque é
decisão registada em §3.

#### P-05 · Sinal configurável + 90 % no término com método  <!-- id:P-05 -->

**Pergunta:** #14 de §28.2 · RF-25/62/71/72/73 ⬜ · RN-23 (90 % no término) · RN-29 (falha de internet →
só numerário) · D-05 e D-10 (10 % configurável, métodos simulados).
**DECISÃO PROPOSTA:** **ENTRA** com **uma única tabela de configuração** —
`config_sistema(chave, valor, data_vigencia, configurado_por)` — de onde se leem **o sinal (%)**, a
**taxa de IVA** (P-10) e a **taxa de IRC** (P-20). Ecrã mínimo em `/gestao/configuracao`. A cobrança dos
90 % usa `transacao_financeira`, que **já existe** com `metodo_pagamento`
(`numerario_dinheiro` · `mb_way` · `multibanco_pos`) e `tipo_transacao` (`restante_90_porcento`).
**Porquê:** o projeto **não tem** tabela de configurações (verificado: 23 tabelas; `config_recibo_verde`
guarda **só** as percentagens dos recibos verdes). Uma tabela chave/valor resolve **três** decisões em
aberto de uma só vez, e segue o **padrão já provado** de `config_recibo_verde` (percentagem + vigência +
`configurado_por`). A alternativa — constantes em código — foi explicitamente rejeitada por **RF-25**
(*"substituir constante"*) e por **RF-62**.
**Impacto:** `DataBase_v3.sql`/migração (**1 tabela nova** — registar em §17 + §17.9) ·
`ConfigRepository`/`ConfigService` · `BookingService` (deixa de usar a constante) · `FiscalController`
(taxa de IRC) · `app/config/api.php` (`admin-config-*`, `admin-payment-collect`) · UI de execução
(90 % + método + caso *offline*) · §17 · §24.5 · RF-25/62/71/72/73 → ✅ · §28.2 #14 → ✅ · testes.
**Esforço:** ~4 h (tabela+leitura 1,5 h · ecrã 1 h · cobrança+método 1,5 h)
**Se decidires o contrário:** manter constantes viola RF-25/RF-62 e obriga a declarar em §22.2 que a
configuração **não** é editável — contradizendo duas decisões (D-05 · D-10) já registadas em §3.

#### P-06 · Detalhes do serviço + carousel  <!-- id:P-06 -->

**Pergunta:** #15 de §28.2 · RF-07 ⬜ · D-06 (página dedicada com carousel) · `servico_foto` **existe mas
está vazia**.
**DECISÃO PROPOSTA:** **ENTRA (Tier 2)** na rota `servicos/:category/:slug` (o padrão `servicos/:category`
já existe em `index.php` L20), com descrição, duração e preço, carousel de `servico_foto` e **fallback
obrigatório** — enquanto `servico_foto` não tiver conteúdo, usa-se a **imagem da categoria** (as 3
categorias já têm imagem; `analise` F-07 mede 7 ficheiros). Upload de imagens no backoffice **não entra**
→ declarar em §22.1.
**Porquê:** é critério de aceitação (#15) e fecha também **Q-28/Q-45** (quem fornece as imagens) sem
depender do cliente: o *fallback* torna o ecrã apresentável na demonstração **mesmo** sem imagens novas.
O carousel tem biblioteca disponível no projeto (Owl Carousel 2, §1.1).
**Impacto:** `index.php` (rota) · `modules/main/serviceDetail.php` (novo) + JS do carousel ·
`ServiceRepository::findBySlug` · `data-api.md` §19 (endpoint público `service-detail`, se necessário) ·
RF-07 → ✅ · §28.2 #15 → ✅ · §24.2 (parcial: sem upload).
**Esforço:** ~4 h · **Se decidires o contrário:** #15 fica por cumprir e o catálogo mantém-se só com modal,
contrariando **D-06** (decisão registada).

#### P-07 · Multicidades: validação dura ou alerta?  <!-- id:P-07 -->

**Pergunta:** #16 de §28.2 · RF-56/57 ⬜ · RN-27 (espaçamento temporal validado) · RN-28 (fim > 19:00 como
exceção) · D-09.
**DECISÃO PROPOSTA:** **Parcial, e o parcial é a decisão:** **ENTRA** o **alerta padronizado de
custos/viabilidade** (§12.4 · RF-57) na listagem de rotas e o **aviso de espaço insuficiente** como
*alerta visível*; a **validação dura** do espaçamento (bloqueio 409) fica **declarada como simplificação**
(§22.1) — o gestor decide manualmente, como em tudo o resto nas rotas.
**Porquê:** **D-01** fixa que *"aprovar ou recusar é inteiramente do gestor"* e que o indicador é **apenas
apoio visual, sem bloqueio automático** — uma validação dura de espaçamento **colide** com essa decisão.
Implementar o alerta satisfaz a parte do critério *"alerta de custos padronizado"* sem introduzir um
bloqueio que a §3.1 proíbe.
**Impacto:** `RotaService` (alerta) · `modules/backoffice/routes.php` + JS · §22.1 (linha nova:
*"multicidades: espaçamento verificado pelo gestor, com alerta"*) · RF-57 → ✅ / RF-56 → 🟡 · §28.2 #16 → 🟡
**Esforço:** ~2 h · **Se decidires o contrário:** o bloqueio dura retira ao gestor a decisão manual (D-01)
e obriga a mexer no motor de rotas — o ponto mais testado (RF-54/55 ✅).

#### P-08 · Lista de horas revalidada quando os serviços mudam  <!-- id:P-08 -->

**Pergunta:** #17 de §28.2 · RF-35 🟡 · D-07 (*"o tempo estimado é re-avaliado a cada alteração"*) · §24.1.
**DECISÃO PROPOSTA:** **ENTRA.** Ao alterar serviços já com data escolhida, o wizard **volta a pedir
`booking-availability`** e reescreve a grelha; se a hora escolhida deixar de existir, avisa e limpa a
seleção.
**Porquê:** é o item **mais barato** de toda a Fase 6: o endpoint existe e está testado
(`booking-availability`, §19.1) — falta apenas o *refresh* no `bookingWizard.js`, e a validação
server-side já existe (409, §3.7). Fecha D-07 e §24.1 no mesmo movimento.
**Impacto:** `modules/main/js/bookingWizard.js` · (eventual) `serviceList` no resumo · RF-35 → ✅ ·
§28.2 #17 → ✅ · §24.1 → fechado · `js_syntax_check` deve continuar verde.
**Esforço:** ~1 h · **Se decidires o contrário:** é o critério mais fácil de perder e o mais difícil de
justificar como «não deu tempo».

#### P-09 · Painel de entrada do gestor e estrutura de menus  <!-- id:P-09 -->

**Pergunta:** `C-01` (a rota `/gestao` **já é** a lista de agendamentos) · RF-63 🟡 · §25.5 (convenção de
menus) · A-03 e A-07 do `relatorio_mensagem-teams.md`.
**DECISÃO PROPOSTA:** **ENTRA (Tier 2).** `/gestao` passa a **painel de entrada**; a lista de agendamentos
fica em **`/gestao/agendamentos`** — rota que **já existe** (`index.php` L31). O painel mostra só o que é
**calculável hoje** (agendamentos do dia, rotas pendentes, alertas fiscais, recibos verdes) e, para o que
depende de importação, um cartão que **declara a origem** («sem importação»). A convenção **§25.5**
atualiza-se no mesmo commit.
**Porquê:** das duas alternativas do `C-01`, esta é a única que **não inventa rota nova** — a de destino já
está implementada e o menu do gestor passa a ter a hierarquia pedida no *Menu Dashboard.docx*. Implementar
o painel **antes** da contabilidade completa evita refazer o menu duas vezes (A-07).
**Impacto:** `index.php` L30 · `modules/backoffice/dashboard.php` (novo) + JS · `backlog.md` **§25.5**
(atualizar a convenção) · RF-63 → ✅ · §24.2 · **C-01 → fechado**.
**Esforço:** ~2 h · **Se decidires o contrário:** painel em `/gestao/painel` mantém a lista onde está, mas
cria uma **terceira** rota de gestão e deixa `/gestao` como lista — contrariando o que o *Menu Dashboard*
descreve como entrada do gestor.

### 2.2 Dinheiro — IVA, importação, contabilidade e RH

#### P-10 · Preços com IVA incluído e onde vive a taxa  <!-- id:P-10 -->

**Pergunta:** Q-01 · Q-46 · Q-47 (`Q-46` uniforme ou por serviço?) · `C-17` (onde vive a taxa e o que se
grava na marcação) · `C-18` (filtro de preço sobe para 60 € bruto?) · pedido da mensagem:
*"valores mostrados ao cliente com IVA incluído"*.
**DECISÃO PROPOSTA:** **ENTRA (decisão + 1 h).** (i) A BD **mantém-se líquida** — `servico.preco_base` não
se toca; (ii) a taxa é **uniforme de 23 %** e vive na `config_sistema` (P-05); (iii) o que o cliente vê é
**calculado** (`bruto = líquido × 1,23`, arredondado a 2 casas) na listagem, no card, no wizard e no
resumo; (iv) **`agendamento_servico.preco_praticado` continua a receber o valor da montra (bruto)** — que é
o que já acontece com os preços redondos; (v) o **filtro de preço** passa a trabalhar em **bruto**
(`C-18`) e o limite sobe para 60 €.
**Porquê:** a análise **provou** que `preco_base` é **líquido** com taxa implícita de **23 %**
(35/35 preços × 1,23 = redondos — `analise` §E.2 / Q-01). Gravar a taxa **por serviço** exigiria coluna nova
+ migração de 35 linhas **sem** benefício demonstrável num dia; e **Q-46** (taxa uniforme) não tem resposta
do cliente — a decisão uniforme é a única compatível com o que os dados mostram.
**Impacto:** `ConfigService` (taxa) · Services/Mapper do catálogo (expor `preco_bruto`) ·
`serviceCategories.php` · `services.php` · `bookingWizard.js` · `ServiceRepository` (filtro em bruto) ·
§7 e §17 (nota: sem coluna nova) · Q-01/Q-46/Q-47 **fechadas por decisão** · **C-17 parcial** · **C-18
fechado**.
**Esforço:** ~1 h (+ P-05) · **Se decidires o contrário:** gravar a taxa por serviço obriga a **duas**
alterações de schema (coluna + dados) e a decidir por 35 serviços sem resposta do cliente — inviável hoje.

#### P-11 · Importação de ficheiros (CSV/XLSX)  <!-- id:P-11 -->

**Pergunta:** RF-75 ⬜ · D-12 · Q-43 (`C-19`/`C-30`: e o `.xlsx`?) · Q-04/Q-36/**Q-66** (que fonte manda?) ·
`C-29`.
**DECISÃO PROPOSTA:** **ENTRA (Tier 1).** Upload em `/gestao/importacao` que aceita **`.csv` e `.xlsx`**,
lido com **`PhpSpreadsheet`** (E-1, com `zip` E-2) — não se escreve leitor próprio. Persistência em
**`importacao`** (cabeçalho: ficheiro, data, autor, nº de linhas) + **`importacao_linha`**
(folha · referência · rótulo · valor) — as duas tabelas de **§17.9**, num **único** *service*. A gravação é
**transacional e substitui** o que estava (**RN-30**). Os controlos têm de recusar ficheiros ilegíveis com
mensagem clara (é a demo).
**Porquê:** D-12 **já decidiu** esta via (ponto 1: bibliotecas E-1; ponto 2: substituição integral; ponto 6:
tabelas justificadas em §17.9) e `C-29` **já foi fechado** (*"a via é a importação para tabelas, não o mapa
de células"*). `Q-43`/`C-19`/`C-30` caíram: o `.xlsx` **é** tratável — a limitação que os originou era
**falsa** (`analise` §F.7: 26/26 entradas). O que **não** está decidido é só a prioridade entre fontes
(**P-13**).
**Impacto:** `DataBase_v3.sql` (**2 tabelas** — §17 + §17.9) · `ImportRepository`/`ImportService` (novo) ·
`ImportController` + `admin-import-upload`/`admin-import-status` · `modules/backoffice/import.php` + JS
(upload) · `vendor` (PhpSpreadsheet — E-1) · RF-75 → ✅ · Q-43/C-19/C-30 → arquivados · `C-29` → confirmado.
**Esforço:** ~5 h · **Se decidires o contrário:** sem importação **não existe** contabilidade (RF-76), nem
os cartões do *Menu Dashboard*, nem a decisão D-12 se cumpre — e a alternativa (ler o `.xlsx` em *runtime*,
o antigo `C-29`) foi rejeitada por decisão registada.

#### P-12 · Contabilidade: quanto das 6 secções entra  <!-- id:P-12 -->

**Pergunta:** RF-76 ⬜ · Q-48/Q-49 (balancete: abas e ordem) · `C-25` (caixa/banco: tabela ou parâmetros?)
· `C-26` (empréstimo + plano) · `C-27` (naturezas das dívidas) · a mensagem pede 6 secções.
**DECISÃO PROPOSTA:** **ENTRA em 1.º ecrã (Tier 2).** `/gestao/contabilidade` com **Resumo**
(disponibilidades · dívidas a receber · dívidas a pagar) e **Rácios**, alimentados pelo **importado**; as
secções **Ativos** (lista + depreciações), **Financiamentos** e **Recursos Humanos** apresentam-se como
**tabelas do importado** (sem cálculo próprio nesta fase). **Sem** tabelas de domínio para ativos,
depreciações, empréstimos ou contas bancárias — os valores vivem nas linhas importadas.
**Porquê:** hoje **não existe** nada disto na BD (verificado: **0** tabelas de ativos / empréstimos /
rácios / contas). Modelar 4 domínios novos + planos de amortização + mapa de depreciações em <24 h é
impossível, e **D-12** dá a saída por decisão: *"o resultado é persistido e os cartões leem da BD"*. O ecrã
fica **honesto** (mostra o que veio do ficheiro) em vez de meio-calculado.
**Impacto:** `modules/backoffice/accounting.php` (novo) + Chart.js (E-3) · `ImportService` (consultas) ·
`index.php` (`/gestao/contabilidade`) · §25.5 (menu) · RF-76 → 🟡 · `C-25`/`C-26`/`C-27` **mantêm-se
abertos** (§24, com nota de que a fase seguinte os modela).
**Esforço:** ~5 h · **Se decidires o contrário:** modelar os domínios primeiro (o caminho «correto») não
cabe em 24 h; o resultado é **nada** de contabilidade em vez de uma leitura fiel do ficheiro.

#### P-13 · Que fonte manda em cada número  <!-- id:P-13 -->

**Pergunta:** **Q-66** · Q-04 · Q-31/Q-33 · Q-36 · `C-20` (*"dono de cada número"*) · `C-10` (dupla
contagem) · A-01.
**DECISÃO PROPOSTA:** **(i)** A **receita** é **sempre da plataforma** (agendamentos executados); o
importado **nunca** a substitui. **(ii)** O importado fornece **só** o que a plataforma **não conhece**
(despesas externas, ativos, financiamentos, saldos de caixa/banco, IVA/IRC, IRS retido). **(iii)** Os
valores **nunca se somam** no mesmo indicador (D-12, ponto 5) e cada cartão/tabela **declara a origem**
(«plataforma» / «importado» / «ambos, não somados»).
**Porquê:** é a única regra que **não exige** resposta do cliente e **não contradiz** nem D-01 nem D-12. O
risco de dupla contagem está **nomeado** em `C-10`/`C-20`; declarar a origem transforma a dúvida numa
propriedade visível do ecrã — e é reversível: quando o cliente responder, muda-se o rótulo, não o modelo.
**Impacto:** `ImportService` (origem por linha) · componente de cartão com rótulo de origem (usado pelo
painel e pela contabilidade) · §5 (regra nova **RN-31 · origem declarada por indicador**) · **Q-66 →
fechada por decisão** · `C-20` → mitigado.
**Esforço:** ~1 h · **Se decidires o contrário:** esperar pelo cliente deixa a contabilidade **sem regra de
leitura** — e amanhã é a entrega.

#### P-14 · Recursos Humanos: o que se calcula  <!-- id:P-14 -->

**Pergunta:** Q-09/Q-11 (subsídios) · Q-17 (mês da comissão) · Q-18 (13.º/14.º) · Q-19 (IRS por
trabalhador) · Q-33/Q-34 (SA e dias) · `C-10` (fonte única do custo de pessoal) · `C-28`.
**DECISÃO PROPOSTA:** **ENTRA parcial.** Ecrã **Recursos Humanos** com, por trabalhador: `salário_base`
(BD, `tipo_contrato` ∈ {efetivo_contratado, recibo_verde}), **comissões** (soma de
`valor_recibo_verde_funcionario` dos serviços aceites — `analise` §2.8.1) e **IRS/SA/SS lidos do
importado**. **13.º/14.º mês NÃO entram** e o **SA não é calculado** (usa-se o valor importado) → declarar
em §22.1.
**Porquê:** a análise **já provou** as relações (`analise` §F.4 / §2.10.1: base da SS = remuneração bruta
**sem** SA; IRS **por trabalhador**; o líquido não desconta a SS patronal; SA = 6,15 € × dias úteis) — **mas**
o 13.º/14.º **não está modelado** (a própria análise o admite) e **Q-34** mostra que os dias do SA
**divergem** (29 na DRI *vs* ~21 no subsídio). Calcular o que ninguém confirmou seria **inventar números na
entrega**.
**Impacto:** `modules/backoffice/staff.php` (novo) · `ImportService` (IRS/SA) · `FuncionarioRepository`
(salário/comissões) · §22.1 (linha nova) · `C-28` → parcialmente fechado (IRS por trabalhador ✅) ·
`C-10` → mitigado (fonte única: salário + comissões).
**Esforço:** ~3 h · **Se decidires o contrário:** calcular SA/13.º/14.º a partir de dias úteis exige uma
regra de calendário que ninguém validou e que **contradiz** os números do próprio cliente (Q-34).

#### P-15 · Rácios, passivo e dívidas: ler ou calcular  <!-- id:P-15 -->

**Pergunta:** Q-11 · Q-29/Q-58 (células dos 5 rácios) · Q-30/Q-59 (passivo não corrente sem células) ·
Q-31/Q-60 (IVA nas dívidas: líquido ou bruto) · Q-32/Q-61 (capital em dívida: 2 valores) · `C-27` · `C-29`.
**DECISÃO PROPOSTA:** Os **5 rácios** e as **dívidas a pagar** são **lidos do importado** (2.º quadro,
`B11..D15` — `analise` §F.9), **exatamente como o ficheiro os traz**, incluindo a posição **líquida** do
IVA. O cartão **Passivo não corrente** fica **oculto enquanto o valor importado for `0`** (em vez de mostrar
0,00 €). O **capital em dívida** usa o valor que vier no ficheiro, com nota de origem. **Sem** cálculo
próprio de rácios.
**Porquê:** `C-29` **já decidiu** a via (importação, não mapa de células em *runtime*); calcular rácios
exigiria decidir o perímetro contabilístico — que é exatamente a pergunta **Q-11**, sem resposta. E há
**dois** valores para a mesma dívida (24 016,80 € *vs* 49 354,66 €): só o ficheiro diz qual o cliente quer
ver; calcular seria escolher por ele.
**Impacto:** `ImportService` (leitura por rótulo) · cartões da contabilidade · §22.1 (nota: sem cálculo
próprio de rácios no MVP) · **Q-29/Q-30/Q-31/Q-32 → fechadas por decisão** · `C-27`/`C-29` → confirmados.
**Esforço:** ~1 h (dentro do P-12) · **Se decidires o contrário:** calcular hoje implica **escolher um
perímetro** sem resposta do cliente e produzir números que vão **divergir** do balancete dele.

### 2.3 Estrutura e módulos novos — adiar com decisão explícita

#### P-16 · Promoções e descontos  <!-- id:P-16 -->

**Pergunta:** Q-25 (percentagem, valor fixo ou ambos? acumulam?) · Q-26 (o cliente vê o desconto no site ou
só no atendimento?) · módulo novo (*"campanhas com data de início e fim"*, *"tags sazonais"*).
**DECISÃO PROPOSTA:** **ADIAR para §25.** Em 24 h **não** se cria o módulo; o site pode mostrar campanhas
como **conteúdo estático** (mesma decisão de P-22). Registar em §22.1 (*"Promoções: sem módulo de gestão no
MVP; campanhas apenas como conteúdo do site"*).
**Porquê:** um módulo com **tabela + CRUD + tags sazonais + abrangência de serviços + regra de acumulação**
é, por si só, mais do que o Tier 1 inteiro. E as duas perguntas-chave (acumulação e visibilidade do
desconto) **mudam o preço mostrado** — ou seja, tocam no P-10: fixar o desconto **antes** de saber a regra
de acumulação criaria dívida técnica imediata.
**Impacto:** `backlog.md` §25 (entrada nova) · §22.1 · Q-25/Q-26 → **mantêm-se abertas** (não bloqueiam
nada da entrega).
**Esforço:** — (decisão) · **Se decidires o contrário:** corta 4-6 h do Tier 1 (importação ou sinal/90 %)
para entregar um módulo que ninguém consegue usar sem responder às duas perguntas.

#### P-17 · Fornecedores  <!-- id:P-17 -->

**Pergunta:** a mensagem diz *"EM ANDAMENTO"* (A-04); a análise diz **prioridade 1 do futuro** (§25.1);
`data-api.md` §19.5 lista `admin-supplier-*` como **previsto e não implementado**.
**DECISÃO PROPOSTA:** **ADIAR** (mantém-se §25.1). O cartão *"dívidas a fornecedores"* mostra o valor
**importado** (P-13), com nota de que a gestão de fornecedores é fase seguinte.
**Porquê:** **é o que está escrito na especificação** (§25.1, prioridade 1 do futuro) e não existe **nada**
no código: 0 tabelas, 0 endpoints, e a única ocorrência de *"fornecedor"* no produto é o *serviço de
geocodificação* (`geocodingApi.js` L7 · L12 · L26 · L38). *"Em andamento"* não corresponde a artefacto
nenhum do repositório.
**Impacto:** A-04 do `relatorio_mensagem-teams.md` → corrigido · §25.1 mantém-se · esforço **poupado** para o
Tier 1.
**Esforço:** — (decisão) · **Se decidires o contrário:** exige tabela + CRUD + ligação a despesas — e a
*dívida a fornecedores* **já aparece** no número importado sem o módulo.

#### P-18 · Acesso do Funcionário ao backoffice  <!-- id:P-18 -->

**Pergunta:** Q-42 (a que terá acesso?) · `C-16` (*"agenda do funcionário"*) · `C-22` (matriz de perfis por
página + endpoint).
**DECISÃO PROPOSTA:** **ADIAR o ecrã; manter o que existe.** O Funcionário continua com **apenas**
`/gestao/servicos` (aceitação) — hoje as APIs `admin-*` **recusam-lhe** o resto (§22.2). Registar em §22.2:
*"a **Agenda do Funcionário** não é entregue no MVP; o funcionário vê os serviços atribuídos na aceitação"*;
a matriz `C-22` fica em §24.
**Porquê:** a restrição **já é** o comportamento atual e está **testada** (as suites cobrem perfis e
segurança, §26) — declarar a limitação **não custa código** e **não abre** superfície de autorização nova,
que é a área mais sensível a erros (§18.6). Um ecrã de agenda obrigaria a decidir a matriz de perfis
**inteira** (C-22), que é decisão do gestor.
**Impacto:** §22.2 (linha nova) · `C-22` → mantém-se em §24 · Q-42 → respondida *(«só aceitação, no MVP»)*.
**Esforço:** — (decisão) · **Se decidires o contrário:** ecrã novo por perfil implica **matriz de
autorização** + testes de 401/403 por endpoint — o risco de segurança mais alto da lista.

### 2.4 Avisos, fiscal e conteúdo — decisões pequenas que fecham dúvidas

#### P-19 · Sino do backoffice vs avisos ao cliente  <!-- id:P-19 -->

**Pergunta:** Q-22 (um ou dois mecanismos?) · Q-23 (que avisos e com quanto tempo?) · Q-24 (*"renovação de
contratos"* e *"revisões da carrinha"* — de onde saíram?) · `C-02` (avisos não fiscais) · `C-03` (contador
global) · `C-21`.
**DECISÃO PROPOSTA:** **Dois mecanismos, e é decisão:** (i) o **sino do backoffice** mantém-se como está —
`alerta_fiscal` + **contador global** — registando em §22.2 *"contador global (1 gestor no MVP)"*;
(ii) os **avisos ao cliente** são o mecanismo do **P-04** (faixa em *Meus Agendamentos*), sem sino próprio;
(iii) *"renovação de contratos"* e *"revisões da carrinha"* **não entram** — nasceram de conversa e **não
existem** na especificação nem em ficheiro recebido.
**Porquê:** o contador global é o que a BD suporta — `alerta_fiscal.visualizado` é `tinyint(1)` **sem coluna
de utilizador** (schema verificado) — e a seed tem **exatamente 1 gestor** (`utilizador` id 1,
`tipo_perfil='gestor'`), logo o cenário que `C-03` teme (*"marcar lido silencia os outros"*) **não se
materializa** no MVP. É o caso típico de *"registar a limitação em vez de construir para um cenário que não
existe"* — o que a §22.2 admite explicitamente.
**Impacto:** §22.2 (2 linhas novas) · nenhum código · `C-02` → **fechado** · `C-03` → fechado com limitação
declarada · `C-21` → fechado (2 mecanismos) · Q-22/Q-23/Q-24 → respondidas.
**Esforço:** — (decisão) · **Se decidires o contrário:** leitura por utilizador exige **tabela nova** +
contador por perfil + testes — para um cenário com 1 gestor.

#### P-20 · Fiscal: IVA apurado vs obrigação e taxa de IRC  <!-- id:P-20 -->

**Pergunta:** Q-03 (ver os **dois** IVA — pago ao Estado e apurado? taxa de IRC editável? valor a pagar
decidido por nós?) · Q-08/Q-10 · `C-05` · `C-14`.
**DECISÃO PROPOSTA:** (i) O calendário fiscal **continua a guardar obrigações** (`obrigacao_fiscal`, `tipo` ∈
{iva, irc, seguranca_social, seguros}) com **valor introduzido à mão** — é o RN-20; (ii) o **IVA apurado**
(das marcações e das despesas importadas) é um **cartão de leitura** no painel/contabilidade, com rótulo de
origem (P-13), e **não** substitui a obrigação; (iii) a **taxa de IRC é editável** (na `config_sistema`,
P-05) e o **valor a pagar é decisão do gestor** — confirmando `C-14` e `C-05`.
**Porquê:** o RN-20 **já fixa** *"valor **manual**"* e `obrigacao_fiscal` **já existe e é usada** (RF-60 ✅,
com alertas 30/15/7/3/1/atraso a funcionar). Manter as duas coisas **separadas e rotuladas** responde a Q-03
sem tocar no que está testado — e é o que a análise **já tinha concluído** (`analise` §2.0: o cliente quer
**editar a taxa** e decidir o valor).
**Impacto:** `FiscalService`/`FiscalController` (taxa via configuração) · cartão «IVA apurado» com origem ·
§13 · `C-05`/`C-14` → **fechados** · Q-03 → respondida.
**Esforço:** ~1 h (depende do P-05) · **Se decidires o contrário:** calcular a obrigação de IVA/IRC
automaticamente **contradiz** o RN-20 e o modelo de dados atual.

#### P-21 · Anexos fiscais  <!-- id:P-21 -->

**Pergunta:** Q-20/Q-62 (*"as declarações devem ficar anexadas ao registo no calendário?"*).
**DECISÃO PROPOSTA:** **ADIAR.** Sem anexos no MVP — registar em §22.1 (*"calendário fiscal sem anexos de
ficheiros"*) e em §25 (trabalho futuro).
**Porquê:** o calendário **não tem** coluna de anexo e o custo real não é o upload: é o armazenamento, os
tipos aceites, o limite de tamanho e a autorização de download — praticamente um dia de trabalho. A §22.1
**já** limita o MVP em envio/armazenamento de ficheiros; isto é a mesma família.
**Impacto:** §22.1 + §25 · Q-20/Q-62 → adiadas, com a resposta *"não no MVP"*.
**Esforço:** — (decisão) · **Se decidires o contrário:** ~4-6 h e um risco de segurança novo (ficheiros
servidos pela aplicação).

#### P-22 · Conteúdos do site e imagens dos serviços  <!-- id:P-22 -->

**Pergunta:** Q-27 (secção de serviços no Início/Sobre é fixa ou editável?) · Q-44 (`C-24`: estática?) ·
Q-28/Q-45 (quem fornece as imagens, formato e dimensão?).
**DECISÃO PROPOSTA:** **Estático — e a decisão fecha três dúvidas:** a secção de serviços do Início/Sobre é
**texto fixo no código** (sem CMS); as imagens dos serviços usam **`servico_foto`** quando existir e, em
falta, a **imagem da categoria** (o *fallback* do P-06). Norma escrita para quando o cliente enviar imagens:
**`.jpg`/`.png`, ~16:9, ≤ 1 MB**.
**Porquê:** `C-24` **propõe exatamente isto** (*"Estática (proposta), como o About — evita um módulo de CMS
fora do âmbito"*) e a análise **já verificou** que `hero`/`about`/`testimonial` são componentes estáticos
(`analise` §2.9 · Q-44). Escrever hoje a norma das imagens fecha Q-45 sem depender de resposta — e o
*fallback* é o que garante o ecrã **na demonstração**, porque nenhuma imagem nova chega em 24 h.
**Impacto:** `data-api.md` §17 (`servico_foto` com conteúdo **ou** fallback) · `modules/main/serviceDetail.php`
(norma documentada) · `C-24` → **fechado (estático)** · Q-27/Q-44/Q-45 → respondidas (RF-07 dependente do
P-06).
**Esforço:** — (contido no P-06) · **Se decidires o contrário:** um CMS em 24 h é inviável e abriria um
módulo novo de permissões (quem edita o quê).

#### P-23 · Gráficos e seletor de período  <!-- id:P-23 -->

**Pergunta:** Q-35/Q-65 (*"o documento novo deixou cair os gráficos e o seletor
mensal/trimestral/anual — mantemos só o gráfico de custos de funcionários?"*).
**DECISÃO PROPOSTA:** **Só o que é decidível:** **um** gráfico (custos de funcionários: remuneração *vs*
valor recebido) com **Chart.js** (E-3) e **sem seletor de período** — o período é o da importação em vigor.
Registar em §22.1; o seletor vai para §25 (trabalho futuro).
**Porquê:** foi o **próprio cliente** que deixou cair os gráficos e o seletor no documento mais recente — a
decisão conservadora é seguir o documento novo. E o seletor obrigaria a definir a **semântica do período**
para cada indicador (mês? trimestre? que datas para o importado?), que é a mesma família de Q-08/Q-09
(saldo inicial e períodos) ainda sem resposta.
**Impacto:** contabilidade/painel (1 gráfico) · §22.1 + §25 · Q-35/Q-65 → respondidas.
**Esforço:** ~1 h (dentro do P-12) · **Se decidires o contrário:** o seletor exige definir a agregação
temporal de **todos** os indicadores — mais trabalho do que o Tier 1 restante.

## 3. COBERTURA — nenhuma dúvida fica sem destino

Prova de que as **36 perguntas** e os **30 conflitos** têm decisão proposta (ou destino declarado). Os IDs
abrem no editor: `git ref-open Q-63` · `git ref-open C-26`.

### 3.1 As 36 perguntas → onde foram decididas

| N.º | ID(s)            | → P      | N.º | ID(s)         | → P      | N.º | ID(s)       | → P      |
| :-- | :--------------- | :------- | :-- | :------------ | :------- | :-- | :---------- | :------- |
| 1   | `Q-01` Q-46 Q-47 | **P-10** | 13  | `Q-53`        | **P-15** | 25  | *(sem ID)*  | **P-16** |
| 2   | `Q-18` C-08      | **P-10** | 14  | `Q-54`        | **P-15** | 26  | *(sem ID)*  | **P-16** |
| 3   | `Q-08` C-14 C-05 | **P-20** | 15  | `Q-55`        | **P-15** | 27  | `Q-44` C-24 | **P-22** |
| 4   | `Q-31` C-20 C-10 | **P-13** | 16  | `Q-56` C-26   | **P-15** | 28  | `Q-45` F-07 | **P-22** |
| 5   | `Q-49`           | **P-13** | 17  | `Q-40` C-10   | **P-14** | 29  | `Q-58`      | **P-15** |
| 6   | `Q-34` Q-35      | **P-14** | 18  | `Q-09` Q-11   | **P-14** | 30  | `Q-59`      | **P-15** |
| 7   | `Q-35` C-04      | **P-12** | 19  | `Q-19` C-28   | **P-14** | 31  | `Q-60` C-27 | **P-15** |
| 8   | `Q-04` C-25      | **P-12** | 20  | `Q-62`        | **P-21** | 32  | `Q-61` C-26 | **P-15** |
| 9   | `Q-48` Q-49      | **P-11** | 21  | *(sem ID)*    | **P-18** | 33  | `Q-63` C-28 | **P-14** |
| 10  | `Q-51` C-27      | **P-15** | 22  | `Q-22` C-21   | **P-19** | 34  | `Q-64`      | **P-14** |
| 11  | `Q-11` C-29      | **P-15** | 23  | `Q-23` C-03   | **P-19** | 35  | `Q-65`      | **P-23** |
| 12  | `Q-52`           | **P-15** | 24  | *(sem fonte)* | **P-19** | 36  | `Q-66` C-20 | **P-13** |

*Notas:* as perguntas **21/25/26** não têm `Q-nn` na análise → **criar ID** quando a decisão for registada
(21 → desativação de funcionário · 25/26 → promoções). A pergunta **24** é **sem fonte**: não existe no
repositório e **não se implementa** (P-19).

### 3.2 Os 30 conflitos → destino

| ID     | Conflito (resumo)                         | Destino                                                        |
| :----- | :---------------------------------------- | :------------------------------------------------------------- |
| `C-01` | `/gestao` = painel ou lista?              | **P-09** → `/gestao` painel · **fechado por decisão**          |
| `C-02` | Avisos não fiscais no modelo fiscal       | **P-19** → mecanismo separado · **fechado**                    |
| `C-03` | Contador do sino global vs por utilizador | **P-19** → global + limitação §22.2 · **fechado**              |
| `C-04` | `transacao_financeira` é só recebimentos  | **P-12/P-15** → importado não escreve lá · **fechado**         |
| `C-05` | Simulador fiscal escrever na obrigação?   | **P-20** → só **lê e calcula** · **fechado**                   |
| `C-06` | KPIs do painel vs decisão manual de rotas | **P-07/P-09** → nenhum KPI bloqueia (D-01) · **fechado**       |
| `C-07` | Fronteira com §22.2 (perfil de leitura)   | **P-18** → limitação declarada · **fechado**                   |
| `C-08` | Base dos 70/30 com ou sem desconto        | **P-10/P-16** → sem descontos no MVP · **fechado**             |
| `C-09` | Autorização por página + endpoint         | **P-18** → mantém-se a atual (com testes) · **fechado**        |
| `C-10` | Fonte única do custo de pessoal           | **P-14** → salário + comissões + importado · **parcial**       |
| `C-11` | Sequência: fornecedores antes da contab.  | **P-17** → fornecedores adiados · **fechado**                  |
| `C-12` | Âmbito da Fase 6 (módulos novos)          | **P-01** → Tier 1/2/3 · **fechado por decisão**                |
| `C-13` | Plano de testes por fase                  | **P-01** + §5 → testes na §5 · **fechado**                     |
| `C-14` | IRC 20 % fixo ou configurável             | **P-20** → configurável, valor manual · **fechado**            |
| `C-15` | Tesouraria antes ou depois de §24.5       | **P-12** → leitura do importado · **fechado**                  |
| `C-16` | «Agenda do funcionário»                   | **P-18** → adiada, limitação §22.2 · **fechado na decisão**    |
| `C-17` | IVA: onde vive a taxa + o que se grava    | **P-10** → config + cálculo · **fechado por decisão**          |
| `C-18` | Filtro de preço sobe para 60 €            | **P-10** → filtro em bruto, 60 € · **fechado**                 |
| `C-19` | Formato de importação + interno/externo   | **P-11/P-13** → CSV **e** XLSX · **arquivado**                 |
| `C-20` | Dono de cada número                       | **P-13** → receita sempre interna · **fechado por decisão**    |
| `C-21` | Notificações: interno vs cliente          | **P-19** → dois mecanismos · **fechado**                       |
| `C-22` | Matriz de perfis por página + endpoint    | **P-18** → mantém-se em §24 · **aberto** (declarado)           |
| `C-23` | Imagem do serviço: `servico_foto`?        | **P-06/P-22** → `servico_foto` + fallback · **fechado**        |
| `C-24` | Secção de serviços no Home estática       | **P-22** → estática · **fechado**                              |
| `C-25` | Disponibilidades: tabela ou parâmetros    | **P-12** → do importado · **aberto em §24** (modelação futura) |
| `C-26` | Empréstimo + plano de amortizações        | **P-15** → valor do ficheiro · **aberto em §24**               |
| `C-27` | Dívidas a pagar: naturezas vs célula      | **P-15** → ler do ficheiro · **parcial**                       |
| `C-28` | IRS retido por trabalhador                | **P-14** → taxa por trabalhador ✅ · **parcial** (13.º/14.º)   |
| `C-29` | Balancete: mapa de células vs importar    | **P-11/P-15** → importar · **confirmado**                      |
| `C-30` | `.xlsx` viável sem pacotes                | **P-11** → E-1 (PhpSpreadsheet) · **arquivado**                |

**Resultado:** **24 conflitos fechados**, 3 parciais (C-10 · C-27 · C-28) e 3 declaradamente abertos em §24
(C-22 · C-25 · C-26) — nenhum fica «esquecido».

## 4. O QUE MUDA NA ESPECIFICAÇÃO (por cada P aceite)

Regra do projeto (`build_spec_on_demand.md` §4): implementação concluída **fecha o ciclo na mesma
alteração** — estado (§4), gap (§24), critério (§28) e testes (§26). Tabela de trabalho:

| P    | `requirements.md` (§4/§5)                         | `delivery.md` (§22/§28)               | Outros                                        |
| :--- | :------------------------------------------------ | :------------------------------------ | :-------------------------------------------- |
| P-02 | RF-12 → ✅ · RN-26 → ✅                           | §28.2 #11 → ✅                        | §19.1 API + §26.4 testes                      |
| P-03 | RF-58/59 → ✅ · RN-24/RN-25 → ✅                  | §28.2 #12 → ✅ · §24.6 → parcial      | §26.4 testes                                  |
| P-04 | RF-13 → ✅                                        | §28.2 #13 → ✅ · §15.3 → ✅           | §26.4 testes                                  |
| P-05 | RF-25/62/71/72/73 → ✅ · RN-23/RN-29 → ✅         | §28.2 #14 → ✅ · §24.5 → fechado      | **§17 + §17.9** (`config_sistema`) · §19.3 UI |
| P-06 | RF-07 → ✅                                        | §28.2 #15 → ✅ · §24.2 → parcial      | §17 (`servico_foto`) · `index.php`            |
| P-07 | RF-57 → ✅ · RF-56 → 🟡                           | §28.2 #16 → 🟡 · **§22.1 linha nova** | §12.4 (alerta)                                |
| P-08 | RF-35 → ✅                                        | §28.2 #17 → ✅ · §24.1 → fechado      | —                                             |
| P-09 | RF-63 → ✅                                        | §24 · §25.5 (convenção de menus)      | **C-01 fechado**                              |
| P-10 | **D-13 (nova)** + RN nova (IVA) · §7              | §22.1 (taxa em configuração)          | §17 (nota: sem coluna nova) · C-17/C-18       |
| P-11 | RF-75 → ✅ · RN-30 → ✅                           | §24 → fechado (importação)            | §17.9 + §17 (2 tabelas) · §1.2 (E-1/E-2)      |
| P-12 | RF-76 → 🟡                                        | §24 (fase seguinte: C-25/26/27)       | §25.5 (menu) · §19.3                          |
| P-13 | **RN-31 (nova)** — origem declarada por indicador | §22.1 · §3.12 (nota)                  | **Q-66 fechada** · C-20                       |
| P-14 | RF novo (RH: custo de pessoal) · C-28 parcial     | **§22.1 linha nova** + §24            | `analise` §F.4 (fonte)                        |
| P-15 | **D-14 (nova)** — contabilidade lê o importado    | §22.1 (sem cálculo de rácios)         | C-27/C-29                                     |
| P-16 | —                                                 | §22.1 + §25 (promoções)               | Q-25/Q-26 abertas                             |
| P-17 | —                                                 | §25.1 (mantém-se) + §19.5             | A-04 corrigido                                |
| P-18 | —                                                 | **§22.2 linha nova** · §24 (C-22)     | §18.6 (autorização)                           |
| P-19 | —                                                 | **§22.2 (2 linhas)**                  | C-02/C-03/C-21 fechados                       |
| P-20 | RN-20 (confirmar) · Q-03 respondida               | §13 (nota)                            | C-05/C-14 fechados                            |
| P-21 | —                                                 | §22.1 + §25 (anexos)                  | Q-20/Q-62                                     |
| P-22 | —                                                 | §22.1 (CMS fora)                      | C-24 fechado · Q-27/Q-44/Q-45                 |
| P-23 | —                                                 | §22.1 + §25 (seletor)                 | Q-35/Q-65                                     |

**Decisões novas propostas para §3** (a numerar por `§29.3`, **sem reutilizar números extintos**):
**D-13 · IVA e preços de montra** (P-10) · **D-14 · Contabilidade por importação e origem dos números**
(P-12/P-13/P-15) · **D-15 · Âmbito da Fase 6 e simplificações aceites** (P-01/P-07/P-16/P-18/P-21/P-23).
Numeração **provisória** — a confirmar em `annex.md` §29.3 antes de escrever (D-13 pode já existir).

## 5. PLANO DAS 24 HORAS (ordem de execução)

| #   | Passo                                                      | P     | Ficheiros principais                                      | Verificação                             |
| :-- | :--------------------------------------------------------- | :---- | :-------------------------------------------------------- | :-------------------------------------- |
| 1   | **Configuração** (`config_sistema` + leitura + ecrã)       | P-05  | SQL, `ConfigService`/`Repository`, `/gestao/configuracao` | migração + leitura pelos 3 consumidores |
| 2   | **Sinal configurável** (substituir a constante)            | P-05  | `BookingService`                                          | regressão: `functional_test`            |
| 3   | **Importação** (upload + 2 tabelas + substituição)         | P-11  | `ImportService`/`Repository`/`Controller`, UI             | upload real do `.xlsx` do cliente       |
| 4   | **Cobrança dos 90 %** + método + caso *offline*            | P-05  | `admin-payment-collect`, UI de execução                   | `http_test` (409/422)                   |
| 5   | **Cancelamento pelo cliente**                              | P-02  | endpoint + botão                                          | `http_test` (401/403/409/200)           |
| 6   | **24 h** (bloqueio na rota + auto-cancelamento na leitura) | P-03  | `RotaService`, *queries* de listagem                      | `functional_test`                       |
| 7   | **Lembrete ≤ 24 h** ao cliente                             | P-04  | `appointments.php` + JS                                   | `http_test` + `asset_test`              |
| 8   | **Slots revalidados**                                      | P-08  | `bookingWizard.js`                                        | `js_syntax_check`                       |
| 9   | **IVA de montra** (bruto calculado + filtro 60 €)          | P-10  | catálogo, wizard                                          | `asset_test` (contrato de nomes)        |
| 10  | **Detalhes do serviço + carousel** (com *fallback*)        | P-06  | `serviceDetail.php`, rota, `ServiceRepository`            | `asset_test` + manual                   |
| 11  | **Contabilidade** (1.º ecrã + rácios do importado)         | P-12  | `accounting.php`, Chart.js                                | manual + `http_test`                    |
| 12  | **Painel `/gestao`** + §25.5                               | P-09  | `dashboard.php`, `index.php`                              | `asset_test`                            |
| 13  | **RH** (leitura)                                           | P-14  | `staff.php`                                               | manual                                  |
| 14  | **Fecho do ciclo na spec** + §22.1/§22.2 + §28.2 + §26.4   | todos | `requirements.md`, `delivery.md`, `backlog.md`            | `md-verify` + `health-check`            |
| 15  | **Suites + gate**                                          | —     | —                                                         | 4 suites + `health-check` = `TUDO OK`   |

**Regra de corte:** se o relógio apertar, corta-se pela **ordem inversa** do Tier (12 → 11 → 10 → 9…) —
nunca se deixa a meio um passo que **toca no schema** (1, 3) nem a cobrança (4), porque são os que deixam o
sistema inconsistente.

## 6. RISCOS E LIMITES DESTE DOSSIER

- **Verificação estática.** Nada foi executado contra MySQL/Apache: leu-se código, schema e documentação.
  Os valores em euros repetidos aqui vêm da `analise_backoffice_gestor.md` (§4.3-4.6) — os `.xlsx` são
  binários e **não** foram re-extraídos nesta passagem.
- **Os esforços são estimativas** de leitura de código, não medições: servem para **ordenar**, não para
  prometer. Se algo escorregar, o corte está definido na §5.
- **O que falta mesmo é tempo, não decisão.** Com o Tier 1 + parte do Tier 2 ficam **5 de 7** critérios da
  §28.2 a ✅ e 2 em 🟡 — contra **0 de 7** hoje.
- **Nada disto foi aplicado:** nem a especificação, nem o produto, nem a mensagem. Este dossier é a lista
  para revisão; a execução só começa depois de aceitares (ou alterares) cada `P-nn`.
- **Risco maior identificado:** a **dupla contagem** (`C-10`/`C-20`) — mitigada por decisão no P-13 (nunca
  somar + origem visível), mas é o sítio onde um erro de leitura produz números errados na demonstração.

---
**Artefacto:** `_dev/docs/out/relatorio_decisoes-pendentes.md` · **Natureza:** apoio (**não normativo**) ·
**Autoridade:** `especificacao_mvp.md` + `_dev/docs/spec/`
**IDs vivos:** `P-01`…`P-23` (aqui) · `A-01`…`A-08` (`relatorio_mensagem-teams.md`) · `Q-nn`/`C-nn`
(`_dev/mapaMentalMVP/analise_backoffice_gestor.md`) — todos abrem com `git ref-open <ID>`
**Gate:** `md-join-tables` → `md-wrap-tables` → `md-align-tables` → `ascii-align` → `health-check` = **TUDO OK**
