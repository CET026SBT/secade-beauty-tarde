# SECADE BEAUTY - Sistema de Agendamentos
> 📌 **DOCUMENTO-MESTRE:** a **especificação única e centralizada** do projeto (requisitos, decisões,
> regras de negócio, arquitetura, BD, API, estados, testes e instalação) está em
> **`especificacao_mvp.md`** — que **prevalece** sobre os restantes `.md`.
> Este README mantém-se como guia de **instalação e uso rápido**. Mapa documental: §29.2 do documento-mestre.
>
> ⚠️ **Onde vive o documento-mestre:** `especificacao_mvp.md`, `mapaMentalMVP/`, `tools/` e
> `.clinerules` **não são versionados nas branches de produto** (`main`, `dev` e restantes) — existem
> apenas na branch **`agent-workspace`**, que **nunca é integrada**. As **regras de Git** estão em
> `.clinerules` §4; o inventário dos ramos, na secção **Ramos do repositório** mais abaixo.

## Projeto Académico · CET026 · Turma Tarde

---

## 📖 SOBRE O PROJETO

**Secade Beauty** é um sistema de gestão de agendamentos para um salão de beleza híbrido que opera em dois modelos:
- **Loja Física** em Évora (Terça a Sábado, 09:00-19:00)
- **Carrinha Ambulante** itinerante (9 cidades do Alentejo)

**Público-alvo:** Idosos com mobilidade reduzida, famílias rurais, procurando serviços de beleza e bem-estar acessíveis.

---

## 🛠️ STACK TECNOLÓGICA

- **Backend:** PHP puro + PDO + MySQL 8.4.3
- **Frontend:** HTML5 + CSS3 + JavaScript + Bootstrap 5.0.0
- **Servidor:** Laragon (Apache + MySQL + PHP)
- **Arquitetura:** MVC Custom (sem frameworks externos)

---

## 📂 ESTRUTURA DO PROJETO

```
secade-beauty-tarde/
├── app/                    # Aplicação backend
│   ├── config/            # Configurações, conexão e API routing
│   ├── controllers/       # Camada de controlo
│   ├── services/          # Lógica de negócio
│   ├── repositories/      # Acesso a dados (PDO)
│   ├── mappers/           # Tradução BD (PT) -> código (EN)
│   └── utils/             # Validator, Session, ValidationException
├── modules/               # Interface frontend
│   ├── common/           # Recursos partilhados (JS, CSS, libs)
│   ├── main/             # Páginas públicas (clientes)
│   └── backoffice/       # Área de gestão (gestor)
├── README.md              # Este ficheiro (instalação + uso)
├── tests/                 # Testes automatizados (CLI + HTTP)
├── index.php              # Front Controller
├── DataBase_v2.sql        # Schema ATUAL (v2+): 24 tabelas + dados de referência
├── database_seed.sql      # Dados de demonstração/teste (utilizadores + morada)
├── database_migration_v2.sql  # Migração incremental v1 -> v2 (uso único)
├── database_migration_v3.sql  # Migração incremental v2 -> v3 (idempotente)
├── DataBase.sql           # [legado] dump v1 — não usar em instalações novas
├── DataBase_backup_pre_v2.sql # [arquivo] cópia do estado antes da v2
└── .htaccess              # Rewrite rules

[existe apenas na branch agent-workspace — ver "Ramos do repositório"]
especificacao_mvp.md       # Documento-mestre (fonte única de verdade)
mapaMentalMVP/             # Apoio a testes: guia manual + mapa de fluxo de dados
tools/                     # Utilitários de manutenção dev-only (encoding, .md)
.clinerules                # Regras permanentes do assistente
```

---

## 🌿 RAMOS DO REPOSITÓRIO (BRANCHES)

> 📌 **Fonte única das regras de Git:** `.clinerules` §4 (modelo de branches, proibições de commit,
> integração por merge/PR e a branch `agent-workspace`) — na branch `agent-workspace`. Aqui fica
> apenas o **inventário dos ramos**, para orientação rápida.

