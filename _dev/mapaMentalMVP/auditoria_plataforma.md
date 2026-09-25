# AUDITORIA DA PLATAFORMA — PLANEAMENTO, CÓDIGO E CRUZAMENTO

**Ficheiro de trabalho (não normativo)** · branch `agent-workspace` · **25/09/2026**

**O que é.** Auditoria do estado do projeto em três frentes: **(a)** o que o **planeamento** afirma
**sem prova**, **(b)** o que o **código** duplica ou contrária às convenções aplicadas e **(c)** o
**cruzamento** entre o planeamento, o estado real da plataforma e os requisitos registados.

**Proveniência (fusão).** Funde dois relatórios que **se cruzavam**: `auditoria_artefactos.md`
(afirmações do planeamento — casos **F-nn**) e `auditoria_convencoes_e_duplicacoes.md` (código — casos
**A-nn**). O **F-02** e o **A-03** são o **mesmo achado** visto de dois lados, e ambos partilham método
e público. Os originais foram **removidos**; o histórico é o **Git** (`_dev/docs/README.md` — doutrina 7).

**Autoridade:** `especificacao_mvp.md` + `_dev/docs/spec/` (normativo). Este documento **apoia a decisão**
e não a substitui.

**Método.** Cada linha tem **prova** — `ficheiro:linha` lida, contagem ou comando executado. Nada é
inferido. Onde a prova não existe, diz-se que não existe (§5).

**Onde está o quê:** §1 achados · §2 cruzamento com o estado real · §3 propostas em aberto ·
§4 atestado (não voltar a rever) · §5 limites desta ronda.

---

## 1. ACHADOS

### 1.1 Planeamento — afirmações sem prova (F-nn)

| #    | Artefacto afirmado                                       | Onde foi afirmado                | Estado                    |
| :--- | :------------------------------------------------------- | :------------------------------- | :------------------------ |
| F-01 | *"Renovação de contratos"* e *"revisões da carrinha"*    | §1.2 · §1.3 · §2.5 · Teams 18/19 | ✅ **removido**           |
| F-02 | `requireManager()` / `requireEmployee()`                 | §1.7 ponto 1                     | ✅ corrigido (e evoluído) |
| F-03 | `includes/boNavbar.php` (caminho inexistente)            | §1.3 (A.2)                       | ✅ corrigido              |
| F-04 | Prefixos `employee-*` vs `admin-employee-*`              | §1.7 · §1.9 · §1.10              | ✅ corrigido (1 família)  |
| F-05 | *"RF-75+"* mas IDs a começar em RF-80                    | §1.10                            | ✅ corrigido (RF-75…)     |
| F-06 | *"50 €"* com **dois** significados (custo vs referência) | §1.4 (B.1) · §12.2 da spec       | ✅ resolvido (custo fora) |
| F-07 | *"só 3 de categoria"* (imagens)                          | §1.11 (E.3) · Q-45               | ✅ corrigido + medido     |

**Os sete estão encerrados** e nenhuma correção regrediu:

- **F-01** — **removeu-se** em vez de reformular: a proveniência não existia no repositório (nem nos
  templates do cliente, nem em nenhuma das 24 tabelas) e o tema não faz sentido. Passou a **pergunta**
  ao cliente. *Lição:* requisito vindo de conversa e ausente do repositório **pergunta-se**, não se
  infere (`.clinerules` §0, regra 3).
- **F-02** — o planeamento apontava métodos **inventados** como se fossem o padrão do projeto. O método
  real é `Session::requireProfileApi([...])`. **Evoluiu em 25/09/2026:** a camada de conveniência que
  existia no `BaseController` foi **removida** (§1.2 · A-03), pelo que a afirmação *"só tem
  `getRequestData()`"* voltou a ser literalmente verdadeira.
- **F-06** — decidido pelo gestor: o **custo fixo** de 50 € saiu do código e de todos os documentos; o
  valor subsiste **apenas** como indicador **visual** (`REFERENCE_PROFITABILITY` · `meetsReference`).
