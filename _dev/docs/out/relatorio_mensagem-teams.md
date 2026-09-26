# RELATÓRIO — ANÁLISE DA MENSAGEM AO TEAMS (backoffice: estrutura, entregas e dúvidas)

<!-- md-wrap-tables:max=220 -->

**Data:** 2026-09-26 · **Âmbito:** `_dev/mapaMentalMVP/mensagem_teams.txt` (5.ª versão, 25/09/2026) — as
3 secções (estrutura proposta, o que já recebemos, 36 dúvidas) — confrontada com `especificacao_mvp.md`,
`_dev/docs/spec/`, o schema (`DataBase_v3.sql`), `index.php` e `app/config/api.php`.
**Autor:** agente · **Artefacto:** `_dev/docs/out/relatorio_mensagem-teams.md` · **Natureza:** apoio (não
normativo) · **Autoridade:** `especificacao_mvp.md` + `_dev/docs/spec/`
**Regenerável:** este ficheiro **substitui-se em bloco** quando a mensagem mudar (DOUTRINA 8) — não se
corrige à mão.

> **Como ler (para quem não tem o contexto).** A mensagem ao Teams é o pedido de esclarecimento que o grupo
> dirige ao cliente: descreve a **estrutura proposta** para o backoffice, o que **já foi recebido** e lista
> **36 perguntas**. Este relatório **verifica essa mensagem contra o repositório** e responde a três coisas,
> por cada ponto: *o que já existe no código*, *o que já está decidido na especificação*, *o que continua
> aberto*. Cada afirmação traz **prova** (ficheiro:linha ou comando executado); nada é inferido sem o dizer.
>
> **IDs.** `A-nn` são os achados **deste** relatório; `Q-nn` (dúvidas) e `C-nn` (conflitos) são do artefacto
> companheiro `_dev/mapaMentalMVP/analise_backoffice_gestor.md`. Todos abrem no editor:
> `git ref-open A-01` · `git ref-open Q-66` · `git ref-open C-21`.
>
> **Divisão de trabalho com os ficheiros existentes.** `analise_backoffice_gestor.md` é a **análise** (o
> raciocínio e as provas, com os IDs); `mensagem_teams_referencias.txt` mantém a **rastreabilidade linha a
> linha** perante o cliente; **este** relatório é o **estado consolidado** — o que a mensagem afirma, o que
> resiste à verificação e o que falta decidir. Não duplica nenhum dos dois: aponta por ID.

## 1. RESUMO

| #    | Achado                                                                                                | Onde                             | Gravidade | Estado |
| :--- | :---------------------------------------------------------------------------------------------------- | :------------------------------- | :-------- | :----- |
| A-01 | A mensagem pede decisões que a **D-12 já fixou** (importação); só a *prioridade* continua aberta      | `mensagem_teams.txt` pergunta 36 | 🟠        | aberto |
| A-02 | A pergunta 22 reabre um *«se»* que a **D-11** já decidiu; o aberto é o *«como/onde»*                  | pergunta 22 · C-21               | 🟠        | aberto |
| A-03 | A proposta de **painel de entrada** colide com a rota `/gestao` que **já é** a lista de agendamentos  | `index.php` L30-31 · C-01        | 🟠        | aberto |
| A-04 | **Fornecedores** é descrito como *«EM ANDAMENTO»*, mas é **previsto** (prioridade 1), não iniciado    | §25.1 · §19.5 · §25.5            | 🟡        | aberto |
| A-05 | Os **preços com IVA incluído** e a **imagem em cada card** não são pedidos novos: têm decisão/registo | D-06 · §24.2 · C-17 · C-18       | 🟡        | aberto |
| A-06 | As **36 perguntas não têm ID** e a numeração **não é sequencial no ficheiro**                         | `mensagem_teams.txt` L163-244    | 🟡        | aberto |
| A-07 | O **menu do backoffice** que a mensagem propõe tem de encaixar na convenção já fixada                 | §25.5 · `index.php` L30-36       | 🟡        | aberto |
| A-08 | A mensagem e a análise **repetem as mesmas perguntas** com identificações diferentes                  | pergunta 12-21 · Q-52…Q-65       | 🟡        | aberto |

