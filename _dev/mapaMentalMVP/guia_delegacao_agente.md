# DELEGAÇÃO AO AGENTE — ESTADO E CONTEXTO (28/09/2026)

> **O que é este ficheiro.** Transferência de contexto de uma sessão para **outro PC/agente** continuar o
> trabalho sem repetir a investigação. Escrito pelo agente que fez a sessão, **a partir de evidência**
> (ficheiro:linha ou comando executado), não de memória.
> **Não é normativo:** o que é requisito está na especificação; o que é operação está no `.clinerules`.
> Onde este documento contradizer a especificação, **prevalece a especificação** — e este ficheiro
> corrige-se.
> **Branch:** `agent-workspace` · **Momento:** 28/09/2026, 13:0x (a Fase 6 foi **interrompida a pedido**).

## 1. COMO RETOMAR (protocolo mínimo)

| Ordem | Ler                                                             | Porquê                                              |
| :---- | :-------------------------------------------------------------- | :-------------------------------------------------- |
| 1     | `.clinerules`                                                   | restrições, convenções aplicadas, fluxo de Git      |
| 2     | `especificacao_mvp.md` (router)                                 | mapa `§ → ficheiro`                                 |
| 3     | **só o domínio** em causa, em `_dev/docs/spec/`                 | nunca carregar os dez ficheiros                     |
| 4     | **este ficheiro**, do §2 ao §7 (é o que evita repetir trabalho) | estado real, decisões já tomadas, perguntas abertas |
| 5     | `_dev/tests/README.md` · `_dev/tools/README.md`                 | ao testar · ao usar ferramentas                     |

**Antes de mexer:** `git pull` nesta branch e confirmar que o `dev` remoto não avançou
(`git log --oneline agent-workspace..origin/dev`). Nesta sessão: **vazio** (o `origin/dev` estava
contido na `agent-workspace`).

## 2. ESTADO DO GIT (o que foi empurrado e o que **não**)

| Branch            | Estado nesta entrega                                                                                          |
| :---------------- | :------------------------------------------------------------------------------------------------------------ |
| `agent-workspace` | ✅ **Tudo** o que existe no disco foi commitado e empurrado (código, migração, spec, testes, `mapaMentalMVP`) |
| `dev`             | ❌ **Sem commits e sem merges** — a pedido explícito. Continua igual ao remoto                                |
| `main`            | ❌ **Intocado**                                                                                               |

> ⚠️ **Consequência a resolver na próxima sessão:** as alterações de **produto** (`app/**`, `modules/**`,
> `DataBase.sql`) estão agora **nesta branch**, mas o `.clinerules` §4 exige que cheguem ao
> `dev` por **branches de contexto** (criadas a partir do `dev`, levando só caminhos de produto:
> `git checkout -b <contexto> dev` + `git checkout agent-workspace -- app/ modules/ …`), e só depois o
> **merge** em `dev`. **A ordem de integração é:** schema → core/infra → higiene → frontend → domínios →
## 3. O QUE FOI FEITO (e onde está cada peça)

### 3.1 Produto — funcionalidade "contadores públicos + IVA + página Sobre nós"

| Caminho                                                                                       | O que passou a fazer                                                                            |
| :-------------------------------------------------------------------------------------------- | :---------------------------------------------------------------------------------------------- |
| `app/config/api.php`                                                                          | Endpoint novo **`site-stats`** (público) → `StatsController::summary`                           |
| `app/config/config.php`                                                                       | E-mail real, **endereço real** (Praça Joaquim António de Aguiar, 12 a 19, U-5-ag, Évora),       |
|                                                                                               | `SITE_MAP_URL`,                                                                                 |
|                                                                                               | **`IVA_RATE` = 0,23**, `SITE_STATS_FALLBACK` e `SITE_STATS_DOCUMENTAL`                          |
| `app/controllers/StatsController.php` *(novo)*                                                | API pública de contadores (só agregados, zero dados pessoais)                                   |
| `app/services/StatsService.php` *(novo)*                                                      | Orquestra os contadores; **sem SQL** (delega nos Repositories, §18.1)                           |
| `app/repositories/{Service,Category,City,Employee,Customer}Repository.php`                    | `countAll()` / `countActive()` e `ServiceRepository::findMinPrice()`                            |
| `modules/common/js/api/api.js`                                                                | `API.stats.summary()` (cache 0 — o valor não pode ficar preso)                                  |
| `modules/common/js/utils/vat.utils.js` *(novo)*                                               | **Única** conversão de IVA do cliente: `rate`, `percentLabel`, `vatAmount`, `gross`, `net`      |
| `modules/common/js/utils/siteStats.utils.js` *(novo)*                                         | Preenche `[data-site-stat]`; regra contagem → documental; valores **derivados** (ver §5)        |
| `modules/common/js/utils/general.utils.js`                                                    | `formatCurrencyWithVat()` (usa o `vatUtils`)                                                    |
| `modules/main/includes/header.php`                                                            | Expõe `window.SITE_CONFIG` (nome, contactos, `ivaRate`, fallbacks dos contadores)               |
| `modules/main/includes/footer.php` · `modules/backoffice/includes/boFooter.php`               | Carregam os dois utils novos nas duas áreas                                                     |
| `modules/main/components/about.php`                                                           | Contadores vêm do `SITE_STATS_FALLBACK`; "preços desde" passou a `data-site-stat="minPrice"`    |
| `modules/main/js/components/about.js`                                                         | Espera pelo valor **antes** de animar (`counterUp` lê o texto existente)                        |
| `modules/main/components/servicesOverview.php` *(novo)*                                       | Bloco "A nossa plataforma" (4 cartões) — entra na home **e** no `/sobre`                        |
| `modules/main/components/about{Commitment,How,Mission,Store,Story,Team,Values}.php` *(novos)* | Sete componentes novos do "Sobre nós" (§3.2)                                                    |
| `modules/main/{home,about}.php`                                                               | Passam a incluir os componentes novos (ordem: missão → história → valores → equipa → como       |
|                                                                                               | funciona → espaço → compromisso → plataforma → testemunhos)                                     |
| `modules/main/js/components/{services,appointments,bookingWizard}.js` ·                       | Mostram valores **com IVA** (`IVA incl.`) e o resumo do wizard ganha subtotal + linha de IVA +  |
| `components/bookingWizard.php`                                                                | total                                                                                           |
| `modules/common/lib-our/jq-preloader/jq-preloader.js`                                         | **Defeito corrigido:** o preloader **clonava** os nós pré-existentes, partindo a identidade dos |
|                                                                                               | widgets (o carrossel de testemunhos ficava imóvel). Passou a **mover**                          |
| `modules/common/img/about-*.svg` · `overview-*.svg` *(14 novos)*                              | **Espaços reservados** de imagem (800×600, moldura tracejada, com o nome do ficheiro escrito    |
|                                                                                               | dentro) — substituir por fotografias reais mantendo o nome                                      |

