# Especificação — Entrega: roadmap, limitações, testes, instalação

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../../especificacao_mvp.md).
> Capítulos: §21 · §22 · §26 · §27 · §28

## 21. ROADMAP POR FASES E ESTADO

> O **cronograma de 7 dias** dos documentos iniciais está **revogado** — substituído por este
> roadmap por fases, alinhado com as regras finais.

| Fase                                | Âmbito                                                                                                                                                  | Estado                |
| :---------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------ | :-------------------- |
| **1 — Catálogo e base**             | Catálogo de serviços (filtros, modal), categorias, autenticação, registo, perfil/moradas, preloader, validators                                         | ✅ CONCLUÍDA          |
| **2 — Agendamentos**                | Wizard **Loja** (5 passos) + Wizard **Carrinha** (7 passos + OTP), disponibilidade, conflitos, página de sucesso                                        | ✅ CONCLUÍDA          |
| **3 — Backoffice do Funcionário**   | Aceitação individual, desfazer/trocar, consolidação, bloqueio de janela, **Simulador de Recibos Verdes**                                                | ✅ CONCLUÍDA          |
| **4 — Backoffice do Gestor**        | Agendamentos (filtros, detalhe por serviço/funcionário, execução, cancelamento), **Rotas com decisão manual** (+50 € visual),                           | ✅ CONCLUÍDA          |
|                                     | **Calendário Fiscal** + alertas, config. de recibos verdes                                                                                              |                       |
| **5 — Integração e testes**         | Fluxos end-to-end (cliente → funcionário → gestor), responsividade, notificações simuladas, **430 verificações**                                        | ✅ CONCLUÍDA          |
| **6 — Requisitos adicionais (§24)** | Página de detalhes + carousel; re-avaliação dinâmica de slots; 24 h + lembrete + cancelamento pelo cliente; multicidades + alerta de custos; config. do | 🟡 **EM CURSO** (§24) |
|                                     | sinal; 10/90 + métodos de pagamento                                                                                                                     |                       |
|                                     | **✅ feito:** slots revalidados (§24.1) · cancelamento pelo cliente (§24.6) · autocomplete (§24.8);                                                     |                       |
|                                     | **painel do gestor** `/gestao` com KPIs e gráficos (Chart.js local) + **agenda do funcionário** + **avisos/sinho** + **sidebar** + **comissões**        |                       |
|                                     | + **fornecedores** (§24.7 · §25.1 · RF-77/78/80/81/84/85) + regras das rotas (RN-31/32/34);                                                             |                       |
|                                     | **⬜ falta:** 6.2 contabilidade (RF-75/76/79) · 6.3 RH (RF-82 · §24.9) · 24 h + lembrete · sinal 10/90 · detalhe de serviço · multicidades · 8 famílias |                       |
|                                     | fiscais                                                                                                                                                 |                       |
| **7 — Promoções**                   | Módulo de **promoções e campanhas** — adiado; avaliar antes o risco de **retro-atualização** de histórico e agendamentos passados (§24.7)               | ⬜ adiada             |

> **Já feito dentro da Fase 6:** a **carga dos dados reais** entregues pelo cliente
> (`database_migration_v4.sql` — durações, 43 fornecedores, 65 clientes) — **RF-86** ✅ · §24.11.
> É a base de dados do módulo de **fornecedores**, que é a prioridade 1 do trabalho futuro (§25.1).
> **Chart.js (E-3):** entra na **Fase 6**, junto com os gráficos do dashboard — **não** fica para depois.
> A biblioteca continua **local** em `modules/common/lib/chartjs/` (nunca por CDN) e **só** carregada no
> backoffice. A **sidebar** do backoffice e a página das **comissões** entram **também na Fase 6** (§25.5);
> `/gestao/agendamentos` **mantém o nome** — a renomeação de rotas **não** faz parte desta fase.

### 21.1 Entregáveis