| Branch            | Papel                                                        |
| :---------------- | :----------------------------------------------------------- |
| `main`            | Código final de qualidade — 100 % funcional de ponta a ponta |
| `dev`             | Desenvolvimento — estado mais avançado do projeto            |
| `agent-workspace` | Documento-mestre, `mapaMentalMVP/`, `tools/` e `.clinerules` |
| restantes         | Branches de trabalho (contexto, funcionalidade, correção)    |

- A `agent-workspace` **nunca é integrada** em `dev` nem em `main`.
- O `README.md`, o `tests/` e todo o código de produto são versionados **normalmente** em `dev`.

### Branches de contexto integradas em `dev`

| Branch                     | Âmbito                                                             |
| -------------------------- | ------------------------------------------------------------------ |
| `database-schema`          | Schema v2, migrações incrementais e dados de referência            |
| `core-stabilization`       | Front controller, mapa de endpoints da API e infraestrutura base   |
| `repo-hygiene`             | `.gitignore` e `.clinerules` fora do controlo de versão do produto |
| `frontend-layout`          | Layout, tema, preloader e assets partilhados                       |
| `customer-auth`            | Registo, login, perfil, moradas e cidades                          |
| `service-catalog`          | Categorias e catálogo de serviços                                  |
| `booking-wizard`           | Wizard de agendamento (loja física e carrinha) + OTP               |
| `backoffice`               | Agendamentos, rotas, serviços e aceitação por funcionário          |
| `fiscal-receipts-feedback` | Calendário fiscal, recibos verdes e feedback do cliente            |
| `docs-and-tests`           | README e testes automatizados                                      |
| `docs-branch-model`        | Modelo de ramos documentado neste README                           |

---

## 🚀 INSTALAÇÃO

### Pré-requisitos
- **Laragon** instalado e em execução
- **MySQL 8.x** ativo
- **PHP 7.4+**

### Passos

1. **Clonar/copiar o projeto** para a pasta do Laragon:
   ```
   C:\laragon\www\secade-beauty-tarde
   ```

2. **Criar a base de dados (opção A — instalação limpa):**
   - Abrir HeidiSQL (ou usar a linha de comandos, ver abaixo)
   - Executar **`DataBase_v2.sql`** — cria a BD `secade_beauty`, as 24 tabelas e os dados de
     referência (categorias, cidades, base de partida, matriz de deslocação, 35 serviços)
   - Executar **`database_seed.sql`** — dados de demonstração (3 utilizadores, 1 morada)

   > ✅ **Não é preciso correr as migrações na opção A.** O `DataBase_v2.sql` já inclui tudo o
   > que elas fazem (a coluna `servico.ativo`, a correção do legado `cliente.morada` e a
   > ausência da tabela `funcionario_categoria`).
   > Correr a `database_migration_v3.sql` é **inofensivo** (é idempotente), mas desnecessário.

   **Opção B — BD já existente (atualização, preserva dados):**
   - Executar `database_migration_v2.sql` (só se a BD ainda estiver na **v1**; é de uso único)
   - Executar `database_migration_v3.sql` (idempotente — pode correr em qualquer schema)
     - além de `servico.ativo` e `cliente.morada`, **remove a tabela `funcionario_categoria`**
       (relação N:N eliminada — as categorias são apenas filtros visuais)
   - Executar `database_seed.sql`

   **Alternativa por linha de comandos (Laragon / PowerShell):**
   ```powershell
   $mysql = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'

   & $mysql -u root --default-character-set=utf8mb4 -e "source DataBase_v2.sql"
   & $mysql -u root --default-character-set=utf8mb4 -e "source database_seed.sql"
   ```
   > ⚠️ Em PowerShell a redireção `<` não funciona (`mysql -u root < ficheiro.sql` falha).
   > Use `-e "source ficheiro.sql"`.

   **Confirmar a importação** (esperado: 24 / 35 / 10 / 3):
   ```sql
   USE secade_beauty;
   SELECT
    (SELECT COUNT(*) FROM information_schema.tables
      WHERE table_schema = 'secade_beauty') AS tabelas,
    (SELECT COUNT(*) FROM servico)          AS servicos,
    (SELECT COUNT(*) FROM cidade)           AS cidades,
    (SELECT COUNT(*) FROM utilizador)       AS utilizadores;
   ```

