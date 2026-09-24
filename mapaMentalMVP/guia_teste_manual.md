# GUIA DE TESTE MANUAL — MVP SECADE BEAUTY
**Objetivo:** percorrer manualmente **todos** os fluxos da aplicação, de ponta a ponta.
**Data:** 24/09/2026 · **Versão do guia:** 1.1

---

## 0. COMO USAR ESTE GUIA

1. Siga os blocos **por ordem** (A → N): cada bloco depende do estado deixado pelo anterior.
2. Cada teste tem: **Ação** → **Esperado** → caixa `[ ]` para marcar.
3. Se é a primeira utilização (ou quer recomeçar do zero), comece pela **secção 2 — Importação da base de dados**.
4. Quando um teste falhar, use a secção **Troubleshooting** (fim do documento) antes de o reportar.
5. **Leia primeiro a secção "Limitações conhecidas"**: há comportamentos intencionais que *parecem* bugs e não são.
6. Blocos com 🗄️ incluem **consulta SQL** para confirmar o resultado na base de dados.

**Legenda:**
- 🅰 Endpoints/rotas envolvidos · 🗄️ Verificação na BD · ⚠️ Atenção/fricção esperada

---

## 1. PRÉ-REQUISITOS

| Item                                  | Verificação                                                                                                                       |
| :------------------------------------ | :-------------------------------------------------------------------------------------------------------------------------------- |
| Laragon a arrancar **Apache + MySQL** | ícone verde no Laragon                                                                                                            |
| Site acessível                        | abrir `http://localhost/secade-beauty-tarde/` → home carrega                                                                      |
| Base de dados `secade_beauty`         | 24 tabelas — para importar do zero, ver **secção 2**                                                                              |
| **Internet ativa**                    | ⚠️ o autocomplete de morada usa a API Nominatim (OpenStreetMap) — **sem internet não consegue registar-se nem criar morada nova** |
| Browser                               | Chrome/Edge com **DevTools** (F12) para ver erros de consola e testar mobile                                                      |

**Ferramentas úteis:**
```powershell
$mysql = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'
& $mysql -u root --default-character-set=utf8mb4 -e "USE secade_beauty; SHOW TABLES;"
```

---

## 2. IMPORTAÇÃO DA BASE DE DADOS

### 2.1 Que ficheiros importar (e porquê)

| Ficheiro                         | O que faz                                                                                             | Quando usar                  |
| :------------------------------- | :---------------------------------------------------------------------------------------------------- | :--------------------------- |
| **`DataBase_v2.sql`**            | Dump **completo**: base + **24 tabelas** + dados de referência (categorias, cidades, base, matriz...) | **1.º** (instalação de raiz) |
| **`database_seed.sql`**          | **Dados de teste**: 3 utilizadores com passwords bcrypt + registos de perfil + 1 morada. Idempotente  | **2.º** (sempre)             |
| `database_migration_v2.sql`      | Migração **incremental** v1→v2 (`ALTER`/`CREATE`), sem destruir dados                                 | Só em BD **v1 com dados**    |
| `database_migration_v3.sql`      | Migração **incremental** v2→v3 (idempotente): cria `servico.ativo`, torna `cliente.morada` anulável   | Só em BD **v2/v1 com dados** |
| ~~`DataBase.sql`~~               | Dump **v1 (legado)** — apenas 21 tabelas                                                              | ❌ Não usar                  |
| ~~`DataBase_backup_pre_v2.sql`~~ | Cópia de segurança do estado anterior à v2                                                            | ❌ Não usar (arquivo)        |

**Detalhe do que `DataBase_v2.sql` já traz:** 3 categorias, 10 cidades, 1 base de partida, 9 linhas de
`matriz_deslocacao` e 35 serviços — tudo com `ativo = 1`.

### 2.2 Resposta curta

```
❓ "Importo o DataBase_v2.sql. Preciso de mais alguma coisa?"

✅ SIM — falta o database_seed.sql.
   Sem ele a BD tem tabelas e catálogo, mas NÃO tem utilizadores →
   não consegue fazer login em nenhum dos 3 perfis.

❌ NÃO precisa das migrações (v2/v3) SE importar o DataBase_v2.sql:
   esse dump já tem o `servico.ativo` e já não tem o legado `cliente.morada`.

⚠️ As migrações servem para OUTRO cenário:
   atualizar uma BD ANTIGA que já tem dados que quer manter.
```

### 2.3 Ordem de importação (instalação de raiz)

```powershell
$mysql = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'
cd C:\laragon\www\secade-beauty-tarde

# 1) Esquema completo + catálogo  (⚠️ APAGA a base secade_beauty existente)
& $mysql -u root --default-character-set=utf8mb4 -e "source DataBase_v2.sql"

# 2) Utilizadores de teste + morada de demonstração
& $mysql -u root --default-character-set=utf8mb4 -e "source database_seed.sql"
```

> ⚠️ **Em PowerShell o `<` não funciona** (`& $mysql -u root < ficheiro.sql` falha).
> Use sempre `-e "source ficheiro.sql"`, como acima (testado e validado).
> Alternativa: painel do Laragon → **phpMyAdmin** → *Import* → `DataBase_v2.sql` → depois `database_seed.sql`.

### 2.4 Confirmar que a importação correu bem

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

-- Os 3 perfis de teste têm de existir:
SELECT id, email, tipo_perfil FROM utilizador ORDER BY id;
-- 1 gestor@secade.pt | 2 funcionario@secade.pt | 3 cliente@teste.pt
```

### 2.5 Se já tem uma BD antiga com dados (migrar em vez de reimportar)

```powershell
$mysql = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'

& $mysql -u root --default-character-set=utf8mb4 -e "source database_migration_v2.sql"
& $mysql -u root --default-character-set=utf8mb4 -e "source database_migration_v3.sql"
& $mysql -u root --default-character-set=utf8mb4 -e "source database_seed.sql"   # opcional
```

> ⚠️ **`database_migration_v2.sql` é de uso único** (v1 → v2): contém `ALTER`/`ADD COLUMN` que
> falham com `Duplicate column` se a BD já estiver na v2. Correr só **uma vez**, e só se a BD
> ainda for v1.
>
> ✅ **`database_migration_v3.sql` é idempotente** e pode correr em qualquer schema (verifica a
> existência das colunas antes de as alterar). Numa BD v2+ apenas imprime mensagens informativas.
>
> ✅ A v3 também **remove a tabela `funcionario_categoria`** (passo 3) — a relação N:N entre
> funcionários e categorias foi eliminada por não ter consumidor (as categorias são apenas filtros
> visuais). Detalhe em `especificacao_mvp.md` §3.2 (D-02). Se restaurar o `DataBase.sql` (v1) ou o
> `DataBase_backup_pre_v2.sql`, correr a v3 remove-a de novo.

### 2.6 Notas

- ⚠️ **Diagnóstico rápido:** se tiver **24 tabelas + catálogo completo** (35 serviços, 10 cidades) mas
  **0 utilizadores**, importou o esquema (e/ou as migrações) **sem o `database_seed.sql`**.
  Não precisa de reimportar tudo — basta correr o **passo 2 da secção 2.3**.
  Consequência típica: não consegue fazer login, e `functional_test`/`http_test` falham com
  *foreign key constraint fails* em `agendamento.cliente_id` (porque falta o cliente de teste #3).
- `config_recibo_verde` fica **vazia** → a aplicação usa o **default 70/30** (`GreenReceiptService`). Não é erro.
- `obrigacao_fiscal` / `alerta_fiscal` ficam **vazias** → o calendário fiscal enche-se ao **criar obrigações** (Bloco J). Não é erro.
- `agendamento`, `rota_ambulante`, `feedback_cliente` ficam **vazias** — é o estado de partida esperado para o guia.
- Depois de importar, corra a **validação da secção 5** para confirmar que tudo funciona.

---

## 3. CREDENCIAIS DE TESTE

| Perfil          | E-mail                  | Password      | Destino por omissão    |
| :-------------- | :---------------------- | :------------ | :--------------------- |
| **Gestor**      | `gestor@secade.pt`      | `Gestor@123`  | `/gestao/agendamentos` |
| **Funcionário** | `funcionario@secade.pt` | `Func@12345`  | `/gestao/servicos`     |
| **Cliente**     | `cliente@teste.pt`      | `Cliente@123` | `/` (home)             |

> O cliente de teste (#3) tem **1 morada** pré-criada: **Rua de Aviz, Nº 10, 7000-123, Évora**.

---

## 4. RESET DA BASE DE DADOS (testes repetíveis)

⚠️ Os testes de agendamento **bloqueiam janelas horárias** e a decisão de rotas altera estados.
Se quiser recomeçar do zero, execute este bloco SQL **antes** de cada corrida do guia:

```sql
USE secade_beauty;

SET FOREIGN_KEY_CHECKS = 0;

-- Descartar movimentos de teste (mantém utilizadores, serviços, categorias, cidades)
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

-- Repor apenas a morada original do cliente de teste
DELETE FROM cliente_morada WHERE cliente_id = 3 AND id <> 1;

