# AUDITORIA DE ARTEFACTOS — PLANEAMENTO DO BACKOFFICE

<!-- md-wrap-tables:max=220 — a §3 é uma tabela de atestado com muitos identificadores longos. -->

**Ficheiro de trabalho (não normativo)** · branch **`agent-workspace`** · 23/09/2026

Varrimento pedido: encontrar, no planeamento do backoffice, tudo o que esteja a ser **afirmado sem
base** — em menções, no código implementado ou em requisitos já definidos no passado. Só entram
nesta lista itens cuja prova foi **lida** (ficheiro + linha) e que **não** se confirmaram.

**Método:** cada afirmação do `analise_backoffice_gestor.md` foi submetida a verificação executável
(varrimento de código, contagem na BD, `git log -S`/`git grep`, execução das suites de teste).
**§1** resume o que falhou; **§3** o que está certo (para não voltar a ser revisto).

---

## 1. RESUMO DOS ACHADOS

| #    | Artefacto                                   | Onde foi afirmado                | Gravidade                 |
| :--- | :------------------------------------------ | :------------------------------- | :------------------------ |
| F-01 | *"Renovação de contratos"* e                | §1.2 · §1.3 · §2.5 · Teams 18/19 | ✅ **removido**           |
|      | *"revisões da carrinha"*                    |                                  |                           |
| F-02 | `requireManager()` / `requireEmployee()`    | §1.7 ponto 1                     | ✅ corrigido              |
| F-03 | `includes/boNavbar.php`                     | §1.3 (A.2)                       | ✅ corrigido              |
| F-04 | Prefixos `employee-*` vs `admin-employee-*` | §1.7 · §1.9 · §1.10              | ✅ corrigido (1 família)  |
| F-05 | Bloco *"RF-75+"* mas IDs a começar em RF-80 | §1.10                            | ✅ corrigido (RF-75…)     |
| F-06 | *"50 €"* com **dois significados**          | §1.4 (B.1) · §12.2 da spec       | ✅ resolvido (custo fora) |
| F-07 | *"só 3 de categoria"* (imagens)             | §1.11 (E.3) · Q-45               | ✅ corrigido              |

> **Estado (24/09/2026): os 7 achados estão encerrados.** F-01 foi **removido** do planeamento (não
> reformulado): a proveniência não existia e o tema não faz sentido. F-06 foi resolvido por decisão do
> gestor — a regra do *custo fixo* de 50 € foi **eliminada do código e de todos os ficheiros**; o 50 €
> ficou só como indicador visual.

---

## 2. DETALHE DOS ACHADOS

### F-02 · `requireManager()` e `requireEmployee()` não existem

**Afirmado em** §1.7, ponto 1: *"hoje cada Controller faz `requireManager()`/`requireEmployee()` —
manter e passar a cobrir as páginas"*.

**Prova (varrimento de toda a árvore):**

| Verificação                          | Resultado                                                                                         |
| :----------------------------------- | :------------------------------------------------------------------------------------------------ |
| `requireManager` em `*.php`/`*.js`   | **0 ocorrências**                                                                                 |
| `function requireEmployee` em `app/` | **1**                                                                                             |
| `app/controllers/BaseController.php` | só `getRequestData()` (L5) e `requireCustomer()` (L18)                                            |
| Método real de autorização           | `Session::requireProfileApi([...])` — ex.: `AdminController.php` L18 · `FiscalController.php` L21 |

**Consequência:** a proposta de *"manter o mesmo padrão"* aponta para métodos inventados. A matriz
de permissões (§1.7) deve ser escrita usando **`Session::requireProfileApi`**. Corrigir a redação.

### F-03 · `includes/boNavbar.php` não existe nesse caminho

**Afirmado em** §1.3 (A.2), tabela do sininho: *"✅ `modules/backoffice/components/menuUserBo.php` +
`includes/boNavbar.php`"*.

**Prova:** `menuUserBo.php` existe em `modules/backoffice/components/` ✔; **não existe** nenhuma
pasta `includes/` na raiz do projeto. O ficheiro real é
**`modules/backoffice/includes/boNavbar.php`**.

**Consequência:** corrigir o caminho.

**Resolução (24/09/2026):** resposta corrigida para `modules/backoffice/includes/boNavbar.php`.

### F-04 · Três prefixos diferentes para a mesma área (Funcionário)

**Afirmado em:** §1.7 usa `employee-agenda-list` e `employee-commission-list`; §1.5 e §1.9 usam
`admin-employee-list`/`-save`/`-toggle`; a **§19.2 da especificação** tem os endpoints do
funcionário como **`admin-service-*`**.

**Prova:** `app/config/api.php` tem **4** endpoints de funcionário —
`admin-service-pending-list`, `admin-service-accepted-list`, `admin-service-accept`,
`admin-service-unaccept` — e **0** com prefixo `employee-`. Isto contradiz o **próprio P3** do
documento (*"Endpoints `admin-<dominio>-<acao>`"*).