1. **Código-fonte completo** (`app/`, `modules/`, `index.php`, assets)
2. **Base de dados**: `DataBase_v2.sql` + `database_seed.sql` (+ migrações `v2`/`v3`) — ordem em §27
3. **Documentação**: `especificacao_mvp.md` (mestre) + `_dev/docs/spec/` (por domínio) + `README.md`
   + `_dev/docs/` (regras e moldes on-demand)
4. **Diagrama de BD**: §17.8 (relações + consulta SQL para regenerar)
5. **Testes automatizados** em `_dev/tests/` — 430 verificações (§26)

## 22. SIMPLIFICAÇÕES ACADÉMICAS E LIMITAÇÕES

### 22.1 Simplificações aceites (âmbito do MVP)
| Área                          | Simplificação do MVP                                                                      |
| :---------------------------- | :---------------------------------------------------------------------------------------- |
| **Pagamentos**                | **Simulados** — sem gateway real; campo `sinal_pago = 0`                                  |
| **SMS / Email**               | **Simulados** (através de log ou `alert()`); sem integração com APIs de envio real        |
| **OTP**                       | Código **mostrado diretamente no ecrã**, validado na sessão                               |
| **Rotas**                     | Validação **manual** por parte do gestor (sem necessidade de execução por CRON)           |
| **Recibos verdes**            | **Simulador de cálculo** — sem comunicação real ou emissão na Autoridade Tributária ou SS |
| **Alertas fiscais**           | Geração **on-demand** (sem agendamento por CRON), totalmente idempotente                  |
| **Feedback**                  | **Público e automático sem moderação** prévia                                             |
| **Fecho de caixa / gorjetas** | Estruturas de dados existentes na BD, mas **sem interface de utilizador (UI)** no MVP     |

### 22.2 Limitações conhecidas (**não são defeitos**)
- **Passo "Profissional"** no wizard de loja é **informativo** — a BD não associa funcionários a slots.
- **`quota_parte_cliente`** existe mas fica a **0 €** — sem regra de cálculo definida; não é cobrada.
- **Categorias** nunca restringem a aceitação (**por decisão**, não por limitação).
- **Backoffice em `modules/backoffice/`** e não em `admin/` (instrução de não tocar em `/admin`).
- **Registo de morada exige escolher uma sugestão do Nominatim** → requer **internet**.
- Apenas as **10 cidades do distrito de Évora** são aceites (regra de negócio).
- **Sem upload de foto de perfil** (usa *placeholder*).
- **O perfil é de leitura** (dados pessoais não editáveis no MVP) + **CRUD completo de moradas**
  (criar, definir principal, remover). Não existe limite de moradas nem edição de morada existente
  (cria-se uma nova e remove-se a antiga).
- O filtro de agendamentos do cliente **não permite filtrar por data** (não especificado).
- **`recusado`** existe no enum mas o fluxo grava `cancelado` (valor legado do schema).
- O **gestor abre** `/gestao/servicos` (supervisão), mas as **APIs `admin-service-*` recusam-lhe**
  aceitar (403) — comportamento pretendido.
- A **equipa** não é associada a *slots*: a capacidade é gerida por conflito de janela, não por nº de
  funcionários (§10.6).

### 22.3 Fora de escopo (declarado)
OTP real por SMS · gateway de pagamento real · CRON automático · app móvel nativa ·
pasta raiz `admin/` · **motorista dedicado / logística de condução** (explicitamente excluído — D-08).

## 26. TESTES E VALIDAÇÃO

### 26.1 Suites automatizadas — **430 verificações** (pré-requisito de BD em `_dev/tests/README.md`)