-- Repor a configuração de recibos verdes a 70/30
DELETE FROM config_recibo_verde WHERE id > 1;

SET FOREIGN_KEY_CHECKS = 1;
```

**Conferir que ficou limpo:**
```sql
SELECT
 (SELECT COUNT(*) FROM agendamento)      AS agendamentos,
 (SELECT COUNT(*) FROM rota_ambulante)   AS rotas,
 (SELECT COUNT(*) FROM obrigacao_fiscal) AS obrigacoes,
 (SELECT COUNT(*) FROM feedback_cliente) AS feedbacks,
 (SELECT COUNT(*) FROM cliente_morada)   AS moradas;
-- esperado: 0, 0, 0, 0, 1
```

---

## 5. TESTES AUTOMÁTICOS (executar ANTES do manual)

Se estes falharem, não vale a pena testar à mão.

```powershell
cd C:\laragon\www\secade-beauty-tarde

php tests/js_syntax_check.php   # esperado: SINTAXE JS: OK
php tests/functional_test.php   # esperado: 105 pass, 0 fail
php tests/http_test.php         # esperado: 119 pass, 0 fail  (requer Apache+MySQL)
php tests/asset_test.php        # esperado: 65 pass, 0 fail   (requer Apache)
```

- [ ] **5.1** As 4 suites passam (289 verificações)
- [ ] **5.2** Nota: `functional_test`, `http_test` e `asset_test` **limpam os seus próprios dados** — pode correr repetidamente

## BLOCO A — SITE PÚBLICO (sem sessão)

### A1 — Home
- **Ação:** abrir `http://localhost/secade-beauty-tarde/`
- **Esperado:** hero, secção "Acerca", cards de categorias, **carrossel de testemunhos** com navegação, footer
- [ ] OK

### A2 — Testemunhos (feedback público)
- **Ação:** na home, olhar para os testemunhos; em DevTools → Network procurar `feedback-list`
- **Esperado:** sem feedback na BD → mostra os **4 testemunhos estáticos** (fallback). A chamada devolve `200`
- 🅰 `GET ?action=feedback-list&limit=6`
- [ ] OK

### A3 — Rota inexistente (404)
- **Ação:** abrir `/isto-nao-existe`
- **Esperado:** página **404** personalizada (não a do Apache), HTTP 404
- [ ] OK

### A4 — Categorias de serviços
- **Ação:** menu **Servicos** → `/servicos`
- **Esperado:** 3 cards — **Cabelereiro, Barbearia, Estética**
- [ ] OK

### A5 — Catálogo de serviços
- **Ação:** `/servicos/cabelereiro` (testar também `/servicos/barbearia` e `/servicos/estetica`)
- **Esperado:** catálogo carregado; botões de filtro por categoria; ao entrar pelo slug, a categoria respetiva fica **pré-selecionada**
- 🅰 `GET ?action=category-all`, `GET ?action=booking-services`
- [ ] OK

### A6 — Filtros do catálogo
- **Ação:** pesquisar `trança` · "Duração máxima" → "Até 1 hora" · arrastar "Preço máximo"
- **Esperado:** lista filtra em tempo real; o rótulo do preço atualiza (ex.: `12,00 €`)
- [ ] OK

### A7 — Modal de detalhes
- **Ação:** clicar **Detalhes** num serviço
- **Esperado:** modal com descrição, preço, duração, categoria e badge de disponibilidade
- ⚠️ O serviço **Limpeza Facial** (id 34) deve mostrar **"Apenas Loja"**; os restantes "Loja e Carrinha"
- [ ] OK

### A8 — Ligação catálogo → wizard
- **Ação:** clicar **Agendar** num serviço
- **Esperado:** vai para `/agendar?services=<id>` mas, **sem sessão, é redirecionado para `/login`**
- [ ] OK

### A9 — Acessos protegidos (sem sessão)
- **Ação:** abrir diretamente `/agendar`, `/perfil` e `/agendamentos`
- **Esperado:** os 3 redirecionam para `/login`
- [ ] OK

### A10 — Backoffice bloqueado (sem sessão)
- **Ação:** abrir `/gestao/agendamentos`, `/gestao/rotas`, `/gestao/servicos`, `/gestao/fiscal`, `/gestao/recibos-verdes`
- **Esperado:** **todas** redirecionam para `/login` (nenhuma mostra conteúdo)
- [ ] OK

---

## BLOCO B — AUTENTICAÇÃO E REGISTO

### B1 — Login falhado
- **Ação:** `/login` → `cliente@teste.pt` / password errada → submeter
- **Esperado:** mensagem **"Credenciais inválidas."** junto ao campo; não entra
- 🅰 `POST ?action=auth-login` → **422**
- [ ] OK

### B2 — Login com sucesso (cliente)
- **Ação:** `cliente@teste.pt` / `Cliente@123`
- **Esperado:** redireciona para a **home**; a navbar passa a mostrar **"João Cliente" + avatar** e menu dropdown
- 🅰 `POST ?action=auth-login` → **200**
- [ ] OK

### B3 — Registo de novo cliente (2 passos)
- **Ação:** `/registo` → preencher e avançar
  - **Passo 1:** Nome, E-mail, Palavra-passe, Confirmar
  - **Passo 2:** Telemóvel, **Morada**, Nº da Porta, Andar/Bloco, **Código Postal**, **Cidade**, aceitar Termos → **Registar**
- ⚠️ **MUITO IMPORTANTE:** no campo **Morada** é obrigatório **escolher uma sugestão da lista** (dropdown do Nominatim). Escrever à mão **não passa** a validação.
- ✅ Ao escolher a sugestão, **Código Postal** e **Cidade** são preenchidos automaticamente (são os valores gravados na BD).
- ⚠️ A cidade preenchida tem de ser **uma das 10 suportadas** (todas do distrito de Évora):
  `Arraiolos`, `Montemor-o-Novo`, `Viana do Alentejo`, `Reguengos de Monsaraz`, `Redondo`, `Vendas Novas`, `Estremoz`, `Vila Viçosa`, `Mourão`, `Évora`
- **Esperado:** após registar, redireciona para `/login` com sucesso
- 🅰 `POST ?action=auth-register`, `GET ?action=city-supported` → **200**
- 🗄️ Confirmar em BD (a primeira morada deve ser **principal**):
  ```sql
  SELECT u.id, u.nome, u.telemovel, u.tipo_perfil,
         cm.rua, cm.numero_porta, cm.codigo_postal, cm.principal, c.nome AS cidade
  FROM utilizador u
  LEFT JOIN cliente_morada cm ON cm.cliente_id = u.id
  LEFT JOIN cidade c ON cm.cidade_id = c.id
  WHERE u.email = '<o-email-que-usou>';
  -- esperado: tipo_perfil='cliente' e principal=1
  ```
- [ ] OK

### B4 — Registo com e-mail duplicado
- **Ação:** repetir o registo anterior com o mesmo e-mail
- **Esperado:** erro **"Este email já se encontra registado."** e o registo **não** é criado
- [ ] OK

### B5 — Validações do registo
- **Ação:** testar (uma de cada vez)
  - password curta → erro de complexidade (8+ caracteres, 1 letra, 1 número, 1 símbolo)
  - confirmar diferente → "As palavras-passe têm de coincidir."
  - telemóvel inválido (`123`) → "Insira um número de telemóvel válido."
  - código postal inválido (`7000`) → "Código postal inválido (formato 0000-000)."
  - cidade fora das suportadas (`Lisboa`) → "apenas aceitamos moradas nas cidades suportadas"
  - sem aceitar os termos → erro no checkbox
- **Esperado:** cada erro aparece **junto ao campo** e impede avançar
- ⚠️ Os erros de **cidade** e **código postal** são apresentados no campo **Morada** (o formulário reagrupa-os lá)
- [ ] OK

### B6 — Logout
- **Ação:** menu do utilizador → **Sair** → confirmar
- **Esperado:** volta à home e a navbar mostra **"Iniciar Sessão"**
- 🅰 `POST ?action=auth-logout`
- [ ] OK

### B7 — Sessão persiste
- **Ação:** voltar a entrar e **recarregar a página (F5)**
- **Esperado:** continua autenticado (sessão mantida)
- [ ] OK

## BLOCO C — CLIENTE: PERFIL E MORADAS

> Entrar como **`cliente@teste.pt` / `Cliente@123`**.

### C1 — Perfil carrega dados reais
- **Ação:** menu do utilizador → **Perfil** (`/perfil`)
- **Esperado:** nome **João Cliente**, e-mail `cliente@teste.pt`, telemóvel, NIF, badge **Cliente**; "Telemóvel validado por OTP" = **Sim**
- 🅰 `GET ?action=customer-profile`, `GET ?action=customer-address-list`, `GET ?action=city-supported`
- [ ] OK

### C2 — Morada pré-existente listada
- **Ação:** observar "Minhas Moradas"
- **Esperado:** 1 morada — **Rua de Aviz, Nº 10, 7000-123, Évora** com badge **"Principal"** e **sem** botão "Tornar principal"
- 🗄️ `SELECT * FROM cliente_morada WHERE cliente_id = 3;` → 1 linha, `principal = 1`
- [ ] OK