- **F-07** — fechou por **medição**: **7** ficheiros de imagem de categoria **vs** **3** cards
  renderizados (**3** categorias na BD). Os dois números medem coisas diferentes (inventário vs uso) e o
  texto passou a dizer os dois.

### 1.2 Código — convenções, duplicações e inconsistências (A-nn)

| #    | Achado                                                                    | Onde                                                      | Estado                  |
| :--- | :------------------------------------------------------------------------ | :-------------------------------------------------------- | :---------------------- |
| A-01 | Bootstrap de sessão **duplicado** + `$_SESSION` directo                   | `services/OTPService.php`                                 | ✅ corrigido (evoluído) |
| A-02 | Guard de página **copiado em 5 páginas**                                  | `modules/backoffice/*.php`                                | ✅ corrigido            |
| A-03 | O **mesmo** guard de API com **dois nomes**, em duas classes              | `BaseController` · `ServiceController`                    | ✅ corrigido            |
| A-04 | Verificação manual e **redundante** de perfil                             | `services/AuthService.php` L41                            | ✅ corrigido            |
| A-05 | Repository **sem Mapper** (o único de 18) → devolvia chaves PT            | `repositories/BookingPersonRepository.php`                | ✅ corrigido            |
| A-06 | `INSERT INTO utilizador` **duplicado** + `password_hash` na camada errada | `ManagerRepository` vs `UserRepository`                   | ✅ corrigido            |
| A-07 | Mapas de estado **duplicados** no cliente                                 | `bo.utils.js` · `main/…/appointments.js`                  | ✅ corrigido            |
| A-08 | Exceções **sem código HTTP** (cairiam em 500)                             | `ExecutionService` · `ServiceAcceptanceService`           | ❌ **retirado**         |
| A-09 | Chamada directa a serviço externo, fora do `apiClient`                    | `common/js/utils/addressAutocomplete.js`                  | ✅ corrigido            |
| A-10 | Id do utilizador por desestruturação ad hoc                               | `GreenReceiptService` · `RotaService`                     | ✅ corrigido            |
| A-11 | **Métodos sem uso** (código morto com manutenção garantida)               | `ManagerService::findManager` · `ManagerRepository::find` | 📌 assinalado           |
|      |                                                                           | · `BookingPersonRepository::findByIdAndBooking`           |                         |
| A-12 | Teste de sintaxe JS com **lista fixa** (não descobre ficheiros novos)     | `_dev/tests/js_syntax_check.php`                          | ✅ corrigido (15→17)    |

**Gravidade (à data do achado):** 🔴 quebrava convenção aplicada · 🟠 duplicação real · 🟡 inconsistência ·
⚪ cosmético.

**A-08 foi um falso positivo** — as 3 exceções **têm** o código **409**, escrito na **linha seguinte**;
o varrimento inicial era de linha única. Registado como armadilha em
`_dev/docs/rules/build_report_on_demand.md` §3 (varrer com **contexto multi-linha**).

**Duas evoluções posteriores (25/09/2026), ambas por decisão do gestor:**

1. **A-03 · A-01 aprofundados.** O `BaseController` **perdeu** `requireProfile()` e `requireCustomer()`:
   eram uma camada de conveniência num ficheiro cujo único assunto é o **transporte do pedido**. As
   **15 chamadas** em 5 controllers passaram a falar com o `Session` directamente. Pela mesma razão o
   `Session` **perdeu** `get`/`set`/`forget` (acesso genérico a chaves de sessão, **um só** consumidor):
   ficou `Session::start()` e a chave do OTP voltou ao `OTPService`, que é quem a conhece. Regra
   atualizada em `.clinerules` §2 (v3.4). **Comportamento inalterado** (401/403 iguais).
