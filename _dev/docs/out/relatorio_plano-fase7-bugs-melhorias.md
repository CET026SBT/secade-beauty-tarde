# PLANO DE IMPLEMENTAÇÃO — FASE 7 · Bugs & Improvements (v3 — decisões respondidas)

<!-- md-wrap-tables:max=220 -->

**Data:** 2026-10-03 · **Base:** `secade_beauty` · **Natureza:** apoio (**não normativo** — plano)
**Artefacto:** `_dev/docs/out/relatorio_plano-fase7-bugs-melhorias.md` · **Versão:** **v3**
**Âmbito:** cruzar a lista de *Bugs/Improvements* com o **estado real do código**, registar as **decisões
fechadas** pelo cliente, e propor **planeamento por fases**. Inclui o
`relatorio_reconciliacao-estados.md` e o `relatorio_estrutura-base-dados.md`.
**Autoridade:** `especificacao_mvp.md` + `_dev/docs/spec/` · **Estado:** 🔵 **em implementação** — **F1 ✅
concluída** (schema + dumps + código + testes); a decorrer para **F2 → F11**.

> 📌 **Versão reduzida (só bloqueios):** `relatorio_fase7-a-validar.md` — **é esse que fecha as decisões
> pendentes** (`D-nn`). Este ficheiro é o **plano completo** (contexto, evidência e desenho).

> **Como ler (v2).** **§1** diz o que ficou **fechado** e o que **ainda falta**. **§2–§3** são o cruzamento com
> o código (inclui 6 itens novos verificados nesta ronda). **§4** é o **modelo final** proposto (estados,
> percentagens, `uploads/`). **§5** é o plano por fases. **§6** Comissões · **§7** RH · **§8** Reconciliação.
> **§9–§11** requisitos a incluir / a ponderar / revogados. **§12** órfãos · **§13** validações.
> **§14** dúvidas (respostas e novas) · **§15** resumo para decisão.

## 0. RESUMO EXECUTIVO

| Dimensão            | Contagem | Nota                                                         |
| :------------------ | :------- | :----------------------------------------------------------- |
| Pontos na tua lista | **~50**  | 9 áreas + **6 itens novos** desta ronda                      |
| **Contradições**    | **14**   | `C-01…C-14` — **9 fechadas** por ti; ver §1.1                |
| **Gaps**            | **7**    | `G-01…G-07` — **6 fechados**; ver §1.1                       |
| **Dúvidas da v1**   | **27**   | quase todas fechadas; falta só a lista de lembretes (D-07.6) |
| **Dúvidas NOVAS**   | **8**    | `D-07.1…D-07.8` (§14.3) — ✅ **respondidas** (§14.4)         |
| Ficheiros afetados  | **~75**  | ~22 novos (Serviço, páginas, JS, validator, `uploads/`)      |
| Fases               | **11+1** | `F1…F11` (§5) + **F8 opcional** p/ o sinal/90 % (§10)        |

**Três descobertas que condicionam o plano:**

1. **As identidades dos 6 trabalhadores EXISTEM** (§7.1) — a spec dizia que não; **corrige-se**.
2. **As Comissões gravam a *promessa*, não a *verdade*** — o teu **C-12** obriga a mudar o critério para
   «serviço **prestado**» (§6.2).
3. **Os `valor_recibo_verde_*` são derivados** — removem-se e calculam-se (§4.2), mantendo a percentagem como
   *snapshot*.

## 1. ESTADO DAS DECISÕES

### 1.1 Fechado nesta ronda (não precisa de mais resposta)

| ID          | Decisão do cliente                                                                                                                                                                                         |
| :---------- | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **C-01**    | Trocar `pendente_aceitacao_funcionarios` → **`pendente_alocacao_funcionarios`** e `totalmente_aceite_funcionarios` → **`totalmente_alocado_funcionarios`**. **`alocacao/alocado` é o verbo dominante** (RV |
|             | e efetivos ficam alocados). Rever o resto do enum.                                                                                                                                                         |
| **C-03**    | **Remover** `valor_recibo_verde_funcionario` e `valor_recibo_verde_plataforma`. Manter `preco_praticado` + `percentagem_funcionario_aplicada` e **calcular na leitura**.                                   |
| **C-04**    | Rename `categoria_profissional` → `categoria_servico`, **tabela + JOINs** (~8 ficheiros). **Os nomes de chave no mapper/JS mantêm-se** (`categoryId`, `categoryName`).                                     |
| **C-05**    | A Área Cliente substitui **2** páginas (Perfil + Histórico). **Staff deixa de ter página de perfil** — passa a ser gerido em **RH**. ➕ coluna de foto em `utilizador`; ➕ componente/utilizador de        |
|             | **crop em `modules/common`**; upload para **`uploads/users/<id>.{ext}` na raiz do projeto** (estrutura e segurança em **§4.6**); **SVG proibido**.                                                         |
| **C-06**    | **Editor de agendamentos é um fluxo completo** (não modal). Âmbito: **não** muda local; **sim** pessoas (add/remove/editar nome + serviços); **sim** morada (add/alterar); **não** muda telemóvel/OTP.     |
|             | **Remover o OTP** do fluxo de agendamento.                                                                                                                                                                 |
| **C-07**    | ➕ **tabela `notificacao`** com «marcar como lida»; a lógica vive no `AlertService` (com endpoints/acessos revistos).                                                                                      |
| **C-08**    | Página de Serviços: acesso **só a funcionários RV + gestores**. RV **aceita/descarta** os seus; gestor **aloca/remove/troca** efetivos. Assim **RV e efetivos** participam em ambulatório.                 |
| **C-09**    | A **% é por funcionário** (**não há «global»**); os **defaults são por tipo de contrato** (efetivo 0 % · RV 70 %), aplicados na **criação/registo** e editáveis pelo gestor. Ver **§4.2 · §7.6**.          |
| **C-10**    | RV: **sem salário base** (`0`), % substancial do lucro. Efetivo: **salário base** + % por serviço **default 0**, configurável.                                                                             |
| **C-11**    | «Terá sempre tempo» = **o cliente pode editar o agendamento até ele entrar numa rota confirmada**; nesse momento recebe o aviso/lembrete.                                                                  |
| **C-13**    | O corte temporal passa a ser **antes da CONFIRMAÇÃO** (não antes da execução). Depois de confirmado e passada a hora, gera-se **alerta ao funcionário** para fechar o estado final.                        |
| **C-14**    | Ao recusar/cancelar por logística, o cliente recebe **novo lembrete** a explicar.                                                                                                                          |
| **6.1**     | Auto-cancelamento passa a **auto-recusa** (não foi o cliente). A staff também **recusa**, não cancela.                                                                                                     |
| **G-01**    | O «Passo 6» era na verdade o **Passo 4 (Preferência de Profissional)** da criação em loja → **não faz sentido, remover**.                                                                                  |
| **G-02**    | RV/efetivo mantêm-se como **tipos de contrato**; ➕ forma de **detetar o tipo a partir da sessão**, organizando os métodos com as mesmas convenções.                                                       |
| **G-03**    | Os **efetivos são redirecionados para a Agenda**; a **Agenda é a página principal de todos os funcionários** (RV e efetivos).                                                                              |
| **G-06**    | **Sim, implementar a regra das 24 h nesta fase.**                                                                                                                                                          |
| **G-07**    | **Sim, criar skeletons customizados** muito semelhantes ao conteúdo que substituem.                                                                                                                        |
| **Q-01**    | **`cropper.js` v1.6.3 fornecido** em `modules/common/lib/cropper` (+ `cropper.min.css`).                                                                                                                   |
| **Q-05**    | **`sweetalert.js` fornecido** em `modules/common/lib/sweetalert`.                                                                                                                                          |
| **Q-06**    | **Não apagar** agendamentos dos **65 clientes reais** nem de quem alimenta gráficos/contabilidade — **só** os gerados em testes.                                                                           |
| **Q-13**    | **Sim**, criar skeletons customizados.                                                                                                                                                                     |
| **Q-15**    | **10 cards por secção** + botão «mostrar mais» que navega para a página respetiva.                                                                                                                         |
| **§9**      | **Manter** `team.php`, `employee.validator.js`, `greenReceipts.js` (revisto em §12).                                                                                                                       |
| **IMPORT.** | Incluir: **24 h + cancelamento pelo cliente + lembrete com alternativas**; **re-avaliação dinâmica dos slots**; **agendador de alertas fiscais**. **Revogar**: **multicidades**.                           |

### 1.2 Ainda em aberto (precisa de ti — ver §14)

| ID       | Falta                                                                                       |
| :------- | :------------------------------------------------------------------------------------------ |
| **D-01** | `C-02` — pediste explicação; **explico em §4.4** e proponho a regra. Falta o teu «sim/não». |
| **D-02** | `C-12` — propus reinterpretar a página de Comissões; **preciso de confirmação** (§6).       |
| **D-03** | `G-04`/`Q-12` — lista final de **tipos de lembrete do cliente** (**§14 D-03**).             |
| **D-04** | `C-06` — confirmar **remoção total do OTP** e o **redesenho da Área Cliente** (§14).        |
| **D-05** | **`Q-14`** — explico o que é o *seed* (§14) e proponho o gerador.                           |
| **D-06** | **Novas dúvidas** `D7.1…D7.8` (§14) — sinal/90 %, UI financeira, etc.                       |

> **Nota metodológica:** nesta v2 mantive **as mesmas convenções de IDs** (`C-nn` conflito · `G-nn` gap ·
> `Q-nn` dúvida · `F-nn` fase · `D-nn` dúvida do relatório de reconciliação · `M-nn` melhoria de BD), para os
> IDs das v1 continuarem a abrir com `git ref-open`.

## 2. MÉTODO E EVIDÊNCIA

Tudo sai de ficheiro lido ou comando executado (caminho + linha nas tabelas). Varredura a `app/**`,
`modules/**`, `DataBase.sql` e `_dev/docs/spec/**`; consultas `SELECT` à BD `secade_beauty` para valores
reais (categorias, contagens). Ficheiros temporários de análise removidos no fim.

## 3. CRUZAMENTO POR ÁREA (inclui os 7 itens novos desta ronda)

### 3.1 Itens NOVOS verificados nesta ronda

| Ponto pedido                                           | Verificação (evidência)                                                                                                      | Veredicto                        |
| :----------------------------------------------------- | :--------------------------------------------------------------------------------------------------------------------------- | :------------------------------- |
| **Imagens SVG repetidas** em `modules/common/img`      | **15 pares duplicados por MD5** — cada ficheiro existe na raiz **e** em `about-images/skeletons/`: `about-commitment`,       | ✅ Confirmado (ver **§12.1**)    |
|                                                        | `about-mission`, `about-store-1…6`, `about-story`, `about-team`, `overview-acompanhamento`, `overview-carrinha`,             |                                  |
|                                                        | `overview-catalogo`, `overview-loja`. **Nenhum é referenciado** (a página Acerca usa os `.png` de `about-images/`; os        |                                  |
|                                                        | `skeletons/` não são usados em lado nenhum). `sb-logo.svg`/`sb-logo-primary.svg` e `sb-title.svg`/`sb-title-primary.svg` têm |                                  |
|                                                        | hashes **diferentes** → **não** são duplicados.                                                                              |                                  |
| **Filtro de categoria não funciona em «cabeleireiro»** | **Causa raiz:** a BD tem `Cabelereiro` (sem o **i** — `DataBase.sql` L180) e o slug gerado é **`cabelereiro`**, mas o        | ✅ Bug confirmado                |
|                                                        | URL/link/tests usam **`cabeleireiro`** (com **i**). `services.js` L130 compara slug↔URL e falha.                             |                                  |
| `serviceCategories.js` — botão «Agendar»               | L26: `<a ... href=".../agendar">agendar</a>` → deve ser **«Ver Serviços»** + `/servicos/:categoria`.                         | ✅ A alterar                     |
| Botão «Detalhes» desnormalizado                        | `services.js` L97: `btn btn-sm btn-outline-primary flex-fill` (sem `extended-border`), ao lado do «Agendar» que é            | ✅ A normalizar                  |
|                                                        | `btn btn-sm btn-primary extended-border` (L98) → **dimensões desiguais**.                                                    |                                  |
| **Menções técnicas visíveis** (pedido desta ronda)     | A nota do painel é a `DashboardService::accountingState()` (L78-84): «…entram com o módulo de contabilidade (**Fase 6.2**).  | ⚠️ **Auditar todas** (§3.8)      |
|                                                        | Até lá este bloco fica vazio…» — é **renderizada** por `renderAccounting` (`dashboard.js` L114-124). Outras: «Simulador de   |                                  |
|                                                        | recibos verdes» (`services.js` L108, `greenReceipts.php` L10/18), «Plataforma» (`services.js` L58, `greenReceipts.js`        |                                  |
|                                                        | L16/58, `commissions.php` L53), «Recibo verde simulado» (`appointments.js` L185, `services.js` L188), «Categoria (visual)»   |                                  |
|                                                        | (`services.php` L47), «RV simulado» (`appointments.js` L185), «(simulado)» em `bookingSuccess.php` L39, `bookingWizard.php`  |                                  |
|                                                        | L102, `servicesOverview.php` L25, `bookingWizard.js` L514.                                                                   |                                  |
| **Legendas dos gráficos com enums crus**               | `dashboard.js` L133-134: `renderDoughnut` passa `chart.labels` **sem formatação** → aparecem                                 | ✅ **Falta o `humanize`** (§3.8) |
|                                                        | `pendente_aceitacao_funcionarios`, `executado`, `totalmente_alocado_funcionarios`. Só o gráfico fiscal (`renderBar`          |                                  |
|                                                        | L136-138) faz `replace(/_/g, " ")`.                                                                                          |                                  |
| **Imagem placeholder no card de serviço**              | `services.js::card()` (L78-103) renderiza **badges + título + descrição + lista + botões** — **não tem imagem nenhuma**. O   | ✅ Novo (F3) — ver **§3.4.4**    |
|                                                        | único sítio com foto por serviço é a tabela `servico_foto` (**vazia e sem uso**). Existe padrão a reutilizar:                |                                  |
|                                                        | `about-images/skeletons/*.svg` (800×600, moldura tracejada dourada + «Substituir por fotografia real»).                      |                                  |