### 3.2 Página `/sobre` — interpretação fixada com o utilizador

As 4 imagens `exemplo-layout-individual-components--*.png` dão **conceito e organização do conteúdo**,
**não** o estilo visual: o estilo é o do projeto (Bootstrap 5 + `style.css`, `font-dancing-script`,
`wow fadeIn`, `bg-light border-bottom border-end`). **Ignorar `/admin`** para estilo; dele só se tiram os
ficheiros do Chart.js. O componente de testemunhos **não** se replica. Conteúdo = **Secade Beauty**
(nada de *LavaFacil*).

### 3.3 Dados — `DataBase.sql` (novo, na raiz do repositório)

Migração **idempotente** (ids explícitos + `ON DUPLICATE KEY UPDATE`), **gerada** a partir dos ficheiros
### 3.4 Especificação (atualizada na mesma alteração — ciclo fechado)

| Ficheiro                                        | O que ganhou                                                                                                                                 |
| :---------------------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------- |
| `_dev/docs/spec/core.md`                        | **§3.16 · D-16** (IVA: a BD guarda o líquido, o cliente vê com IVA) e a **regra dos números publicados** (§3.12)                             |
| `_dev/docs/spec/requirements.md`                | **RF-85** (fornecedores) · **RF-86** (carga dos ficheiros) · **RF-87/88** (IVA) · **RN-36**                                                  |
| `_dev/docs/spec/data-api.md`                    | `DataBase.sql` na ordem de importação (§17.7) e a **justificação de BD** da tabela `fornecedor` (§17.9)                                      |
| `_dev/docs/spec/finance.md`                     | **2.º documento do calendário fiscal** (`imagem (2).png`) com prazos e **responsáveis**, e a **divergência** entre os dois documentos (§13)  |
| `_dev/docs/spec/backlog.md`                     | **§24.11** (carga dos ficheiros + decisões tomadas) e **§24.12** (faturas de vendas: cruzamento pendente)                                    |
| `_dev/docs/spec/backlog-future.md` *(novo)*     | **§25** — extraído do `backlog.md` (este passou das 400 linhas; corte na fronteira de `§N`)                                                  |
| `_dev/docs/spec/data-api-endpoints.md` *(novo)* | **§19** — extraído do `data-api.md` (idem), com `site-stats` e a regra do número publicado                                                   |
| `_dev/docs/spec/delivery.md`                    | Instalação com a **v4** (§27.2/§27.3), contagens esperadas (§27.4), critérios **26–28** da Fase 6, roadmap 🟡 **EM CURSO**, testes = **294** |
| `_dev/docs/spec/annex.md`                       | Mapa documental com os dois ficheiros novos e os **ficheiros-fonte do cliente**                                                              |
| `especificacao_mvp.md`                          | Router: `§1–3` → **D-01…D-16**; `§19` e `§25` apontam para os ficheiros novos                                                                |

### 3.5 Testes (todos a passar no fim da sessão)

| Suite                            | Resultado medido              |
| :------------------------------- | :---------------------------- |
| `_dev/tests/js_syntax_check.php` | **SINTAXE JS: OK** (exit 0)   |
| `_dev/tests/functional_test.php` | **105 pass, 0 fail** (exit 0) |
| `_dev/tests/http_test.php`       | **119 pass, 0 fail** (exit 0) |
| `_dev/tests/asset_test.php`      | **70 pass, 0 fail** (exit 0)  |
| **Total**                        | **294 verificações**          |

- **Falha corrigida no `http_test.php`** (secção 12): a asserção *"moradas E2E removidas"* contava
  **toda** a tabela `cliente_morada` (`cliente_id > 3`) e passou a falhar com **65** por causa dos clientes
  reais importados — agora conta **só** as moradas do cliente E2E (`cliente_id = {$e2eCustomerId}`).
- `_dev/tests/README.md` e `delivery.md` §26.1 atualizados para **294** e para o novo pré-requisito
  (**Apache + MySQL** no `asset_test`, porque as páginas autenticadas exigem sessão).
do cliente (nunca transcrita à mão). Aplicada **duas vezes** sem erro.