### C3 — Adicionar morada
- **Ação:** **+ Adicionar** → cidade **Montemor-o-Novo**, CP `7050-123`, Rua `Rua Nova`, Nº `7` → **Guardar morada**
- **Esperado:** aparece na lista **sem** o badge "Principal" (só uma morada é principal)
- 🅰 `POST ?action=customer-address-store` → **200**
- 🗄️ `SELECT COUNT(*) FROM cliente_morada WHERE cliente_id = 3;` → **2**
- [ ] OK

### C4 — Validação: cidade não suportada
- **Ação:** forçar uma cidade fora da lista (DevTools → alterar `value` do `<select>` para `Lisboa`) → Guardar
- **Esperado:** erro **"Lamentamos, mas de momento apenas aceitamos moradas nas cidades suportadas."**
- [ ] OK

### C5 — Validação: código postal
- **Ação:** guardar com CP `1234`
- **Esperado:** erro **"Código postal inválido (formato 0000-000)."**
- [ ] OK

### C6 — Definir como principal
- **Ação:** na morada nova → **Tornar principal**
- **Esperado:** o badge **"Principal"** passa para a nova; a antiga perde-o
- 🅰 `POST ?action=customer-address-set-principal`
- 🗄️ `SELECT id, principal FROM cliente_morada WHERE cliente_id = 3;` → **exatamente uma** linha com `principal = 1`
- [ ] OK

### C7 — Remover morada
- **Ação:** remover a morada de **Montemor-o-Novo** (caixote → confirmar)
- **Esperado:** desaparece da lista; a de Évora mantém-se
- 🗄️ `SELECT COUNT(*) FROM cliente_morada WHERE cliente_id = 3;` → **1**
- [ ] OK

## BLOCO D — CLIENTE: AGENDAMENTO LOJA FÍSICA (5 passos)

> Ferramenta: **DevTools → Network** aberto para observar cada chamada.

### D1 — Abrir o wizard
- **Ação:** menu **Agendar** (`/agendar`)
- **Esperado:** indicador de passos no topo; **Passo 1** ativo; grelha de **35 serviços** com checkbox, duração e preço
- 🅰 `GET ?action=category-all`, `GET ?action=booking-services`
- [ ] OK

### D2 — Filtro por categoria no wizard
- **Ação:** clicar num botão de categoria (ex.: **Barbearia**)
- **Esperado:** só ficam visíveis os serviços dessa categoria (os que já estavam **marcados continuam marcados**)
- [ ] OK

### D3 — Totais automáticos
- **Ação:** marcar **Corte de Cabelo** (30 min / 12,20 €) e **Barba** (20 min / 4,07 €)
- **Esperado:** **Duração total: 50 min** · **Valor total: 16,27 €**
- [ ] OK

### D4 — Validação: sem serviços
- **Ação:** desmarcar tudo → **Continuar**
- **Esperado:** alerta **"Selecione pelo menos um serviço para continuar."** — não avança
- [ ] OK

### D5 — Passo 2: Canal
- **Ação:** com serviços marcados → **Continuar**
- **Esperado:** 2 cards; **ambos selecionáveis** (estes serviços não exigem espaço físico)
- [ ] OK

### D6 — Bloqueio "Apenas Loja" ⭐ teste importante
- **Ação:** **Voltar** ao Passo 1 → marcar **Limpeza Facial** → avançar para o Passo 2
- **Esperado:** card da **Carrinha esbatido/inativo** + aviso **"Os serviços selecionados exigem espaço físico…"**
- **Ação:** tentar escolher a Carrinha → **não deve ser possível**
- [ ] OK

### D7 — Seguir com Loja Física
- **Ação:** desmarcar **Limpeza Facial** → escolher **Loja Física** → **Continuar**
- **Esperado:** avança para **Passo 3 · Data e Hora**; o passo **"Profissional"** fica no fluxo (visível no indicador de passos)
- [ ] OK

### D8 — Regra Terça a Sábado
- **Ação:** tentar uma **segunda** ou **domingo** → Continuar
- **Esperado:** erro **"Apenas é possível agendar de Terça a Sábado."**
- [ ] OK

### D9 — Slots de disponibilidade
- **Ação:** escolher uma **terça-feira futura**
- **Esperado:** grelha de **slots de 30 min (09:00 → 19:00)**; slots ocupados **desativados**
- 🅰 `GET ?action=booking-availability&date=…&duration=50&local=loja_fisica`
- 🗄️ `SELECT COUNT(*) FROM agendamento WHERE DATE(data_hora_pretendida) = '<data>';` → **0** (ainda nada criado)
- [ ] OK

### D10 — Validação: sem hora
- **Ação:** não escolher hora → **Continuar**
- **Esperado:** aviso **"Selecione um horário disponível."** — não avança
- [ ] OK

### D11 — Passo 4: Profissional
- **Ação:** escolher **10:00** → Continuar
- **Esperado:** Passo **"Profissional"** com **"Sem preferência"** marcada
- [ ] OK

### D12 — Passo 5: Resumo
- **Ação:** Continuar
- **Esperado:** **Canal: Loja Física (Évora)**, serviços, data/hora, duração **50 min**, total **16,27 €**, **Sinal 10% (simulado) ≈ 1,63 €**, **Restante ≈ 14,64 €** + aviso de pendente de validação logística
- [ ] OK

### D13 — Confirmar agendamento (loja)
- **Ação:** **Confirmar Agendamento**
- **Esperado:** redireciona para `/agendamento-sucesso?id=<N>&local=loja_fisica` com mensagem de sucesso e o **ID da marcação**
- 🅰 `POST ?action=booking-create-store` → **200**
- 🗄️
```sql
SELECT id, local_prestacao, data_hora_pretendida, estado_reserva, valor_total, valor_sinal
FROM agendamento ORDER BY id DESC LIMIT 1;
-- esperado: local_prestacao      = 'loja_fisica'
--           estado_reserva       = 'pendente_validacao_logistica_loja'
--           valor_total          = 16.27   | valor_sinal = 1.63

SELECT COUNT(*) FROM agendamento_servico WHERE agendamento_id = <N>;      -- 2
SELECT DISTINCT estado_aceitacao FROM agendamento_servico
 WHERE agendamento_id = <N>;   -- 'aceite'  (aceitação AUTOMÁTICA na loja)
```
- [ ] OK

### D14 — Conflito de janela (loja)
- **Ação:** repetir o agendamento para a **mesma data e hora**
- **Esperado:** erro **"Já existe um agendamento confirmado nesta janela horária. Escolha outro slot."** (HTTP **409**)
- [ ] OK

## BLOCO E — CLIENTE: AGENDAMENTO CARRINHA AMBULANTE (7 passos + OTP) ⭐

> Este é o fluxo mais complexo. **Não feche a página** durante o OTP (o código vive na sessão).

### E1 — Escolher serviços sem espaço físico
- **Ação:** `/agendar` → marcar **Barba** (20 min / 4,07 €) e **Design de Sobrancelha com Linha** (30 min / 8,13 €) → Continuar
- **Esperado:** totais **50 min / 12,20 €**
- [ ] OK

### E2 — Escolher "Carrinha Ambulante"
- **Ação:** selecionar o card da **Carrinha** → **Continuar**
- **Esperado:** avança para **Passo 2B · Morada e Pessoas**; ⚠️ **o passo "Profissional" NÃO aparece** (é exclusivo da loja)
- [ ] OK

### E3 — Morada pré-selecionada
- **Ação:** observar o dropdown "Morada de atendimento"
- **Esperado:** a morada de Évora vem **pré-selecionada** (é a principal); existe ainda a opção **"+ Introduzir nova morada"**
- 🅰 `GET ?action=customer-address-list`, `GET ?action=city-supported`
- [ ] OK

### E4 — Nova morada dentro do wizard
- **Ação:** escolher **"+ Introduzir nova morada"** → cidade **Arraiolos**, CP `7040-100`, Rua `Rua do Largo`, Nº `3`
- **Esperado:** o formulário de nova morada abre; ao confirmar no fim, cria a morada e usa-a
- [ ] OK

### E5 — Estrutura por pessoa
- **Ação:** observar "Pessoas e serviços"
- **Esperado:** **Pessoa 1** já existe com o nome **pré-preenchido "João Cliente"** (cliente logado)
- **Ação:** marcar **Barba** para a Pessoa 1
- [ ] OK

### E6 — Adicionar Pessoa 2 (serviços partilhados)
- **Ação:** **+ Adicionar pessoa** → nome `Maria Familiar` → marcar **Barba** **e** **Design de Sobrancelha**
- **Esperado:** 2 blocos de pessoa; a Pessoa 2 permite o **mesmo serviço** que a Pessoa 1 (partilha)
- ⚠️ Marcar o **mesmo serviço** para as duas pessoas é intencional — e é **contabilizado 2×**
- [ ] OK

