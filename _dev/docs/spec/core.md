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

### 1.2 Exceções aprovadas à stack (fechadas)

> Registadas por decisão do gestor do projeto (**25/09/2026**). Valem **só** para o que está listado —
> não abrem a porta a outros pacotes nem a novos frameworks. Justificação: **§3.12 · D-12**.

| #   | Exceção                                                                                                                     | Âmbito                                                                              |
| :-- | :-------------------------------------------------------------------------------------------------------------------------- | :---------------------------------------------------------------------------------- |
| E-1 | **Bibliotecas do material de formação** (`PhpSpreadsheet`, `TCPDF`, `PHPMailer` + dependências), via Composer `vendor/`     | **Devem ser usadas** na importação de ficheiros (CSV/XLSX) e no que dela depender   |
| E-2 | **Extensão PHP `zip`** ativa no Laragon (`php.ini`)                                                                         | Requisito do PhpSpreadsheet para ler `.xlsx`                                        |
| E-3 | **Chart.js** (v2.9.4, servido **localmente** de `modules/common/lib/chartjs/`) nos **ecrãs contabilísticos/financeiros** do | Painel do gestor e `/gestao/contabilidade` (§4.5 · D-13); **nunca** no site público |
|     | backoffice                                                                                                                  |                                                                                     |
| E-4 | **Tabelas novas** na BD para persistir o que é importado (§17.9)                                                            | Só as tabelas de importação; **nenhuma** tabela existente é alterada                |

Fora destas exceções mantém-se a regra geral: **não instalar pacotes nem adotar frameworks externos**
(`.clinerules` §1).

> **Nota sobre a E-3:** a biblioteca é **servida localmente** de `modules/common/lib/chartjs/` (`Chart.bundle.min.js` — Chart.js **v2.9.4** com o Moment.js embutido), copiada do exemplar que **já existe no projeto** em `admin/vendor/chart.js/`; a demonstração **não depende de internet**.
> ⚠️ Os exemplos do material de formação usam a **v4** (`cdn.jsdelivr.net/npm/chart.js@4.4.1`) e **não correm** na v2:
> na v2 as escalas são `options.scales.yAxes`, as legendas `options.legend` e os padrões globais `Chart.defaults.global`; os *demos* do próprio repositório (`admin/js/demo/chart-*.js`) já são v2 e servem de molde.

## 2. REGRAS DE OURO E PREVALÊNCIA

### 2.0 Fluxo das regras — o que prevalece sobre o quê

| #   | Camada                                         | Papel                                                                             | Fonte          |
| :-- | :--------------------------------------------- | :-------------------------------------------------------------------------------- | :------------- |
| 1   | Regras de operação                             | Como o trabalho é executado (restrições, convenções **aplicadas**, Git)           | `.clinerules`  |
| 2   | **Regras de Ouro (§2.A–§2.E)**                 | Invariantes de produto — nenhuma decisão as contraria                             | este documento |
| 3   | **Decisões finais (§3 · D-01…D-15)**           | Resolvem cada conflito de fontes; prevalecem sobre as fontes originais            | este documento |
| 4   | **Regras de negócio (§5 · RN-01…RN-35)**       | Regra operativa e testável; **no detalhe, a RN vence a §2** (a §2 dá o princípio) | este documento |
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

## 3. DECISÕES FINAIS (D-01 … D-15)

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

### 3.12 — D-12 · Importação de ficheiros, persistência e origem dos dados

**Decisão (25/09/2026):** o backoffice **importa ficheiros externos** (CSV/XLSX) por upload e
**persiste** o resultado em tabelas do projeto; os cartões, tabelas e gráficos leem **da BD**.

| #   | O que fica decidido                                                                                                                                                           |
| :-- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | A leitura usa as **bibliotecas do material de formação (E-1)** — `PhpSpreadsheet` para `.xlsx`, com a **extensão `zip`** ativa (E-2); CSV em `fgetcsv` nativo                 |
| 2   | A importação **substitui integralmente** a anterior, numa transação (**RN-30**) — nunca soma nem acumula                                                                      |
| 3   | Os gráficos usam **Chart.js servido localmente** (E-3), restrito aos ecrãs **contabilísticos/financeiros** (D-13 · §3.13)                                                     |
| 4   | O **upload funciona na demonstração**; pode haver uma importação prévia, que é substituída por qualquer importação feita ao vivo                                              |
| 5   | **Origem de cada número:** valores **importados** e valores **calculados na plataforma** (receita de agendamentos, custos de rota, comissões) **não se somam no mesmo total** |
| 6   | **Novas tabelas** (justificação em §17.9): apenas as necessárias para persistir o importado                                                                                   |