**Gravidade:** 🔴 bloqueia · 🟠 decisão necessária · 🟡 incoerência · ⚪ verificado OK (não mexer)
**Estado:** aberto · resolvido neste relatório (só informativo) · depende do cliente.

## 2. DETALHE

### A-01 · A mensagem pede decisões que a D-12 já fixou  <!-- id:A-01 -->

**Afirmado em:** `_dev/mapaMentalMVP/mensagem_teams.txt`, *«IMPORTAÇÃO DE FICHEIROS»*, pergunta 36 —
*"os valores dos vossos ficheiros passam a ser importados para a base de dados … Uma importação nova
substitui a anterior por completo"*.
**Prova:** a decisão está registada na especificação, na **mesma data** da mensagem (25/09/2026):
`.clinerules` §1.1 (E-1…E-4) → `_dev/docs/spec/core.md` **§1.2** e **§3.12 · D-12** → requisitos
**RF-75 · RF-76 · RN-30** em `requirements.md` (§4.7 · §5.1) → tabelas novas justificadas em
`data-api.md` **§17.9**.
**Porque é problema:** o que a mensagem apresenta como proposta *("passam a ser importados", "substitui a
anterior"*) **já é regra aprovada**. Enviá-la assim reabre uma decisão fechada e atrasa a resposta.
**Proposta:** na próxima versão, marcar a pergunta 36 como **parcialmente respondida** — fica só a
prioridade (*ficheiro importado vs. cálculo da plataforma*), que é a dúvida **Q-66**; o resto passa a
*«já decidido: D-12 · RF-75 · RN-30»*.
**IDs relacionados:** `D-12` · `RF-75` · `RF-76` · `RN-30` · **Q-66** · **C-20**

### A-02 · A pergunta 22 reabre o «se» que a D-11 já decidiu  <!-- id:A-02 -->

**Afirmado em:** pergunta 22 — *"O sino do backoffice e os avisos ao cliente devem ser o mesmo mecanismo ou
dois separados?"*
**Prova:** o **aviso ao cliente existe por decisão**: `core.md` **§3.11 (D-11)** (alerta/lembrete
automático ≤ 24 h sem rota), `booking.md` **§15.3** (conteúdo e simulação), **RF-13** (`requirements.md`),
`backlog.md` **§24.6** (*crítico*) e `delivery.md` **§28.2/13** (critério de aceitação). O que **não**
está decidido é **um ou dois mecanismos** (**C-21**) e **onde** o cliente o vê (`§24.6` escreve *"e/ou na
home"*).
**Porque é problema:** se a pergunta for lida como *«devemos avisar o cliente?»*, o cliente pode responder
*«não»* a algo já aprovado — e a especificação deixaria de bater com a resposta.
**Proposta:** reformular para *«o lembrete ao cliente **já está decidido** (§15.3); falta escolher se o
sino do backoffice e o aviso ao cliente são **um** mecanismo ou **dois** (C-21) e onde aparece (§24.6)»*.
**IDs relacionados:** `D-11` · `RF-13` · **C-21** · **C-03** · `§24.6`

### A-03 · O painel de entrada colide com a rota `/gestao`  <!-- id:A-03 -->

**Afirmado em:** secção 1 da mensagem — *"GESTOR — Resumo (painel de entrada)"*.
**Prova:** `index.php` L30-31: `"gestao" => … /modules/backoffice/appointments.php` **e**
`"gestao/agendamentos" => … appointments.php` — hoje `/gestao` **é a lista de agendamentos**; as rotas do
backoffice são só seis (`gestao`, `gestao/agendamentos`, `gestao/rotas`, `gestao/servicos`,
`gestao/fiscal`, `gestao/recibos-verdes` — `index.php` L30-36).
**Porque é problema:** criar o painel *em* `/gestao` move a lista de agendamentos de URL sem que isso esteja
decidido — e a lista é referida por `§25.5` (convenção de menus).
**Proposta:** decisão necessária entre **(a)** `/gestao` = painel e `/gestao/agendamentos` = lista (rota já
existente) e **(b)** painel em `/gestao/painel` — é o **C-01**; registar a escolha na especificação
(**A-07**) antes de implementar.
**IDs relacionados:** **C-01** · `§25.5` · `§19.3`

### A-04 · «Fornecedores EM ANDAMENTO» — na verdade é previsto  <!-- id:A-04 -->

**Afirmado em:** secção 1 — *"Fornecedores (EM ANDAMENTO — prioridade 1 do trabalho futuro)"*.
**Prova:** é **prioridade 1 do futuro** e está **previsto**, não iniciado: `backlog.md` **§25.1**
(*"Prioridade 1 — Fornecedores ⭐"*), `data-api.md` **§19.5** (`admin-supplier-*` na lista de *endpoints
previstos e não implementados*) e `backlog.md` L184 (`/gestao/fornecedores* → gestor (PRIORIDADE 1 do
futuro)`). No código **não existe** nada: `app/config/api.php` não tem qualquer `admin-supplier-*`;
`DataBase_v3.sql` tem **23 tabelas** e nenhuma é de fornecedores; e a única ocorrência de *"fornecedor"*
no produto é outra coisa — o *fornecedor de geocodificação* (`modules/common/js/api/geocodingApi.js`
L7 · L12 · L26 · L38).
**Porque é problema:** *«em andamento»* sugere trabalho iniciado; o cliente pode planear a demonstração
contando com isso.
**Proposta:** corrigir para *«previsto e prioritário (§25.1), ainda não iniciado»*.
**IDs relacionados:** `§25.1` · `§19.5` · `§25.5`

### A-05 · Preços com IVA e imagem do card não são pedidos novos  <!-- id:A-05 -->

**Afirmado em:** secção 1 (*SITE PÚBLICO*) — *"Valores mostrados ao cliente com IVA incluído"* e
*"Imagem em cada card de serviço … (POR IMPLEMENTAR)"*.
**Prova:** (i) **imagem**: `data-api.md` L30 já tem a tabela `` `servico_foto` `` — *"Galeria de imagens
(**a alimentar** — D-06)"* — e o requisito está em `backlog.md` **§24.2** (*"Página de detalhes de serviço
+ carousel (D-06)"*), com a decisão em `core.md` **§3.6**: não é pedido por registar, é **gap já
registado**; (ii) **IVA**: na especificação **não existe** nenhuma regra de *«IVA incluído»* (pesquisa em
`_dev/docs/spec/*.md` por `IVA incluído` / `com IVA` → **0 resultados**), o que é coerente com o apurado em
**Q-01**: `preco_base` é **líquido** e a taxa é **implícita (23 %)** — e está em aberto onde vive a taxa
(**C-17**) e o que se grava na marcação; a montra (preços redondos) implica a **C-18** (filtro a 60 €
bruto).
**Porque é problema:** apresentar os dois como novidade esconde que **falta decidir** o essencial (onde
vive a taxa, e o que fica gravado na marcação) — e essa decisão mexe no modelo de dados.
**Proposta:** ligar explicitamente *«IVA incluído»* a **C-17/C-18** e *«imagem do card»* a **D-06 · §24.2**.
**IDs relacionados:** `D-06` · **C-17** · **C-18** · `§24.2` · **Q-01** · **Q-46**

### A-06 · Perguntas sem ID e numeração não sequencial  <!-- id:A-06 -->

**Afirmado em:** secção 3 — as 36 dúvidas são numeradas `1…36`, mas no ficheiro a ordem é
`1…21` → `29…36` (*«DOS FICHEIROS NOVOS»*) → `22…28` (*«AVISOS» · «PROMOÇÕES» · «SITE E CONTEÚDOS»*).
**Prova:** `mensagem_teams.txt` — a pergunta 21 fecha o bloco *«PESSOAL»*, seguem-se 29…36, e só no fim
aparecem 22…28; nenhuma pergunta tem ID (os IDs `Q-nn` vivem na análise).
**Porque é problema:** cruzar *«pergunta 31»* ↔ **Q-60** ↔ `§13` é feito à mão em cada ronda; e a análise já
cita *«pergunta 36»* por número — se a numeração mudar, as citações partem-se.
**Proposta:** **congelar** os números (já são citados) e acrescentar o **ID estável** ao lado
(`31 · Q-60`), com um índice no topo pela ordem do ficheiro. Assim cada pergunta não precisa de ID
próprio: usa o da análise (**A-08**).
**IDs relacionados:** `Q-58`…`Q-66` · **C-27** · **C-28**

### A-07 · O menu proposto tem de encaixar na convenção de §25.5  <!-- id:A-07 -->

**Afirmado em:** secção 1 — a mensagem propõe no menu do gestor **Contabilidade**, **Equipa/Recursos
Humanos**, **Promoções**, **Fornecedores** e um **Resumo (painel)**.
**Prova:** já existe uma convenção fixada para os menus — `backlog.md` **§25.5** (*"Estrutura de menus do
backoffice (convenção a manter)"*), que lista as rotas previstas (`/gestao/fornecedores* → gestor
(PRIORIDADE 1 do futuro)`); as rotas implementadas são as seis de `index.php` L30-36.
**Porque é problema:** cinco entradas novas no menu sem as numerar na convenção deixam a `§25.5`
desatualizada e o mapa de perfis (`§22.2`, **C-22**) sem resposta para o Funcionário.
**Proposta:** antes de mexer no menu, atualizar **§25.5** com as rotas novas (`/gestao/painel` ou `/gestao`,
`/gestao/contabilidade`, `/gestao/equipa`, `/gestao/promocoes`) e com o perfil de cada uma (**C-22**).
**IDs relacionados:** `§25.5` · `§22.2` · **C-01** · **C-22** · **C-16**

### A-08 · A mensagem e a análise repetem as mesmas perguntas  <!-- id:A-08 -->

**Afirmado em:** perguntas 12-21 (ativos, depreciações, financiamentos, pessoal) e 29-35 (ficheiros novos).
**Prova:** as mesmas dúvidas estão na análise com ID próprio — `analise_backoffice_gestor.md` **§2.10**
(**Q-48…Q-66**) e a checklist de **§3.1** (35 linhas, com `C-nn`); a análise fecha-as e reabre-as com
referência explícita *«pergunta 36 de `mensagem_teams.txt`»* (L1110-1111).
**Porque é problema:** é a mesma pergunta em dois sítios com duas identificações — a **redundância** que a
DOUTRINA 2 (`_dev/docs/README.md`) proíbe. Uma resposta do cliente dada *«à pergunta 33»* tem de ser
registada *«na Q-63»*, e sem essa ponte alguém atualiza um lado e não o outro.
**Proposta:** **a análise é a fonte dos IDs**; a mensagem passa a citá-los (`33 · Q-63`) e **não** cria
IDs próprios. A `mensagem_teams_referencias.txt` continua a rastreabilidade linha a linha.
**IDs relacionados:** **Q-52**…**Q-66** · **C-25**…**C-29** · DOUTRINA 2

## 3. MAPA DAS 36 PERGUNTAS — estado real de cada uma

**Estados:** `ABERTA` espera resposta (muda números ou modelo) · `PARCIAL` parte já está provada e resta uma
decisão · `SEM FONTE` nasceu da conversa, não existe no repositório · `SEM ID` a análise ainda não lhe deu
número (a criar quando houver decisão).

### 3.1 Valores, IVA e origem dos números (1-11)

| N.º | Pergunta (resumo)                                       | ID              | Estado  | Onde / o que falta                                     |
| :-- | :------------------------------------------------------ | :-------------- | :------ | :----------------------------------------------------- |
| 1   | Preços com IVA? Taxa uniforme?                          | `Q-01` · `Q-46` | PARCIAL | `preco_base` líquido + 23 % provados; falta **C-17**   |
| 2   | Sinal e comissões: com ou sem IVA? Remover?             | `C-08` · `Q-18` | ABERTA  | ⚠ *remover o sinal* contradiz **D-05** (§3.5 · §24.5) |
| 3   | IRC: taxa editável e valor decidido por nós?            | `C-14` · `C-05` | PARCIAL | editável = decidido; a taxa em si falta confirmar      |
| 4   | Cada indicador vem da plataforma ou do ficheiro?        | `C-20` · `C-10` | ABERTA  | base da **Q-66**; risco de **dupla contagem**          |
| 5   | Existem vendas fora da plataforma?                      | `Q-49`          | ABERTA  | não há como saber do código — resposta do cliente      |
| 6   | Fórmula da margem de lucro e que custos entram?         | `Q-34` · `Q-35` | ABERTA  | hoje **não existe** cálculo de margem                  |
| 7   | Despesas externas (rendas, luz, seguros…): como chegam? | `Q-35` · `C-04` | ABERTA  | sem modelo de despesas (gap da §24)                    |
| 8   | Caixa e banco são duas contas?                          | `Q-04` · `C-25` | ABERTA  | o balancete separa (**9 040,05 €** + **35 646,37 €**)  |
| 9   | As células mudam de posição de mês para mês?            | `Q-49`          | PARCIAL | 9 folhas nomeadas; falta confirmar a estabilidade      |
| 10  | Naturezas das «dívidas a pagar» confirmam-se?           | `Q-51` · `C-27` | ABERTA  | fornecedores · financiamento · IVA · Segurança Social  |
| 11  | Rácios: lidos do balancete ou calculados?               | `Q-58` · `C-29` | PARCIAL | **C-29 decidido** (importação); falta as células       |

### 3.2 Ativos, financiamentos e pessoal (12-21)

| N.º | Pergunta (resumo)                                   | ID              | Estado  | Onde / o que falta                                        |
| :-- | :-------------------------------------------------- | :-------------- | :------ | :-------------------------------------------------------- |
| 12  | Revalorizações e imparidades ficam de fora?         | `Q-52`          | ABERTA  | existem colunas no ficheiro; estão vazias                 |
| 13  | Localização no mapa de depreciações? Valor com IVA? | `Q-53`          | ABERTA  | com/sem IVA muda o valor de aquisição                     |
| 14  | Depreciação do período ou acumulada?                | `Q-54`          | ABERTA  | coincidem este ano; **divergem** nos seguintes            |
| 15  | Há vendas ou abates de ativos?                      | `Q-55`          | ABERTA  | sem dados, não há registo a construir                     |
| 16  | Um ou vários financiamentos? Taxa/prazo fixos?      | `Q-56` · `C-26` | ABERTA  | define a entidade empréstimo + plano de amortizações      |
| 17  | Comissões: mês do aceite ou do pagamento?           | `Q-40` · `C-10` | PARCIAL | mecanismo proposto (§2.8.1); falta a regra do período     |
| 18  | Mensal inclui subsídios? 13.º/14.º contam?          | `Q-09` · `Q-11` | PARCIAL | SA provado (6,15 € × dias úteis); 13.º/14.º não modelados |
| 19  | Taxas de IRS por trabalhador mantêm-se?             | `C-28`          | ABERTA  | provado: taxa **por trabalhador**, nunca da empresa       |
| 20  | Declarações fiscais anexadas ao calendário?         | `Q-62`          | ABERTA  | hoje o calendário **não** guarda anexos                   |
| 21  | Desativar funcionário: e as marcações futuras?      | **SEM ID**      | ABERTA  | a criar ID na análise (não existe regra na spec)          |

### 3.3 Avisos, promoções e site (22-28)

| N.º | Pergunta (resumo)                                                  | ID              | Estado        | Onde / o que falta                                   |
| :-- | :----------------------------------------------------------------- | :-------------- | :------------ | :--------------------------------------------------- |
| 22  | Sino e avisos ao cliente: um ou dois mecanismos?                   | `C-21`          | PARCIAL       | o *«se»* está em **D-11** (§15.3); falta o *«como»*  |
| 23  | Que avisos, além dos fiscais, e com que antecedência?              | `Q-23` · `C-03` | ABERTA        | contador global vs. por utilizador (**C-03**)        |
| 24  | «Renovação de contratos» e «revisões da carrinha»: de onde saíram? | —               | **SEM FONTE** | não estão na spec nem em ficheiro recebido           |
| 25  | Desconto: percentagem, valor fixo ou ambos? Acumulam?              | **SEM ID**      | ABERTA        | módulo novo (análise §1.6) — sem ID ainda            |
| 26  | Cliente vê o desconto no site ou no atendimento?                   | **SEM ID**      | ABERTA        | decide se o preço mostrado é o final                 |
| 27  | Secção de serviços: fixa ou editável?                              | `Q-44` · `C-24` | ABERTA        | proposta: **estática** (evita um CMS fora do âmbito) |
| 28  | Quem fornece as imagens, que formato/dimensão?                     | `Q-45` · `F-07` | ABERTA        | hoje **nenhuma** imagem de serviço existe            |

### 3.4 Ficheiros novos do cliente (29-36)

| N.º | Pergunta (resumo)                                        | ID              | Estado | Onde / o que falta                                       |
| :-- | :------------------------------------------------------- | :-------------- | :----- | :------------------------------------------------------- |
| 29  | Rácios: confirmam as células `B11..D15`?                 | `Q-58`          | ABERTA | as células indicadas apontam para outra coluna           |
| 30  | «Passivo não corrente»: células ou dispensar?            | `Q-59`          | ABERTA | valor é sempre `0` nas três colunas                      |
| 31  | IVA nas dívidas: posição líquida ou bruta?               | `Q-60` · `C-27` | ABERTA | muda o total de «Dívidas a pagar»                        |
| 32  | Capital em dívida: **24 016,80 €** ou **49 354,66 €**?   | `Q-61` · `C-26` | ABERTA | plano de amortização vs. conta do balancete              |
| 33  | Subsídio de alimentação: por mês ou calculado?           | `Q-63` · `C-28` | ABERTA | varia com os dias úteis (20/21/22 dias)                  |
| 34  | DRI diz **29 dias**; o SA usa **~21**. Gralha?           | `Q-64`          | ABERTA | ou são critérios diferentes                              |
| 35  | Gráficos e período (mensal/trimestral/anual) mantêm-se?  | `Q-65`          | ABERTA | o documento novo deixou cair isso                        |
| 36  | Ficheiro importado ou cálculo da plataforma: qual manda? | `Q-66`          | ABERTA | é a **única** parte não coberta pela **D-12** (ver A-01) |

**Leitura do mapa:** das 36 perguntas, **28 estão abertas**, **7 parciais** (parte provada, resta uma
decisão) e **1 não tem fonte nenhuma** no repositório (a 24). Confirma o que a própria mensagem diz na
*NOTA FINAL*: os blocos 1-21 e 29-36 condicionam o desenho do painel, da contabilidade, dos RH e das
notificações; os módulos que **não** dependem de valores externos (agendamentos, rotas, serviços, promoções,
site público) podem avançar sem elas.

## 4. VERIFICADO E CORRETO (caderneta «não voltar a rever»)

Tudo o que a mensagem afirma sobre **o que já existe** confere com o código — e as suas listas de
*«ainda não existe»* conferem com o schema. Não é preciso voltar a auditar isto:

| Afirmação da mensagem                                       | Prova                                                                                                                | Medido em  |
| :---------------------------------------------------------- | :------------------------------------------------------------------------------------------------------------------- | :--------- |
| «Agendamentos (JÁ EXISTE)»                                  | `app/config/api.php` L26-29 (`admin-appointments-*`) · `index.php` L30-31                                            | 2026-09-26 |
| «Rotas (JÁ EXISTE)»                                         | `api.php` L31-32 (`admin-routes-*`) · `index.php` L32                                                                | 2026-09-26 |
| «Fiscal (JÁ EXISTE: IVA, IRC, SS, Seguros)»                 | `api.php` L39-46 · `DataBase_v3.sql` L394: `tipo enum('iva','irc','seguranca_social','seguros')`                     | 2026-09-26 |
| «Recibos verdes (JÁ EXISTE: percentagens + simulador)»      | `api.php` L44-46 (`admin-green-receipt-*`) · `index.php` L36                                                         | 2026-09-26 |
| «Serviços (JÁ EXISTE, em modo supervisão)»                  | `index.php` L34 (`/gestao/servicos`)                                                                                 | 2026-09-26 |
| «Funcionário — aceitar, desfazer, trocar (JÁ EXISTE)»       | `api.php` L34-37 · `data-api.md` §19.2                                                                               | 2026-09-26 |
| «NÃO EXISTE: ativos, depreciações, empréstimos, rácios»     | `DataBase_v3.sql`: **23 tabelas**, nenhuma delas (`promocao`/`depreciacao`/`emprestimo`/`racio` = **0** ocorrências) | 2026-09-26 |
| «NÃO EXISTE: fornecedores e despesas externas»              | 0 tabelas; previsto em `data-api.md` §19.5 e `backlog.md` §25.1                                                      | 2026-09-26 |
| «NÃO EXISTE: retenção de IRS na fonte, por trabalhador»     | `DataBase_v3.sql`: `irs` e `retenc` = **0** ocorrências                                                              | 2026-09-26 |
| «Promoções (NOVO)», «Contabilidade (NOVO)», «Equipa (NOVO)» | 0 tabelas, 0 rotas (`index.php` L30-36), 0 endpoints (`api.php` L5-51)                                               | 2026-09-26 |
| «Os ficheiros novos substituem os antigos»                  | `annex.md` §29.2 (L49-52: *Menu Dashboard.docx* ↔ *Menu APOIO 3.docx*, etc.)                                         | 2026-09-26 |
| Sino com contador **global** (base do `C-03`)               | `DataBase_v3.sql`: `alerta_fiscal.visualizado` é `tinyint(1)` **sem** coluna de utilizador                           | 2026-09-26 |
| Receita só existe no livro de recebimentos (base do `C-04`) | `DataBase_v3.sql`: `transacao_financeira.agendamento_id` **NOT NULL** + 4 tipos de recebimento                       | 2026-09-26 |
| Custo de pessoal fixo vem do salário base                   | `DataBase_v3.sql` L330-331: `tipo_contrato enum('efetivo_contratado','recibo_verde')` · `salario_base`               | 2026-09-26 |

## 5. LIMITES DO TRABALHO

- **Verificação estática.** Nada foi executado contra o MySQL/Apache: leu-se código, schema e
  documentação. Os valores em euros que este relatório repete (**9 040,05 €** · **35 646,37 €** ·
  **24 016,80 €** · **49 354,66 €**) vêm da análise (`analise_backoffice_gestor.md` §4.3-4.6) e **não**
  foram re-extraídos dos `.xlsx` nesta passagem — são binários e não se citam linha a linha.
- **Não verificado por natureza:** a pergunta 24 (renovações/revisões da carrinha) veio da conversa e não
  existe em ficheiro nenhum; a resposta só pode vir do cliente.
- **Não tocado:** `mensagem_teams.txt`, `mensagem_teams_referencias.txt` e
  `analise_backoffice_gestor.md` **não foram alterados** — são as fontes; este relatório é derivado
  (DOUTRINA 8). O que aqui se propõe é o que **o humano** levar à próxima versão da mensagem.
- **O que não foi decidido aqui:** nenhuma das escolhas em aberto (`C-01`…`C-28`) — decisões de gestão são
  manuais (`.clinerules` §1.1 · `core.md` §3.1).

## 6. PRÓXIMOS PASSOS

| #   | Ação                                                                                                              | Depende de           | Risco                                                 |
| :-- | :---------------------------------------------------------------------------------------------------------------- | :------------------- | :---------------------------------------------------- |
| 1   | Corrigir a mensagem na próxima versão: **A-01…A-08** (o que já está decidido, ID ao lado do número, «previsto» em | —                    | baixo (texto)                                         |
|     | vez de «em andamento»)                                                                                            |                      |                                                       |
| 2   | Decidir **C-01** (rota do painel) e atualizar `backlog.md` **§25.5** + mapa de perfis (**C-22**)                  | 1                    | médio (rotas e menu)                                  |
| 3   | Enviar as 36 perguntas **com o estado** (aberta/parcial/sem fonte) — evita pedir decisões já tomadas              | 1                    | baixo                                                 |
| 4   | Criar **ID** para as perguntas 21, 25 e 26 (não têm `Q-nn` na análise)                                            | 1 e 2                | baixo                                                 |
| 5   | Registar as respostas do cliente na especificação: `Q-xx`/`C-xx` → `RF`/`RN`/`D` (`§29.3`, passo 4 de             | respostas do cliente | **alto** (modelo de dados: C-04 · C-26 · C-27 · C-28) |
|     | `build_spec_on_demand`)                                                                                           |                      |                                                       |
| 6   | Regerar este relatório quando a mensagem mudar (mesmo nome) e refazer o índice                                    | 1‑5                  | baixo                                                 |

---
**Artefacto:** `_dev/docs/out/relatorio_mensagem-teams.md` · **Natureza:** apoio (**não normativo**) ·
**Autoridade:** `especificacao_mvp.md` + `_dev/docs/spec/`
**IDs vivos:** `A-01`…`A-08` (aqui) · `Q-nn`/`C-nn` (`_dev/mapaMentalMVP/analise_backoffice_gestor.md`)
**Gate:** `md-join-tables` → `md-wrap-tables` → `md-align-tables` → `ascii-align` → `health-check` = **TUDO OK**