| Suíte de Testes                  | Verificações | Âmbito Coberto Principal                                                                                                                                |
| :------------------------------- | :----------- | :------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `_dev/tests/functional_test.php` | **153**      | Camadas Service/Repository: catálogo, disponibilidade, conflitos, OTP, decisão manual de rotas, backoffice, perfil/moradas, Fase 3/4, transações de     |
|                                  |              | registo, painel/avisos/agenda, fornecedores, comissões e integridade relacional                                                                         |
| `_dev/tests/http_test.php`       | **179**      | Stack real (Apache + roteamento + sessões): autenticação de perfis, APIs REST, códigos de erro, fluxo end-to-end de carrinha, cancelamento pelo cliente |
|                                  |              | e fluxos de registo/login                                                                                                                               |
| `_dev/tests/asset_test.php`      | **98**       | Validação de assets (HTTP 200), injeção de scripts por página e contrato de nomes do formulário de registo                                              |
| `_dev/tests/js_syntax_check.php` | 26 ficheiros | Verificação estrutural e de sintaxe de todos os ficheiros JavaScript do ecossistema                                                                     |

**Execução:** comandos, pré-requisitos por suite (Apache e MySQL) e garantias de repetibilidade em
**`_dev/tests/README.md`** — fonte única dos testes. Esta secção guarda o **registo de validação**
(âmbito e contagens), que é o que a §28 cita.

### 26.2 Propriedades das suites
- **Repetíveis:** limpam os próprios dados (agendamentos, rotas, execuções, feedbacks, fiscal,
  utilizadores E2E) no início/fim.
- **End-to-end onde importa:** registo e login dos 3 perfis (cliente, funcionário, gestor) estão
  cobertos de ponta a ponta.
- **Guard de contrato:** o `asset_test` falha se reaparecer uma chave em português no registo.
- **Validações complementares, já executadas:** `php -l` em **todos** os ficheiros PHP (0 erros) e
  aplicação das migrações/seed em MySQL 8.4.3 sem erros.

### 26.3 Testes que provam decisões-chave
| Decisão Arquitetural / de Negócio       | Prova / Mecanismo de Validação Técnica                                                                                                             |
| :-------------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Rotas são manuais**                   | Aprovação de rota abaixo de 50 € define como `confirmado`; recusa acima de 50 € define como `cancelado`                                            |
| **Categorias não restringem**           | Tabela `funcionario_categoria` eliminada da BD; aceitação de serviços é livre entre funcionários                                                   |
| **Consolidação bloqueia**               | Tentar desfazer um agendamento após estar consolidado resulta num erro HTTP **409**                                                                |
| **Custos de pessoal e rácios conferem** | Bateria de conferência contra o Balancete: folha de salários ↔ conta **63 = 27 234,13** e os **5 rácios** ↔ Balanço de março — §3.12 linhas 8 a 12 |
| **OTP é obrigatório e de uso único**    | Código inválido retorna **422**; tentativa de reutilização do código falha imediatamente                                                           |
| **Feedback é único e pós-execução**     | Submissão duplicada ou antes da execução do serviço resulta num erro HTTP **409**                                                                  |
| **Perfis de acesso são respeitados**    | Retorno de **401** sem sessão, **403** para perfis incorretos e redirecionamento automático nas páginas                                            |

### 26.4 Cobertura em falta (a acrescentar com o resto da Fase 6)
Testes end-to-end para o que ainda falta da **§24** quando for implementado
(24 h + auto-cancelamento + lembrete, sinal configurável + 10/90 + métodos de pagamento,
multicidades + alerta de custos, página de detalhe de serviço com carousel) e para os módulos
6.2 contabilidade e 6.3 RH.

**Estratégia:** manter a cobertura end-to-end no **registo e login** e continuar a fazer crescer os
testes *server-to-end* à medida que as restantes funcionalidades estabilizarem.