**Regras:** RN-30 · **Requisitos:** RF-75 · RF-76 · **Exceções:** E-1…E-4 (§1.2) · **Dúvida aberta:**
pergunta **36** de `mensagem_teams.txt` (que fonte prevalece em cada indicador) — enquanto não houver
resposta, **cada widget declara a sua origem** e as somas mistas ficam interditas.

**Confirmado nos documentos oficiais do grupo de contabilidade** (Balanço, Demonstração de resultados,
Balancete e mapa de conferência de IVA do 1.º trimestre de 2026) — reduz a dúvida da origem sem a fechar:

> **Ficheiros entregues a 28/09/2026** (folha de salários **atualizada**, calendário fiscal e simulador de
> gastos/rendimentos): um ficheiro novo **substitui** o anterior **quando fecha com os documentos
> oficiais**; quando **não fecha**, prevalece o **oficial** e a diferença é **perguntada** — nunca somada
> nem escondida (linhas **13** e **14** abaixo). Os ficheiros de 23/09 que estes substituem **não** são
> usados como fonte.

| #   | O que os documentos provam                                                                               | Consequência no que se implementa                                                                   |
| :-- | :------------------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------------------------------- |
| 1   | A **Demonstração de resultados é acumulada** desde janeiro (fevereiro inclui janeiro)                    | O valor **mensal** é a **diferença** para o mês anterior — nunca o valor publicado                  |
| 2   | As **vendas são tributadas a 23 %**, confirmado fatura a fatura no mapa de conferência                   | Taxa **uniforme na venda**; nas **compras** há 6 % e 23 % e existe IVA **não dedutível**            |
| 3   | O **IVA dedutível** está no **Ativo**; do lado do **Passivo** só há **Segurança Social + IRS retido**    | São naturezas diferentes e **não se somam** num cartão de "dívidas a pagar"                         |
| 4   | O **capital em dívida** é o saldo da conta de **empréstimos bancários**                                  | O cartão lê o **saldo da conta**; o plano de amortização **não** é valor contabilístico             |
| 5   | A coluna de **2025 está vazia** em todos os mapas                                                        | **Não há período homólogo**: a variação só se calcula **mês a mês dentro de 2026**                  |
| 6   | **Clientes = 0** no balanço                                                                              | Confirma o recebimento imediato (§14.1) e serve de **caso de teste**                                |
| 7   | Pelo menos **um valor do resumo entregue** (`.xlsx`) **não coincide** com o documento oficial            | ⚠️ **Divergência a perguntar** ao grupo de contabilidade: prevalece o **documento oficial**         |
| 8   | A **folha de salários atualizada** **fecha com o Balancete**: 6321 = 19 950,00 · 6324 = 2 312,40 · 632 = | O custo de pessoal e o **líquido a pagar** são **calculados** na plataforma (RF-82), nunca lidos de |
|     | 22 262,40 · IRS retido 1 221,00 · SS do trabalhador 2 194,50 · conta **63 = 27 234,13**                  | célula                                                                                              |
| 9   | O **subsídio de alimentação = dias úteis × 6,15 €**; em 2026: janeiro 21 · fevereiro 20 · março 22 =     | Base **variável por mês** (RN-35); o critério de feriados fica **a confirmar** (perguntas 33 e 34)  |
|     | **63 dias** = 387,45 (em fevereiro **não** se desconta o Carnaval)                                       |                                                                                                     |
| 10  | A base da **SS patronal (23,75 %)** é o **saldo de remunerações** (19 880,77) e não o bruto (19 950,00): | O encargo patronal usa a **base corrigida**; **2 cêntimos** de arredondamento a documentar          |
|     | 23,75 % × 19 880,77 = 4 721,68 [Balancete 635 = 4 721,70]                                                |                                                                                                     |
| 11  | As **depreciações conferem**: 44 715,45/4 anos = 2 794,71 · 913,99/3 anos = 76,17 · **2 870,88** = conta | Tabela de ativos e cartão de depreciações ficam com **prova numérica** (§26.3)                      |
|     | 64; 45 629,44 − 2 870,88 = **42 758,56** = Ativo não corrente de março                                   |                                                                                                     |
| 12  | Os **5 rácios** derivam do Balanço de março e **fecham**: liquidez 0,593596 · fundo de maneio −34 463,82 | Os rácios são **calculados** do Balanço importado, com **fórmula no `tooltip`** (§3.13 ponto 4)     |
|     | · autonomia 0,089098 · endividamento 0,910902 · solvabilidade 0,097813                                   |                                                                                                     |
| 13  | O **simulador de gastos/rendimentos** diverge em **62** (15 277,70 vs **6 527,30**) e **69** (425,00 vs  | ⚠️ **Perguntar as duas contas**; o dashboard apresenta o **RAI oficial (−12 945,58)**               |
|     | **415,32**); o RAI dele (−21 705,66, que **substitui** o −21 705,26 de 25/09) é **exatamente** −12       |                                                                                                     |
|     | 945,58 − 8 750,40 − 9,68                                                                                 |                                                                                                     |
| 14  | O **plano de amortização** (25 983,20 amortizado · 24 016,80 em dívida) **não é** o saldo real (645,34 · | **Real** e **plano** são indicadores **distintos**, identificados como tal — nunca somados          |
|     | **49 354,66**)                                                                                           |                                                                                                     |