2. **A-11 com a natureza corrigida.** O `ManagerService::findManager` **não** é uma camada sobre o
   `Session` — é uma **leitura de domínio** à BD (`ManagerRepository::find` filtra
   `tipo_perfil = 'gestor'`, L10-23). É, isso sim, **código sem uso**. Decisão em **§3**.

> **Nota de método (armadilha medida):** o `search_codebase` **não indexa `app/`** — varrer convenções
> só com ele dá "0 resultados" **falsos**. As varreduras desta auditoria usaram `Select-String` sobre a
> árvore real (`app/`, `modules/`, `index.php`).
---

## 2. CRUZAMENTO — PLANEAMENTO × ESTADO × REQUISITOS

### 2.1 Estado real da plataforma (medido)

| Frente                                                        | Estado                | Prova                                                    |
| :------------------------------------------------------------ | :-------------------- | :------------------------------------------------------- |
| Fases **1–5** (catálogo, wizards, backoffice, fiscal, testes) | ✅ concluídas         | `delivery.md` §21                                        |
| **Fase 6** — requisitos adicionais (§24)                      | ⬜ a iniciar          | `backlog.md` §24 — os 6 blocos estão 🟡/⬜               |
| **Fornecedores** (§25.1 — *prioridade máxima*)                | ⬜ ausente            | 0 ficheiros `Supplier*` · 0 endpoints `admin-supplier-*` |
| Módulos do `analise`: Resumo, Contabilidade, RH, Promoções    | ⬜ ausentes           | 0 ficheiros `Dashboard*`/`Accounting*`/`Promotion*`      |
| **RF-75+…RF-97 · RN-30+ · D-12+** (requisitos propostos)      | ⬜ **não registados** | `analise` §1.10: *"nada foi escrito na especificação"*   |
| **Decisões** (31 itens, C-01…C-30)                            | ⬜ todas abertas      | `analise` §3.1 — checklist inteira ⬜                    |

### 2.2 Achados do cruzamento (X-nn)

> Estes são **novos** nesta auditoria: resultam de pôr o planeamento, o estado do código e os requisitos
> **lado a lado**. Nenhum foi corrigido — exigem **decisão** (§3).