3. **Configurar conexão** (se necessário):
   - Editar `app/config/connection.php`
   - Verificar credenciais MySQL (default: root / sem password)

4. **Aceder ao sistema:**
   ```
   http://localhost/secade-beauty-tarde
   ```

---

## 🔑 CREDENCIAIS DE DEMONSTRAÇÃO

Criadas por `database_seed.sql`:

| Perfil      | E-mail                  | Password      | Acesso                                                                   |
| ----------- | ----------------------- | ------------- | ------------------------------------------------------------------------ |
| Gestor      | `gestor@secade.pt`      | `Gestor@123`  | Entra no backoffice em `/gestao/agendamentos` (e rotas, fiscal, recibos) |
| Cliente     | `cliente@teste.pt`      | `Cliente@123` | Marcações, perfil e moradas (morada pré-criada em Évora)                 |
| Funcionário | `funcionario@secade.pt` | `Func@12345`  | Entra no backoffice em `/gestao/servicos` (aceitação de serviços)        |

> O **cliente não tem acesso** ao backoffice: os endpoints `admin-*` e as páginas `/gestao/*` validam o perfil (401/403/redirect).

---

## 📋 FUNCIONALIDADES IMPLEMENTADAS

### ✅ Já Funcionais
- [x] Autenticação (login/logout) com sessão por perfil
- [x] Registo de clientes (wizard multi-step)
- [x] Validação robusta (email, NIF, telefone, CC, código postal) client + server
- [x] Gestão de sessões e de perfis (cliente / funcionário / gestor)
- [x] API REST básica (padrão `?action=dominio-acao`)

### ✅ Implementado (Fases 1-5)
- [x] **Catálogo de serviços** — `/servicos` e `/servicos/<categoria>`, filtros (categoria, preço, duração, pesquisa), modal de detalhes e badge "Apenas Loja"
- [x] **Wizard de Agendamento LOJA FÍSICA** (5 passos: canal → serviços → data/hora → profissional → resumo) com slots de 30 min e sinal de 10% simulado
- [x] **Wizard de Agendamento CARRINHA AMBULANTE** (7 passos: canal → serviços/pessoas → morada → OTP → data/hora → sinal → resumo)
- [x] **OTP simulado** (código de 6 dígitos mostrado no ecrã, validado contra a sessão, com expiração de 10 min)
- [x] **Estrutura por pessoa** no ambulatório ("Pessoa 1..N" com serviços partilhados)
- [x] **Backoffice de Agendamentos** — lista paginada, filtros (data, local, estado), detalhe por serviço/funcionário, cancelamento e registo de execução
- [x] **Backoffice de Rotas** — listagem por dia+cidade, custos/rentabilidade, **decisão MANUAL** (aprovar/recusar) com indicador de 50 € apenas visual
- [x] **Perfil do cliente** — dados pessoais e gestão de moradas (criar, definir principal, remover)
- [x] **Histórico de agendamentos** do cliente com filtros por estado
- [x] Página de confirmação de agendamento (`/agendamento-sucesso`)
- [x] Interface responsiva (mobile-first) em todos os fluxos

### ✅ Fases 3 e 4 (implementadas)
- [x] **Aceitação individual de serviços** pelos funcionários (`/gestao/servicos`), com categorias como filtros visuais
- [x] **Desfazer/trocar** aceitação enquanto o agendamento não estiver consolidado
- [x] **Consolidação "Totalmente Aceite por Funcionários"** ao aceitar o último serviço, com **bloqueio da janela temporal**
- [x] **Simulador de Recibos Verdes** — percentagens configuráveis com vigência por data (`/gestao/recibos-verdes`), cálculo registado na aceitação
- [x] **Calendário Fiscal** (`/gestao/fiscal`) — IVA, IRC, Segurança Social e Seguros, com **alertas progressivos 30/15/7/3/1 dia + diários em atraso**
- [x] **Feedback do cliente** após execução do serviço, com reflexo público nos testemunhos da página inicial
- [x] **Backoffice segregado por perfil**: o funcionário vê Serviços (aceitação) e o gestor vê Agendamentos, Rotas, Fiscal e Recibos Verdes