**Estado:** 🟡 desenho fechado; implementação ⬜.

**Porquê estas exceções:** as bibliotecas vieram no material de formação entregue ao projeto
(`0605-MATERIA/csv_pdf_email`) e resolvem, sem código próprio, a leitura de `.xlsx` — que é o formato em
que o cliente entrega o balancete. **Alternativa rejeitada nesta fase:** leitor próprio em PHP puro
(ZIP por `zlib` + XML por `SimpleXML`) — viável e já testado, mas com mais código para manter e sem o
tratamento de datas/formatos que a biblioteca já oferece.

**Números publicados no site (contadores da página pública).** Os contadores do `Sobre nós` e do
catálogo (`site-stats` · §19.1) obedecem à mesma regra de origem: **primeiro o valor contado na BD**;
se a contagem for **0**, publica-se o **valor documental** (`SITE_STATS_FALLBACK`); e para as chaves
listadas em **`SITE_STATS_DOCUMENTAL`** o documental mantém-se **mesmo com contagem > 0** — é o caso de
`team`, porque a folha de salários documenta **6 trabalhadores** e a BD tem **1** (§24.9). Assim a página
**nunca publica um número inventado nem um número sabidamente incompleto**.

### 3.13 — D-13 · Forma de apresentação: gráficos no financeiro, tabelas e calendários na operação
**Decisão (28/09/2026):** os **dados contabilísticos/financeiros** apresentam-se **maioritariamente em
gráficos** (Chart.js · E-3), com **tabelas apenas onde fizer sentido**; a **gestão de operação**
(agendamentos, rotas, fiscal, serviços) mantém **tabelas** e **calendários** — sem gráficos.
**Requisitos:** RF-77 · RF-78 · RF-79 · **Estado:** ⬜ por implementar (§24.7).

**Forma dos indicadores no dashboard** (complementa D-13; é apresentação, não cálculo):

| #   | Forma                                                                             | Onde se aplica                              |
| :-- | :-------------------------------------------------------------------------------- | :------------------------------------------ |
| 1   | **Cartão de KPI:** ícone + rótulo + valor + **variação face ao mês anterior**     | totais de topo (receita, custos, resultado) |
| 2   | **Circular com o total ao centro** e legenda com **valor absoluto e percentagem** | distribuições (canal, rubrica, categoria)   |
| 3   | **Barras agrupadas por rubrica**, com legenda de séries                           | rendimentos *vs* gastos, por mês            |
| 4   | **Fórmula no `tooltip`** do indicador                                             | indicadores de **rácio**                    |
| 5   | **Lista de detalhe + total do período**                                           | ativos e depreciações, custo de pessoal     |
| 6   | **Faixa-resumo `A + B = C`**                                                      | somas parciais que fecham num total         |
| 7   | **Período identificado** no próprio cartão                                        | todos os indicadores                        |
| 8   | **Cor e estado são informativos** — nunca bloqueiam nem decidem (RN-05 · §3.1)    | indicadores de rácio                        |