**Consequência:** escolher **um** prefixo antes de escrever qualquer endpoint. Recomendação:
`admin-employee-*` para o CRUD da equipa · `admin-service-*` (já existente) para a aceitação ·
**eliminar** os `employee-*` sem prefixo.

### F-05 · §1.10 diz "RF-75+" e depois começa em RF-80

**Afirmado em** §1.10: *"Blocos livres verificados: `RF-75+` (§4.6 termina em RF-74)…"* — e, logo
abaixo, a lista de IDs sugeridos começa em **RF-80**.

**Prova:** `especificacao_mvp.md` — §4.6 termina em **RF-74** (L405) ✔ · §5.1 termina em **RN-29**
(L441) ✔ · **D-11** é o último `D-nn` ✔. Os limites estão certos; **RF-75…RF-79 ficam sem
justificação** (nem reservados, nem usados).

**Consequência:** ou começar em RF-75, ou declarar que 75-79 ficam reservados.

### F-06 · O mesmo 50 € é *custo* e *indicador* — a decisão precede a Contabilidade

**Afirmado em** §1.4 (B.1): *"Custos de rota — `custo_estimado_combustivel`, `lucro_servicos`,
`lucro_total` … incl. **custo fixo de 50 €**"*.

**Prova:** o código usa **duas** constantes, ambas `50.0` — `RotaService.php` L22
`FIXED_OPERATIONAL_COST = 50.0` (somada ao custo total: L80) e L23
`REFERENCE_PROFITABILITY = 50.0` (só visual: L115 · devolvida como `meetsReference`). A
especificação espelha as duas: §12.2 L677 (custo) e §3.1 L143 (referência visual). O `.clinerules`
afirma que o 50 € é **"indicador de apoio visual, nunca gatilho automático"** — sem mencionar custo.

**Consequência:** quando a Contabilidade somar "Gastos", herda **50 € por rota** como despesa real.
É preciso decidir: **(a)** manter como custo operacional (é o que a spec e o código dizem hoje), ou
**(b)** separar os dois valores para nunca mais confundir custo com referência — o risco do
algoritmo revogado (§3.13 · linha 1). **Não decidir sem registo** (§29.3).

**Resposta** este valor de 50€ deve ser somente visual! Não sei onde fomos buscar a ideia de que os 50€ seriam um
custo adicional e concreto de operação a ser aplicado á rota, não me faz sentido! Os custos de combustivel é que 
ainda não estão a ser calculados, acredito que na BD só tenhamos as distâncias ou tempos relacionados á deslocação
entre cidades, mas iremos precisar de arranjar uma forma de estimar o custo de combustivel dentro da cidade (creio
que os 50€ eram uma estimativa muito primordial como placeholder para esse custo, mas esse custo vai precisar de ser
devidamente estimado, possivelmente usando uma API de geolocalização, a implementada no addressAutocomplete poderá ser
util para isso, mas se a formos usar precisaremos de centralizar a implementação dela numa api dela mesma (semelhante á 
nossa apiClient.js e api.js))

**Resolução (24/09/2026): regra removida.** O **custo fixo de 50 €** foi eliminado do código
(`FIXED_OPERATIONAL_COST` removido de `RotaService`) e de **todos** os ficheiros: agora
`rentabilidade = receita − combustível`, sem qualquer custo fixo. O valor de **50 €** subsiste
**apenas** como `REFERENCE_PROFITABILITY` — indicador visual, que nunca decide.
**Verificação:** 105 testes funcionais continuam a passar; medição direta — Arraiolos **−2,68 €**
(abaixo) · Estremoz **184,97 €** (acima).

⚠️ **Gap legítimo que fica aberto** (deste mesmo esclarecimento): a `matriz_deslocacao` cobre o
percurso **base → cidade**; o **custo intra-cidade** continua **por estimar** — era exatamente esse o
papel provisório que os 50 € desempenhavam. Registado em `especificacao_mvp.md` **§25.3**.

### F-07 · "3 imagens de categoria" — existem 7 ficheiros

**Afirmado em** §1.11 (E.3) e Q-45: *"`modules/common/img` tem só **3 de categoria** + 4 de equipa +
4 testemunhos"*.

**Prova:** em `modules/common/img` existem **7** `.png` elegíveis para categoria — `barbearia`,
`cabelereiro`, `estetica`, `manicure`, `massage`, `pedicure`, `skin-care` — além de `sb-logo`/
`sb-title` (marca), 4 `team-*` e 4 `testimonial-*`. O `serviceCategories.js` (L19-20) constrói o
caminho por *slug* → `/modules/common/img/<slug>.png`.

**Consequência:** a contagem **não** altera a recomendação (o *fallback* continua obrigatório para
os 35 serviços), mas o número está errado — e já foi usado para argumentar escassez de imagens.

---

## 3. O QUE FOI VERIFICADO E ESTÁ CERTO (não voltar a rever)

> Tudo abaixo foi medido **hoje**. Serve de atestado: não vale a pena despender leitura a
> reconfirmar estes pontos.

