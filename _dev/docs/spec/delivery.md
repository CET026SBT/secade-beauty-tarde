# Especificação — Entrega: roadmap, limitações, testes, instalação

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../../especificacao_mvp.md).
> Capítulos: §21 · §22 · §26 · §27 · §28

## 21. ROADMAP POR FASES E ESTADO

> O **cronograma de 7 dias** dos documentos iniciais está **revogado** — substituído por este
> roadmap por fases, alinhado com as regras finais.

| Fase                                | Âmbito                                                                                                                               | Estado                 |
| :---------------------------------- | :----------------------------------------------------------------------------------------------------------------------------------- | :--------------------- |
| **1 — Catálogo e base**             | Catálogo de serviços (filtros, modal), categorias, autenticação, registo, perfil/moradas, preloader, validators                      | ✅ CONCLUÍDA           |
| **2 — Agendamentos**                | Wizard **Loja** (5 passos) + Wizard **Carrinha** (7 passos + OTP), disponibilidade, conflitos, página de sucesso                     | ✅ CONCLUÍDA           |
| **3 — Backoffice do Funcionário**   | Aceitação individual, desfazer/trocar, consolidação, bloqueio de janela, **Simulador de Recibos Verdes**                             | ✅ CONCLUÍDA           |
| **4 — Backoffice do Gestor**        | Agendamentos (filtros, detalhe por serviço/funcionário, execução, cancelamento), **Rotas com decisão manual** (+50 € visual),        | ✅ CONCLUÍDA           |
|                                     | **Calendário Fiscal** + alertas, config. de recibos verdes                                                                           |                        |
| **5 — Integração e testes**         | Fluxos end-to-end (cliente → funcionário → gestor), responsividade, notificações simuladas, **289 verificações**                     | ✅ CONCLUÍDA           |
| **6 — Requisitos adicionais (§24)** | Página de detalhes + carousel; re-avaliação dinâmica de slots; 24 h + lembrete + cancelamento pelo cliente; multicidades + alerta de | ⬜ **A INICIAR** (§24) |
|                                     | custos; config. do sinal; 10/90 + métodos de pagamento                                                                               |                        |

### 21.1 Entregáveis

1. **Código-fonte completo** (`app/`, `modules/`, `index.php`, assets)
2. **Base de dados**: `DataBase_v2.sql` + `database_seed.sql` (+ migrações `v2`/`v3`) — ordem em §27
3. **Documentação**: `especificacao_mvp.md` (mestre) + `_dev/docs/spec/` (por domínio) + `README.md`
   + `_dev/docs/` (regras e moldes on-demand)
4. **Diagrama de BD**: §17.8 (relações + consulta SQL para regenerar)
5. **Testes automatizados** em `_dev/tests/` — 289 verificações (§26)

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

### 26.1 Suites automatizadas — **289 verificações, todas a passar**

| Suíte de Testes                  | Verificações | Âmbito Coberto Principal                                                                                                                               |
| :------------------------------- | :----------- | :----------------------------------------------------------------------------------------------------------------------------------------------------- |
| `_dev/tests/functional_test.php` | **105**      | Camadas Service/Repository: catálogo, disponibilidade, conflitos, OTP, decisão manual de rotas, backoffice, perfil/moradas, Fase 3/4, transações de    |
|                                  |              | registo e integridade relacional                                                                                                                       |
| `_dev/tests/http_test.php`       | **119**      | Stack real (Apache + roteamento + sessões): autenticação de perfis, APIs REST, códigos de erro, fluxo end-to-end de carrinha e fluxos de registo/login |
| `_dev/tests/asset_test.php`      | **65**       | Validação de assets (HTTP 200), injeção de scripts por página e contrato de nomes do formulário de registo                                             |
| `_dev/tests/js_syntax_check.php` | 15 ficheiros | Verificação estrutural e de sintaxe de todos os ficheiros JavaScript do ecossistema                                                                    |

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
| Decisão Arquitetural / de Negócio    | Prova / Mecanismo de Validação Técnica                                                                  |
| :----------------------------------- | :------------------------------------------------------------------------------------------------------ |
| **Rotas são manuais**                | Aprovação de rota abaixo de 50 € define como `confirmado`; recusa acima de 50 € define como `cancelado` |
| **Categorias não restringem**        | Tabela `funcionario_categoria` eliminada da BD; aceitação de serviços é livre entre funcionários        |
| **Consolidação bloqueia**            | Tentar desfazer um agendamento após estar consolidado resulta num erro HTTP **409**                     |
| **OTP é obrigatório e de uso único** | Código inválido retorna **422**; tentativa de reutilização do código falha imediatamente                |
| **Feedback é único e pós-execução**  | Submissão duplicada ou antes da execução do serviço resulta num erro HTTP **409**                       |
| **Perfis de acesso são respeitados** | Retorno de **401** sem sessão, **403** para perfis incorretos e redirecionamento automático nas páginas |