| #    | Achado                                                                                                                       |
| :--- | :--------------------------------------------------------------------------------------------------------------------------- |
| X-01 | **Ordem invertida.** O `analise` propõe **4 módulos novos** (Resumo, Contabilidade, RH, Promoções), mas a **prioridade       |
|      | máxima** declarada é **Fornecedores** (§25.1, ⬜) — e o próprio card *"Dívidas a Fornecedores"* depende dela (**C-11**,      |
|      | cuja recomendação é *"Fornecedores antes da contabilidade completa"*). O quadro §1.2 **não reordena** nada.                  |
| X-02 | **Dois desenhos concorrentes para a mesma notificação.** §24.6 (ponto 5) planeia o **lembrete ao cliente** (≤ 24 h) como     |
|      | requisito **CRÍTICO** da Fase 6; o `analise` §1.3 + **C-21** propõem um **"sininho"** que o engloba. Nenhum aponta o         |
|      | outro: se a Fase 6 for feita primeiro, o sininho nasce já a duplicar.                                                        |
| X-03 | **Requisitos fora da especificação.** ~20 requisitos existem **só** no `analise`. Por §29.3 (requisito entra na spec         |
|      | **antes** do código), a Fase 6+ **não tem requisitos normativos** e o ciclo §29.3 ainda não foi aberto.                      |
| X-04 | **Tabelas novas sem justificação registada.** O `analise` implica ➕ (`despesa`+`fornecedor`+`fatura_fornecedor`,            |
|      | `promocao`+`promocao_contexto`+`promocao_servico`, `emprestimo`, `conta_bancaria`, `config_fiscal`, eventual `notificacao`)  |
|      | sobre as **24** atuais. `.clinerules` §1 proíbe alterar a BD **sem justificação registada na especificação** — nenhuma       |
|      | está registada.                                                                                                              |
| X-05 | **Contradição interna do `analise`.** §1.2 apresenta `alerta_fiscal.visualizado` como ✚ *reutilizável*, mas **C-03** mostra |
|      | que a coluna é **global** (não por utilizador). O quadro-resumo não sinaliza o conflito que o próprio documento identifica.  |
| X-06 | **Conflitos duplicados.** **C-09** e **C-22** são o **mesmo** conflito (ocultar módulos financeiros ao Funcionário). Com     |
|      | C-02 · C-03 · C-21 (avisos/notificações), são **5 conflitos para 2 temas** — a checklist §3.1 pede 31 decisões onde          |
|      | talvez baste menos.                                                                                                          |
| X-07 | **C-30 corrige C-19 e Q-43.** A afirmação *"`.xlsx` exigiria biblioteca externa"* é **falsa** (`zlib`+`gzinflate` leem o     |
|      | ZIP; medido 12/12 e 15/15 entradas). Uma decisão de arquitetura (formato de importação) assentou em pressuposto errado.      |
| X-08 | **Contagens de teste desatualizadas.** **C-13** fala de *"289 verificações"*; hoje são **105** + **119** + **66** = **290**, |
|      | com o `http_test` a **118/1** (falha de dados de ambiente, não de código). §26.1 / §28.1 ponto 10 dizem *"289 a passar"* —   |
|      | deixou de ser exato.                                                                                                         |
| X-09 | **Risco de dupla contagem reconhecido.** **C-20** + §2.7 (origem dos números) e §25.3: a plataforma já produz vendas e       |
|      | custos internamente; o `analise` propõe ler o balancete **por célula**. É coerente — **desde que** a origem de cada          |
|      | número fique decidida (D-12+). Sem isso, Contabilidade nasce ambígua.                                                        |
| X-10 | **"50 €": encerrado e coerente.** Todas as ocorrências que restam na spec são o **indicador visual** (RN-05 ·                |
|      | `meetsReference` · `booking.md` §12.2) ou a **nota de remoção** do custo (`backlog.md` §25.3). **Sem contradição             |
|      | residual** — os vestígios são deliberados e verdadeiros.                                                                     |
| X-11 | **Ferramenta de verificação morta (achado colateral).** O `_dev/tools/widthcheck.php` tinha `require` com **caminho          |
|      | absoluto** pré-move da umbrella → não corria, e o `health-check` **reportava "tudo nivelado"** (crash não casava a           |
|      | expressão). **Corrigido** e o ponto cego fechado; `md-widths` re-verificado: **0 problemas em 27 ficheiros**.                |
---

## 3. PROPOSTAS EM ABERTO

> Propostas que **não** foram implementadas. Ficam aqui para não se perderem, com o que as decide.
> As que exigem **decisão de produto** estão marcadas 🧑; as técnicas 📐.