### 3.2 Base de Dados

| Ponto pedido                                     | Estado atual (evidência)                                                          | Veredicto / decisão                                                           |
| :----------------------------------------------- | :-------------------------------------------------------------------------------- | :---------------------------------------------------------------------------- |
| `agendamento.estado_reserva` — rever o enum      | 8 valores (`DataBase.sql` L31). O cliente nomeou os 2 primeiros.                  | ⚠️ **C-01 fechado**; enum completo proposto em **§4.1**                       |
| `agendamento_servico` — `#percentageFields`      | 3 colunas (L95–97); as 2 de valor são **derivadas** (`ROUND(...)` no `accept()`). | ✅ **C-03 fechado** — remover as 2; manter `percentagem_funcionario_aplicada` |
|                                                  |                                                                                   | como snapshot                                                                 |
| `categoria_profissional` → `categoria_servico`   | FK em `servico.categoria_id`; JOINs em ~8 ficheiros; **BD tem `Cabelereiro`**     | ✅ **C-04 fechado**; lacunas do rename em **§4.3**                            |
|                                                  | (sem i).                                                                          |                                                                               |
| ➕ **`funcionario.percentagem_comissao`** (nova) | Não existe (L520-524: só `tipo_contrato`, `salario_base`, `cc`, `ativo`).         | ⚠️ Falta ➕ (F1)                                                              |
| ➕ **`utilizador.foto`** (nova)                  | Não existe (L763-773). Não há upload de imagens em nenhum sítio.                  | ⚠️ Falta ➕ (F1)                                                              |
| ➕ **`notificacao`** (nova)                      | Não existe.                                                                       | ⚠️ Falta ➕ (F1)                                                              |
| `funcionario.salario_base` para RV               | `NOT NULL`; `EmployeeService::validateInput` L23 **exige sempre**;                | ✅ **C-10 fechado** — default `0` + validação só p/ efetivos                  |
|                                                  | `EmployeeRepository::create` não distingue contrato.                              |                                                                               |

### 3.3 App (server side)

| Ponto pedido                                    | Estado atual (evidência)                                                          | Veredicto                                                                          |
| :---------------------------------------------- | :-------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------- |
| `config.php` — `team` deixa de ser fallback     | `SITE_STATS_FALLBACK["team"]=6` (L53) + `SITE_STATS_DOCUMENTAL=["team"]` (L65);   | ✅ Confirmado — contar **funcionários+gestores** na BD e limpar a lista documental |
|                                                 | comentário L60-63 explica                                                         |                                                                                    |
| Detetar **tipo de contrato** a partir da sessão | `Session` tem `isManager()`/`isEmployee()`/`isCustomer()`/`userId()`; **não tem** | ⚠️ **G-02 fechado** — ➕ método no `Session` (F2)                                  |
|                                                 | `employeeContractType()`                                                          |                                                                                    |
| **Agendador de alertas fiscais**                | `FiscalService::generateAlerts()` é `private` (L192) e corre **on-demand** dentro | ⚠️ Novo — ver **§9.3**                                                             |
|                                                 | do calendário fiscal.                                                             |                                                                                    |
| **Reconciliador de estados**                    | **Não existe** (ver `relatorio_reconciliacao-estados.md`).                        | ⚠️ Novo — ver **§8**                                                               |

### 3.4 Site principal (`modules/main`)

| Ponto pedido                                               | Estado atual (evidência)                                                                                 | Veredicto                                       |
| :--------------------------------------------------------- | :------------------------------------------------------------------------------------------------------- | :---------------------------------------------- |
| Navbar: «Agendar» não ativo em `/agendar`                  | `_navigation.php` L11 `page="appointments"`; `booking.php` L10 `$currentPage="agendar"` → nunca coincide | ✅ Bug confirmado                               |
| Navbar: «Agendar» ativo em `/agendamentos`                 | `appointments.php` L10 `$currentPage="appointments"` → coincide por acidente                             | ✅ Mesma causa                                  |
| Testemunhos com skeleton errado                            | `testimonial.js` L81 (`.jq-skeleton-service-category-card`)                                              | ✅ Bug confirmado                               |
| Login sem Enter                                            | `login.php` L48 botão `type="button"`; `<form>` sem submit                                               | ✅ Bug confirmado                               |
| Aviso Terça–Sábado no passo datetime da carrinha           | `bookingWizard.php` ~L241 (passo partilhado)                                                             | ✅ Bug confirmado — mostrar só em `loja_fisica` |
| Slots desalinhados em wrap                                 | `.slots-grid`/`.slot-btn` em `style.css` L1020                                                           | ✅ Ajuste CSS                                   |
| **Botão «Ver Serviços»** + `/servicos/:categoria`          | `serviceCategories.js` L26                                                                               | ✅ A alterar                                    |
| **`Detalhes` com `extended-border`**                       | `services.js` L97-98                                                                                     | ✅ A normalizar                                 |
| **Filtro de categoria (bug do i)**                         | Ver §3.1                                                                                                 | ✅ Bug confirmado                               |
| **Remover passo «Preferência de Profissional»** (loja)     | `bookingWizard.php` data-step `professional` + JS `SECTIONS.loja_fisica` L23                             | ✅ **G-01 fechado**                             |
| **Remover o OTP** do agendamento                           | `booking-otp-request` + passo `otp` (`SECTIONS` L24, `OTPService`).                                      | ⚠️ **C-06/D-04** — pendente (§14)               |
| **`serviceCategories` só na Home; `/servicos` = catálogo** | Hoje `/servicos` → `serviceCategories.php` (grelha de categorias) e `/servicos/:category` →              | ✅ Novo (F3) — ver **§3.4.1**                   |
|                                                            | `services.php` (catálogo com filtros). O componente `components/serviceCategories.php` é incluído        |                                                 |
|                                                            | **na Home** (`home.php` L14) **e** na página `serviceCategories.php` L18.                                |                                                 |
| **Hero da Home com altura da janela**                      | `components/hero.php` é incluído na Home; usa `hero-bg`/`hero-slider-*` (`modules/common/img/`) e a      | ✅ Novo (F3) — ver **§3.4.3**                   |
|                                                            | altura vem do `style.css`. Deve passar a ocupar **no máximo a altura do viewport** (`100vh`/`100dvh`,    |                                                 |
|                                                            | menos a navbar).                                                                                         |                                                 |

#### 3.4.1 Reorganização do catálogo (requisito desta ronda)

**Pedido:** o componente `serviceCategories` (grelha de 3 categorias) fica **só na Home**; os seus botões
navegam para a **página de catálogo** (`/servicos`); e é o **catálogo** que passa a ser servido em `/servicos`
**e** em `/servicos/:categoria` (com o filtro de categoria aplicado).

**Estado atual vs. alvo:**

| Rota                  | Hoje (página)                                    | Alvo                                               |
| :-------------------- | :----------------------------------------------- | :------------------------------------------------- |
| `/` (Home)            | `home.php` — inclui `serviceCategories` (grelha) | **mantém** a grelha, com botões **«Ver Serviços»** |
| `/servicos`           | `serviceCategories.php` (**grelha**)             | **`services.php`** (catálogo + filtros)            |
| `/servicos/:category` | `services.php` (catálogo)                        | **mantém** (catálogo, filtro aplicado)             |
| —                     | `serviceCategories.php` (página)                 | **órfã** → remover (§12)                           |

**Alterações concretas:**

1. `index.php` L18: `"servicos" => .../services.php` (antes apontava para `serviceCategories.php`).
2. `index.php` L19: `"servicos/:category"` mantém-se (`services.php`).
3. `serviceCategories.js` L26: botão passa de `<a href=".../agendar">agendar</a>` para
   **«Ver Serviços»** → `${BASE_URL}/servicos/${slugifiedName}` (usa o slug que **já** calcula — L19).
4. `serviceCategories.php` (página) → **removida**; o componente `components/serviceCategories.php` fica só na Home.
5. `services.js` L130: corrigir o desencontro de slug (**bug do `cabeleireiro`**, §3.1) — ver **§3.4.2**.
6. O botão «Agendar» por serviço (card do catálogo, `services.js` L98) **mantém-se** — é o caminho natural para
   marcar depois de escolher o serviço.

#### 3.4.2 Bug do filtro `cabeleireiro` — duas vias de resolução (escolher)

O serviço `services.js::resolveInitialCategory()` (L126-132) compara o **slug da categoria na BD** com o
**`:category` do URL**. A BD tem `Cabelereiro` (sem «i») → slug `cabelereiro`; o URL usado pelos links/tests
é `cabeleireiro` (com «i») → **nunca casa**.

| Via   | O que muda                                                                             | Prós / Contras                                                                                                     |
| :---- | :------------------------------------------------------------------------------------- | :----------------------------------------------------------------------------------------------------------------- |
| **A** | **Corrigir a BD** (`Cabelereiro` → `Cabeleireiro`) + regenerar `DataBase.sql`/`_clean` | ✅ Correto ortograficamente; ⚠️ mexe em dados e **qualquer bookmark** com o slug antigo deixa de casar (tolerável) |
| **B** | **Alias/tolerância no front** (mapa de sinónimos `cabeleireiro → cabelereiro`)         | ✅ Não mexe na BD; ❌ perpetua o erro ortográfico e cria exceção de código                                         |

> **Recomendação: via A** — o nome está **errado na origem** (`Cabelereiro` não existe em português) e a BD é
> a fonte de verdade. Encaixa na **F1** (que já vai mexer na tabela das categorias para o rename `C-04`), pelo
> que o custo marginal é nulo. ⚠️ Implica atualizar os **tests** que usam `cabelereiro`
> (`_dev/tests/http_test.php` L107-108, `asset_test.php` L85).

#### 3.4.3 Hero da Home — altura máxima = viewport (requisito desta ronda)

**Pedido:** o *page-hero* da Home deve ocupar **no máximo a altura da janela do browser**.

**Proposta técnica:**

| Aspeto            | Decisão proposta                                                                                                                                                        |
| :---------------- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Unidade           | **`100dvh`** (dynamic viewport height) com *fallback* `100vh` — resolve a barra do browser e o teclado em mobile, onde `100vh` «salta».                                 |
| Compensação       | Descontar a altura da **navbar fixa** (a navbar é `sticky-top`, existe em ambas as páginas): altura = `calc(100dvh - <altura-navbar>)` ou usar `min-height` no wrapper. |
| Comportamento     | `min-height: 100dvh` (não `height`) — se o conteúdo for maior, **cresce**; nunca corta texto.                                                                           |
| Alvo da alteração | `modules/common/css/style.css` (classe do hero) + confirmar em `modules/main/components/hero.php` que não há altura fixa inline.                                        |
| Alcance           | Aplicar também ao **page-header** das páginas internas (evitar saltos ao navegar).                                                                                      |