### E7 — Validação: pessoa sem serviço
- **Ação:** adicionar **Pessoa 3** com nome mas **sem serviços** → Continuar
- **Esperado:** erro **"Selecione pelo menos um serviço para a Pessoa 3."** — não avança
- **Ação:** remover a Pessoa 3 (botão **Remover**)
- [ ] OK

### E8 — Validação: pessoa sem nome
- **Ação:** apagar o nome da Pessoa 2 → Continuar
- **Esperado:** erro **"Indique o nome da Pessoa 2."**
- [ ] OK

### E9 — Continuar para o OTP
- **Ação:** corrigir os dados → **Continuar**
- **Esperado:** avança para **Passo 2C · Verificação por OTP**
- [ ] OK

### E10 — Validação: OTP antes de pedir
- **Ação:** escrever `123456` e clicar **Continuar** (sem ter pedido o código)
- **Esperado:** erro **"Solicite primeiro o envio do código OTP."**
- [ ] OK

### E11 — Pedir o código OTP
- **Ação:** clicar **Enviar Código**
- **Esperado:** aparece caixa amarela **"Simulação SMS: o seu código OTP é ______"** com **6 dígitos** visíveis
- 🅰 `POST ?action=booking-otp-request` → **200** (a resposta inclui `otpCode`)
- ⚠️ **Anote o código mostrado.**
- [ ] OK

### E12 — OTP errado
- **Ação:** escrever um código errado (`000000`, ou trocar um dígito) → Continuar
- **Esperado:** erro **"O código introduzido não corresponde ao código enviado."** — não avança
- [ ] OK

### E13 — OTP correto
- **Ação:** escrever o **código mostrado** → Continuar
- **Esperado:** caixa verde **"Telemóvel validado com sucesso."** e avança para o **Passo 3 · Data e Hora**
- [ ] OK

### E14 — Data e slots (carrinha)
- **Ação:** escolher uma **terça-feira futura** (diferente da usada no Bloco D)
- **Esperado:** slots disponíveis; a duração pedida pelo wizard deve ser **70 min**
  (Pessoa 1: Barba = 20 min; Pessoa 2: Barba 20 + Design 30 = 50 min → **20 + 50 = 70 min**)
- 🅰 `GET ?action=booking-availability&date=…&duration=70&local=carrinha_ambulante`
- ⚠️ Confirme em Network o valor de `duration` — deve refletir a soma **por pessoa** (e não um valor deduplicado)
- [ ] OK

### E15 — Escolher hora
- **Ação:** escolher **09:00** → Continuar
- **Esperado:** avança para **Passo 4 · Política de Sinal**
- [ ] OK

### E16 — Política de sinal
- **Ação:** observar
- **Esperado:** avisos de que na **1ª marcação em ambulatório o sinal é dispensado** e de que fica **pendente** de validação da rota
- [ ] OK

### E17 — Validação: termos obrigatórios
- **Ação:** sem marcar a caixa → Continuar
- **Esperado:** erro **"Deve aceitar as condições para continuar."**
- **Ação:** marcar a caixa → Continuar
- [ ] OK

### E18 — Resumo (com pessoas)
- **Ação:** observar o Passo 5
- **Esperado:** **Canal: Carrinha Ambulante**, **Morada** (a escolhida), lista **Pessoa 1 (João Cliente)**: Barba · **Pessoa 2 (Maria Familiar)**: Barba, Design de Sobrancelha, **Sinal: Dispensado**, e valor total
- ⚠️ Valor esperado: **4,07 (P1) + 4,07 + 8,13 (P2) = 16,27 €**
- [ ] OK

### E19 — Confirmar agendamento (carrinha)
- **Ação:** **Confirmar Agendamento**
- **Esperado:** redireciona para `/agendamento-sucesso?id=<N>&local=carrinha_ambulante`, com aviso de **pendente** ("A rota será validada pela equipa")
- 🅰 `POST ?action=booking-create-amb` → **200**
- 🗄️
```sql
-- agendamento
SELECT id, local_prestacao, estado_reserva, valor_total, valor_sinal
FROM agendamento ORDER BY id DESC LIMIT 1;
-- esperado: local_prestacao = 'carrinha_ambulante'
--           estado_reserva  = 'pendente_aceitacao_funcionarios'
--           valor_total     = 16.27   | valor_sinal = 0.00

-- pessoas (estrutura por pessoa)
SELECT id, nome_pessoa FROM agendamento_pessoa WHERE agendamento_id = <N>;
-- esperado: 2 linhas (João Cliente, Maria Familiar)

-- serviços: 3 registos (Barba×2 + Design×1), todos 'pendente'
SELECT agendamento_pessoa_id, servico_id, estado_aceitacao
FROM agendamento_servico WHERE agendamento_id = <N>;
-- esperado: 3 linhas, todas com estado_aceitacao = 'pendente'
```
- [ ] OK

### E20 — OTP não é reutilizável
- **Ação:** sem recarregar, voltar atrás no wizard e tentar confirmar novamente com o **mesmo** OTP
- **Esperado:** **"Código OTP inválido ou expirado."** (o código é consumido na 1ª utilização)
- [ ] OK

## BLOCO F — CLIENTE: HISTÓRICO DE AGENDAMENTOS

### F1 — Listagem
- **Ação:** menu do utilizador → **Agendamentos** (`/agendamentos`)
- **Esperado:** aparecem os **2 agendamentos** criados (D13 loja + E19 carrinha), cada um com **#ID**, badge de estado, badge **Loja/Carrinha**, data/hora, valor e lista de serviços
- 🅰 `GET ?action=booking-my`, `GET ?action=feedback-my`
- [ ] OK

### F2 — Serviços com pessoa
- **Ação:** observar o cartão do agendamento da **carrinha**
- **Esperado:** cada serviço mostra a etiqueta da **pessoa** (João Cliente / Maria Familiar)
- [ ] OK

### F3 — Filtros por estado
- **Ação:** clicar em **Pendentes**, **Confirmados**, **Cancelados**, **Todos**
- **Esperado:** a lista filtra imediatamente; **Cancelados** está vazio nesta fase
- [ ] OK

### F4 — Estado inicial correto
- **Ação:** comparar os badges com a BD
- **Esperado:** loja = **"Pendente validação (loja)"**; carrinha = **"Aguarda aceitação"**
- 🗄️ `SELECT id, local_prestacao, estado_reserva FROM agendamento ORDER BY id;`
- [ ] OK

### F5 — Feedback indisponível (ainda)
- **Ação:** observar os cartões
- **Esperado:** ⚠️ **não** aparece o formulário de avaliação — nenhum serviço foi **executado** ainda (só aparece nos estados `executado`/`concluido`)
- [ ] OK

---

## BLOCO G — FUNCIONÁRIO: ACEITAÇÃO E CONSOLIDAÇÃO (FASE 3) ⭐

> **Sair** e entrar como **`funcionario@secade.pt` / `Func@12345`**.
> Deve aterrar em `/gestao/servicos`.

### G1 — Menu do funcionário (segregação de perfil)
- **Ação:** observar a navbar do backoffice
- **Esperado:** badge **"Funcionário"**; menu com **Serviços** e **Agendamentos** apenas
- ⚠️ **NÃO** deve existir **Rotas**, **Calendário Fiscal** nem **Recibos Verdes**
- [ ] OK

### G2 — Serviços por aceitar
- **Ação:** observar a coluna **"Por aceitar"**
- **Esperado:** os **3 serviços** do agendamento da carrinha (Barba · Pessoa 1, Barba · Pessoa 2, Design · Pessoa 2), com categoria, data/hora e valor; contador **"3 serviço(s) por aceitar"**
- 🅰 `GET ?action=admin-service-pending-list`
- ⚠️ Os serviços da **loja NÃO aparecem** aqui (aceitação é exclusiva do ambulatório)
- [ ] OK

### G3 — Indicador do recibo verde
- **Ação:** observar o topo da página
- **Esperado:** badge **"Simulador de recibos verdes: 70% / 30%"**
- [ ] OK

### G4 — Filtro por categoria (visual)
- **Ação:** filtrar por **Estética** / **Barbearia**
- **Esperado:** a lista filtra; o filtro é apenas visual e **não limita** a aceitação
- [ ] OK

### G5 — Aceitar o 1.º serviço
- **Ação:** em **Barba (João Cliente)** clicar **Aceitar serviço**
- **Esperado:** mensagem de sucesso com o **recibo verde simulado** (ex.: *"Recibo verde simulado: 2,85 € para si e 1,22 € para a plataforma"*)
- ⚠️ Para 4,07 € a 70% → **2,85 € / 1,22 €**
- **Esperado adicional:** o serviço sai da lista "Por aceitar" e aparece em **"Aceites por mim"**, com os dois valores e a percentagem
- 🅰 `POST ?action=admin-service-accept` → **200**
- 🗄️
```sql
SELECT id, servico_id, agendamento_pessoa_id, funcionario_id, estado_aceitacao,
       percentagem_funcionario_aplicada,
       valor_recibo_verde_funcionario, valor_recibo_verde_plataforma
FROM agendamento_servico WHERE agendamento_id = <N>;
-- 1 linha com funcionario_id=2, estado_aceitacao='aceite',
-- percentagem=70.00, valores 2.85 / 1.22
SELECT estado_reserva FROM agendamento WHERE id = <N>;
-- esperado: ainda 'pendente_aceitacao_funcionarios' (NÃO consolidado)
```
- [ ] OK