### 26.4 Cobertura em falta (a acrescentar com a Fase 6)
Testes end-to-end para as funcionalidades da **§24** quando forem implementadas
(cancelamento pelo cliente, 24 h, 10/90, multicidades, página de detalhe de serviço).
**Estratégia:** manter a cobertura end-to-end no **registo e login** e continuar a fazer crescer os
testes *server-to-end* à medida que as restantes funcionalidades estabilizarem.

## 27. INSTALAÇÃO E IMPORTAÇÃO DA BD

### 27.1 Pré-requisitos
Laragon com **Apache + MySQL** ativos · projeto em `C:\laragon\www\secade-beauty-tarde` ·
**internet** (o autocomplete de morada usa a API Nominatim) · browser com DevTools.

### 27.2 Importação (instalação de raiz) — 2 ficheiros
```powershell
$mysql = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'
cd C:\laragon\www\secade-beauty-tarde

# 1) Esquema completo + catálogo  (⚠️ APAGA a base secade_beauty existente)
& $mysql -u root --default-character-set=utf8mb4 -e "source DataBase_v2.sql"

# 2) Utilizadores de teste + morada de demonstração   ← OBRIGATÓRIO
& $mysql -u root --default-character-set=utf8mb4 -e "source database_seed.sql"
```
> ⚠️ Em **PowerShell** a redireção `<` não funciona — usar sempre `-e "source ficheiro.sql"`.
> Alternativa: painel do Laragon → phpMyAdmin → *Import*.
> **Não** é preciso correr as migrações: o `DataBase_v2.sql` já inclui tudo o que elas fazem.

### 27.3 Migração de uma BD antiga (preserva dados)
```powershell
& $mysql -u root --default-character-set=utf8mb4 -e "source database_migration_v2.sql"  # só se BD v1
& $mysql -u root --default-character-set=utf8mb4 -e "source database_migration_v3.sql"  # idempotente
& $mysql -u root --default-character-set=utf8mb4 -e "source database_seed.sql"          # opcional
```
- `database_migration_v2.sql` é de **uso único** (falha com `Duplicate column` se repetido).
- `database_migration_v3.sql` é **idempotente** e pode correr em qualquer schema.

### 27.4 Confirmar a importação
```sql
USE secade_beauty;
SELECT
 (SELECT COUNT(*) FROM information_schema.tables
   WHERE table_schema = 'secade_beauty')        AS tabelas,        -- esperado: 24
 (SELECT COUNT(*) FROM servico)                 AS servicos,       -- esperado: 35
 (SELECT COUNT(*) FROM servico WHERE ativo = 1) AS servicos_ativos,-- esperado: 35
 (SELECT COUNT(*) FROM categoria_profissional)  AS categorias,     -- esperado: 3
 (SELECT COUNT(*) FROM cidade)                  AS cidades,        -- esperado: 10
 (SELECT COUNT(*) FROM matriz_deslocacao)       AS deslocacoes,    -- esperado: 9
 (SELECT COUNT(*) FROM utilizador)              AS utilizadores,   -- esperado: 3
 (SELECT COUNT(*) FROM cliente_morada)          AS moradas;        -- esperado: 1
```
> ⚠️ **Diagnóstico rápido:** **24 tabelas + catálogo completo** mas **0 utilizadores** = importou o
> esquema **sem** o `database_seed.sql`. Basta correr o passo 2 de §27.2 (não é preciso reimportar).
> Consequência: não consegue fazer login e os testes HTTP falham com *foreign key* em
> `agendamento.cliente_id` (falta o cliente de teste #3).

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
10. ✅ Validações em client e server; **testes automatizados a passar** (289).

### 28.2 Critérios da Fase 6 (a cumprir com a §24)
11. ⬜ Cliente consegue **cancelar** o seu agendamento pela plataforma, **sem penalização**.
12. ⬜ Nenhuma rota é criada com agendamentos a **menos de 24 h**; agendamentos sem rota às 24 h são
    **auto-cancelados** e **retidos** na BD.
13. ⬜ Cliente recebe **lembrete** com sugestão de loja física ou reagendamento.
14. ⬜ Sinal **configurável** no backoffice; **90 %** cobrados no término com **método simulado**.
15. ⬜ **Página de detalhes** por serviço com **carousel**.
16. ⬜ **Multicidades** validado com espaçamento temporal + **alerta de custos** padronizado.
17. ⬜ Lista de horas **revalidada** quando os serviços mudam.

### 28.3 Critérios transversais (sempre)
✅ Código organizado e legível · ✅ interface responsiva · ✅ validações client+server ·
✅ prepared statements · ✅ testes a passar · ✅ documentação atualizada.