> ⚠️ **Nota:** o `style.css` já tem um `min-height: 100vh` (L20) num seletor — é preciso **identificar a que
> elemento pertence** antes de mexer, para não afetar outras páginas (o gate do §13.2 verifica-o).

#### 3.4.4 Imagem (placeholder SVG) no topo do card de serviço — requisito desta ronda

**Pedido:** cada card do **catálogo de serviços** passa a ter uma **imagem no topo**, com um **`placeholder .svg`**
feito no mesmo molde do que foi usado nos componentes «Acerca».

**Estado atual (evidência):**

| Elemento                               | Estado                                                                                                                                                        |
| :------------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `services.js::card()` (L78-103)        | **Sem `<img>`** — só badges, título, descrição, lista de dados e os 2 botões                                                                                  |
| `modules/main/components/services.php` | Container da grelha (`services-container`) — sem imagens                                                                                                      |
| `servico_foto` (BD)                    | **Existe, vazia e sem uso** — colunas `servico_id` · `url_foto` · `destaque` · `ordem_exibicao`; **é o destino**: as fotos reais entram **nesta fase** (F3.1) |
| Padrão de placeholder                  | `about-images/skeletons/*.svg` — 800×600, fundo `#f8f5f2`, moldura tracejada `#c9a227`, texto «Substituir por fotografia real (800 × 600)»                    |
| Imagens de categoria existentes        | `cabelereiro.png` · `barbearia.png` · `estetica.png` (+ `manicure`, `massage`, `pedicure`, `skin-care`) em `modules/common/img/`                              |

**Proposta (desenho):**

| Aspeto                 | Decisão proposta                                                                                                                                                                                |
| :--------------------- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Quantos ficheiros**  | **3 placeholders**, um por **categoria** (`servico-cabeleireiro.svg`, `servico-barbearia.svg`, `servico-estetica.svg`) — dá variedade visual **sem criar 35 ficheiros** nem fingir fotos reais. |
| **Local**              | **`modules/common/img/service-images/skeletons/`** — a pasta-mãe **já existe** (`service-images/`, com 3 fotos reais `service-1..3.jpg`); os `.svg` vão para a subpasta `skeletons/`,           |
|                        | **espelhando** `about-images/skeletons/`.                                                                                                                                                       |
| **Molde**              | **Exatamente o mesmo** dos `about-images/skeletons/*.svg`: 800×600, `#f8f5f2`, moldura tracejada `#c9a227`, título + «Substituir por fotografia real» + nome do ficheiro.                       |
| **Render no card**     | `<img>` no topo do `card-body` (ou `card-img-top`), `class="card-img-top object-fit-cover"` com `ratio` fixo (ex.: `ratio-4x3`) para **todos os cards terem a mesma altura** independentemente  |
|                        | da imagem.                                                                                                                                                                                      |
| **Ligação ao serviço** | **Placeholder agora** (mapa `categoria → placeholders.svg` no JS); **foto real nesta fase** (F3.1): o gestor carrega e grava em **`servico_foto`** (`destaque` = a do card).                    |
| **Não quebrar**        | Fallback: se a categoria não casar, usa-se um placeholder genérico (`servico-generico.svg`) — **nunca** um `src` vazio.                                                                         |

**Nota de coerência com o resto:** usar o **mesmo molde** dos `about` faz a plataforma parecer **consistente**
enquanto não há fotos reais — e deixa óbvio para quem entrega o projeto **que imagem falta e onde a pôr**.

> ⚠️ **Nota de Git:** estes `.svg` são **ativos de produto** (não dados) → **versionam-se** em `modules/common/img/service-images/skeletons/`.
> ⚠️ **Nota de segurança (coerente com §4.6):** estes SVG são **nossos** (feitos à mão, sem `<script>`); a
> **proibição de SVG aplica-se só ao upload do utilizador**, não a ativos do projeto.

### 3.5 Área Cliente (nova) — decisões fechadas

| Ponto pedido                                               | Estado atual (evidência)                                                     | Veredicto                                                     |
| :--------------------------------------------------------- | :--------------------------------------------------------------------------- | :------------------------------------------------------------ |
| Substitui **Perfil + Histórico** (2 páginas)               | `profile.php` (`$currentPage="profile"`) e `appointments.php`                | ✅ **C-05 fechado** — **staff perde a página de perfil**      |
|                                                            | (`"appointments"`)                                                           |                                                               |
| Menu hamburguer → Área Cliente (scroll à secção)           | `menuUser.php` L66 aponta `/agendamentos`; link «Perfil» **comentado**       | ✅ A alterar                                                  |
|                                                            | (L45-51)                                                                     |                                                               |
| ➕ **Foto** (crop quadrado pelo rosto)                     | Avatar fixo `testimonial-1.jpg` em `menuUser` L40, `menuUserBo` L27,         | ✅ **Q-01** — `cropper.js` v1.6.3 fornecido; ➕ componente em |
|                                                            | `profile.php` L44                                                            | `modules/common`                                              |
| Upload **`uploads/users/<id>.{ext}`** (raiz) + **sem SVG** | `uploads/` **não existe**; ver **§4.6**                                      | ✅ A criar (F1/F9)                                            |
| Editores em **modal bootstrap**                            | Molde existente: `suppliers.php` `#supplierFormModal` (`modal fade` + `Form` | ✅ Base existe (reutilizar)                                   |
|                                                            | de `form.utils.js`)                                                          |                                                               |
| **Editar agendamento** (fluxo completo)                    | **Não existe** endpoint/Service de edição.                                   | ✅ **C-06 fechado** (âmbito em §4.5)                          |
| Alterar serviços → **novos lembretes**                     | `AlertService` é derivado; não há notificação por evento.                    | ⚠️ Depende de **C-07** (`notificacao`)                        |
| Secção de **Alertas/Lembretes do cliente**                 | Não existe para clientes (`AlertService` é `gestor`/`funcionario`)           | ✅ **C-07 fechado** — ➕ `notificacao` + grupos do cliente    |

### 3.6 Backoffice — Alocação de serviços (`#servCarrinha`)

| Ponto pedido                                                | Estado atual (evidência)                                                | Veredicto                                                                 |
| :---------------------------------------------------------- | :---------------------------------------------------------------------- | :------------------------------------------------------------------------ |
| Gestor **aloca** em vez de aceitar                          | `services.php` L6 deixa `funcionario`+`gestor`, mas `ServiceController` | ✅ **C-08 fechado** — API passa a `["gestor","funcionario"]` com ramo por |
|                                                             | L20-40 exige **`["funcionario"]`** → gestor **falha**                   | perfil                                                                    |
| `#1` Bloqueio (mesma cidade/hora)                           | **Não existe.** `assertNoWindowConflict` (L198-217) corre na            | ⚠️ Reformular — ver **§4.4**                                              |
|                                                             | **consolidação** e compara **qualquer** ambulatório, **sem cidade**     |                                                                           |
| `#2` Funcionário em 2 cidades no mesmo dia                  | **Não existe** qualquer regra                                           | ⚠️ Novo (F4)                                                              |
| `disabled` condicional                                      | Não existe                                                              | ⚠️ Novo (F4)                                                              |
| Texto «Simulador de recibos verdes: xx% / yy%»              | `services.js` L108                                                      | ✅ A reformular (contextual por perfil)                                   |
| Bloqueio de janela na **confirmação** (não na consolidação) | Hoje bloqueia ao consolidar (`consolidateIfComplete`)                   | ✅ **C-02/D-01** — ver §4.4                                               |
| Tags «Aceite» por perfil                                    | `services.js` L50 badge fixo                                            | ✅ Normalizar (**alocado/aceite**)                                        |
| «A receber» / «Plataforma»                                  | `services.js` L54-59                                                    | ✅ Renomear por perfil                                                    |
| Mostrar `nome_pessoa` nos cartões                           | `services.js` L14/41                                                    | ✅ Remover destes cartões                                                 |
| Sticky cabeçalho/filtros                                    | `.bo-filters` sem `sticky-top`                                          | ⚠️ CSS (F4)                                                               |
| `col-lg-12 col-xl-6`                                        | `services.php` L34/59 = `col-lg-7`/`col-lg-5`                           | ⚠️ CSS (F4)                                                               |
| Ecrã só p/ **gestor + RV**                                  | —                                                                       | ✅ **C-08/G-03**: RV vê; **efetivo é redirecionado para a Agenda**        |

### 3.7 Backoffice — Avisos / Lembretes

| Ponto pedido                                 | Estado atual (evidência)                                                                               | Veredicto                                        |
| :------------------------------------------- | :----------------------------------------------------------------------------------------------------- | :----------------------------------------------- |
| Gestor: fiscais (incl. atraso) **realçados** | `AlertService::fiscalGroup()` + `overdueGroup()` (L90-132) — grupos separados, **sem realce visual**   | 🟡 Falta realce (F5)                             |
| Gestor: **Serviços por alocar**              | `pendingServicesGroup()` (L134) com rótulo «Serviços por aceitar»                                      | 🟡 Ajustar rótulo + alvo (F5)                    |
| Gestor: Rotas por decidir                    | `routesGroup()` (L157)                                                                                 | ✅ Manter                                        |
| RV: «Rotas» **não** deve aparecer            | `buildGroups()` L79-80 injeta o grupo **sempre** (count 0 → card aparece)                              | ✅ Bug confirmado (F5)                           |
| Efetivo: **alocações planeadas**             | **Não existe** (depende de F4)                                                                         | ⚠️ Novo (F5, após F4)                            |
| `#limiteCards` (10) + «mostrar mais»         | `AlertService` devolve **todos** os itens                                                              | ✅ **Q-15 fechado** (F5)                         |
| `#rotaDinamica` (scroll + highlight)         | Convenção: `window.APP_PARAMS` (`header.php` L40, `boHeader.php` L38) + `generalUtils.scrollToElement` | ✅ Há base; falta **utilitário partilhado** (F2) |
|                                              | (`general.utils.js` L36)                                                                               |                                                  |

### 3.8 Transversais (ambos os sites)

| Ponto pedido                                          | Estado atual (evidência)                                                                                             | Veredicto                                 |
| :---------------------------------------------------- | :------------------------------------------------------------------------------------------------------------------- | :---------------------------------------- |
| **Swal** a substituir `alert`/`confirm`/`prompt`      | Nativo em `services.js` L205, `alerts.js` L58, `suppliers.js`, `routes.js`, `appointments.js` (main/bo), …           | ✅ **Q-05 fechado** (F2)                  |
|                                                       | **lib fornecida** (`modules/common/lib/sweetalert/sweetalert.js`)                                                    |                                           |
| ➕ **`generalUtils.humanize()`** (pedido desta ronda) | **Não existe.** Usos manuais: `dashboard.js` L137 `replace(/_/g, " ")`; `alerts.js` L97 `replace("_", " ")`          | ✅ **A criar** (F2) — ver **§3.10**       |
| `(visual)` → vazio                                    | `services.php` L47 «Categoria (visual)»; `ServiceAcceptanceService` L14 «filtros visuais»                            | ✅ A limpar                               |
| «Plataforma» → «Empresa»                              | `services.js` L58; `greenReceipts.js` L16/58; `greenReceipts.php` L44; `commissions.php` L53                         | ✅ A renomear                             |
| Remover o conceito de «Simulador»                     | `greenReceipts.php` L10/18; `GreenReceiptService` docblock L8; `services.js` L108/185/188; `appointments.js` L185    | ✅ A remover (**C-09**)                   |
| Textos na 3.ª pessoa / genéricos                      | `bookingWizard.php` L304 «(com aviso ao cliente)»; `bookingSuccess.php` L39 «(notificação simulada)»                 | ✅ A revisar                              |
| Sidebar «Recibos Verdes» **sem icon**                 | `boSidebar.php` L36 usa `bi-cash-coin` — **0 ocorrências** no CSS local dos `bootstrap-icons`                        | ✅ Causa raiz; trocar por `bi-cash-stack` |
| Páginas sem altura mínima                             | `style.css` L20 `min-height:100vh` num só seletor; auditar `login`, `registo`, `servicos` + **backoffice**           | ⚠️ Auditar (F3)                           |
| **`debugger;` esquecido**                             | `modules/backoffice/js/components/dashboard.js` **L154** — instrução de depuração em produção                        | ✅ Remover (F3)                           |
| **Escape de HTML (validação geral)**                  | `generalUtils.escapeHtml` existe (`general.utils.js`); há **strings geradas com interpolação** — auditar todas (§13) | ⚠️ Auditar (F11)                          |