### G6 — Desfazer a aceitação
- **Ação:** em **"Aceites por mim"** → **Desfazer** → confirmar
- **Esperado:** sucesso; o serviço **volta a "Por aceitar"**
- 🗄️ `SELECT estado_aceitacao, funcionario_id FROM agendamento_servico WHERE id = <id>;`
  → `pendente` e `funcionario_id = NULL`
- [ ] OK

### G7 — Aceitar novamente
- **Ação:** aceitar outra vez o mesmo serviço
- **Esperado:** volta a "Aceites por mim"
- [ ] OK

### G8 — Total a receber
- **Ação:** observar o cabeçalho "Aceites por mim"
- **Esperado:** **"1 aceite(s) · 2,85 € a receber"**
- [ ] OK

### G9 — Consolidar (aceitar o último serviço)
- **Ação:** aceitar os **2 serviços restantes** (basta aceitar o último)
- **Esperado:** no último, a mensagem inclui **"Agendamento TOTALMENTE ACEITE (janela temporal bloqueada)."**
- 🅰 a resposta deve trazer `consolidated: true`
- 🗄️
```sql
SELECT estado_reserva FROM agendamento WHERE id = <N>;
-- esperado: 'totalmente_aceite_funcionarios'
SELECT COUNT(*) FROM agendamento_servico
 WHERE agendamento_id = <N> AND estado_aceitacao = 'pendente';
-- esperado: 0
```
- [ ] OK

### G10 — Desfazer bloqueado após consolidação ⭐ teste crítico
- **Ação:** tentar **Desfazer** um dos serviços aceites
- **Esperado:** erro/aviso **"O agendamento já está totalmente aceite por funcionários e não permite desfazer nem trocar."** (HTTP **409**)
- **Esperado adicional:** a aceitação **mantém-se** (não é removida)
- [ ] OK

### G11 — Totais finais do funcionário
- **Ação:** observar "Aceites por mim"
- **Esperado:** **3 aceites** e o total a receber = **2,85 + 2,85 + 5,69 = 11,39 €**
  *(Design de Sobrancelha 8,13 € × 70% = 5,69 €)*
- [ ] OK

### G12 — Funcionário não acede a rotas/fiscal
- **Ação:** tentar abrir `/gestao/rotas`, `/gestao/fiscal` e `/gestao/recibos-verdes` com a sessão de funcionário
- **Esperado:** **redirect** para a home (não mostra conteúdo)
- [ ] OK

## BLOCO H — GESTOR: AGENDAMENTOS, DETALHE E EXECUÇÃO

> **Sair** e entrar como **`gestor@secade.pt` / `Gestor@123`** → aterra em `/gestao/agendamentos`.

### H1 — Menu do gestor
- **Ação:** observar a navbar
- **Esperado:** badge **"Gestor"**; menu com **Agendamentos**, **Rotas**, **Calendário Fiscal** e **Recibos Verdes**
- [ ] OK

### H2 — Listagem
- **Ação:** observar a tabela
- **Esperado:** os **2 agendamentos** (loja + carrinha) com #ID, **cliente**, data/hora, local, cidade, estado, valor; contador "N agendamento(s)"; paginação
- 🅰 `GET ?action=admin-appointments-list&page=1&perPage=10`
- [ ] OK

### H3 — Filtros
- **Ação:** filtrar por **Data** = data do agendamento da loja; depois **Local** = Carrinha; depois **Estado** = `Totalmente aceite por funcionários`
- **Esperado:** cada filtro reduz a lista ao esperado; o botão ✕ limpa tudo
- [ ] OK

### H4 — Detalhe por serviço/funcionário ⭐
- **Ação:** no agendamento da **carrinha** (estado *Totalmente aceite*) clicar no ícone do **olho**
- **Esperado:** modal com:
  - cliente (nome, telemóvel, e-mail), data/hora, local, **cidade** e **morada**
  - **estado** e **valor total**
  - **Progresso aceitação: 3 aceite(s) + 0 pendente(s)** e **Consolidado: Sim — janela bloqueada**
  - lista **"Serviços e funcionários"** com, por serviço: nome, **pessoa**, badge **Aceite**, **funcionário (Ana Técnica)** e **RV simulado (2,85 € / 1,22 €)**
- 🅰 `GET ?action=admin-appointment-details&bookingId=<N>`
- [ ] OK

### H5 — Detalhe da loja
- **Ação:** abrir o detalhe do agendamento da **loja**
- **Esperado:** serviços **Aceite** (automático) mas **sem funcionário atribuído** ("por atribuir"); **sem** morada (loja não tem)
- [ ] OK

### H6 — Registar execução
- **Ação:** no detalhe do agendamento da **carrinha** → **Registar execução do serviço** → confirmar
- **Esperado:** sucesso; o modal fecha; a listagem recarrega
- 🅰 `POST ?action=admin-appointment-execute` → **200**
- 🗄️
```sql
SELECT estado_reserva FROM agendamento WHERE id = <N>;   -- 'executado'

SELECT id, rota_id, estado_execucao, data_hora_inicio_real
FROM execucao_agendamento WHERE agendamento_id = <N>;
-- esperado: 1 linha, estado_execucao='concluido'
```
- [ ] OK

### H7 — Execução é idempotente
- **Ação:** tentar registar execução novamente
- **Esperado:** não duplica (o botão desaparece do detalhe; via API devolve `alreadyRegistered`)
- 🗄️ `SELECT COUNT(*) FROM execucao_agendamento WHERE agendamento_id = <N>;` → **1**
- [ ] OK

### H8 — Cancelar um agendamento
- **Ação:** no detalhe do agendamento da **loja** → **Cancelar agendamento** → confirmar
- **Esperado:** estado passa a **Cancelado**
- ⚠️ O botão fica **desativado** para estados `cancelado`/`executado`/`concluido`
- 🅰 `POST ?action=admin-appointment-cancel`
- 🗄️ `SELECT estado_reserva FROM agendamento WHERE id = <N>;` → `cancelado`
- [ ] OK

### H9 — Erro 409 ao cancelar duas vezes
- **Ação:** tentar cancelar outra vez (via console/API)
- **Esperado:** HTTP **409** — *"Este agendamento não pode ser cancelado (estado atual: cancelado)."*
- [ ] OK

## BLOCO I — GESTOR: DECISÃO MANUAL DE ROTAS ⭐⭐

> **O teste mais importante da Fase 4:** provar que a decisão é **manual** e que os **50 € são só indicador**.

### I0 — Pré-requisito: ter um agendamento de ambulatório para decidir
- **Ação:** com o **cliente**, criar novo agendamento de ambulatório (Bloco E) e, com o **funcionário**, aceitar todos os serviços (Bloco G)
- **Esperado:** fica em `pendente_aceitacao_funcionarios` ou `totalmente_aceite_funcionarios`, num dia futuro
- 🗄️
```sql
SELECT a.id, a.data_hora_pretendida, a.estado_reserva, a.valor_total, cid.nome AS cidade
FROM agendamento a
JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
JOIN cidade cid ON cm.cidade_id = cid.id
WHERE a.local_prestacao = 'carrinha_ambulante'
  AND a.estado_reserva IN ('pendente_aceitacao_funcionarios','totalmente_aceite_funcionarios');
-- anote a DATA e a CIDADE
```
- [ ] OK

### I1 — Abrir as rotas
- **Ação:** menu **Rotas** (`/gestao/rotas`)
- **Esperado:** data **pré-preenchida com D+1**; tabela com Data, Cidade (+distrito), Agendamentos (total + *N aceite(s)*), **Receita**, **Combustível**, **Rentabilidade**, **Estado** e **Decisão**
- 🅰 `GET ?action=admin-routes-list`
- [ ] OK

### I2 — Não existe algoritmo automático
- **Ação:** ler a caixa informativa e procurar botões
- **Esperado:** texto **"Decisão manual: o gestor aprova ou recusa livremente. (…) a referência de 50 € é apenas visual e não decide por si."**
- ⚠️ **Não deve existir** nenhum botão "Validar Rotas" nem "Executar algoritmo"
- [ ] OK

### I3 — Indicador de 50 € (apenas visual)
- **Ação:** observar a coluna **Rentabilidade** e o ícone ao lado
- **Esperado:**
  - ✅ ícone **verde** se ≥ 50 € → tooltip *"Rentabilidade igual ou acima da referência de 50 €"*
  - ⚠️ ícone **amarelo** se < 50 € → tooltip *"Abaixo da referência de 50 € (apenas indicador visual)"*
- ⚠️ Confirme a conta: `Rentabilidade = Receita − Combustível`
- [ ] OK