| Carga                            | Resultado verificado na BD                                                                                                                      |
| :------------------------------- | :---------------------------------------------------------------------------------------------------------------------------------------------- |
| **Durações** dos 35 serviços     | 35 casados por nome; **os preços já coincidiam** com a BD (nada mudou) → Σ = **3 103 min**                                                      |
| **`fornecedor`** *(tabela nova)* | **43** registos                                                                                                                                 |
| **Clientes**                     | `utilizador` **ids 100–164** (65, com `password_hash='*'` → login impossível de propósito), `cliente` 65, `cliente_morada` **ids 200–264** (65) |
| Conferência                      | 65 clientes · 65 moradas · 65 utilizadores · 65 e-mails gerados · **0 duplicados**                                                              |

**Total atual da BD `secade_beauty`: 25 tabelas** (24 + `fornecedor`).
## 4. BASE DE DADOS — ESTADO, REPETIÇÃO E VERIFICAÇÃO

| Facto                   | Valor (verificado por consulta em 28/09/2026)                                     |
| :---------------------- | :-------------------------------------------------------------------------------- |
| Esquema                 | `secade_beauty` · **25 tabelas** (24 do `DataBase.sql` + **`fornecedor`**)        |
| Catálogo                | 35 serviços (todos `ativo=1`) · 3 categorias · 10 cidades · 9 deslocações         |
| **Fornecedores**        | **43** (tabela criada pela v4)                                                    |
| **Clientes importados** | **65** (`cliente.id` ≥ 100; `utilizador.id` 100–164; `cliente_morada.id` 200–264) |
| E-mails gerados         | **65** no domínio `@cliente.secade.local`                                         |
| Durações                | Σ = **3 103 min** para os 35 serviços                                             |
| Utilizadores            | 3 (seed: gestor, funcionário, cliente #3) + 65 importados                         |

**Aplicar / repetir a v4** (é idempotente; correr sempre da raiz do projeto):

```powershell
$mysql = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'
cd C:\laragon\www\secade-beauty-tarde
& $mysql -u root --default-character-set=utf8mb4 -e "source DataBase.sql"
```

**Verificar** (deve dar 25 · 43 · 65 · 65 · 65 · 65 · 3103):

```powershell
& $mysql -u root --default-character-set=utf8mb4 secade_beauty -e "
SELECT (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='secade_beauty') tabelas,
       (SELECT COUNT(*) FROM fornecedor) fornecedores,
       (SELECT COUNT(*) FROM cliente WHERE id>=100) clientes,
       (SELECT COUNT(*) FROM cliente_morada WHERE id>=200) moradas,
       (SELECT COUNT(*) FROM utilizador WHERE email LIKE '%@cliente.secade.local') emails,
       (SELECT SUM(duracao_estimada_minutos) FROM servico) soma_duracoes;"
```

> ⚠️ **Esta BD tem dados reais de cliente.** As suites de teste assumem que ela está importada; as
> asserções que contavam tabelas inteiras tiveram de ser limitadas (§3.5). **Nunca** correr
> `DataBase.sql` sobre esta BD de trabalho sem querer (ele **apaga** a base) — é o passo 1 de §27.2.

### 4.1 Detalhes da carga que importam a quem continuar

| Detalhe                        | Decisão registada (e porquê)                                                                                                                                        |
| :----------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| E-mail do cliente              | A folha **não traz e-mail** → gerado `<slug>.<NIF\|sN>@cliente.secade.local`; domínio inexistente, só para garantir unicidade (**a confirmar**)                     |
| Telefone / rua / código postal | Vêm **vazios** na folha → ficam **em branco**; `cliente_morada` só com a **cidade** (que é a chave da rota, §8)                                                     |
| 5 fornecedores sem NIF válido  | `Temu` · `Bandido Portugal.pt` · `ViceDeal.com` · `Aliexpress` · `Consumíveis` → `nif` = `NULL` e o rótulo passou para `observacoes`                                |
| "Manuel jacinto (renda)" ×2    | NIFs diferentes → são **duas rendas distintas**, ambas mantidas (não é duplicado a limpar)                                                                          |
| Correções por semelhança       | `Cordolete`→**Cordelete** (serviço 2) · `Tranças Box Braids`→**Box Braids** (serviço 1) · `Arraiaolos`→**Arraiolos** (5 moradas) — ⚠️ **a confirmar com o cliente** |
| Login dos importados           | `password_hash = '*'` → conta **inutilizável de propósito** (é histórico, não acesso)                                                                               |
| Ids reservados                 | **Clientes ≥ 100** · **moradas ≥ 200** — deixar livres para não colidir com novos imports                                                                           |
## 5. DECISÕES E CONVENÇÕES NOVAS (herdadas desta sessão)

1. **D-16 · RN-36 — o catálogo guarda o líquido, o cliente vê o valor com IVA.** A BD mantém o preço base
   tributável; **todo** o ecrã virado ao cliente converte por `vatUtils` (taxa de `IVA_RATE`, 23 %). O
   **sinal de 10 %** incide sobre o valor **com IVA**. O resumo do wizard mostra **subtotal + IVA + total**.
   ✅ implementado no catálogo, nos dois wizards e em "Meus Agendamentos"; ⬜ a contabilidade (§24.7) usa a
   mesma utilidade. **Nunca** escrever `1.23` num componente.
2. **Regra do número publicado (`site-stats`).** Ordem: (1) **valor contado na BD**; (2) se a contagem for
   **0** → **valor documental** (`SITE_STATS_FALLBACK`); (3) para as chaves de **`SITE_STATS_DOCUMENTAL`** o
   documental mantém-se **mesmo com contagem > 0**. Hoje só `team` está nessa lista: a folha de salários
   prova **6 trabalhadores** e a BD tem **1** — sem isto a página publicaria "1 profissional".
   ⚠️ Quando as identidades dos 6 entrarem na BD, **retirar `team` da lista**.
3. **Valores derivados no cliente.** `siteStats.utils.js` tem `DERIVED`: `minPrice` vem de `minPriceNet`
   (contado na BD, sem IVA) e é apresentado **com IVA** (`5,01 €` a partir de `4,07 €`). O HTML traz sempre
   o valor documental, pelo que **sem JS os números continuam corretos**.
4. **Migração a partir da fonte, nunca transcrita.** `database_migration_vN.sql` é **gerado** por um script
   (`.tmp-gen-v4.php` + `.tmp-ods-dump.php` + `.tmp-xlsx-dump.php`), **idempotente**, com **ids explícitos**
   e `ON DUPLICATE KEY UPDATE`, e **aplicado duas vezes** para provar idempotência.
5. **Nada inventado.** Campo ausente na fonte → **em branco** + **nota explícita** na especificação.
   Aliases só se corrigem quando a correspondência é inequívoca — e ficam **registados como a confirmar**.
6. **Preço do catálogo = valor real.** O `.ods` de durações trouxe os preços; **coincidiam** com a BD, pelo
   que a v4 **não** mexeu em preços.
7. **Modularizar antes de acrescentar** (`.clinerules` §5, limite 400 linhas). Feito nesta sessão:
   `data-api.md` → `data-api-endpoints.md` (§19) e `backlog.md` → `backlog-future.md` (§25), ambos com
   router atualizado. **O router `especificacao_mvp.md` e `annex.md` §29.2 já apontam para eles.**
## 6. O QUE FALTA — PLANO DE CONTINUAÇÃO

### 6.1 Fase 6 (âmbito fechado: §24 + backoffice + sidebar + comissões)

Ordem interna **já fixada** (§24.7 · `backlog.md`), a manter:
**6.0 painel/dashboard → 6.1 fornecedores → 6.2 contabilidade → 6.3 RH → 6.4 sidebar + comissões →
6.5 área do funcionário**. **Promoções ficam fora** (Fase 7 · §24.7).
> ↪ Nota: um commit anterior nesta branch diz *"adia promoções para a Fase 8"*; a **especificação diz
> Fase 7** (`delivery.md` §21 e `backlog.md` §24.7). Seguir a especificação.

| #       | Trabalho                                                                                                                                              | Onde está definido               |
| :------ | :---------------------------------------------------------------------------------------------------------------------------------------------------- | :------------------------------- |
| 6.0     | **Dashboard do gestor** em `/gestao` (KPIs + gráficos) e encaminhamento do funcionário para a agenda; **sininho** com não lidos                       | RF-77 · §3.14 · §24.7            |
| 6.0     | **Chart.js v2.9.4** — copiar de `/admin/vendor/chart.js/` para **`modules/common/lib/chartjs/`** (local, **nunca por CDN**), carregado **só** no      | E-3 · §1.2 · §3.13               |
|         | backoffice                                                                                                                                            |                                  |
| 6.0     | **Ficheiros do `/admin`**: **só** os do Chart.js são para tirar de lá. Não usar o `/admin` para estilo nem layouts                                    | `rules §1` (§1.1 E-3)            |
| 6.0     | **Tabelas financeiras + CSV do 1.º trimestre** (`balanco`, `dr`, `balancete`, `iva_apuramento`) — a importação persiste em BD (nunca lê o ficheiro em | §17.9 · D-12 · RN-30 · RF-75/76  |
|         | cada request)                                                                                                                                         |                                  |
| 6.1     | **Fornecedores**: página `/gestao/fornecedores` + `admin-supplier-*` sobre a tabela **já carregada** (43)                                             | RF-85 · §25.1 · §17.9            |
| 6.2     | **Contabilidade** `/[gestao/contabilidade]`: gráficos como forma principal, tabelas onde fizer sentido                                                | RF-76 · RF-79 · D-13             |
| 6.3     | **RH**: `PayrollService` (RN-35), líquido a pagar **calculado**, taxas de IRS **por trabalhador como dado**                                           | RF-82 · §24.9                    |
| 6.4     | **Sidebar** do backoffice (a navbar está no limite) + **página das comissões** (`valor_recibo_verde_funcionario` **já gravado**)                      | RF-84 · §25.5                    |
| 6.5     | **Agenda do funcionário** `/gestao/agenda` (calendário, **só rotas confirmadas**); `admin-employee-agenda-list`                                       | RF-78 · RN-33 · §10.1            |
| 6.0/6.5 | **Regras a corrigir:** RN-31 (rota recusa **409** com serviços pendentes) · RN-32 ("Por aceitar" deixa de mostrar o que já está em rota confirmada) · | §24.7                            |
|         | RF-80 (excluir agendamento de rota **não decidida** → volta a *qualificado*, nunca `cancelado`)                                                       |                                  |
| §24     | Restantes requisitos da Fase 6: cancelamento pelo cliente + 24 h + lembrete (§24.6) · sinal configurável + 10/90 + métodos (§24.5) · multicidades     | §24                              |
|         | (§24.4) · detalhe de serviço + carousel (§24.2) · re-avaliação dinâmica de slots (§24.1)                                                              |                                  |
| §24.8   | **Defeito do autocomplete** em `/registo` (dropdown no canto superior esquerdo) — ⚠️ **já aparece corrigido** no `asset_test` (70 pass) e existe o    | §24.8 · `delivery.md` §28.2 (21) |
|         | commit `fix-autocomplete-dropdown` no `dev`; **confirmar visualmente antes de repetir trabalho**                                                      |                                  |

**Fechar o ciclo em cada item** (`rules §0.5`): estado em `requirements.md` §4 · gap em `backlog.md` §24 ·
critério em `delivery.md` §28 · testes em `delivery.md` §26 (+ `_dev/tests/README.md`).
### 6.2 Trabalho que ficou **em curso** quando a sessão foi interrompida

| #   | Item                                                                                   | Estado real                                                                        |
| :-- | :------------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------- |
| 1   | **Calendário fiscal → especificação** (2.º documento com prazos + responsáveis)        | ✅ **feito** (`finance.md` §13)                                                    |
| 2   | **Registar a migração v4 na especificação**                                            | ✅ **feito** (`data-api.md` §17.7/§17.9 · `backlog.md` §24.11 · `delivery.md` §27) |
| 3   | **Reescrita dos componentes do `about`** pelas imagens de referência                   | ✅ **feito** (7 componentes novos + `/sobre` a responder **200**)                  |
| 4   | **`Faturas de vendas SECADE BEAUTY,Lda.xlsx`** — extrair e cruzar com Balancete/DR/IVA | 🟡 **lido e registado** (`backlog.md` §24.12); **cruzamento por fazer**            |
| 5   | **Propagar o endereço real** (contacto, rodapé, `base_partida`)                        | ❌ **por fazer** — ver §6.3                                                        |
| 6   | **Modularizar o `data-api.md`** (400 linhas)                                           | ✅ **feito** (`data-api-endpoints.md`)                                             |
| 7   | **Fase 6** (implementação)                                                             | ⬜ **por começar** — o §6.1 é a ordem                                              |
| 8   | **Gate + testes**                                                                      | ✅ **feito** no fim da sessão (TUDO OK · 294)                                      |

### 6.3 Pendência concreta e verificada: o endereço antigo continua na BD e nos `.sql`

**Evidência (medida nesta sessão):**

| Onde                                         | Valor encontrado                                                                                       |
| :------------------------------------------- | :----------------------------------------------------------------------------------------------------- |
| `secade_beauty.base_partida` (id 1, Évora)   | `morada = 'Rua do Centro de Formação'` ← **obsoleto**                                                  |
| `DataBase.sql` (baseline do projeto)         | contém o mesmo texto obsoleto em `base_partida.morada`                                                 |
| `app/config/config.php` · **`SITE_ADDRESS`** | ✅ **já corrigido** para *"Espaço comercial, Praça Joaquim António de Aguiar, 12 a 19, U-5-ag, Évora"* |

**A fazer:** (1) corrigir o valor na BD e no esquema (`DataBase.sql` e/ou numa migração **v5**
idempotente — preferir a migração, para não reescrever a baseline já entregue); (2) correr a busca em todo
o produto (`app/**`, `modules/**`, `*.sql`) pelo texto antigo; (3) confirmar
`modules/main/components/contact.php` e o rodapé; (4) **perguntar** o **código postal** e as
**coordenadas** da loja (não constam em nenhum ficheiro entregue).
## 7. PERGUNTAS ABERTAS PARA O CLIENTE (não inferir nenhuma)

> **Estado:** a mensagem para o grupo/Teams está **por enviar** (`_dev/mapaMentalMVP/mensagem_teams.txt`).

### 7.1 Bloqueadas há mais tempo (contabilidade — contas que não fecham)

| #   | Assunto                                                                            | Divergência medida                                             |
| :-- | :--------------------------------------------------------------------------------- | :------------------------------------------------------------- |
| 1   | **Conta 62** (fornecimentos e serviços externos)                                   | 15 277,70 ↔ 6 527,30                                           |
| 2   | **Conta 69**                                                                       | 425,00 ↔ 415,32                                                |
| 3   | **Banco CTT** — saldos                                                             | divergência entre documentos                                   |
| 4   | **Critério do subsídio** (feriados e Carnaval) + **61 vs 63 dias** por trabalhador | falta a origem dos 2 dias (faltas/férias?)                     |
| 5   | **Taxas de IRS por trabalhador** — são fixas ou mudam de mês para mês?             | —                                                              |
| 6   | **Prazos em fim de semana** — data nominal ou dia útil seguinte?                   | o próprio ficheiro trata casos iguais de forma diferente (§13) |

### 7.2 Novas (abertas nesta sessão)

| #   | Assunto                                                                                                                                                    | Onde está registado |
| :-- | :--------------------------------------------------------------------------------------------------------------------------------------------------------- | :------------------ |
| 7   | **Nomes/NIF/NISS dos 6 trabalhadores** — **não constam em ficheiro nenhum** (verificado nas 2 fontes de salários; a folha *DMR – DRI* está vazia) →        | §24.9               |
|     | **a BD não pode ser completada** sem eles                                                                                                                  |                     |
| 8   | **E-mails dos 65 clientes** — aceitam o endereço gerado `@cliente.secade.local` ou fornecem os reais?                                                      | §24.11              |
| 9   | **Correções por semelhança** a confirmar: `Cordolete`→`Cordelete`, `Tranças Box Braids`→`Box Braids`, `Arraiaolos`→`Arraiolos`                             | §24.11              |
| 10  | **Calendário fiscal — qual documento manda em cada família?** A imagem dá SS **dia 30** e IRC **31/05**; o `Obrigações Fiscais.xlsx` dá SS **20–25** e IRC | §13                 |
|     | **2027-06-30**/**2026-08-31**. Só o IVA (entrega 20 · pagamento 25) coincide                                                                               |                     |
| 11  | **Faturas de vendas** — o cabeçalho (29 638,74 / 24 101,65) **não** corresponde ao detalhe de janeiro (10 183,93 / 8 279,62): são períodos diferentes?     | §24.12              |
| 12  | **Endereço/postal/coordenadas** da loja para os mapas                                                                                                      | §6.3 deste ficheiro |

## 8. AMBIENTE, COMANDOS E VERIFICAÇÕES

### 8.1 Caminhos fixos

| Peça         | Caminho                                                                          |
| :----------- | :------------------------------------------------------------------------------- |
| PHP 8.3      | `C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe`                           |
| MySQL 8.4.3  | `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe` (root, **sem** password) |
| Projeto      | `C:\laragon\www\secade-beauty-tarde`                                             |
| URL canónica | **`http://localhost/secade-beauty-tarde`**                                       |

> ⚠️ **`http://secade-beauty-tarde.test/` dá 500 em quase todas as rotas** (inclusive em `/agendar`,
> `/servicos` e `/sobre`) e **não** é causado por estas alterações: o sintoma é do *vhost*, não do código.
> Testar sempre pela **URL canónica**. (Medido: `.test/` → 500 em `/sobre`, `/servicos`, `/agendar`,
> `/contacto`; `/` → 200. Na canónica: `/`, `/servicos`, `/sobre`, `/contacto`, `/login`, `/agendar` → 200.)

### 8.2 Gate obrigatório (antes de concluir **qualquer** alteração)

```powershell
$php = 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe'
# 1) por ficheiro alterado, NESTA ordem, todos com --write:
#    md-join-tables -> md-wrap-tables -> md-align-tables -> ascii-align
& $php _dev/tools/md-join-tables.php  <ficheiro.md> --write
& $php _dev/tools/md-wrap-tables.php  <ficheiro.md> --write
& $php _dev/tools/md-align-tables.php <ficheiro.md> --write
& $php _dev/tools/ascii-align.php     <ficheiro.md> --write
# 2) saúde geral — tem de terminar em TUDO OK
& $php _dev/tools/health-check.php
```

**Estado no fim desta sessão:** `health-check` = **TUDO OK** (encoding limpo · `md-verify` TUDO OK em
31 ficheiros · tabelas niveladas). Se tocar em **JS**: `& $php _dev/tests/js_syntax_check.php`.

### 8.3 Testes (todos verdes no fim da sessão)

```powershell
& $php _dev/tests/js_syntax_check.php   # SINTAXE JS: OK
& $php _dev/tests/functional_test.php   # 105 pass, 0 fail   (MySQL)
& $php _dev/tests/http_test.php         # 119 pass, 0 fail   (Apache + MySQL)
& $php _dev/tests/asset_test.php        #  70 pass, 0 fail   (Apache + MySQL)
```

### 8.4 Manipular os ficheiros do cliente (nunca à mão, nunca com BOM)

```powershell
cd _dev/mapaMentalMVP
& $php .tmp-xlsx-dump.php 'Ficheiro.xlsx' ['Nome da folha'] [linhaInicio] [linhaFim]   # .xlsx -> texto
& $php .tmp-ods-dump.php  'Ficheiro.ods'                                              # .ods -> texto
& $php .tmp-docx-text.php 'Ficheiro.docx'                                             # .docx -> texto
& $php .tmp-odt-dump.php  'Ficheiro.odt'                                              # .odt -> texto
& $php .tmp-gen-v4.php                                                                # regenera o dump da BD (v4)
```

> ❌ **Nunca** usar `Get-Content` + `Set-Content` do PowerShell 5.1 para escrever ficheiros: introduz
> **BOM** e **mojibake**. Usar `php _dev/tools/file-edit.php` ou .NET (`rules §3`).
## 9. FICHEIROS-FONTE DO CLIENTE — **NÃO VERSIONADOS** (por decisão, não por esquecimento)

Estes ficheiros **existem no disco do PC onde a sessão correu** (`_dev/mapaMentalMVP/`) e **não** foram
empurrados: contêm dados financeiros e pessoais reais do cliente (NIF, salários, faturas, contabilidade) e
o repositório é partilhado. **Ficam locais.** O que foi versionado são os **utilitários** que os leem (§8.4)
— com eles, quem tiver os ficheiros recupera qualquer extração; sem os ficheiros, o que já foi extraído
está **na especificação** e **na BD**.

| Ficheiro (local)                                                       | Conteúdo                                               | Já aproveitado em                                      |
| :--------------------------------------------------------------------- | :----------------------------------------------------- | :----------------------------------------------------- |
| `Serviços, Clientes e Fornecedores.xlsx`                               | 43 fornecedores + 65 clientes                          | ✅ BD (`DataBase.sql` · §24.11)                        |
| `Secade Duração Serviços 1.ods`                                        | durações + preços dos 35 serviços                      | ✅ BD (durações; preços já coincidiam)                 |
| `Faturas de vendas SECADE BEAUTY,Lda.xlsx`                             | vendas por serviço, loja + ambulante                   | 🟡 §24.12 (cruzamento pendente)                        |
| `Contabilidade Secade Beauty.xlsx` (9 folhas)                          | balancete, DR, inventários, depreciações, RH, DMR      | ✅ §24.9 · §3.12 (conferência da conta 63)             |
| `Gastos e Rendimentos_Simulador processamento de salários Secade.xlsx` | taxas e fórmulas da folha de salários                  | ✅ §24.9                                               |
| `Obrigações Fiscais.xlsx`                                              | calendário fiscal de 2026 (8 famílias)                 | ✅ §13 · §24.10                                        |
| `imagem.png` · `imagem (1).png` · `imagem (2).png`                     | obrigações fiscais, menus, calendário com responsáveis | 🟡 `imagem (2).png` ✅ §13; as outras 2 por cruzar     |
| `Menu Dashboard.docx` · `Menu APOIO 3.docx`                            | menus e mapa de células do backoffice                  | 🟡 `Menu Dashboard.docx`; o *APOIO 3* está substituído |
| `Balancete/Balanço/DR *.pdf` · `Apuramento IVA *.pdf`                  | contabilidade oficial do 1.º trimestre                 | ✅ §3.12 (conferências) — usado no `balanco`/`dr`      |
| `exemplo-layout-individual-components--*.png` (4)                      | referência de **conceito** para o `about`              | ✅ §3.2                                                |
| `trabalho-lavandaria-info.odt`                                         | material de referência de **outro** projeto (24,5 MB)  | ❌ não usar como fonte de regras                       |

> ⚠️ **Ao usar o `trabalho-lavandaria-info.odt`:** é o projeto *LavaFacil* (material de formação). Serve de
> **ideia de estrutura** — **nunca** de requisito, nome, preço ou texto. Foi disso que saiu a confusão
> *LavaFacil vs Secade Beauty* que a §3.2 resolve.

**Derivados que também não foram versionados** (regeneráveis pelo §8.4, com os mesmos dados sensíveis):
`_dev/mapaMentalMVP/.tmp-xlsx/*.txt` e `.tmp-odt-dump.txt`.

## 10. CHECKLIST PARA A PRÓXIMA SESSÃO

1. `git pull` na `agent-workspace`; confirmar `git log --oneline agent-workspace..origin/dev` **vazio**.
2. **Levar o produto ao `dev`** pelos **branches de contexto** (`rules §4`), na ordem *schema → core/infra →
   higiene → frontend → domínios → testes* — sem esquecer que **`_dev/tests/**` ↔ `tests/**`** no `dev`
   (e **sem** as adaptações de profundidade: `dirname(__DIR__)` mantém-se lá, `__DIR__, 2` é só aqui).
3. Escolher um item do **§6.1 (Fase 6)** e fechá-lo de ponta a ponta, com o **gate** e as **4 suites** no fim.
4. **Antes de repetir trabalho**, confirmar que o defeito do §24.8 (autocomplete) ainda existe — há
   **commit** `fix-autocomplete-dropdown` no `dev` e o `asset_test` já o cobre.
5. Manter a **coerência documental**: `requirements.md` §4 ↔ `backlog.md` §24 ↔ `backlog-future.md` §25 ↔
   `delivery.md` §28. Se um ficheiro passar das **400 linhas**, modularizar na fronteira de `§N`
   (como se fez com §19 e §25) e atualizar o **router** e `annex.md` §29.2.
6. **Dados de cliente nunca se inventam.** Falta um campo → em branco + nota + **pergunta** (§7).

## 11. FRASES ÚTEIS PARA O UTILIZADOR (o que ele decidiu nesta sessão)

- *"Fase 6 não deve ser interrompida"* — os ficheiros novos entram **onde encaixam melhor**, sem parar a fase.
- *"Ignora o `/admin` para estilo"* — só o Chart.js vem de lá.
- *"Não faças merge de `dev` para `main`"*; esta entrega vai **toda** para `agent-workspace`, **sem**
  commits nem merges no `dev`/`main`.
- *"Se estiver a demorar muito, pula a verificação; se passar das 13:00, continua — a consistência é mais
  importante do que o prazo."*

---
**Fim do documento.** Escrito em 28/09/2026 na branch `agent-workspace`. Se alterar este ficheiro, manter a
mesma disciplina de evidência (caminho:linha ou comando executado).
---

## 12. ADENDA — 2.ª SESSÃO (28/09/2026, à tarde): o que a Fase 6 passou a ter

> Esta secção é o **delta** em relação ao §1–§11 acima. Tudo o que aqui se diz foi **medido**
> (consultas à BD, comandos executados, suites de teste corridas).

### 12.1 Fase 6 entregue (por ordem da §24.7)

| #     | Módulo                                                                                   | Onde vive                                                                                      |
| :---- | :--------------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------------------- |
| 6.0   | **Painel do gestor** em `/gestao` (KPIs do dia/7 dias + 4 gráficos)                      | `modules/backoffice/dashboard.php` · `app/{controllers,services,repositories}/Dashboard*`      |
| 6.0   | **Chart.js 2.9.4 local** (copiado de `admin/vendor/chart.js/`)                           | `modules/common/lib/chartjs/Chart.bundle.min.js` · carregado em `boFooter.php` (só backoffice) |
| 6.0   | **Avisos por perfil** + **sininho** com contador                                         | `modules/backoffice/avisos.php` · `app/services/AlertService.php` · `menuUserBo.php`           |
| 6.0   | **Sidebar** do backoffice (menu por perfil; `collapse` abaixo de `lg`)                   | `modules/backoffice/includes/boSidebar.php` · `modules/common/css/style.css`                   |
| 6.0   | **Regras das rotas** RN-31 / RN-32 / RN-34 + **diálogo de detalhes** com incluir/excluir | `RotaService::decideRoute` · `BookingRepository` · `routes.php` · `routes.js`                  |
| 6.0   | **Convenção única dos botões de ação** (ícone + `title`, dourado/azul/vermelho)          | `boUtils.actionButton()` + `.bo-action--*` no `style.css`                                      |
| 6.1   | **Fornecedores** (`/gestao/fornecedores`, RF-85) sobre os **43 reais**                   | `Supplier*` (mapper, repo, service, controller) + `suppliers.php` + `suppliers.js`             |
| 6.4   | **Comissões** (`/gestao/comissoes`, RF-84) — valores da aceitação, gestor vs funcionário | `Commission*` + `commissions.php` + `commissions.js`                                           |
| 6.5   | **Agenda do funcionário** (`/gestao/agenda`, RN-33) — calendário próprio, sem lib nova   | `EmployeeAgendaService` · `AgendaController` · `agenda.php` · `agenda.js`                      |
| §24.1 | **Slots revalidados** quando os serviços mudam (avulso e por pessoa)                     | `bookingWizard.js` (`refreshSlotsIfNeeded`)                                                    |
| §24.6 | **Cancelamento pelo cliente** (`customer-booking-cancel`, RF-12), sem penalização        | `BookingService::cancelCustomerBooking` + botão em `/agendamentos`                             |

### 12.2 Testes — **430 verificações**, todas a passar

| Suite                 | Antes | Agora     |
| :-------------------- | :---- | :-------- |
| `functional_test.php` | 105   | **153**   |
| `http_test.php`       | 119   | **179**   |
| `asset_test.php`      | 70    | **98**    |
| `js_syntax_check.php` | 17 f. | **26 f.** |
| **Total**             | 294   | **430**   |

### 12.3 O que **continua em falta** na Fase 6 (sem invenções)

1. **6.2 Contabilidade** (RF-75/76/79 · §17.9): importação dos ficheiros para tabelas próprias
   (`balanco`, `dr`, `balancete`, `iva_apuramento`) e os gráficos sobre esses dados. O painel mostra
   hoje um **estado vazio explicativo** nesse bloco — nunca um número estimado.
2. **6.3 RH** (RF-82 · §24.9): `PayrollService` (RN-35). Bloqueado pela **falta das identidades dos
   6 trabalhadores** (pergunta 7 do §7.2).
3. **§24.6 itens 2–5:** regra das 24 h, auto-cancelamento sem rota (`expirou_sem_rota`) e **lembrete**
   ao cliente.
4. **§24.5:** sinal configurável (substituir a constante `DEPOSIT_PERCENTAGE`), cobrança dos 90 % com
   método simulado (Dinheiro/Multibanco/MB Way) e cenário offline → numerário.
5. **§24.2:** página de detalhes por serviço com carousel (o modal atual continua a ser o único
   detalhe; `servico_foto` continua sem conteúdo).
6. **§24.4:** multicidades (agrupamento por dia + conjunto de cidades, validação de espaçamento com
   `matriz_deslocacao.tempo_estimado_minutos`), flexibilidade horária da carrinha (fim > 19:00) e o
   alerta padronizado de custos junto ao indicador de 50 €.
7. **§24.10:** alargar o enum de `obrigacao_fiscal.tipo` (SAF-T, DMR, retenções, IES/DA) e importar o
   calendário de 2026 do ficheiro.
8. **§24.12:** cruzar o `Faturas de vendas …xlsx` com 71/vendas, 72/IVA e a DR.
9. **Endereço/postal da loja** na BD (`base_partida`) — ver §6.3.

### 12.4 Notas de implementação que evitam repetir trabalho

- **RN-31 mudou os testes:** aceitar **todos** os serviços antes de decidir a rota é obrigatório; os
  dois agendamentos de ambulatório do `functional_test` tiveram de ficar em **janelas distintas**
  (17:00 e 09:00) porque o bloqueio de janela temporal só permite **uma consolidação sobreposta**.
- **`ApiClient.post` aceita `FormData`** (multipart → `$_POST`) e JSON; `BaseController::getRequestData()`
  lê os dois. O formulário de fornecedores usa `Form` (`form.utils.js`) e converte para JSON no `submit`.
- **`Validator::regex` falha com campo vazio** — em campos opcionais o padrão tem de aceitar vazio
  (`/^(…)?$/`). Foi o que partiu a 1.ª versão do `SupplierService`.
- **`findPending()` devolve linhas mapeadas (camelCase)** — o `AlertService` usava chaves da BD e gerava
  avisos do PHP; corrigido.
- **`/gestao` deixou de ser a lista de agendamentos** — passa a painel (gestor) e agenda (funcionário);
  `/gestao/agendamentos` mantém-se (§24.7).
- **Entrega ao `dev`:** as branches de contexto desta sessão são `fase6-infra`, `fase6-painel`,
  `fase6-rotas`, `fase6-fornecedores`, `fase6-comissoes`, `cliente-cancelamento` e `testes-430`
  (o mapa de ficheiros de cada uma está no histórico do Git; o `dev` recebeu-as por merge).

### 12.5 Estado do Git no fim da 2.ª sessão (para a próxima não adivinhar)

| Branch (local = remoto) | Commit final | Conteúdo                                                                        |
| :---------------------- | :----------- | :------------------------------------------------------------------------------ |
| `agent-workspace`       | `67efe83`    | tudo o que **não é produto** (`_dev/**`, `especificacao_mvp.md`, `.clinerules`) |
| `dev`                   | `46e6ad5`    | produto + `tests/` — recebeu, por merge, as 8 branches de contexto              |
| `main`                  | `66f141f`    | merge de `dev` (código final, 100% funcional de ponta a ponta)                  |

**Branches de contexto da sessão (todas empurradas):** `fase6-infra` · `fase6-repositorios` ·
`fase6-painel` · `fase6-rotas` · `fase6-fornecedores` · `fase6-comissoes` · `cliente-cancelamento` ·
`testes-430` — cada uma com ficheiros **disjuntos** (`rules §4`).

**Verificação final, já na branch `dev`:** as **4 suites** passam
(`153 + 179 + 98 = 430`, sintaxe JS OK) e a paridade de produto entre `dev` e `agent-workspace` é
**total** (`git diff dev agent-workspace -- app modules index.php DataBase.sql README.md`
não devolve nada).