### 3.9 Dados (*seed*)

| Ponto pedido                              | Estado atual (evidência)                                                                       | Veredicto                                                    |
| :---------------------------------------- | :--------------------------------------------------------------------------------------------- | :----------------------------------------------------------- |
| Apagar agendamentos/serviços de **teste** | `DataBase.sql` tem 10 agendamentos + 94 linhas de serviço; **65 clientes reais** (ids 100-164) | ✅ **Q-06 fechado** — só os de teste; **preservar** os reais |
| Cenários realistas por estado/género      | Não há coluna de género; o nome próprio é o indicador                                          | ⚠️ **Q-14/D-05** — proposta em §14                           |

### 3.10 Auditoria de textos técnicos VISÍVEIS (pedido desta ronda)

> **Regra:** texto que o utilizador vê **nunca** menciona Fases, `§`, `RF`/`RN`, «MVP», nomes de ficheiros,
> `Controller`/`Service`/`Repository`, «endpoint» ou estados crus de `ENUM`.

| Onde                                                                | Texto a corrigir                                                      | Ação                                                                  |
| :------------------------------------------------------------------ | :-------------------------------------------------------------------- | :-------------------------------------------------------------------- |
| `DashboardService::accountingState()` L82                           | «…entram com o módulo de contabilidade (**Fase 6.2**). Até lá…» (nota | Reescrever sem «Fase» (ex.: «Estará disponível quando a contabilidade |
|                                                                     | do painel)                                                            | for importada.»)                                                      |
| `services.js` L108                                                  | «**Simulador de** recibos verdes: x% / y%»                            | «Repartição do serviço: x% / y%» (contextual)                         |
| `greenReceipts.php` L10/18 + `greenReceipts.js`                     | «Simulador de Recibos Verdes»                                         | **«Configuração de Percentagens»** (C-09)                             |
| `services.js` L58, `greenReceipts.js` L16/58, `commissions.php` L53 | «Plataforma»                                                          | **«Empresa»**                                                         |
| `appointments.js` L185, `services.js` L188                          | «RV simulado: …» / «Recibo verde simulado: …»                         | Texto neutro («Repartição: X € / Y €»)                                |
| `services.php` L47                                                  | «Categoria (visual)»                                                  | «Categoria»                                                           |
| `bookingWizard.php` L102, `bookingSuccess.php` L39,                 | «(simulada)»/«(simulado)»                                             | Rever caso a caso (a simulação é requisito académico → texto neutro)  |
| `servicesOverview.php` L25, `bookingWizard.js` L514                 |                                                                       |                                                                       |
| `dashboard.js` L133-134                                             | **Enums crus** como legendas de gráfico                               | `humanize()` (F2)                                                     |
| `alerts.js` L97                                                     | `replace("_", " ")` (só a 1.ª ocorrência)                             | `humanize()` (F2)                                                     |

## 4. MODELO FINAL PROPOSTO

### 4.1 `agendamento.estado_reserva` — enum revisto (C-01)

O cliente alterou os 2 primeiros valores. Proponho o **conjunto completo revisto** — a decisão fechada é o verbo
**alocação**; o resto é ajuste de coerência (**precisa do teu «sim»** — ver **§14 D-04**):

| Estado atual (hoje)                 | Proposta v2                                         | Porquê                                                                                |
| :---------------------------------- | :-------------------------------------------------- | :------------------------------------------------------------------------------------ |
| `pendente_aceitacao_funcionarios`   | **`pendente_alocacao`**                             | Verbo dominante; `_funcionarios` é ruído (o canal já está em `local_prestacao`)       |
| `pendente_validacao_logistica_loja` | `pendente_validacao_logistica_loja` (**manter**)    | Continua exato                                                                        |
| `totalmente_aceite_funcionarios`    | **`totalmente_alocado`**                            | C-01                                                                                  |
| `confirmado`                        | `confirmado` (**manter**)                           | É o gatilho do aviso/lembrete ao cliente (C-11/C-13)                                  |
| `recusado`                          | **`recusado`** — passa a ser **o** estado de recusa | **6.1**: auto-recusa **e** recusa da staff usam este estado                           |
| `cancelado`                         | **`cancelado`** — reservado ao **cliente**          | **6.1**: deixa de ser usado pela staff                                                |
| `executado`                         | `executado` (**manter**)                            | Momento do `ExecutionService::registerExecution`                                      |
| `concluido`                         | `concluido` (**manter**)                            | Fecho (`R2` da reconciliação)                                                         |
| —                                   | ➕ **`cancelado_terreno`**?                         | **Dúvida D7.2** (§14): o `execucao_agendamento` já tem este estado — vale promovê-lo? |

> ⚠️ **Impacto do rename:** ~40 ocorrências no código + migração de dados. **Tem de ser feito ANTES** de
> qualquer trabalho de reconciliação (§8.3) e antes da F4 (alocação).

### 4.2 Percentagens (C-03 · C-09 · C-10)

| Peça                                                      | Decisão                                                                                                                                        |
| :-------------------------------------------------------- | :--------------------------------------------------------------------------------------------------------------------------------------------- |
| `preco_praticado`                                         | **Fonte de verdade do valor do serviço** (valor líquido praticado, já com ajuste/desconto).                                                    |
| `percentagem_funcionario_aplicada`                        | **Snapshot** da % no momento da alocação — é o que garante o histórico (§11 da spec).                                                          |
| `valor_recibo_verde_funcionario`                          | **REMOVER** → calcular `ROUND(preco_praticado × pct ÷ 100, 2)` na leitura.                                                                     |
| `valor_recibo_verde_plataforma`                           | **REMOVER** → calcular `preco_praticado − valor_funcionario` na leitura.                                                                       |
| ➕ `funcionario.percentagem_comissao`                     | **NOT NULL** — **é** a percentagem do funcionário (único motor); nasce com o **default do tipo de contrato** e é editável no formulário de RH. |
| ➕ `config_percentagem_padrao` (ex-`config_recibo_verde`) | **Renomear** a tabela (`NF-01`): passa a guardar a **% padrão por tipo de contrato** (`efetivo_contratado` → 0 % · `recibo_verde` → 70 %),     |
|                                                           | **configurável no backoffice** — **não é estática**. Lida na **criação/edição** do funcionário para pré-preencher.                             |
| UI                                                        | **Uma só** percentagem introduzida; a outra é `100 − x` (resolve o `#percentageFields`).                                                       |
| Récibos verdes (RV)                                       | `salario_base = 0`; a % **nasce a 70 %** (default de `config_percentagem_padrao`) e é editável.                                                |
| Efetivo                                                   | `salario_base > 0`; a % **nasce a 0 %** (default de `config_percentagem_padrao`) e é editável.                                                 |

**Resposta direta à tua pergunta:** **sim** — podes aplicar `percentagem_funcionario_aplicada` a
`preco_praticado` e obter os dois valores; o `preco_praticado` **é** o somatório (funcionário + empresa). **Não
há problema de histórico** porque a **percentagem fica gravada** na linha. O único cuidado é a **ordem do
arredondamento**: `funcionário = ROUND(preco × pct ÷ 100, 2)` e `empresa = preco − funcionário` (assim a soma
fecha sempre ao cêntimo).

### 4.3 Lacunas do rename `categoria_profissional` → `categoria_servico` (C-04) — resposta

Tens razão no processo (**rename da tabela + JOINs**). Verifiquei e **faltam 5 arestas** que não estavam
explícitas na v1 — a tua intuição estava certa, mas o rename tem mais pontas soltas do que parece:

| #   | Lacuna                                                                                                         | Onde                                                                               |
| :-- | :------------------------------------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------- |
| 1   | A **constraint FK** chama-se `fk_servico_categoria` (o **nome mantém-se**, mas a **referência** à tabela muda) | `DataBase.sql` L637                                                                |
| 2   | O **alias SQL** `cp` (= «categoria profissional») aparece nas queries → passa a `cs`                           | `CategoryRepository` L11-27 · `ServiceRepository` L11-30, L71-73                   |
| 3   | Os **dois dumps** têm de ser regenerados                                                                       | `DataBase.sql` · `DataBase_clean.sql`                                              |
| 4   | A **spec** menciona `categoria_profissional` em **5 ficheiros**                                                | `data-api.md` §17 · `operations.md` §7 · `annex.md` · `backlog.md` · `relatorio_*` |
| 5   | Os **tests** verificam a página/tabela                                                                         | `_dev/tests/*` (via `asset_test`/`http_test`)                                      |

> ✅ **Sem lacuna (confirmado):** mantém-se a **coluna** `servico.categoria_id` e as **chaves da API**
> `categoryId`/`categoryName` — como disseste, o mapper não menciona «Professional» nem «Service» e **não deve**.

### 4.4 C-02 explicado — o que existe hoje vs. o que passas a querer (D-01)

**Hoje (implementado).** Quando o **último** serviço de um agendamento de ambulatório é alocado,
`ServiceAcceptanceService::consolidateIfComplete()` (L183-192) faz duas coisas:
1. muda o agendamento para `totalmente_aceite_funcionarios` (→ `totalmente_alocado`);
2. chama `assertNoWindowConflict()` (L198-217), que **rejeita** se existir **outro** agendamento de ambulatório
   na mesma **janela temporal** — **sem olhar à cidade** (compara só data/hora + duração).

**O que está errado (são dois problemas diferentes misturados):**

| Problema                      | Descrição                                                                                                                                                        |
| :---------------------------- | :--------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **(a) Bloqueia cedo demais**  | O bloqueio acontece **na alocação**, ou seja, **antes** de a rota ser decidida. Se a rota for **recusada**, o bloqueio já estragou a agenda sem necessidade.     |
| **(b) Bloqueia largo demais** | Compara **qualquer** ambulatório, mesmo de **outra cidade** — o que proíbe dois clientes em Évora e em Reguengos à mesma hora, sem que colidam na operação real. |

**O que passas a querer (C-02/C-13):** o bloqueio só faz sentido sobre o **recurso finito**, que é a
**rota confirmada** (a carrinha está num sítio e a equipa também).

**Regra proposta (preciso do teu «sim»):**

```text
R-ALOC : alocar servico   -> valida SO "o funcionario ja esta noutra cidade no mesmo dia?"   (#2)
R-CONF : confirmar rota   -> valida "mesma cidade + janela sobreposta?"                      (#1) -> 409 se colidir
R-24H  : decidir rota     -> so com >= 24 h de antecedencia (RF-58 / RN-24)
```

Consequência direta: o cliente é **notificado** quando a rota é **confirmada** (não quando o último serviço é
alocado) — que é exatamente o que decidiste em **C-11**.

### 4.5 Âmbito do editor de agendamento (C-06)

| Passo do wizard de criação | No editor? | Nota                                             |
| :------------------------- | :--------- | :----------------------------------------------- |
| Canal (loja / carrinha)    | ❌ **não** | `local_prestacao` é imutável                     |
| Serviços + pessoas         | ✅ **sim** | add / remove / editar pessoas e os seus serviços |
| Morada (carrinha)          | ✅ **sim** | adicionar ou alterar                             |
| OTP                        | ❌ **não** | e **remover do fluxo de criação** (**D-04**)     |
| Data / hora                | ✅ **sim** | com **re-avaliação de slots** (§9.2)             |
| Profissional (loja)        | ❌ **não** | passo **removido** (G-01)                        |
| Política de sinal          | ✅ (1×)    | mantém-se informativo                            |

**Abordagem de implementação (a tua preferência = opção 1):** **modularizar o wizard atual** em sub-componentes
por contexto e injetar a lógica de «edição» por configuração. **Critério de decisão explícito:** se a
modularização fizer a complexidade disparar (mais ramos do que linhas ganhas), **cai-se para o clone** (opção 2).
A avaliação faz-se no início da **F9**, com esse limite escrito.

### 4.6 Estrutura do `uploads/` (nova raiz de ficheiros do utilizador) — C-05