### I4 — APROVAR uma rota ABAIXO da referência ⭐ (prova de que é manual)
- **Ação:** escolher uma linha com rentabilidade **abaixo de 50 €** (ícone amarelo) → **✅ Aprovar** → confirmar
- **Esperado:** **sucesso, mesmo estando abaixo da referência!** A mensagem mostra receita, custo, rentabilidade e badge **"abaixo da referência (indicador)"**
- 🅰 `POST ?action=admin-route-decide` `{cityId, date, decision:"aprovada"}` → **200**
- 🗄️
```sql
SELECT estado_reserva, COUNT(*) FROM agendamento
 WHERE local_prestacao = 'carrinha_ambulante'
   AND DATE(data_hora_pretendida) = '<data>'
 GROUP BY estado_reserva;
-- esperado: 'confirmado'

SELECT cidade_id, data_rota, estado_rota, lucro_total, lucro_servicos,
       decidido_por, decidido_em, observacoes_decisao
FROM rota_ambulante ORDER BY id DESC LIMIT 1;
-- esperado: estado_rota='aprovada', decidido_por=1 (gestor),
--           decidido_em preenchido, lucro_total = rentabilidade
```
- [ ] OK

### I5 — RECUSAR uma rota ACIMA da referência ⭐
- **Ação:** noutra cidade/data, escolher uma linha com rentabilidade **acima de 50 €** (ícone verde) → **❌ Recusar** → confirmar
- **Esperado:** **sucesso, apesar de estar acima!** Badge **"acima da referência"**; os agendamentos dessa cidade ficam **cancelados**
- 🗄️ `SELECT estado_reserva FROM agendamento WHERE id IN (<ids dessa cidade>);` → `cancelado`
- [ ] OK