### 🔭 Trabalho futuro (prioridade definida em `especificacao_mvp.md` §25)

**Prioridade 1 — Fornecedores** (gestão de fornecedores no backoffice)

**Prioridade 2 — Requisitos adicionais** (detalhe no §24 do documento-mestre):
- [ ] **Cancelamento do agendamento pelo cliente** + regra das **24 h** + lembrete com alternativas
- [ ] **Sinal configurável** no backoffice + cobrança dos 90 % + **métodos de pagamento** simulados
- [ ] **Re-avaliação dinâmica dos slots** quando os serviços mudam no wizard
- [ ] **Rotas multicidades** (com validação de espaçamento) + flexibilidade horária + alertas padronizados
- [ ] **Página de detalhes de serviço** com carousel de imagens
- [ ] Encaminhamento por **tipo de contrato** (loja vs. recibo verde)

**Prioridade 3 — Consolidações técnicas:**
- [ ] OTP/SMS e e-mail reais (integração com gateway)
- [ ] Gateway de pagamento real
- [ ] CRON/agendador para geração automática de alertas fiscais
- [ ] Migração do backoffice de `modules/backoffice/` para a pasta raiz `admin/`
- [ ] UI para `transacao_financeira`, `fecho_caixa_diario` e `gorjeta`

---

## 📄 DOCUMENTAÇÃO TÉCNICA

Toda a documentação está **centralizada**; os documentos de planeamento antigos foram consolidados
num único ficheiro e removidos, para evitar divergência de informação e reduzir o atrito de manutenção:

1. **`especificacao_mvp.md`** — ⭐ **Documento-mestre (fonte única de verdade)**: requisitos, decisões
   finais, regras de negócio, modelo de dados (com diagrama de relações), arquitetura e convenções,
   API, máquina de estados, testes e instalação. **Prevalece** em caso de conflito.
2. **`README.md`** — este ficheiro: instalação e uso rápido.
3. **`mapaMentalMVP/`** — apoio aos testes: `guia_teste_manual.md` (guia passo-a-passo) e
   `mapa_fluxo_dados.md` (mapa visual do fluxo de dados ponta-a-ponta).
4. **`.clinerules`** — regras permanentes do projeto.
5. **`tools/`** — utilitários de manutenção (encoding seguro, formatação/validação dos `.md`); guia próprio em `tools/README.md`.

---

## 🧪 TESTES

> 📌 **Fonte única:** **`tests/README.md`** — suites, âmbito de cada uma, comandos de execução,
> pré-requisitos (Apache e MySQL) e garantias de repetibilidade. Estado atual: **289 verificações,
> todas a passar** (105 funcionais + 119 HTTP + 65 de assets + sintaxe JS).
>
> Testes **manuais** (fluxos por interface, navegação mobile, responsividade):
> `mapaMentalMVP/guia_teste_manual.md`. Critérios de aceitação: `especificacao_mvp.md` §28.

### Manual — Criar um Cliente
1. Aceder a http://localhost/secade-beauty-tarde/registo
2. Preencher o wizard de registo (2 steps): dados pessoais → telemóvel + morada (autocomplete)
3. Submeter formulário

### Manual — Login
1. Aceder a http://localhost/secade-beauty-tarde/login
2. Usar email e password cadastrados (ver credenciais de demonstração)
3. Verificar redirecionamento

### Manual — Agendamento em Loja (5 passos)
1. `/agendar` → selecionar serviços → "Loja Física"
2. Escolher data (Terça a Sábado) e hora
3. Confirmar no resumo → estado `pendente_validacao_logistica_loja`

### Manual — Agendamento Carrinha (7 passos + OTP)
1. `/agendar` → selecionar serviços (sem "Apenas Loja") → "Carrinha Ambulante"
2. Morada + pessoas/serviços → "Enviar Código" e usar o OTP mostrado no ecrã
3. Data/hora → política de sinal → confirmar → estado `pendente_aceitacao_funcionarios`