**Cobertura acrescentada em 28/09/2026 (Fase 6.0/6.1/6.4 · §24.1 · §24.6):**
`functional_test` — painel (KPIs/séries/estado vazio da contabilidade), avisos por perfil (com e sem
sessão de gestor), agenda (mês, filtro `rotas_confirmadas`, RN-33), fornecedores (CRUD, validações,
desativação, 404), comissões (snapshot da aceitação, 70/30, abrangência por perfil) e cancelamento
pelo cliente (posse, 409, 404). `http_test` — endpoints novos e autorizações (403/401), RN-31 (**409**
antes da aceitação, agregação só depois), RN-34 (`bookingIds`), `/gestao` por perfil e páginas novas.
`asset_test` — assets novos (Chart.js local, `form.utils`, validadores), injeção por página e
elementos-chave do backoffice (sidebar, sino, calendário, formulário de fornecedores).

## 27. INSTALAÇÃO E IMPORTAÇÃO DA BD

### 27.1 Pré-requisitos
Laragon com **Apache + MySQL** ativos · projeto em `C:\laragon\www\secade-beauty-tarde` ·
**internet** (o autocomplete de morada usa a API Nominatim) · browser com DevTools.

### 27.2 Importação (instalação de raiz) — 2 ficheiros + migrações de dados
```powershell
$mysql = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'
cd C:\laragon\www\secade-beauty-tarde

# 1) Esquema completo + catálogo  (⚠️ APAGA a base secade_beauty existente)
& $mysql -u root --default-character-set=utf8mb4 -e "source DataBase_v2.sql"

# 2) Utilizadores de teste + morada de demonstração   ← OBRIGATÓRIO
& $mysql -u root --default-character-set=utf8mb4 -e "source database_seed.sql"

# 3) Dados REAIS entregues pelo cliente (durações · 43 fornecedores · 65 clientes)
& $mysql -u root --default-character-set=utf8mb4 -e "source database_migration_v4.sql"
```
> ⚠️ Em **PowerShell** a redireção `<` não funciona — usar sempre `-e "source ficheiro.sql"`.
> Alternativa: painel do Laragon → phpMyAdmin → *Import*.
> **Não** são precisas as migrações v2/v3: o `DataBase_v2.sql` já inclui tudo o que elas fazem.
> O passo **3** é **dados reais de cliente** (§24.11), não demonstração — é o que dá conteúdo à gestão de
> **fornecedores** (§25.1) e às **durações** reais do catálogo. É **idempotente**: pode repetir-se.

### 27.3 Migração de uma BD antiga (preserva dados)
```powershell
& $mysql -u root --default-character-set=utf8mb4 -e "source database_migration_v2.sql"  # só se BD v1
& $mysql -u root --default-character-set=utf8mb4 -e "source database_migration_v3.sql"  # idempotente
& $mysql -u root --default-character-set=utf8mb4 -e "source database_migration_v4.sql"  # idempotente
& $mysql -u root --default-character-set=utf8mb4 -e "source database_seed.sql"          # opcional
```
- `database_migration_v2.sql` é de **uso único** (falha com `Duplicate column` se repetido).
- `database_migration_v3.sql` e `database_migration_v4.sql` são **idempotentes**
  (a v4 usa ids explícitos + `ON DUPLICATE KEY UPDATE`) e podem correr em qualquer schema.
- A **v4** traz **dados reais** (§24.11): durações, `fornecedor` (43) e clientes (65) — em produção é
  **obrigatória**, numa instalação de demonstração é opcional.