**Decisão:** as fotos **não** vão para `modules/common/img` (que é **código/ativos versionados**); passam a
viver numa pasta **`uploads/` na raiz do projeto** (dados de execução, escritos pela aplicação).

**Estrutura proposta:**

```text
uploads/                        <- nova; na raiz do projeto (junto a index.php)
├── .htaccess                   <- NEGA execução de scripts nesta árvore (só serve estáticos)
├── .gitkeep                    <- mantém a pasta no repositório (ver nota de Git abaixo)
├── users/                      <- avatares por utilizador
│   └── <user-id>.{jpg|jpeg|png|webp}
└── services/                   <- fotos dos serviços (D-25/D-13 — servico_foto VOLTA a ter uso)
    ├── service-<id>.jpg        <- imagem PRINCIPAL do card (= `destaque`)
    └── service-<id>-<n>.jpg    <- restantes (carousel dos detalhes)
```

> **Migração (D-25/D-13):** as fotos já existentes em `modules/common/img/service-images/service-{id}.jpg`
> **migram** para `uploads/services/`. A tabela **`servico_foto`** passa a ser **usada** (deixa de ser morta):
> guarda `url_foto`, `destaque` e `ordem_exibicao` por serviço. O **upload** é gerido em `/gestao/catalogo`
> (F3.1). *(Isto **substitui** o `D-15`, que era «estático por id, sem upload».)*

**Porque esta forma:**

| Decisão                           | Justificação                                                                                                                       |
| :-------------------------------- | :--------------------------------------------------------------------------------------------------------------------------------- |
| **Raiz, não `modules/`**          | `modules/` é **código** (versionado, com convenções de framework). Dados escritos em runtime não pertencem lá.                     |
| **Nome = `<id>` do utilizador**   | **Elimina o path traversal** na origem: o nome **nunca** vem do utilizador → não há `../`, nem colisões, nem caracteres perigosos. |
| **Uma extensão de uma whitelist** | `jpg` · `jpeg` · `png` · `webp`. **`svg` fica de fora** (pode conter `<script>`), e também `php`/`phtml`/`phar`/`html`.            |
| **Guardar só o caminho na BD**    | `utilizador.foto` = `uploads/users/3.jpg` (**relativo**) — portável entre ambientes; o URL monta-se com `BASE_URL`.                |
| **Sobrescrever o mesmo `<id>`**   | Trocar de foto **não acumula lixo**; a versão `?v=<timestamp>` na URL resolve o *cache* do browser.                                |

**Como fica servido (sem tocar no `.htaccess` da raiz):** a regra atual já serve **ficheiros existentes**
(`RewriteCond %{REQUEST_FILENAME} !-f`) — `/uploads/users/3.jpg` é servido diretamente. Nada muda aí.

**Segurança — o que o fluxo de upload tem de fazer (defesa em profundidade):**

| #   | Medida                                                                                                                                                               |
| :-- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Whitelist de extensão** e **MIME real** (não confiar no `Content-Type` do browser nem só na extensão).                                                             |
| 2   | **Re-encode com GD** (`imagecreatefrom*` → `image*`): além de aplicar o crop, **destrói qualquer payload** embutido (ex.: PHP dentro de um JPEG) e **limpa o EXIF**. |
| 3   | **Limite de tamanho** (ex.: 2 MB) e de dimensões, validados **no servidor** (o `accept` do input é só UX).                                                           |
| 4   | `uploads/.htaccess` a **desligar o motor PHP** e **negar** ficheiros executáveis/interpretáveis.                                                                     |
| 5   | Gravar **sempre** como `<id>.<ext>` — nunca o nome enviado pelo utilizador.                                                                                          |
| 6   | **Nunca** devolver o conteúdo do ficheiro por PHP: serve-se como estático (evita `Content-Type` manipulável).                                                        |

**Conteúdo proposto para `uploads/.htaccess`** (a criar na F1):

```apache
# Dados de utilizador: NUNCA executar nada aqui dentro.
php_flag engine off
RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phar .cgi .pl
<FilesMatch "\.(php|phtml|phar|cgi|pl|py|rb|sh|html?|svg|js)$">
    Require all denied
</FilesMatch>
```

**Nota de Git:** os uploads são **dados de execução** → propor **`/uploads/*` no `.gitignore`** com
`!/uploads/.gitkeep` e `!/uploads/.htaccess` (mantém a pasta e a proteção versionadas, ignora as fotos).

> ⚠️ **`uploads/` é produto** (não é `_dev/`), logo entra nas branches de contexto como qualquer ficheiro de
> produto: `.htaccess`, `.gitkeep` e a linha do `.gitignore`. **As fotos não.**

## 5. PLANO DE IMPLEMENTAÇÃO (v2 — por fases)

> **Ordem por dependência.** A **F1** primeiro porque tudo o resto depende dos **nomes** e das **colunas** que
> ela fecha. As fases são **branches de contexto** independentes (`.clinerules` §4).