### Manual — Fase 3: Aceitação (funcionário)
1. Login como `funcionario@secade.pt` → `/gestao/servicos`
2. "Aceitar serviço" (mostra o recibo verde simulado)
3. Aceitar o **último** serviço → o agendamento fica **Totalmente Aceite** (bloqueia a janela temporal) e o botão "Desfazer" é bloqueado a partir daí

### Manual — Fase 4: Decisão manual de rotas (gestor)
1. Login como `gestor@secade.pt` → `/gestao/rotas`
2. Escolher a data → ver receita, custo, rentabilidade e o **indicador de 50 €** (apenas visual)
3. Clicar ✅ **Aprovar** ou ❌ **Recusar** → agendamentos passam a `confirmado`/`cancelado`

### Manual — Fase 4: Calendário Fiscal
1. `/gestao/fiscal` → "Nova obrigação" (IVA/IRC/SS/Seguros) com prazo
2. Verificar o alerta progressivo (30/15/7/3/1 dia ou "Em atraso") e marcar como pago

### Manual — Fase 3/4: Recibos Verdes e Feedback
1. `/gestao/recibos-verdes` → alterar percentagens (a soma tem de ser 100) e guardar
2. Em `/gestao/agendamentos` → detalhe → "Registar execução do serviço"
3. Em `/agendamentos` (cliente) → avaliar com estrelas + comentário → reflete-se nos testemunhos da home

### Manual — Backoffice de Agendamentos
1. `/gestao/agendamentos` → filtrar por data/local/estado
2. Abrir detalhe (ícone do olho, com serviços e funcionários) ou cancelar (ícone ✕)

### API
Testar endpoints com Postman/Insomnia:
```
POST http://localhost/secade-beauty-tarde/api?action=auth-login
Body: { "email": "teste@test.com", "password": "Test@123" }
```

---

## 🔒 RESTRIÇÕES E SIMPLIFICAÇÕES

> 📌 **Fonte única:** simplificações académicas e limitações em `especificacao_mvp.md` §22; restrições
> de execução em `.clinerules` §1 (ambos na branch `agent-workspace`). Resumo: pagamentos, SMS/e-mail
> e OTP **simulados** · rotas decididas **manualmente** (sem CRON) · **sem** pacotes ou frameworks
> externos · **sem** tocar em `/admin` · **prepared statements** obrigatórios.

---

## 👥 EQUIPA

**Projeto Académico - CET026 - Turma Tarde**  
**Curso:** Técnico Especialista em Tecnologias e Programação de Sistemas de Informação
**Instituição:** IEFP
**Ano Letivo:** 2026

---

## 📅 ROADMAP POR FASES

> Estado e âmbito de cada fase: `especificacao_mvp.md` §21 (branch `agent-workspace`).

| Fase | Âmbito                                                                                 | Estado       |
| ---- | -------------------------------------------------------------------------------------- | ------------ |
| 1    | Catálogo, categorias, autenticação, registo, perfil/moradas                            | ✅           |
| 2    | Wizards de agendamento (Loja e Carrinha + OTP)                                         | ✅           |
| 3    | Backoffice do Funcionário (aceitação, consolidação, recibos verdes)                    | ✅           |
| 4    | Backoffice do Gestor (agendamentos, rotas **manuais**, fiscal, config. recibos verdes) | ✅           |
| 5    | Integração e testes (289 verificações)                                                 | ✅           |
| 6    | Requisitos adicionais (ver §24 do documento-mestre)                                    | ⬜ a iniciar |

---

## 📞 SUPORTE

Para questões sobre o projeto, consultar o **documento-mestre** `especificacao_mvp.md`
(índice no §0 e anexos no §29). Para testar manualmente, usar `mapaMentalMVP/guia_teste_manual.md`.
Ambos residem na branch **`agent-workspace`** (nunca integrada em `dev`/`main`).

---

## 📜 LICENÇA

Projeto académico - Todos os direitos reservados © 2026

---

**Versão:** 2.1 | **Data:** 24/09/2026 | **Status:** 🟢 Fases 1-5 concluídas · Fase 6 em curso