| #    | Proposta                                                                                                    | Decide |
| :--- | :---------------------------------------------------------------------------------------------------------- | :----- |
| P-01 | **A-11 — remover ou manter os métodos sem uso.** `ManagerService::findManager` + `ManagerRepository::find`  | 🧑     |
|      | e `BookingPersonRepository::findByIdAndBooking`. **Se** o módulo de RH (`analise` §1.5) passar a listar     |        |
|      | **gestores**, o par *findManager/find* ganha uso e deve ficar; senão é manutenção garantida a zero. O       |        |
|      | `findByIdAndBooking` já tem nota a dizer o mesmo (L33-37).                                                  |        |
| P-02 | **Ordem de implementação (X-01/X-02).** Fechar a **Fase 6 (§24)** primeiro, ou aceitar os 4 módulos novos   | 🧑     |
|      | como *"Fase 6 acrescida"* (**C-12**)? Em qualquer caso: **Fornecedores antes da Contabilidade** (§25.1 +    |        |
|      | C-11) e **uma só** decisão para notificações (X-02).                                                        |        |
| P-03 | **Registar os requisitos (X-03).** Levar `RF-75+`/`RN-30+`/`D-12+` à especificação (§29.3) **antes** de     | 🧑     |
|      | implementar. Sem isto, os módulos novos não têm requisitos normativos.                                      |        |
| P-04 | **Justificar as tabelas novas (X-04).** Cada ➕ do `analise` precisa de registo na especificação            | 🧑     |
|      | (`.clinerules` §1). Alternativa já recomendada em **C-29/C-30**: ler o balancete por **mapa de células** e  |        |
|      | não importá-lo para tabelas.                                                                                |        |
| P-05 | **Consolidar a checklist de decisões (X-06).** C-09/C-22 são **um** conflito; C-02/C-03/C-21 são **um**     | 📐     |
|      | tema. A checklist §3.1 pede 31 decisões onde talvez bastem menos.                                           |        |
| P-06 | **A-12 — descoberta automática no `js_syntax_check`.** A lista fixa passou de 15 → **17** ficheiros, mas    | 📐     |
|      | continua a **não descobrir** ficheiros novos (foi assim que ficou cego). Varrer `modules/**/js/**/*.js`.    |        |
| P-07 | **Atualizar as contagens nos documentos (X-08).** *"289 verificações"* → **290** (105+119+66), com o        | 📐     |
|      | `http_test` a **118/1** por dados de ambiente. Toca `delivery.md` §26/§28, `_dev/tests/README.md`,          |        |
|      | `README.md` e `annex.md`.                                                                                   |        |
| P-08 | **O que resta do A-09.** O geocoding está centralizado; continua em aberto o seu uso para **estimar o       | 🧑     |
|      | custo intra-cidade** (§25.3) — era esse o papel provisório dos 50 € fixos.                                  |        |
| P-09 | **L307 do `style.css`.** O espaço entre os blocos do menu vem de `gap-lg-2` no `<ul>` (Bootstrap 5.0.0,     | 📐     |
|      | ≥ 992 px), **não** de `margin`. Correção: `gap-lg-0` (ou `gap: 0` no pai). Diagnosticado, **não** aplicado. |        |

---

## 4. ATESTADO — VERIFICADO E CERTO (não voltar a rever)

> Medido em **23–25/09/2026**. Serve para não se despender leitura a reconfirmar.
> **Números atualizados** nesta revisão — as contagens anteriores (289 · 65 · 22 ficheiros) estavam
> corretas **à data** e ficaram desatualizadas por alterações medidas.