| Fase     | Nome                                          | Âmbito                                                                                                                               | Depende de | Risco     |
| :------- | :-------------------------------------------- | :----------------------------------------------------------------------------------------------------------------------------------- | :--------- | :-------- |
| **F1**   | **BD + normalização**                         | `estado_reserva` (C-01), rename `categoria_servico` + corrigir `Cabeleireiro` (C-04/§3.4.2), **remover** as 2 colunas de valor       | —          | **Alto**  |
|          |                                               | (C-03),                                                                                                                              |            |           |
|          |                                               | ➕ `funcionario.percentagem_comissao`, ➕ `utilizador.foto`, ➕ `notificacao`, `salario_base` default 0 (C-10), ➕ `uploads/` +      |            |           |
|          |                                               | `.htaccess`; regenerar `DataBase.sql`/`_clean`                                                                                       |            |           |
| **F2**   | **Core/infra**                                | ➕ `generalUtils.humanize()`; ➕ `Session::employeeContractType()`; **Swal** (Q-05); impacto global das **mensagens contextuais**;   | F1         | Baixo     |
|          |                                               | helper de **highlight + scroll** (`#rotaDinamica`); skeletons (Q-13)                                                                 |            |           |
| **F3**   | **Bugs do site público**                      | Catálogo: `/servicos` = catálogo, `serviceCategories` só na Home + «Ver Serviços» (§3.4.1);                                          | F1, F2     | Baixo     |
|          |                                               | **imagem placeholder SVG no card de serviço** (§3.4.4);                                                                              |            |           |
|          |                                               | **hero com altura do viewport** (§3.4.3); `Detalhes` com `extended-border`; navbar                                                   |            |           |
|          |                                               | `$currentPage`; skeleton testemunhos; Enter no login; aviso de horário por canal; slots alinhados; altura mínima; `bi-cash-coin` →   |            |           |
|          |                                               | `bi-cash-stack`; remover `debugger;`                                                                                                 |            |           |
| **F3.1** | **Fotos dos serviços**                        | Gestão por serviço em `/gestao/catalogo` (gestor): carregar, definir `destaque`, remover; grava em `servico_foto` +                  | F3         | Médio     |
|          |                                               | `uploads/services/…`; galeria no modal                                                                                               |            |           |
| **F4**   | **Alocação** (`#servCarrinha`)                | Acesso gestor+RV (C-08); dropdown de alocação; `#1`/`#2` (R-ALOC/R-CONF/R-24H de §4.4); `disabled`; textos/tags por perfil; sticky + | F1, F2     | **Alto**  |
|          |                                               | grid (`col-lg-12 col-xl-6`); remover `nome_pessoa` dos cartões; **remover** bloqueio na consolidação                                 |            |           |
| **F5**   | **Avisos / Lembretes**                        | Grupos por perfil (gestor/RV/**efetivo**), remover «Rotas» ao funcionário, `#limiteCards` (10 + «mostrar mais»), realce de atrasados | F4         | Médio     |
| **F6**   | **Automáticos (tempo)**                       | **Reconciliação** (R1/R2 + cascata, §8) **e** **agendador de alertas fiscais** (§9.3) — desenhados como **um serviço de manutenção** | F1, §9.1   | Médio     |
| **F7**   | **RH**                                        | Página `/gestao/rh`, criar/editar, **soft delete** com impacto nos serviços, salário base por contrato, % por funcionário; **foto**  | F1, F2     | Médio     |
|          |                                               | (reusa o componente de §4.6)                                                                                                         |            |           |
| **F8**   | **Comissões**                                 | Reinterpretação por perfil (C-12/§6), «Serviços prestados», «Empresa», receber a config de % (C-09)                                  | F7         | Médio     |
| **F9**   | **Área Cliente**                              | Nova página (perfil + agendamentos + lembretes), foto + crop (`cropper`), modais/editors, **editor de agendamento** (§4.5), menu     | F1–F8      | **Alto**  |
|          |                                               | hamburguer                                                                                                                           |            |           |
| **F10**  | **24 h + cancelamento do cliente + lembrete** | Regra das 24 h (RF-58/RN-24), auto-recusa (RF-59/RN-25 · 6.1), cancelamento pelo cliente (RF-12) e **lembrete com alternativas**     | F6         | Médio     |
|          |                                               | (RF-13)                                                                                                                              |            |           |
| **F11**  | **Dados (*seed*) + higiene**                  | Apagar agendamentos **de teste** e gerar cenários realistas (Q-06/Q-14); remover órfãos (§12); auditoria de `escapeHtml` (§13)       | F1         | Baixo     |
| **F12**  | **Integração final (Git)**                    | Dividir **tudo** em branches de contexto (incl. o commit WIP + pendentes) → push → merge em `dev` → push → `main` → push             | **F1–F11** | **Médio** |

**Notas de sequenciamento:**

- **F4 antes de F5:** os «avisos por perfil» do funcionário **efetivo** dependem de existir alocação.
- **F6 antes de F10:** o auto-cancelamento das 24 h **é** parte da reconciliação (R1 exclui os estados «em
  preparação» — **C-13**); e o fecho do estado final por alerta ao funcionário (C-13) usa o serviço de F6.
- **F1 antes de tudo:** o rename dos estados e das colunas muda ~40 sítios; fazê-lo depois custa o dobro.
- **F9 depois de F1·F7:** a Área Cliente usa a **mesma** coluna `foto` e o **mesmo** componente de crop do RH.

### 5.1 Requisitos adicionais desta fase (§9) — onde entram

| Requisito                                 | Fase                                                     |
| :---------------------------------------- | :------------------------------------------------------- |
| Cancelamento do cliente + 24 h + lembrete | **F10**                                                  |
| Re-avaliação dinâmica de slots            | **F3/F9** (criação já revalida — §24.1; falta no editor) |
| Agendador de alertas fiscais              | **F6**                                                   |

## 6. COMISSÕES — o ecrã e a resposta ao C-12

### 6.1 O que é (explicação para quem não sabe contabilidade)

O ecrã é uma **folha de remuneração por serviço**. Cada linha é um `agendamento_servico` que um funcionário
**prestou**. No momento da alocação/aceitação, o sistema **congelou** a percentagem aplicada; a partir dela
calculam-se *quanto é do funcionário* e *quanto é da empresa*. O ecrã **soma** por funcionário e por mês.
Nada é recalculado: se a percentagem mudar amanhã, as linhas antigas **não** mudam (é histórico).

### 6.2 C-12 — tens razão, e o plano corrige-se (D-02)

> **Disseste:** «a página de Comissões só faz sentido apresentar lucros e valores concretos e reais, não
> supostos de acontecer e planeados; um serviço alocado é só uma promessa, um serviço realizado é uma verdade».

**Confirmo: estás certo** — e o teu texto **corrige um erro da minha v1**. Como hoje o critério é
`estado_aceitacao='aceite'` (a *promessa*), a página está a mostrar **valores que ainda podem não acontecer**
(um agendamento pode ser recusado na rota). Isso entra em conflito com a própria natureza de uma **folha de
remuneração**.

**Correção proposta:** o critério passa a ser **serviço prestado** (realizado), não alocado.

| Antes (v2 inicial)                 | Agora (corrigido)                                                                           |
| :--------------------------------- | :------------------------------------------------------------------------------------------ |
| Filtro `estado_aceitacao='aceite'` | Filtro no **desfecho real**: agendamento `executado`/`concluido` (+ `execucao_agendamento`) |
| «Serviços aceites no mês»          | **«Serviços prestados no mês»**                                                             |

> ⚠️ **Consequência honesta (já te aviso):** com os dados atuais há **1** `execucao_agendamento` na BD → a
> página ficaria **quase vazia**. É por isso que o **seed da F11** é importante: ele cria histórico realista de
> serviços executados sem os quais esta página não tem o que mostrar.

### 6.3 O que cada perfil vê (a tua especificação)

**Funcionário:**

| Elemento                               | O que mostra                                                                                    | Nota                                                                        |
| :------------------------------------- | :---------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------- |
| Card **«Serviços prestados»**          | Quantidade de serviços **prestados** por ele no mês                                             | —                                                                           |
| Card **«Comissões do mês»**            | Valor que **lucrou** presta(n)do serviços nesse mês                                             | **D-07.1** (§14): incluir o **salário base** se for efetivo?                |
| Card **«Valor total gerado»**          | Total que os **seus** serviços geraram (o que ele lucrou **+** o que a empresa lucrou)          | Proposta de título: **«Valor gerado»** ou **«Faturação dos meus serviços»** |
| Tabela **«Serviços prestados no mês»** | Lista dos serviços que **prestou** (não os que aceitou/alocou)                                  | —                                                                           |
| ❌ **Não vê**                          | «Funcionários com prestações» · «Totais por funcionário» — **redundantes** para quem só se vê a | —                                                                           |
|                                        | si                                                                                              |                                                                             |

**Gestor:**

| Elemento                               | O que mostra                                     | Nota                                                 |
| :------------------------------------- | :----------------------------------------------- | :--------------------------------------------------- |
| Card **«Serviços prestados»**          | Total de serviços prestados **por todos** no mês | —                                                    |
| Card **«Comissões do mês»**            | Valor **a pagar a todos** no mês                 | **D-07.2** (§14): incluir salário base dos efetivos? |
| Card **«Funcionários com prestações»** | Quantos funcionários prestaram serviços          | —                                                    |
| Tabela **«Totais por funcionário»**    | Comissões de todos os funcionários (como hoje)   | —                                                    |
| Tabela **«Serviços prestados no mês»** | Todos os serviços prestados (não os alocados)    | —                                                    |

> **Resposta às tuas 3 perguntas:**
> 1. *«Devo incluir o salário base?»* → **Proposta: não misturar.** O salário base é **folha fixa (RH)**; as
>    comissões são **variável por serviço**. Sugiro um card **separado** «Salário base (fixo)» quando o perfil
>    for efetivo — mantém a leitura honesta e evita somar coisas de natureza diferente. (Confirma em **D-07.1/2**.)
> 2. *«Faz sentido o card do valor total gerado?»* → **Sim**, e é útil: é o número que justifica ao gestor
>    quanto vale o funcionário para a empresa. Só falta o **nome**.
> 3. *«O cartão Funcionários com aceitações é para o gestor?»* → **Sim**: para o funcionário seria redundante
>    (veria a própria linha).

## 7. RECURSOS HUMANOS — plano (F7)

### 7.1 🔎 Descoberta: as identidades dos trabalhadores **EXISTEM**

A spec (**`backlog.md` §24.9**) afirma: *«As identidades dos 6 trabalhadores não constam de nenhum ficheiro
entregue»*. **Isso está desatualizado.** Encontrei-as no ficheiro `Contabilidade Secade Beauty.xlsx`, folha
**«Menu Recursos Humanos»** (extração em `_dev/mapaMentalMVP/.tmp-xlsx/contabilidade.txt`, linhas 79-86):

| Nome                   | Vencimento base    | IRS retido     | Confere com o Balancete?      |
| :--------------------- | :----------------- | :------------- | :---------------------------- |
| **Cassiana Tavares**   | 1 200,00 €         | 288,00 €       | ✅ (6.321 = 19 950 no total)  |
| **Delfina Benjamim**   | 1 200,00 €         | 288,00 €       | ✅                            |
| **Iracelma Joaquim**   | 1 000,00 €         | 108,00 €       | ✅                            |
| **Luís Chivela**       | 1 000,00 €         | 108,00 €       | ✅                            |
| **Sebenildes Cabamba** | 1 250,00 €         | 321,00 €       | ✅                            |
| **Teresa Elisabeth**   | 1 000,00 €         | 108,00 €       | ✅                            |
| **TOTAL**              | **6 650,00 €/mês** | **1 221,00 €** | ✅ **19 950,00 €** (×3 meses) |

> ✅ **Já não preciso de gerar identidades** (era o plano B do teu pedido). As **6 pessoas reais** estão
> identificadas, com **salário base** e **IRS** por trabalhador — o que resolve a **pergunta 19** do §24.9
> (a taxa de IRS é **dado por trabalhador**, e cá está ela: 8 % para 1 200 €, 3,6 % para 1 000 € e 8,56 % para
> 1 250 €, que bate exatamente com o §24.9).
>
> **Ação:** corrigir o §24.9 na spec (o «não constam» passa a «constam na folha *Menu Recursos Humanos*»).

### 7.2 Decisões que simplificam (o teu pedido)

| Decisão                          | Efeito                                                                 |
| :------------------------------- | :--------------------------------------------------------------------- |
| **Ignorar feriados**             | `dias úteis` = **dias úteis do mês (seg–sex)** sem descontar feriados. |
| **Ignorar dias por trabalhador** | Subsídio **uniforme** para todos: `dias úteis × 6,15 €` (**RN-35**).   |
| **IRS por trabalhador**          | **Dado na BD** (coluna nova — ver §7.3), não constante de código.      |

> ⚠️ **Nota de coerência:** a folha real dá **61 dias** à Sebenildes (2 dias a menos que os 63 dos restantes)
> por faltas/férias. Como decidiste **ignorar** essa diferença, o **subsídio calculado** será 2 × 6,15 € =
> **12,30 € acima** do que a folha do cliente mostra. Fica **assinalado** — é o único desvio face aos ficheiros.

### 7.3 Modelo de dados proposto (F1)

| Coluna                                | Tipo                        | Porquê                                                              |
| :------------------------------------ | :-------------------------- | :------------------------------------------------------------------ |
| ➕ `funcionario.percentagem_comissao` | `decimal(5,2)` **NOT NULL** | % por serviço (por funcionário — **não há global**; C-09/C-10)      |
| ➕ `funcionario.irs_taxa`             | `decimal(5,2)` NULL         | Taxa de IRS do trabalhador (dado, **não** constante — §24.9)        |
| `funcionario.salario_base`            | default **0.00**            | **Só efetivos** têm base; RV ficam a `0` (C-10)                     |
| ➕ `funcionario.ativo`                | já existe                   | **Soft delete** do funcionário (ver §7.5) — nenhuma linha é apagada |

> **Nota (para não haver confusão):** as **categorias profissionais** dizem respeito ao **serviço**
> (`servico.categoria_id`), **não** ao funcionário. A antiga tabela `funcionario_categoria` foi **removida**
> (D-02) — não se reintroduz.

### 7.4 Funcionalidades de RH (a página `/gestao/rh`)

| #   | Funcionalidade                                                                                                         | Nota                                     |
| :-- | :--------------------------------------------------------------------------------------------------------------------- | :--------------------------------------- |
| 1   | Listar funcionários (ativos e inativos): nome, tipo de contrato, base, % e IRS                                         | —                                        |
| 2   | **Criar** funcionário (reutiliza `user.validator` + `employee.validator` — este **está vazio** e passa a ter conteúdo) | Foto com crop (§4.6)                     |
| 3   | **Editar** funcionário (dados + contrato + base + % + IRS)                                                             | Sem editar `id`/histórico                |
| 4   | **Desativar** (soft delete) com o fluxo de impacto de §7.5                                                             | Nunca `DELETE`                           |
| 5   | Indicadores: nº efetivos, nº RV, custo fixo mensal                                                                     | Sem inventar (valores de `salario_base`) |
| 6   | (Depois) **Folha**: líquido a pagar calculado (`PayrollService` · **RN-35**)                                           | ⬜ fora desta fase                       |

### 7.5 Fluxo de **desativação de funcionário** (o teu requisito)

> **Requisito:** remover um funcionário é **soft delete** (preservar histórico). Se ele estiver associado a
> serviços que ainda vão ser executados, **avisar o gestor** de que será removido dos serviços **pendentes**;
> o `estado_aceitacao` desses serviços volta a **`pendente`** e o `estado_reserva` do agendamento é **ajustado**.

**Algoritmo proposto (transacional, num só `executeTransactional`):**

```text
1. Contar servicos do funcionario em agendamentos NAO terminais
   (excluir: confirmado? -> ver D-07.3; excluir sempre: recusado, cancelado, executado, concluido)
2. Se houver: devolver ao gestor a CONTAGEM + LISTA (dialogo de confirmacao)  -> ele decide
3. Se confirmar (ou se nao houver nenhum):
   a. agendamento_servico: funcionario_id = NULL, estado_aceitacao = 'pendente',
      aceito_em = NULL, percentagem_funcionario_aplicada = NULL
   b. agendamento: recalcular estado_reserva -> 'pendente_alocacao'
      (so quando deixou de estar totalmente_alocado)
   c. funcionario: ativo = 0   (soft delete)
4. Se o servico estava em rota CONFIRMADA -> NAO mexer (preserva o compromisso; ver D-07.3)
```

> ⚠️ **Dúvida D-07.3** (§14): a tua regra diz «não confirmados/recusados/cancelados/executados/concluidos».
> **Se o agendamento estiver `confirmado`** (rota aprovada) e o funcionário for desativado, mexer-lhe desfaz um
> compromisso já comunicado ao cliente. Proponho **bloquear** os `confirmado` e **avisar** que primeiro há que
> retirar a rota. Confirma.

### 7.6 Percentagem aplicada (regra final — resolve C-09 · C-10)

```text
percentagem aplicada = funcionario.percentagem_comissao   (sempre definida — NOT NULL)

# Nasce no registo/criacao com o default do TIPO DE CONTRATO:
#   efetivo_contratado -> 0 %        recibo_verde -> 70 %
# O gestor edita por funcionario. NAO existe "global" nem fallback.
```

O formulário de RH **pré-preenche** com o **default do tipo de contrato** escolhido e o gestor **pode
alterá-lo** por funcionário. **Não há valor «global» nem cadeia de fallback**: a percentagem aplicada é
**sempre** a que está gravada no funcionário.

## 8. RECONCILIAÇÃO DE ESTADOS — integrada na F6 (com C-13 e 6.1)

> Fonte: `relatorio_reconciliacao-estados.md`. Aqui ficam só a **integração**, os **conflitos** e a **ordem**.

### 8.1 Sinergia (onde a reconciliação ajuda)

| Ponto do plano              | Como ajuda                                                                                              |
| :-------------------------- | :------------------------------------------------------------------------------------------------------ |
| **Avisos/Lembretes** (§3.7) | O fecho automático tira das listagens o que já passou — os avisos deixam de mostrar «fantasmas».        |
| **Rotas** (§3.6)            | `rota_ambulante` fecha-se sozinha (cascata bottom-up) — o gestor deixa de ver rotas velhas por decidir. |
| **Área Cliente** (§3.5)     | O histórico do cliente fica coerente com os filtros de estado.                                          |
| **`#rotaDinamica`** (§3.7)  | O lembrete aponta para um estado **já reconciliado**.                                                   |

### 8.2 A regra reescrita com as tuas decisões (C-13 · 6.1)

**A tua decisão (C-13):** o corte temporal é **antes da CONFIRMAÇÃO** (não antes da execução); depois de
confirmado, se passar a hora, gera-se um **alerta ao funcionário** para fechar o estado final.

**A tua decisão (6.1):** auto-cancelamento passa a **auto-recusa**; a **staff recusa**, não cancela.

**Regras revistas (proposta):**

| #   | Condição                                                                                       | De → Para                                                                                                       |
| :-- | :--------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------------------------------------------- |
| R1a | `NOW() > corte` e o agendamento **ainda não está em rota** (não `confirmado`) e não é terminal | `pendente_alocacao` / `pendente_validacao_logistica_loja` / `totalmente_alocado` → **`recusado`** (motivo: sem  |
|     |                                                                                                | rota)                                                                                                           |
| R1b | `NOW() > inicio` e o estado é **`confirmado`** (já em rota)                                    | **NÃO** muda sozinho → gera **alerta ao funcionário** para fechar (`executado`/`concluido`/`cancelado_terreno`) |
| R2  | `NOW() > (fim + 4 h)` e o estado é **`executado`**                                             | → **`concluido`**                                                                                               |
| R3  | **Todos** os filhos diretos `concluido` (e ≥ 1 filho)                                          | rota `aprovada`/`em_execucao` → **`concluida`**                                                                 |
| R4  | **Todos** os filhos diretos `recusado` (e ≥ 1 filho)                                           | rota `planeada`/`aprovada` → **`recusada`**                                                                     |

> ⚠️ **`recusado` vs `cancelado`** — regra final que decorre de 6.1:
> **`cancelado` = só o cliente cancela** · **`recusado` = tudo o que a staff/sistema decide**.
> Impacto: `RotaService` **L136** (`$bookingState = $approved ? "confirmado" : "cancelado"` →
> passa a **`"recusado"`**) e `BookingService::cancelBooking` (do gestor) → também **`recusado`** (**D-07.4**).

### 8.3 Conflitos (revisados com as tuas respostas)

| ID       | Conflito                                                                                                                                                                 | Estado                         |
| :------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :----------------------------- |
| **C-13** | ~~R1 cancelaria antes de o cliente ser notificado~~ → **resolvido** pela tua decisão: R1 só atua **antes da confirmação**; depois dela, alerta ao funcionário.           | ✅ **Fechado** (regra em §8.2) |
| **C-14** | ~~Contadores do sino mudam sem o utilizador ver~~ → **resolvido**: o sino conta só **o que tem ação pendente**; os grupos do `AlertService` filtram por                  | ✅ **Fechado**                 |
|          | **estados não terminais**.                                                                                                                                               |                                |
| **6.1**  | ~~`cancelado` usado pela staff~~ → **novo** divisor: `cancelado` = cliente · `recusado` = staff/sistema. **Requer rename no `RotaService` e no cancelamento do gestor.** | ✅ **Fechado** + ação (F1/F4)  |

### 8.4 Ordem obrigatória

```text
1. F1  -> nomes do enum (C-01), recusado/cancelado (6.1), colunas novas
2. F6  -> reconciliacao (R1a/R1b/R2/R3/R4) + agendador fiscal (§9.3)
3. F10 -> regra das 24 h (R-24H) — encaixa em R1a
4. F4  -> alocacao e o bloqueio sobre a ROTA confirmada (nao sobre a consolidacao)
```

> ⚠️ **Não fazer a F6 antes da F1.** As `UPDATE` da reconciliação referenciam **nomes de estado**; se o enum
> mudar depois, apontam para valores inexistentes.

## 9. REQUISITOS A INCLUIR NESTA FASE

### 9.1 Cancelamento + 24 h + lembrete (o «pacote» do cliente)

| Requisito                        | Estado hoje                                                          | Onde entra            |
| :------------------------------- | :------------------------------------------------------------------- | :-------------------- |
| **Cancelamento pelo cliente**    | ✅ **já existe** (`customer-booking-cancel` · RF-12)                 | — (revisão de texto)  |
| **Regra das 24 h**               | ❌ **não existe** (`RotaService::decideRoute` aceita qualquer data)  | **F10**               |
| **Auto-recusa sem rota às 24 h** | ❌ não existe                                                        | **F10** (R1a de §8.2) |
| **Lembrete com alternativas**    | ❌ não existe (a promessa já está no texto de `aboutCommitment.php`) | **F10** + F5          |

### 9.2 Re-avaliação dinâmica dos slots

| Onde                      | Estado                                                                                                        |
| :------------------------ | :------------------------------------------------------------------------------------------------------------ |
| **Wizard de criação**     | ✅ **já implementado** (§24.1) — `bookingWizard.js::refreshSlotsIfNeeded()` (L418), chamado ao mudar serviços |
| **Editor de agendamento** | ⚠️ **a implementar** — o editor (F9) herda a mesma lógica                                                     |

### 9.3 Agendador de alertas fiscais (a tua pergunta)

> **Perguntaste** se faria sentido usar uma arquitetura semelhante ao reconciliador. **Resposta: sim,
> exatamente** — é o **mesmo problema** (trabalho periódico sem CRON) e deve ser o **mesmo mecanismo**.

**O que existe hoje:** `FiscalService::generateAlerts()` é `private` (L192) e só corre **quando o gestor abre o
calendário fiscal**. Se ninguém abrir, não há alertas novos.

**Proposta — um só «serviço de manutenção», com 2 tarefas:**

```text
MaintenanceService::run()            <- idempotente + transacional
├── reconcileStates()   (R1a..R4)    <- ja descrito no §8
└── generateFiscalAlerts()           <- extraido do FiscalService (passa a publico)
```

**Quando correr (a tua pergunta sobre o login):**

| Opção                                          | Prós                                                    | Contras                                           |
| :--------------------------------------------- | :------------------------------------------------------ | :------------------------------------------------ |
| **A** — em cada leitura relevante (o planeado) | Zero infraestrutura; corre quando alguém usa o sistema  | Dias **sem ninguém entrar** → alertas atrasados   |
| **B** — no login                               | Cobre **todos** os perfis, num ponto único e previsível | Atrasa o login (mitigável)                        |
| **C** — endpoint dedicado `/cron`              | Padrão clássico                                         | Precisaria de agendador externo (Laragon não tem) |

**Recomendação (a mais económica e robusta neste contexto):** **A + B combinadas**, com **guard de tempo**
(registo da última execução) que impede repetir antes de N minutos:

- o **login** garante que **pelo menos uma vez por sessão** o sistema se atualiza;
- o **guard** evita correr 20× por minuto;
- **síncrono basta** (a BD é pequena — o teu próprio argumento). Se algum dia pesar > 2 s, acrescenta-se o modo
  assíncrono **sem mudar a arquitetura** (é o mesmo `MaintenanceService`).

## 10. REQUISITOS A PONDERAR — sinal + 90 % + UI financeira (D-06)

> **Pediste** explicação detalhada de negócio, funcional e gráfico. Aqui fica — **sem implementar**.

### 10.1 Negócio (porque existe)

Hoje a plataforma **promete** ao cliente «paga 10 % agora e 90 % no fim» (`bookingWizard.php`), mas **não
regista nada disso**: não há valor configurável, não há registo do recebimento, não há método. É uma
**promessa sem livro**. O requisito transforma a promessa em **operação registada** — é o que liga a
**operação** (`execucao_agendamento`) ao **financeiro** (`transacao_financeira`).

### 10.2 Funcional (o que passa a existir)

| #   | Funcionalidade                                                     | Quem            | Onde                                            |
| :-- | :----------------------------------------------------------------- | :-------------- | :---------------------------------------------- |
| 1   | **Configurar o sinal** (%, ativo/inativo, 1.ª marcação dispensada) | gestor          | Configurações (ex-«Recibos Verdes»)             |
| 2   | Cálculo do sinal no **wizard** a partir dessa configuração         | sistema         | `BookingService` (substitui os 10 % fixos)      |
| 3   | **Registar o sinal** com método                                    | gestor          | detalhe do agendamento                          |
| 4   | **Registar os 90 %** no fim (ou `pagamento_integral`)              | gestor/execução | `execucao_agendamento` → `transacao_financeira` |
| 5   | **Métodos**: `numerario_dinheiro` · `mb_way` · `multibanco_pos`    | —               | **já existe** no ENUM de `transacao_financeira` |

### 10.3 Gráfico (layout)

A UI natural é o **detalhe do agendamento** (onde já se registra a execução), com um bloco **«Pagamentos»**:
valor do sinal (pago/pendente), valor restante, método, e botão «Registar pagamento». No **painel**, a receita
passa a poder vir do **livro real** (em vez de `agendamento.valor_total`).

### 10.4 Relação com a operação e com as 3 tabelas (as tuas 2 perguntas)

1. **Operação/execução** — é **no mesmo momento**: na loja é a execução; na carrinha é o desfecho da rota. Faz
   sentido registar os dois **no mesmo ecrã** (detalhe/execução) → contexto de **F4/F6**.
2. **UI das 3 tabelas** — **sim, estão relacionadas**, mas são três coisas distintas:

| Tabela                 | O que é                   | UI proposta                                       | Prioridade                              |
| :--------------------- | :------------------------ | :------------------------------------------------ | :-------------------------------------- |
| `transacao_financeira` | **Livro de recebimentos** | No detalhe do agendamento (sinal / 90 % / método) | **Alta** (é o que o sinal/90 % precisa) |
| `gorjeta`              | Gorjetas por prestação    | No mesmo bloco, campo opcional no fecho           | Média                                   |
| `fecho_caixa_diario`   | **Fecho de caixa do dia** | Página própria (gestor), somando o dia            | Baixa (pode ficar de fora)              |

> ⚠️ **D-06.1** (§14): é um **pacote grande** e **não** está nos teus requisitos obrigatórios. Proponho uma
> **FASE 8 (a ponderar)** em vez de o meter na F7. Confirma se entra agora ou depois.

## 11. REQUISITOS REVOGADOS

| Requisito              | Ação                                                                                         |
| :--------------------- | :------------------------------------------------------------------------------------------- |
| **Rotas multicidades** | ✅ **Revogado** — a rota é **1 cidade/dia**; retirar de §24.4 · §25 · `backlog` e dos testes |

## 12. ÓRFÃOS E CÓDIGO A REMOVER (lista revista)

> **Revista de acordo com as tuas respostas.** Passa a haver três categorias.

### 12.1 Remover

| Ficheiro / elemento                                   | Estado                                         | Ação                                                      |
| :---------------------------------------------------- | :--------------------------------------------- | :-------------------------------------------------------- |
| `app/services/ServiceService.php`                     | **0 referências**                              | Remover                                                   |
| `serviceCategories.php` (**página**)                  | Substituída pelo catálogo                      | Remover (§3.4.1) — o **componente** fica na Home          |
| `bi-cash-coin` (`boSidebar.php` L36)                  | **Ícone inexistente** na lib                   | Trocar por `bi-cash-stack`                                |
| `debugger;` (`dashboard.js` L154)                     | Instrução de depuração                         | Remover                                                   |
| Passo `professional` (wizard loja)                    | Sem sentido (G-01)                             | Remover do PHP + `SECTIONS.loja_fisica`                   |
| **15 SVG duplicados** na raiz de `modules/common/img` | Duplicados exatos em `about-images/skeletons/` | **Remover a cópia da raiz** (mantém-se a de `skeletons/`) |
| `modules/common/img/about.jpg`                        | Substituído por `about.png`                    | Já removido (confirmar)                                   |

### 12.2 Manter (as tuas decisões)

| Elemento                           | Decisão do cliente | Nota                                                    |
| :--------------------------------- | :----------------- | :------------------------------------------------------ |
| `modules/main/components/team.php` | **Manter**         | Passa a ser **usado** (ligado à secção de equipa)       |
| `employee.validator.js`            | **Manter**         | **Passa a ter conteúdo** (formulários de RH)            |
| `greenReceipts.js`                 | **Manter**         | Sobrevive como **Configurações de percentagens** (C-09) |

### 12.3 Rever (dependem de decisões)

| Elemento                                  | Depende de                                                                 |
| :---------------------------------------- | :------------------------------------------------------------------------- |
| `profile.php` (parte cliente)             | F9 — vira **redirect** para a Área Cliente (Q-08)                          |
| `appointments.php` (main)                 | F9 — absorvido pela Área Cliente                                           |
| `OTPService` + passo `otp`                | **D-04** (§14) — se o OTP sai do agendamento, isto fica para uso no perfil |
| `modules/main/components/aboutValues.php` | Confirmar se é usado além de `about.php` (evitar órfão)                    |

## 13. VALIDAÇÕES/ CORREÇÕES GERAIS

### 13.1 Escape de HTML — auditoria obrigatória

**Estado:** `generalUtils.escapeHtml` existe e é usado na maioria dos sítios, mas **há interpolações sem
escape** (é o caso clássico de risco de injeção). Exemplo já identificado: `dashboard.js` interpola valores de
API; `services.js`, `appointments.js` (main/bo) interpolam dados que vêm do servidor.

| Ação                                                                                                         | Onde                          |
| :----------------------------------------------------------------------------------------------------------- | :---------------------------- |
| **Auditoria sistemática**: toda a interpolação `${...}` em template strings que recebe dados do servidor     | `modules/**/js/components/**` |
| **Regra**: campos de **texto livre** (nome, comentário, observações, morada) **sempre** escapados            | —                             |
| **Valores numéricos/formatados** (`formatCurrency`, `formatDuration`, `formatDateTime`) considerados seguros | —                             |
| **Não** escapar HTML **intencional** (badges, ícones) — mas **auditar** cada um desses casos                 | —                             |

> ⚠️ **Nota:** `escapeHtml` **não** substitui validação no servidor. Continuam a valer as regras da §18.6
> (prepared statements, validação no `Validator`, `hash_equals` no OTP).

### 13.2 Gate obrigatório (`.clinerules` §3 · `_dev/docs/README.md`)

Antes de fechar **cada fase**:

```bash
php _dev/tools/md-join-tables.php    <f> --write
php _dev/tools/md-wrap-tables.php    <f> --write
php _dev/tools/md-align-tables.php   <f> --write
php _dev/tools/ascii-align.php       <f> --write
php _dev/tools/health-check.php          # tem de dar TUDO OK
php _dev/tests/js_syntax_check.php       # se tocar em JS
```

E, no fim de cada fase com impacto em produto: **`php -l`** nos PHP tocados + as **4 suites** de `_dev/tests/`.

## 14. DÚVIDAS — RESPOSTAS DADAS e NOVAS

### 14.1 Confirmadas por ti (fecho formal)

| ID                  | Pergunta da v1                                   | A tua resposta                                                         | Estado                                             |
| :------------------ | :----------------------------------------------- | :--------------------------------------------------------------------- | :------------------------------------------------- |
| **G-04 / Q-12**     | Tipos de lembrete do cliente                     | «Já respondi nos pontos anteriores» → decorre de **C-07/C-13/C-14**    | ⚠️ **Ver D-07.6** — não encontro a lista explícita |
| **G-05 / Q-09**     | Âmbito da edição do agendamento                  | «Já respondi» → **§4.5** (C-06)                                        | ✅ **Fechado**                                     |
| **G-06**            | Implementar a regra das 24 h agora?              | **Sim**                                                                | ✅ **Fechado**                                     |
| **Q-02…Q-04, Q-08** | Acessos e motores                                | **C-08 / C-09 / Q-08**                                                 | ✅ **Fechado**                                     |
| **Q-07**            | Rename da categoria: só tabela ou tudo?          | «Já respondi» → **tabela + JOINs**, chaves da API mantêm-se (**§4.3**) | ✅ **Fechado**                                     |
| **Q-10**            | Qual percentagem ganha?                          | **A do funcionário** (por funcionário; **não há global**) — **§7.6**   | ✅ **Fechado**                                     |
| **Q-11**            | «Passo 6»                                        | Era o **Passo 4 (Profissional)** → **remover** (G-01)                  | ✅ **Fechado**                                     |
| **Q-13**            | Criar skeletons?                                 | **Sim**                                                                | ✅ **Fechado**                                     |
| **Q-15**            | Limite de cards                                  | **10 + «mostrar mais»** (navega para a página)                         | ✅ **Fechado**                                     |
| **Q-08**            | Já existem atalhos de gestão no menu hamburguer? | **Sim** — `menuUser.php` L23-34 (`$managementLinks`, por perfil)       | ✅ **Confirmado**                                  |

### 14.2 Q-14 — «o que é o *seed*?» (explicação)

> **Perguntaste** onde vi «seed com género». Explico o termo.

**O que é um *seed*:** é o **conjunto de dados de demonstração** que o dump (`DataBase.sql`) carrega, para a
aplicação não arrancar vazia. Hoje inclui os **10 agendamentos** de exemplo, os **65 clientes reais** e o
catálogo. («Seed» = *semear* a base com dados iniciais.)

**Onde entrou o «género»:** na tua lista original — «gera-me agendamentos com serviços mais realistas, consoante
o género da pessoa (ex.: João Cliente não faz Manicure)». Como **não existe coluna de género** na BD, é preciso
um critério para decidir a coerência serviço↔pessoa.

**Proposta (D-05):** **inferir pelo nome próprio** (lista PT de nomes femininos/masculinos) e mapear cada nome a
serviços plausíveis — **sem criar coluna nova**. O gerador é um script de apoio (como o
`_dev/mapaMentalMVP/.tmp-gen-v4.php` que já existe).

### 14.3 Novas dúvidas (D-07.x) — ✅ respondidas (ver §14.4)

| ID         | Dúvida                                                                                             | Proposta                                                                                            |
| :--------- | :------------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------------------------------- |
| **D-07.1** | Comissões (**C-12**): o card «Comissões do mês» do **funcionário efetivo** inclui o                | **Não misturar** — card separado «Salário base (fixo)»; as comissões são só o variável.             |
|            | **salário base**?                                                                                  |                                                                                                     |
| **D-07.2** | Comissões: o card «Comissões do mês» do **gestor** («a pagar a todos») inclui os salários base dos | **Não** — o «a pagar» é só a parte **variável**; o fixo vive no **RH**.                             |
|            | efetivos?                                                                                          |                                                                                                     |
| **D-07.3** | Desativar funcionário com serviços num agendamento **`confirmado`** (rota já aprovada): mexer ou   | **Bloquear** + avisar «retire primeiro a rota» (não se desfaz um compromisso já comunicado).        |
|            | bloquear?                                                                                          |                                                                                                     |
| **D-07.4** | `recusado` vs `cancelado`: confirmas que o **cancelamento feito pelo gestor**                      | **Sim** (6.1) — `cancelado` fica reservado ao **cliente**.                                          |
|            | (`admin-appointment-cancel`) passa a **`recusado`**?                                               |                                                                                                     |
| **D-07.5** | O `execucao_agendamento.estado_execucao` tem `cancelado_terreno` — promover também a estado do     | **Não** agora; usa-se `recusado`/`concluido` no agendamento e o detalhe fica na execução.           |
|            | **agendamento**?                                                                                   |                                                                                                     |
| **D-07.6** | **(D-03 · G-04/Q-12)** Confirmar a lista de **tipos de lembrete do cliente**.                      | Proposta: (a) **marcação confirmada** (em rota) · (b) **recusada/cancelada por logística** (C-14) · |
|            |                                                                                                    | (c) **lembrete 24 h** (RF-13).                                                                      |
| **D-07.7** | **(D-04)** Confirmas a **remoção total do OTP** do fluxo de agendamento (criação **e** edição)?    | **Sim**; o OTP **passa a servir o perfil** (alterar telemóvel / e-mail / palavra-passe).            |
| **D-07.8** | Sinal/90 % + UI financeira (**§10**): entra **nesta fase** ou fica numa **F8**?                    | ✅ **Entra na Fase 7** (decisão do cliente) — a F8 encolhe; ver §15.                                |

### 14.4 Registo das respostas desta ronda (D-01…D-26)

> Registado aqui para **não se perder** (o `relatorio_fase7-a-validar.md` passa a mostrar **só** o que está
> pendente). ⚠️ = difere da proposta.

| ID                             | Resposta                                                                                                                                | Difere? |
| :----------------------------- | :-------------------------------------------------------------------------------------------------------------------------------------- | :------ |
| `D-07`                         | Concordo → **cortar o sufixo**: `pendente_alocacao` / `totalmente_alocado`                                                              | —       |
| `D-08`                         | Concordo → **manter os outros 6** valores do enum                                                                                       | —       |
| `D-09`                         | Concordo → `cancelado` = cliente · `recusado` = staff (incl. gestor)                                                                    | —       |
| `D-10`                         | Concordo → **remover as 2 colunas derivadas**                                                                                           | —       |
| `D-11`                         | Concordo → nomes das colunas novas                                                                                                      | —       |
| `D-12`                         | **Corrigir na BD** (`Cabelereiro` → `Cabeleireiro`)                                                                                     | —       |
| `D-13`                         | **Criar os skeletons**; imagens reais = **principal** (≈ `destaque`)                                                                    | 🟡      |
| `D-14`                         | **Sim** — fotos `service-{id}` (nome a fechar — ver `a-validar` §1)                                                                     | 🟡      |
| `D-15`                         | **Não vão para `uploads/`** — **estáticas por id** (por agora)                                                                          | —       |
| `D-16`                         | Card = **1 imagem** · detalhes (carousel) = **várias** (**nº por definir**)                                                             | 🟡      |
| `D-21`                         | **Correção do modelo** — sem «global», sem fallback (ver **§4.2 · §7.6**)                                                               | ⚠️ SIM  |
| `D-22`                         | **Sim** — ⚠️ **registar para poder reverter** (12,30 € acima do ficheiro do cliente)                                                    | 🟡      |
| `D-23`, `D-24`                 | **Sim** — «Plataforma» → **«Empresa»** · «Simulador» → fora                                                                             | —       |
| `D-25`                         | **Sim para `users`**; serviços = imagens estáticas por id                                                                               | 🟡      |
| `D-26`                         | Cliente a **acrescentar as restantes fotos agora**                                                                                      | 🟡      |
| `D-01`                         | **Sim** (R-ALOC · R-CONF · R-24H)                                                                                                       | —       |
| `D-02`, `D-17`, `D-18`         | **Sim**                                                                                                                                 | —       |
| `D-03`, `D-04`, `D-19`, `D-20` | **Sim**                                                                                                                                 | —       |
| `D-05`, `D-06`                 | **Sim**                                                                                                                                 | —       |
| `D-07.1`, `D-07.2`             | **Não** — não misturar **salário base** com comissões                                                                                   | —       |
| `D-07.3`                       | **Bloquear** — não mexer em agendamentos `confirmado`                                                                                   | —       |
| `D-07.5`                       | **Não** — não promover `cancelado_terreno`                                                                                              | —       |
| `D-07.8`                       | **Implementar já na Fase 7** (era F8)                                                                                                   | ⚠️ SIM  |
| `D-25`/`D-13`                  | **`servico_foto` volta a ter uso**; fotos em `uploads/services/service-{id}.jpg`; **migrar** as existentes                              | ⚠️ SIM  |
| `D-26`                         | **+3 fotos** (total **6**); **1 template genérico** p/ as que faltam (placeholders)                                                     | 🟡      |
| `NF-01`                        | `config_recibo_verde` → **`config_percentagem_padrao`**: guarda os **defaults por tipo de contrato**, **configuráveis** (não estáticos) | ⚠️ SIM  |

## 15. RESUMO PARA DECISÃO

1. **Está tudo pronto para começar** pelas fases do **§5**; a **F1** (BD) vem primeiro porque tudo depende dela.
2. **Já tenho** as respostas a **D-07.1 … D-07.8** (**§14.3**) — **fechadas** (§14.4). Falta só o **«avança»**
   e os **4 itens pendentes** de `relatorio_fase7-a-validar.md` §1 (todos não bloqueantes).
3. **Descoberta relevante:** as **identidades dos 6 trabalhadores EXISTEM** (**§7.1**) — o §24.9 da spec está
   **desatualizado** e deve ser corrigido na mesma alteração.
4. **Duas correções que o teu *feedback* provocou** no plano: o critério das **Comissões** passa a «serviço
   **prestado**» (não alocado — **C-12**) e o divisor **`recusado` (staff) vs `cancelado` (cliente)** (**6.1**).
5. **Auditoria de textos técnicos VISÍVEIS** fica registada em **§3.10** (inclui a nota do painel que apontaste).
6. **Não implementei nada** — nem PHP, nem JS, nem schema. Este relatório é para **decisão**.

---

## ANEXO — CONVENÇÕES A RESPEITAR (herdadas da v1, mantêm-se válidas)

- **Formulários novos:** `form.utils.js` (`Form`/`FormValidators`) + **reutilizar validators existentes**
  (`user.validator`, `customer.validator`, `supplier.validator`, `booking.validator`); criar novos **só** quando
  necessário.
- **Endpoints:** famílias existentes (`admin-`, `admin-service-`, `customer-`, `booking-`, `auth-`, públicas) —
  **sem novos prefixos** (`.clinerules` §2).
- **Camadas:** Controller autoriza · Service valida/orquestra/transaciona · Repository SQL com prepared
  statements · Mapper BD→EN.
- **Navegação dinâmica:** `window.APP_PARAMS` + `generalUtils.scrollToElement` (convenção existente) e **um**
  utilitário partilhado para o *highlight* temporário.
- **BD:** alterações exigem **justificação registada** (`.clinerules` §1 · `data-api.md` §17.9) e novo dump.
- **Ficheiros temporários de análise** são sempre removidos no fim (não ficam no repositório).