| Afirmação do planeamento                                      | Prova                                                     |
| :------------------------------------------------------------ | :-------------------------------------------------------- |
| `servico` tem **35** registos; só **1** com                   | `DataBase_v3.sql` L477 (bloco de 35 linhas; só a linha    |
| `requer_espaco_fisico = 1` (Limpeza Facial, id 34)            | do id 34 termina em `, 1)`)                               |
| **24 tabelas** na BD (valor que o documento cita)             | `CREATE TABLE` = 24 (v2 e v3); v1 = 21                    |
| `servico_foto` existe, está **vazia** e **não é usada**       | `url_foto`/`destaque`/`ordem_exibicao` + FK CASCADE;      |
| em código nenhum                                              | **0** referências em `*.php`/`*.js`                       |
| `funcionario.ativo` **já existe**                             | `DataBase_v3.sql` L333                                    |
| `utilizador` **não** tem `ativo` (Q-13)                       | tabela `utilizador` sem essa coluna                       |
| `transacao_financeira` só tem **recebimentos** (gap B.2/C-04) | `agendamento_id`/`funcionario_id` **NOT NULL**;           |
|                                                               | `tipo_transacao` = 4 valores de entrada                   |
| `rota_ambulante` tem `custo_estimado_combustivel`,            | colunas presentes em `DataBase_v3.sql`                    |
| `lucro_servicos`, `lucro_total`, `quota_parte_cliente`        |                                                           |
| `promocao` **não existe** (é ➕, não ✚)                      | 0 `CREATE TABLE promocao`                                 |
| `alerta_fiscal` tem `visualizado` e **não** tem               | divergência com §17.6 (L878) **real**, já assinalada      |
| `mensagem`/`lido`                                             |                                                           |
| Endpoints existentes: `admin-service-*` (4), `admin-fiscal-*` | `app/config/api.php` L34-46                               |
| (5), `booking-services`, `admin-appointment-cancel`           |                                                           |
| Endpoints **novos** (0 em `api.php`): `admin-alert-*`,        | confirmado; `admin-supplier-*` **previsto** em §19.5      |
| `admin-dashboard-summary`, `admin-accounting-balance`,        | (spec L1330)                                              |
| `admin-supplier-*`, `employee-*`                              |                                                           |
| `api.js` existe (P2)                                          | `modules/common/js/api/api.js`                            |
| `EmployeeService`/`EmployeeRepository` existem (✚)           | `app/services/` · `app/repositories/`                     |
| `SupplierService`, `AccountingService`, `PromotionService`,   | **0** ficheiros → ➕ correto                              |
| `DashboardService`, `AlertService`, `servicesSummary.php`     |                                                           |
| `/gestao` aponta para a lista de agendamentos (C-01)          | `index.php` → `modules/backoffice/appointments.php`       |
| Bootstrap **5.0.0**; jQuery 3.6.1; Owl/Wow/Lightbox/CounterUp | `modules/common/lib/`                                     |
| (base do P7 — não instalar gráficos)                          |                                                           |
| `services.php` `max="50"` + `services.js` `maxPrice: 50`      | `services.php` L28 · `services.js` L7                     |
| **289 verificações** = 105 + 119 + 65                         | executado hoje: `functional_test` **105 pass**,           |
|                                                               | `asset_test` **65 pass** (o 119 exige Apache)             |
| Todas as secções citadas existem                              | §4.6 · §5.1 · §10.6 · §12.4 · §14.1/2 · §15.3 · §17.6/8 · |
|                                                               | §18.x · §19.2/3/5 · §22.1/2/3 · §23.4 · §24.2/6 ·         |
|                                                               | §25.1-25.5 · §26.1-26.4 · §28.2 · §29.3                   |
| Citações pontuais corretas                                    | RF-63 (L395) · RF-13 (L340) · RN-03 (L415) ·              |
|                                                               | §25.4 dashboard (L1704) · §14.2 P-1…P-5 (L747-751) ·      |
|                                                               | §22.2 403 `admin-service-*` (L1469) ·                     |
|                                                               | `obrigacao_fiscal.tipo` enum (L394)                       |

---

## 4. O QUE FALTA REVER (se for preciso, noutra ronda)

1. **Textos do cliente que não estão no repositório** — os requisitos da 1.ª e da 2.ª iteração
   chegaram por Teams e **não são verificáveis** aqui. É a limitação de fundo desta auditoria:
   posso provar **o que está no código**; não posso provar o que o cliente escreveu fora dele.
2. **`http_test.php` (119)** — não re-executado (requer Apache + MySQL ativos). Os outros dois
   foram corridos e batem ao número.
3. **`serviceCategories.php`** — confirmar quantos **cards** renderiza (relevante para F-07, que
   provou a contagem de **ficheiros**, não a de cards).

---

**Ficheiro:** `mapaMentalMVP/auditoria_artefactos.md` · **branch:** `agent-workspace` ·
**Estado:** os **7 achados encerrados** (ver §1) ·
**Nota:** documento de apoio à decisão, **não normativo**; a autoridade é `especificacao_mvp.md`.