| Afirmação                                                                   | Prova                                                                |
| :-------------------------------------------------------------------------- | :------------------------------------------------------------------- |
| **Suites:** `functional_test` **105/0** · `asset_test` **66/0** ·           | executadas em 25/09/2026                                             |
| `http_test` **118/1** (falha de **dados**, não de código)                   | conta real com morada na BD — ver §5.1                               |
| **`asset_test` voltou a correr** (66 checks)                                | faltava uma **vírgula** no array (L59-60) — corrigida no `dev`       |
| **Gate:** `md-verify` **TUDO OK (27 ficheiros)** · pipeline **idempotente** | `_dev/tools/health-check.php` → **TUDO OK**                          |
| `js_syntax_check` **OK** com **17** ficheiros                               | lista fixa (A-12)                                                    |
| `servico` tem **35** registos; só **1** com `requer_espaco_fisico = 1`      | `DataBase_v3.sql` L477 (só o id 34 termina em `, 1)`)                |
| **24 tabelas** na BD (v2 e v3); v1 = 21                                     | `CREATE TABLE`                                                       |
| `servico_foto` existe, está **vazia** e **sem uso** em código               | 0 referências em `*.php`/`*.js`                                      |
| `funcionario.ativo` **existe**; `utilizador` **não** tem `ativo`            | `DataBase_v3.sql` L333                                               |
| `transacao_financeira` só tem **recebimentos** (**gap** B.2 / C-04)         | `agendamento_id`/`funcionario_id` **NOT NULL**                       |
| `promocao` **não existe** (é ➕, não ✚)                                    | 0 `CREATE TABLE promocao`                                            |
| **0** endpoints novos em `api.php`: `admin-alert-*` · `admin-supplier-*` ·  | `app/config/api.php` L34-46 · `admin-supplier-*` previsto em §19.5   |
| `admin-employee-*` (F-04 aplicado)                                          |                                                                      |
| Sem SQL fora dos repositories · corpo do pedido centralizado                | `->prepare(`/`SELECT ` em `services/`+`controllers/` = **0** ·       |
|                                                                             | `php://input` ocorre **só** em `BaseController`                      |
| *Hashing* da password **num só sítio** (depois de A-06)                     | **uma** chamada real: `UserService:38`                               |
| **17 de 18** repositórios com mapper concreto (+1 `null` por desenho)       | varrimento de `protected ?string $mapper`                            |
| `escapeHtml`/`formatCurrency`/`slugify` **não** reimplementados             | definidos só em `generalUtils`                                       |
| Transporte HTTP centralizado                                                | `$.ajax` só em `apiClient.js` (+ libs de terceiros)                  |
| Os **4** `validateInput` **não** duplicam lógica                            | cada um valida o **seu** domínio e compõem-se (ver §5.3)             |
| **62** exceções com código coerente: 422×26 · 404×14 · 409×11 · 403×4 ·     | as **2** sem código são **legítimas**: `BaseRepository` L12 (fatal   |
| 500×2 · 405×1 · 401×1 · sem código **2**                                    | de ligação) · `ValidationException` → convertida em **422**          |
| `api.php` honra o código da exceção                                         | `>= 400 && <= 599 ? getCode() : 400`                                 |
| Transações e validação centralizadas                                        | `executeTransactional` em `BaseService` · **9** services usam        |
|                                                                             | `validate()`                                                         |
| **A-11 (natureza):** `ManagerRepository::find` **é** uma leitura de domínio | `WHERE u.tipo_perfil = 'gestor'` (L10-23) — **não** é camada sobre o |
|                                                                             | `Session`; é apenas **sem uso** (ver §1.2)                           |

---

## 5. LIMITES DESTA RONDA

1. **Textos do cliente fora do repositório.** Os requisitos da 1.ª e 2.ª iteração chegaram por Teams e
   **não são verificáveis** aqui — é a limitação de fundo. Posso provar **o que está no código**; não
   posso provar o que o cliente escreveu fora dele. Os anexos (`Menu APOIO 3.docx`, `CUSTOS RH 2.xlsx`)
   são **binários**: só o conteúdo parafraseado é citável.
2. **Não avaliado:** qualidade funcional, desempenho, segurança ofensiva, acessibilidade e CSS (fora do
   diagnóstico pontual **P-09**). `admin/` fora por instrução (`.clinerules` §1).
3. **Duplicação semântica.** O detetor compara **nomes** e **texto** de blocos idênticos; duplicações com
   nomes diferentes e corpos distintos podem escapar — nomeadamente entre os **4** `validateInput`
   (`CustomerAddressService` · `CustomerService` · `EmployeeService` · `UserService`), que **não** foram
   comparados regra a regra. Ronda seguinte, se for pedida.

---

**Ficheiro:** `_dev/mapaMentalMVP/auditoria_plataforma.md` · **branch:** `agent-workspace`
**Estado:** F-01…F-07 e A-01…A-12 **encerrados** (A-08 **retirado**) · X-nn **novos, por decidir**
**Itens abertos:** §3 (**P-01…P-09**)
**Origem:** fusão de `auditoria_artefactos.md` (F-nn) + `auditoria_convencoes_e_duplicacoes.md` (A-nn)
**Nota:** documento de apoio à decisão, **não normativo** — a autoridade é `especificacao_mvp.md`
(router) + `_dev/docs/spec/`.