- A variação do ponto **1** só existe **dentro de 2026** (não há coluna de 2025 — ver §3.12).
- Os **limiares** que dão a cada rácio o seu estado (*bom* / *atenção*) são **decisão do cliente** e
  **não se inferem**; enquanto não houver resposta, o cartão mostra o valor e a fórmula, **sem** veredicto.
- O **seletor de período** (mensal / trimestral / anual) está **por confirmar**; até lá o dashboard
  apresenta **um** período e identifica-o (ponto **7**).

### 3.14 — D-14 · Entrada e páginas do backoffice por perfil
**Decisão (28/09/2026):** `/gestao` é o **dashboard do gestor** (substitui o encaminhamento para a lista,
que se mantém em `/gestao/agendamentos`); o **funcionário** tem como entrada a sua **agenda**
`/gestao/agenda` (calendário de rotas confirmadas) e a aceitação/descarte continua em `/gestao/servicos`,
em **listagem**. Cada página serve **um só contexto e um só perfil** — não há páginas partilhadas que
mudem de conteúdo conforme o perfil.
**Requisitos:** RF-77 · RF-78 · **Estado:** ⬜ por implementar (§24.7).

### 3.15 — D-15 · Notificações, sininho e leitura (C-02 · C-03 decididos)
**Decisão (28/09/2026):** o **contador do sininho** usa a tabela existente `alerta_fiscal`
(`COUNT(*) WHERE visualizado = 0`) e a marcação de lido continua **global** — limitação assumida enquanto
houver **um** gestor (§22.2). Os lembretes **não fiscais** (fornecedores, operação) e a leitura **por
utilizador** exigem ➕ tabela `notificacao` com `origem`, alimentada pelo mesmo padrão **on-demand** (sem
CRON); o **calendário fiscal fica fiscal** e o enum `obrigacao_fiscal.tipo` **não** é alargado (evita
partir o que existe). A **página centralizada de avisos** é uma por perfil, alcançável pelo **menu do
utilizador** e pelo **clique no sino** (`/gestao/avisos`).
**Requisitos:** RF-81 · **Estado:** ⬜ por implementar (§24.7).

### 3.16 — D-16 · O catálogo guarda o líquido, o cliente vê o valor com IVA

> **Decisão (28/09/2026):** a BD guarda **sempre** o **preço base tributável** (sem IVA) em
> `servico.preco_base` e nos valores gravados no agendamento; **todos** os ecrãs **virados ao cliente**
> apresentam o valor **com IVA**, calculado numa única utilidade (`vatUtils`) a partir da taxa de
> configuração (**`IVA_RATE`**, 23 % — `app/config/config.php`).

**Motivo:** o catálogo do cliente vinha **sem IVA** e o Balancete/faturas trabalham **com** IVA — sem uma
regra única, o mesmo serviço aparecia com dois valores diferentes conforme o ecrã (§24.12). A conversão é
**de apresentação**: não se altera o que está gravado (mantém-se a coerência contabilística e a
possibilidade de a taxa mudar sem reescrever histórico).

| Onde                                        | Valor mostrado                                                         |
| :------------------------------------------ | :--------------------------------------------------------------------- |
| Catálogo, modal de detalhes, "Meus Agendamentos", resumo do wizard | **com IVA** (`vatUtils.gross()` · `generalUtils.formatCurrencyWithVat()`) |
| Resumo do wizard (**loja e carrinha**)      | **subtotal sem IVA + linha de IVA + total com IVA**                     |
| **Sinal de 10 %**                           | calculado sobre o valor **com IVA**                                     |
| BD (`preco_base`, `agendamento_servico.*`)  | **sem IVA** — inalterado                                                |

**Requisitos:** RF-87 · RF-88 · **Regras:** RN-36 · **Estado:** ✅ implementado (catálogo, wizard de loja,
wizard de carrinha e "Meus Agendamentos"); ⬜ a **contabilidade** (§24.7) passa a ler a mesma utilidade.