### 27.4 Confirmar a importação
```sql
USE secade_beauty;
SELECT
 (SELECT COUNT(*) FROM information_schema.tables
   WHERE table_schema = 'secade_beauty')        AS tabelas,        -- esperado: 25 (24 + fornecedor)
 (SELECT COUNT(*) FROM servico)                 AS servicos,       -- esperado: 35
 (SELECT COUNT(*) FROM servico WHERE ativo = 1) AS servicos_ativos,-- esperado: 35
 (SELECT COUNT(*) FROM categoria_profissional)  AS categorias,     -- esperado: 3
 (SELECT COUNT(*) FROM cidade)                  AS cidades,        -- esperado: 10
 (SELECT COUNT(*) FROM matriz_deslocacao)       AS deslocacoes,    -- esperado: 9
 (SELECT COUNT(*) FROM utilizador)              AS utilizadores,   -- 3 (só seed) ou 68 (com a v4)
 (SELECT COUNT(*) FROM cliente_morada)          AS moradas,        -- 1 (só seed) ou 66 (com a v4)
 (SELECT COUNT(*) FROM fornecedor)              AS fornecedores;   -- esperado: 43 (só com a v4)
```
> ⚠️ **Diagnóstico rápido:** **24 tabelas + catálogo completo** mas **0 utilizadores** = importou o
> esquema **sem** o `database_seed.sql`. Basta correr o passo 2 de §27.2 (não é preciso reimportar).
> Consequência: não consegue fazer login e os testes HTTP falham com *foreign key* em
> `agendamento.cliente_id` (falta o cliente de teste #3).
> ⚠️ **Esperados diferentes conforme a BD:** numa instalação **de demonstração** (sem o passo 3) são
> **3 utilizadores · 1 morada · 0 fornecedores**; com a **v4** (§24.11) são **68 utilizadores ·
> 66 moradas · 43 fornecedores** e **25 tabelas**.

### 27.5 Configuração e acesso
- Conexão: `app/config/connection.php` (default: `localhost`, `root`, sem password, DB `secade_beauty`).
- Acesso: **`http://localhost/secade-beauty-tarde`**
- **Credenciais de demonstração** (criadas por `database_seed.sql`):

| Perfil          | E-mail de Teste         | Password de Teste | Destino por Omissão Pós-Login |
| :-------------- | :---------------------- | :---------------- | :---------------------------- |
| **Gestor**      | `gestor@secade.pt`      | `Gestor@123`      | `/gestao/agendamentos`        |
| **Funcionário** | `funcionario@secade.pt` | `Func@12345`      | `/gestao/servicos`            |
| **Cliente**     | `cliente@teste.pt`      | `Cliente@123`     | `/`                           |

- O cliente de teste (#3) tem **1 morada** pré-criada (Évora, Rua de Aviz).

### 27.6 Reset de dados para testes repetíveis
```sql
USE secade_beauty;
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM feedback_cliente;
DELETE FROM execucao_agendamento;
DELETE FROM transacao_financeira;
DELETE FROM gorjeta;
DELETE FROM agendamento_servico;
DELETE FROM agendamento_pessoa;
DELETE FROM agendamento;
DELETE FROM rota_ambulante;
DELETE FROM alerta_fiscal;
DELETE FROM obrigacao_fiscal;
DELETE FROM cliente_morada WHERE cliente_id = 3 AND id <> 1;
DELETE FROM config_recibo_verde WHERE id > 1;
SET FOREIGN_KEY_CHECKS = 1;
-- esperado: agendamentos=0, rotas=0, obrigacoes=0, feedbacks=0, moradas=1
```

## 28. CRITÉRIOS DE ACEITAÇÃO

### 28.1 Estado atual (MVP entregue)
1. ✅ Cliente faz agendamento em **loja** sem morada e sem pessoas; serviços **automaticamente
   aceites**, pendentes de validação logística.
2. ✅ Cliente faz agendamento de **ambulatório** com morada, OTP e **estrutura por pessoa** com
   serviços partilhados; a duração reflete o agrupamento.
3. ✅ Funcionário **aceita individualmente**, desfaz/troca enquanto não consolidado, e vê o
   **simulador de recibos verdes** na aceitação.
4. ✅ Ao ser aceite o **último serviço**, o agendamento fica *totalmente aceite* e **bloqueia a
   concorrência na janela temporal**.
5. ✅ Gestor consulta agendamentos com filtros e **detalhe por serviço/funcionário**.
6. ✅ Gestor analisa rotas por dia+cidade com custos/lucros, vê os **50 € apenas como referência** e
   decide **manualmente** (aprovar/recusar).
7. ✅ **Calendário fiscal** centralizado com alertas 30/15/7/3/1/atraso para IVA, IRC, SS e Seguros.
8. ✅ **Nenhum acesso do cliente ao backoffice**; perfis respeitados em todos os endpoints `admin-*`.
9. ✅ **Feedback do cliente** após execução, com reflexo público nos testemunhos.
10. ✅ Validações em client e server; **testes automatizados a passar** (430).

### 28.2 Critérios da Fase 6 (a cumprir com a §24)
11. ✅ Cliente consegue **cancelar** o seu agendamento pela plataforma, **sem penalização** (`customer-booking-cancel` · RF-12).
12. ⬜ Nenhuma rota é criada com agendamentos a **menos de 24 h**; agendamentos sem rota às 24 h são **auto-cancelados** e **retidos** na BD.
13. ⬜ Cliente recebe **lembrete** com sugestão de loja física ou reagendamento.
14. ⬜ Sinal **configurável** no backoffice; **90 %** cobrados no término com **método simulado**.
15. ⬜ **Página de detalhes** por serviço com **carousel**.
16. ⬜ **Multicidades** validado com espaçamento temporal + **alerta de custos** padronizado.
17. ✅ Lista de horas **revalidada** quando os serviços mudam (§24.1 — avulso e por pessoa).
18. ✅ `/gestao` é o **dashboard do gestor** (KPIs + gráficos) e o funcionário é encaminhado para a sua **agenda**.
19. ✅ A **agenda** do funcionário mostra **só** agendamentos de **rotas confirmadas**, em calendário.
20. ✅ Uma rota **não** é confirmada com serviços por aceitar (**409**), a lista **"Por aceitar"** deixa de mostrar serviços assim que o agendamento entra em rota confirmada, e a rota expõe o **detalhe dos agendamentos qualificados**.
21. ✅ O *dropdown* do autocomplete **não** aparece no carregamento de `/registo` (§24.8 — corrigido na branch `fix-autocomplete-dropdown`).
22. ✅ O gestor **reverte/exclui** um agendamento **antes** de a rota ser confirmada e ele volta a **qualificado** (nunca `cancelado`); em rota confirmada **não se altera**.
23. ✅ Existe uma página de **avisos** por perfil, com **contador de não lidos** no sino e ligação no menu do utilizador.
24. ✅ Existe página das **comissões** por funcionário, com os valores **já gravados na aceitação**.
25. ✅ O backoffice tem **sidebar** (a navbar atual está no limite) e as páginas `/gestao/*` mantêm a autorização por perfil.
26. ✅ Existe **gestão de fornecedores** em `/gestao/fornecedores` sobre a tabela `fornecedor` **já carregada** com os 43 fornecedores reais (RF-85 · §25.1).
27. ✅ As **durações reais dos 35 serviços**, os **43 fornecedores** e os **65 clientes** entregues pelo cliente estão na BD por **migração idempotente** gerada a partir do ficheiro (`database_migration_v4.sql` — RF-86 · §24.11).
28. ✅ Os **contadores públicos** (`site-stats`) nunca publicam número **inventado** nem **sabidamente incompleto**: contagem da BD → valor documental → chaves de `SITE_STATS_DOCUMENTAL` (§3.12).

> **Em falta na Fase 6 (por ordem da §24.7):** 6.2 contabilidade/gráficos dos dados importados
> (RF-75/76/79) · 6.3 RH (`PayrollService` — RF-82 · §24.9) · os critérios 12 a 16 acima
> (24 h + lembrete, sinal configurável + 10/90 + métodos, detalhe de serviço + carousel,
> multicidades + alerta de custos) · as 8 famílias fiscais (§24.10 · RF-83). **Promoções** são a
> **Fase 7** (§24.7).

### 28.3 Critérios transversais (sempre)
✅ Código organizado e legível · ✅ interface responsiva · ✅ validações client+server ·
✅ prepared statements · ✅ testes a passar · ✅ documentação atualizada.