### I6 — A decisão fica registada
- **Ação:** clicar **Atualizar**
- **Esperado:** o **Estado** da rota passa a **Aprovada**/**Recusada**; os botões de decisão **desaparecem**
- [ ] OK

### I7 — Não se decide duas vezes
- **Ação:** repetir a decisão na mesma cidade+data (via console/API)
- **Esperado:** HTTP **409** — *"Não existem agendamentos de ambulatório em condições de decisão…"*
- [ ] OK

### I8 — Filtros das rotas
- **Ação:** filtrar por **Cidade** e por **Estado**
- **Esperado:** a tabela filtra corretamente
- [ ] OK

## BLOCO J — GESTOR: CALENDÁRIO FISCAL E ALERTAS PROGRESSIVOS

### J1 — Abrir o calendário
- **Ação:** menu **Calendário Fiscal** (`/gestao/fiscal`)
- **Esperado:** 4 cartões de resumo (**Total**, **Pendentes**, **Vencem em 7 dias**, **Em atraso**), botão **+ Nova obrigação** e a tabela de obrigações
- 🅰 `GET ?action=admin-fiscal-calendar-list`, `GET ?action=admin-fiscal-alert-list`
- [ ] OK

### J2 — Criar obrigação que vence em 7 dias → alerta `7_dias`
- **Ação:** **+ Nova obrigação** → Tipo **IVA**, Designação `IVA Trimestral T1`, Periodicidade **Trimestral**, Valor `1234.56`, Prazo = **hoje + 7 dias** → **Guardar obrigação**
- **Esperado:** aparece na tabela; na coluna **Dias** mostra `7`; na coluna **Alerta** aparece badge **"7 dias"**
- 🅰 `POST ?action=admin-fiscal-obligation-create` → **200**
- 🗄️
```sql
SELECT id, tipo, designacao, data_prazo, estado, valor_estimado
FROM obrigacao_fiscal ORDER BY id DESC LIMIT 1;

SELECT tipo_alerta, data_alerta, visualizado
FROM alerta_fiscal WHERE obrigacao_fiscal_id = <id>;
-- esperado: tipo_alerta='7_dias'
```
- [ ] OK

### J3 — Criar obrigação a 30 dias → alerta `30_dias`
- **Ação:** nova obrigação Tipo **IRC**, Prazo = **hoje + 30 dias** → Guardar
- **Esperado:** badge de alerta **"30 dias"**
- ⚠️ **Este é um bom caso de regressão:** um bug anterior classificava erradamente prazos curtos como "30_dias"
- [ ] OK

### J4 — Criar obrigação a 1 dia → alerta `1_dia`
- **Ação:** nova obrigação Tipo **Seguros**, Prazo = **amanhã**
- **Esperado:** badge **"1 dia"**
- [ ] OK

### J5 — Criar obrigação EM ATRASO → alerta `em_atraso`
- **Ação:** nova obrigação Tipo **Segurança Social**, Prazo = **hoje − 5 dias**
- **Esperado:** na coluna Dias mostra `-5 (atraso)` a vermelho; badge **"Em atraso"**; o cartão **"Em atraso"** incrementa
- 🗄️ `SELECT tipo_alerta FROM alerta_fiscal ORDER BY id DESC LIMIT 1;` → `em_atraso`
- [ ] OK

### J6 — Painel de alertas
- **Ação:** observar o painel amarelo **"Alertas fiscais"** no topo
- **Esperado:** lista todos os alertas não lidos com badge do tipo (30 dias / 7 dias / 1 dia / Em atraso), nome da obrigação e prazo
- [ ] OK

### J7 — Alertas são idempotentes
- **Ação:** recarregar a página 2–3 vezes (F5)
- **Esperado:** os alertas **não duplicam** (mesma contagem)
- 🗄️ `SELECT COUNT(*) FROM alerta_fiscal;` → mantém-se igual entre refreshes
- [ ] OK

### J8 — Marcar alertas como visualizados
- **Ação:** **Marcar como visualizados**
- **Esperado:** o painel de alertas desaparece (já não há não-lidos)
- 🗄️ `SELECT COUNT(*) FROM alerta_fiscal WHERE visualizado = 0;` → **0**
- [ ] OK

### J9 — Marcar obrigação como paga
- **Ação:** na obrigação do IVA, ícone ✅ (Marcar como pago) → confirmar
- **Esperado:** o **Estado** passa a **Pago**; o botão fica **desativado**; a coluna **Alerta** passa a **"Pago"**; o cartão **Pendentes** diminui
- 🅰 `POST ?action=admin-fiscal-obligation-paid`
- 🗄️
```sql
SELECT estado, data_pagamento FROM obrigacao_fiscal WHERE id = <id>;
-- esperado: estado='pago', data_pagamento preenchida
```
- [ ] OK

### J10 — Pagar duas vezes → 409
- **Ação:** repetir (via console/API) `admin-fiscal-obligation-paid` para o mesmo id
- **Esperado:** HTTP **409** — *"Esta obrigação já se encontra marcada como paga."*
- [ ] OK

### J11 — Filtros do calendário
- **Ação:** filtrar por Tipo (**IVA**) e por Estado (**Pendente** / **Pago**)
- **Esperado:** a tabela filtra corretamente
- [ ] OK

### J12 — Validações (422)
- **Ação:** tentar criar obrigação sem designação, sem prazo, e com **valor negativo**
- **Esperado:** erro de validação (HTTP **422**) com mensagem junto do formulário
- [ ] OK

---

## BLOCO K — GESTOR: SIMULADOR DE RECIBOS VERDES (configuração)

### K1 — Abrir a página
- **Ação:** menu **Recibos Verdes** (`/gestao/recibos-verdes`)
- **Esperado:** cartão **"Configuração em vigor"** com **70% / 30%** e badge *"Valores por omissão"* ou *"Configuração registada"*; formulário de nova configuração; tabela de histórico
- 🅰 `GET ?action=admin-green-receipt-config`
- [ ] OK

### K2 — Pré-visualização da soma
- **Ação:** alterar **% Funcionário** para `80` (deixando a plataforma em `30`)
- **Esperado:** aviso **"Soma atual: 110% — tem de ser exatamente 100%."** a vermelho
- [ ] OK

### K3 — Guardar com soma inválida → 422
- **Ação:** **Guardar configuração**
- **Esperado:** erro **"A soma das percentagens tem de ser exatamente 100."** (HTTP **422**)
- [ ] OK

### K4 — Guardar configuração válida
- **Ação:** colocar **80% / 20%**, data de vigência hoje → **Guardar configuração**
- **Esperado:** sucesso com exemplo *"Exemplo para 100 €: 80,00 € / 20,00 €"*; a configuração em vigor passa a **80% / 20%**; nova linha no **histórico**
- 🗄️ `SELECT * FROM config_recibo_verde ORDER BY id DESC LIMIT 1;` → `80.00 / 20.00`
- [ ] OK

### K5 — A configuração afeta apenas aceitações FUTURAS
- **Ação:** voltar a `/gestao/servicos` (como funcionário) e aceitar um novo serviço
- **Esperado:** o recibo verde simulado usa agora **80%**; os serviços **já aceites** mantêm os valores antigos (não são recalculados)
- 🗄️ `SELECT id, percentagem_funcionario_aplicada FROM agendamento_servico WHERE funcionario_id = 2;`
  → linhas **novas** com `80.00`, linhas **antigas** com `70.00`
- **Ação (repor):** voltar a **70/30** para não afetar os testes seguintes
- [ ] OK

## BLOCO L — FLUXO END-TO-END: FEEDBACK DO CLIENTE (FASE 2) ⭐

> Este bloco fecha o ciclo **cliente → funcionário → gestor → cliente**.

### L1 — Estado do agendamento
- **Ação:** confirmar que o agendamento da carrinha está **executado** (feito em H6)
- 🗄️ `SELECT estado_reserva FROM agendamento WHERE id = <N>;` → `executado`
- [ ] OK

### L2 — Formulário de avaliação aparece
- **Ação:** **sair** e entrar como **`cliente@teste.pt`** → `/agendamentos`
- **Esperado:** no agendamento **executado** aparece a secção **"Avaliar este serviço"** com **5 estrelas** e caixa de comentário
- ⚠️ Nos agendamentos **não** executados o formulário **não** aparece
- 🅰 `GET ?action=feedback-my`
- [ ] OK

### L3 — Validação: sem classificação
- **Ação:** escrever comentário mas **não** escolher estrelas → **Enviar avaliação**
- **Esperado:** erro **"Escolha uma classificação de 1 a 5 estrelas."** — não envia
- [ ] OK

### L4 — Enviar avaliação
- **Ação:** escolher **5 estrelas** + comentário "Serviço excelente!" → **Enviar avaliação**
- **Esperado:** o formulário é substituído por uma caixa verde com estrelas e comentário
- 🅰 `POST ?action=feedback-create` → **200**
- 🗄️
```sql
SELECT f.id, f.classificacao_estrelas, f.comentario, f.data_feedback
FROM feedback_cliente f
JOIN execucao_agendamento e ON f.execucao_agendamento_id = e.id
WHERE e.agendamento_id = <N>;
-- esperado: 1 linha, estrelas = 5
```
- [ ] OK

### L5 — Não avaliar duas vezes
- **Ação:** recarregar e tentar enviar de novo (via API/console)
- **Esperado:** HTTP **409** — *"Este agendamento já foi avaliado."*
- [ ] OK

### L6 — Feedback aparece no site público ⭐
- **Ação:** ir à **home** (`/`) e observar o carrossel de testemunhos
- **Esperado:** aparece **"João Cliente"** com **5 estrelas** e o comentário; por baixo do título surge *"Média de satisfação: 5 / 5 · 1 avaliação(ões)"*
- ⚠️ Deve **substituir** os testemunhos estáticos (que só aparecem quando **não há** feedback na BD)
- 🅰 `GET ?action=feedback-list&limit=6`
- [ ] OK

### L7 — Feedback de agendamento não executado é rejeitado
- **Ação:** via API, tentar `feedback-create` num agendamento `pendente_validacao_logistica_loja`
- **Esperado:** HTTP **409** — *"Só é possível avaliar agendamentos já executados."*
- [ ] OK

### L8 — Feedback de outro cliente é rejeitado
- **Ação:** via API, tentar avaliar um agendamento que **não é seu**
- **Esperado:** HTTP **403** — *"Só pode avaliar os seus próprios agendamentos."*
- [ ] OK

## BLOCO M — SEGURANÇA E PERFIS

> Testes de autorização — **os mais relevantes para a avaliação**.

### M1 — Sem sessão → 401 nas APIs
- **Ação:** numa janela **anónima**, abrir:
  `/api?action=customer-profile` · `/api?action=booking-my` · `/api?action=feedback-my` · `/api?action=admin-routes-list`
- **Esperado:** todas devolvem `{"success":false,"message":"Sessão não iniciada."}` com **HTTP 401**
- [ ] OK

### M2 — Cliente → 403 nas APIs de gestão
- **Ação:** autenticado como **cliente**, abrir:
  `/api?action=admin-routes-list` · `/api?action=admin-appointments-list` · `/api?action=admin-fiscal-calendar-list` · `/api?action=admin-service-pending-list`
- **Esperado:** todas devolvem **HTTP 403** — *"Sem permissões para esta operação."*
- [ ] OK

### M3 — Gestor → 403 na API de aceitação ⭐
- **Ação:** autenticado como **gestor**, abrir `/api?action=admin-service-pending-list`
- **Esperado:** **HTTP 403** — a aceitação é **exclusiva do funcionário**
- [ ] OK

### M4 — Cliente → redirect nas páginas de gestão
- **Ação:** autenticado como **cliente**, abrir `/gestao/agendamentos`, `/gestao/rotas`, `/gestao/fiscal`, `/gestao/recibos-verdes`
- **Esperado:** todas fazem **redirect** para a home (não mostram conteúdo)
- [ ] OK

### M5 — Funcionário → redirect no fiscal
- **Ação:** autenticado como **funcionário**, abrir `/gestao/fiscal`
- **Esperado:** **redirect** para a home
- [ ] OK

### M6 — Método HTTP errado → 405
- **Ação:** na consola do browser (autenticado):
  ```js
  fetch('/secade-beauty-tarde/api?action=booking-services', { method: 'POST' })
    .then(r => console.log(r.status));   // 405
  ```
- **Esperado:** **405** — *"Invalid HTTP method."*
- [ ] OK

### M7 — Endpoint inexistente → 404
- **Ação:** abrir `/api?action=nao-existe`
- **Esperado:** **404** — *"Invalid endpoint."*
- [ ] OK

### M8 — Validação server-side (não confiar só no browser) ⭐
- **Ação:** na consola, enviar dados inválidos deliberadamente:
  ```js
  fetch('/secade-beauty-tarde/api?action=booking-create-store', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ date: '2020-01-01', time: '10:00', serviceIds: [] })
  }).then(r => console.log(r.status));   // 422
  ```
- **Esperado:** **422** com erros de validação (o servidor **não** confia apenas no cliente)
- [ ] OK

### M9 — Isolamento de dados entre clientes
- **Ação:** registar um **2.º cliente**, entrar com ele e abrir `/agendamentos`
- **Esperado:** **não** vê os agendamentos do `cliente@teste.pt`
- [ ] OK

### M10 — SQL Injection (verificação por tentativa)
- **Ação:** no campo de pesquisa do catálogo escrever `' OR 1=1 --` e no login usar `admin'--` como e-mail
- **Esperado:** **nenhum** comportamento anómalo: a pesquisa devolve vazio e o login falha normalmente (prepared statements)
- [ ] OK

---

## BLOCO N — RESPONSIVIDADE E INTERFACE

### N1 — Navegação mobile
- **Ação:** DevTools → modo dispositivo (**iPhone SE · 375px**)
- **Esperado:** menu hambúrguer funciona; sem scroll horizontal indesejado
- [ ] OK

### N2 — Catálogo em mobile
- **Ação:** `/servicos/cabelereiro` em 375px
- **Esperado:** cards em **1 coluna**; filtros utilizáveis; modal de detalhes legível
- [ ] OK

### N3 — Wizard em mobile
- **Ação:** `/agendar` em 375px; percorrer os passos
- **Esperado:** indicador de passos **quebra linha** corretamente; botões acessíveis; construtor de pessoas utilizável
- [ ] OK

### N4 — Backoffice em tablet
- **Ação:** `/gestao/agendamentos` em **768px**
- **Esperado:** tabelas com scroll horizontal **dentro** do card (não quebra o layout)
- [ ] OK

### N5 — Feedback visual das ações
- **Ação:** repetir uma ação com AJAX (ex.: aceitar um serviço)
- **Esperado:** aparece o **spinner** (overlay do `jq-preloader`) durante o pedido e desaparece no fim
- [ ] OK

### N6 — Estado vazio tratado
- **Ação:** no catálogo, pesquisar `zzzzz`
- **Esperado:** mensagem **"Nenhum serviço corresponde aos filtros selecionados."** (não uma lista vazia muda)
- [ ] OK

### N7 — Sem erros na consola
- **Ação:** DevTools → Console, em cada página principal (home, catálogo, wizard, perfil, agendamentos, 5 páginas de gestão)
- **Esperado:** **sem erros vermelhos** (avisos de terceiros são aceitáveis)
- [ ] **Reportar qualquer erro vermelho**, indicando a página
- [ ] OK

## VERIFICAÇÃO FINAL NA BASE DE DADOS

Depois de percorrer todos os blocos, estas consultas devem devolver um quadro coerente:

```sql
USE secade_beauty;

-- 1. Agendamentos e estados
SELECT a.id, a.local_prestacao, a.data_hora_pretendida, a.estado_reserva, a.valor_total,
       cid.nome AS cidade
FROM agendamento a
LEFT JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
LEFT JOIN cidade cid        ON cm.cidade_id = cid.id
ORDER BY a.id;

-- 2. Serviços, funcionário, aceitação e recibo verde
SELECT s.agendamento_id, s.agendamento_pessoa_id, s.servico_id,
       s.estado_aceitacao, s.funcionario_id,
       s.percentagem_funcionario_aplicada AS pct,
       s.valor_recibo_verde_funcionario   AS rv_func,
       s.valor_recibo_verde_plataforma    AS rv_plat
FROM agendamento_servico s ORDER BY s.agendamento_id, s.id;

-- 3. Estrutura por pessoa
SELECT agendamento_id, id, nome_pessoa FROM agendamento_pessoa ORDER BY agendamento_id, id;

-- 4. Rotas e auditoria da decisão manual
SELECT id, data_rota, cidade_id, estado_rota, custo_estimado_combustivel AS combustivel,
       lucro_servicos AS receita, lucro_total AS rentabilidade,
       decidido_por, decidido_em, LEFT(observacoes_decisao, 60) AS obs
FROM rota_ambulante ORDER BY id;

-- 5. Execução e feedback
SELECT e.agendamento_id, e.estado_execucao, f.classificacao_estrelas, f.comentario
FROM execucao_agendamento e
LEFT JOIN feedback_cliente f ON f.execucao_agendamento_id = e.id
ORDER BY e.id;

-- 6. Fiscal e alertas
SELECT id, tipo, designacao, data_prazo, estado, data_pagamento FROM obrigacao_fiscal ORDER BY id;
SELECT obrigacao_fiscal_id, tipo_alerta, data_alerta, visualizado FROM alerta_fiscal ORDER BY id;

-- 7. Configuração de recibos verdes
SELECT * FROM config_recibo_verde ORDER BY id;
```

**Coerências a confirmar:**
- [ ] Nenhum agendamento de loja tem `agendamento_pessoa_id` preenchido
- [ ] Todos os serviços de **loja** estão `aceite` (aceitação automática)
- [ ] O agendamento da carrinha consolidado tem **0** serviços `pendente`
- [ ] Serviços aceites têm sempre `percentagem_funcionario_aplicada` + os **dois** valores de RV
- [ ] Rota decidida tem `decidido_por` + `decidido_em` preenchidos
- [ ] Cada agendamento executado tem **≤1** linha em `execucao_agendamento` e **≤1** em `feedback_cliente`
- [ ] **Nenhum** alerta fiscal duplicado para a mesma `(obrigacao, tipo, data)`

## ⚠️ LIMITAÇÕES CONHECIDAS — **NÃO SÃO BUGS**

Antes de reportar, confirme que não é um destes casos **intencionais** (simplificações académicas):

| Comportamento                                                  | Porque é intencional                                                        |
| :------------------------------------------------------------- | :-------------------------------------------------------------------------- |
| OTP aparece no ecrã                                            | Simulação de SMS (sem gateway), conforme a especificação §22.1              |
| Não há pagamento real                                          | Pagamentos/sinal **simulados**; `sinal_pago` fica `0`                       |
| Não há e-mails nem SMS                                         | Notificações simuladas (aparecem em mensagens/UI)                           |
| Não existe botão "Validar Rotas" nem algoritmo de 100 €        | **Revogado**: a decisão é manual (Fase 4) — ver `especificacao_mvp.md` §3.1 |
| Os 50 € não mudam estados                                      | É **apenas indicador visual** (intencional)                                 |
| Categorias não limitam a aceitação do funcionário              | São **filtros visuais** (`especificacao_mvp.md` §3.2)                       |
| "Profissional" não aparece na carrinha e é informativo na loja | Não existem funcionários associados a *slots* na BD                         |
| `quota_parte_cliente` fica a 0                                 | Não há regra de negócio definida para o seu cálculo                         |
| Registar morada exige escolher sugestão do Nominatim           | Validação **anti-erro** de morada; requer **internet**                      |
| Só as 10 cidades do distrito de Évora são aceites              | Regra de negócio (área de cobertura)                                        |
| Backoffice em `modules/backoffice/` e não em `admin/`          | Instrução explícita de não tocar em `/admin`                                |
| Sem upload de foto de perfil                                   | Fora do âmbito do MVP (usa placeholder)                                     |
| O filtro do cliente não permite filtrar por data               | Não especificado no MVP                                                     |

---

## 🔧 TROUBLESHOOTING

| Sintoma                                                       | Causa provável                                           | Como resolver                                                      |
| :------------------------------------------------------------ | :------------------------------------------------------- | :----------------------------------------------------------------- |
| **"Erro crítico: A ligação à base de dados não foi..."**      | MySQL parado                                             | arrancar MySQL no Laragon                                          |
| Página em branco / 500                                        | erro PHP                                                 | ver `C:\laragon\logs\`; correr `php -l` no ficheiro                |
| **`Field 'morada' doesn't have a default value`** ao registar | BD **v1** sem a migração v3 aplicada                     | correr `database_migration_v3.sql` (§2.5) **ou** reimportar (§2.3) |
| Catálogo vazio / `Unknown column 's.ativo'`                   | BD **v1/v2** sem a coluna `ativo`                        | correr `database_migration_v3.sql` (§2.5) **ou** reimportar (§2.3) |
| **Não consigo fazer login** / utilizador inexistente          | faltou importar o `database_seed.sql`                    | correr o **passo 2 da secção 2.3**                                 |
| Catálogo com **0 serviços** / 0 cidades                       | importou `DataBase.sql` (v1) em vez de `DataBase_v2.sql` | reimportar pela **secção 2.3**                                     |
| Login entra mas navbar não mostra o nome / tudo dá 403        | sessão sem perfil (linhas em branco antes de `<?php`)    | verificar se `connection.php` **não** tem espaços                  |
| Registo de morada não avança ("selecione uma morada válida")  | não escolheu sugestão, ou **sem internet**               | escolher uma sugestão da lista; ligar à internet                   |
| Não consigo agendar (todos os slots bloqueados)               | agendamentos de testes anteriores na mesma data          | correr o **reset** (secção 4) ou usar **outra data**               |
| `Já existe um agendamento confirmado nesta janela horária`    | conflito de janela **esperado**                          | escolher outro horário/data (é um teste válido)                    |
| Não aparecem serviços "Por aceitar"                           | já foram aceites, ou o agendamento não é de ambulatório  | verificar `estado_aceitacao` e `local_prestacao` na BD             |
| **Não consigo desfazer** a aceitação                          | agendamento **consolidado**                              | comportamento correto (**409**) — não é bug                        |
| Não aparece o formulário de feedback                          | agendamento não está `executado`                         | registar a execução no backoffice (**H6**)                         |
| "O agendamento já foi avaliado"                               | 1 avaliação por agendamento                              | comportamento correto (**409**)                                    |
| `fetch` no console dá erro de caminho                         | URL sem o prefixo correto                                | usar sempre `/secade-beauty-tarde/api?action=…`                    |
| Alterações não aparecem                                       | cache do browser                                         | **Ctrl+Shift+R** (hard reload)                                     |

## ✅ CHECKLIST GLOBAL (resumo)

| Bloco | Âmbito                                                    | Testes   | Concluído |
| :---: | :-------------------------------------------------------- | :------: | :-------: |
| **A** | Site público (home, catálogo, 404, acessos)               | 10       | [ ]       |
| **B** | Autenticação e registo                                    | 7        | [ ]       |
| **C** | Perfil e moradas                                          | 7        | [ ]       |
| **D** | Agendamento **Loja Física** (5 passos)                    | 14       | [ ]       |
| **E** | Agendamento **Carrinha + OTP** (7 passos)                 | 20       | [ ]       |
| **F** | Histórico do cliente                                      | 5        | [ ]       |
| **G** | **Fase 3** — aceitação, desfazer, consolidação, bloqueio  | 12       | [ ]       |
| **H** | Agendamentos: detalhe por serviço/funcionário + execução  | 9        | [ ]       |
| **I** | **Fase 4** — decisão **manual** de rotas + indicador 50 € | 9        | [ ]       |
| **J** | Calendário Fiscal + alertas progressivos                  | 12       | [ ]       |
| **K** | Simulador de Recibos Verdes (configuração)                | 5        | [ ]       |
| **L** | **Fase 2** — feedback end-to-end                          | 8        | [ ]       |
| **M** | Segurança e perfis                                        | 10       | [ ]       |
| **N** | Responsividade e interface                                | 7        | [ ]       |
|       | **TOTAL**                                                 | **~135** |           |

### Os 5 testes que mais importam para a avaliação
1. **E19** — carrinha cria agendamento `pendente_aceitacao_funcionarios` com **estrutura por pessoa** e valor **16,27 €**
2. **G9 + G10** — a consolidação muda o estado **e** bloqueia o desfazer (409)
3. **I4 / I5** — **prova de que a decisão é manual**: aprova abaixo dos 50 € e recusa acima
4. **L6** — o feedback do cliente aparece **publicamente** na home
5. **M2 / M3 / M4** — perfis respeitados (403 nas APIs, redirect nas páginas)

---

## 📝 REGISTO DE DEFEITOS ENCONTRADOS

| #   | Bloco | Teste | Descrição | Severidade | Evidência (print/console/BD) |
| --- | ----- | ----- | --------- | ---------- | ---------------------------- |
| 1   |       |       |           |            |                              |
| 2   |       |       |           |            |                              |
| 3   |       |       |           |            |                              |
| 4   |       |       |           |            |                              |
| 5   |       |       |           |            |                              |

**Severidade:** 🔴 Bloqueante · 🟠 Grave · 🟡 Menor · 🔵 Cosmético

---

**Fim do guia.** Mapa do fluxo de dados: ver `mapa_fluxo_dados.md` (mesma pasta).
