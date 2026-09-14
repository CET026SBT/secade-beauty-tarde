# SECADE BEAUTY - Sistema de Agendamentos
## Projeto Académico | Entrega: 21/09/2026

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
│   ├── config/            # Configurações e API routing
│   ├── controllers/       # Camada de controlo
│   ├── services/          # Lógica de negócio
│   ├── repositories/      # Acesso a dados
│   └── utils/             # Validator, Session
├── modules/               # Interface frontend
│   ├── common/           # Recursos partilhados
│   └── main/             # Páginas públicas
├── Database.sql          # Schema da base de dados
├── index.php             # Front Controller
└── .htaccess             # Rewrite rules
```

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

2. **Criar a base de dados:**
   - Abrir HeidiSQL
   - Executar o ficheiro `Database.sql`
   - Verificar criação de 17 tabelas

3. **Configurar conexão** (se necessário):
   - Editar `app/config/connection.php`
   - Verificar credenciais MySQL (default: root / sem password)

4. **Aceder ao sistema:**
   ```
   http://localhost/secade-beauty-tarde
   ```

---

## 📋 FUNCIONALIDADES IMPLEMENTADAS

### ✅ Já Funcionais
- [x] Autenticação (login/logout)
- [x] Registo de clientes (wizard multi-step)
- [x] Validação robusta (email, NIF, telefone)
- [x] Gestão de sessões
- [x] API REST básica

### 🔨 Em Desenvolvimento (Semana 1 - PRIORIDADE ALTA)
- [ ] Catálogo de serviços completo
- [ ] **Wizard agendamento LOJA FÍSICA** (5 steps)
- [ ] **Wizard agendamento CARRINHA AMBULANTE** (7 steps + OTP) - OBRIGATÓRIO
- [ ] Sistema de disponibilidade de slots
- [ ] **Algoritmo de Viabilidade de Rotas** - OBRIGATÓRIO
- [ ] **Backoffice completo** (agendamentos + rotas)

---

## 📄 DOCUMENTAÇÃO TÉCNICA

Consultar os seguintes ficheiros na raiz do projeto:

1. **`tecnologias_projeto.md`** - Inventário técnico completo
2. **`fluxo_funcionalidades.md`** - Especificação funcional
3. **`plano_desenvolvimento.md`** - Cronograma de 7 dias
4. **`.clinerules`** - Regras permanentes do projeto

---

## 🧪 TESTES

### Criar um Cliente
1. Aceder a http://localhost/secade-beauty-tarde/registo
2. Preencher wizard de registo (3 steps)
3. Submeter formulário

### Login
1. Aceder a http://localhost/secade-beauty-tarde/login
2. Usar email e password cadastrados
3. Verificar redirecionamento

### API
Testar endpoints com Postman/Insomnia:
```
POST http://localhost/secade-beauty-tarde/api?action=auth-login
Body: { "email": "teste@test.com", "password": "Test@123" }
```

---

## 🔒 RESTRIÇÕES E SIMPLIFICAÇÕES

### Académicas
✅ **Pagamentos:** Simulação (sem gateway real)  
✅ **SMS/Email:** Log ou alert() na tela  
✅ **OTP:** Código mostrado sem envio real  
✅ **Rotas:** Validação manual (sem CRON)

### Técnicas
❌ **Proibido:** Instalar novos pacotes  
❌ **Proibido:** Usar frameworks externos  
❌ **Proibido:** Modificar pasta `/admin`  
✅ **Obrigatório:** Prepared statements (segurança)

---

## 👥 EQUIPA

**Projeto Académico - Turma Tarde**  
**Curso:** Desenvolvimento Web  
**Instituição:** [Nome da Instituição]  
**Ano Letivo:** 2026

---

## 📅 CRONOGRAMA (Semana 14-21/09)

- **Dia 1 (14/09):** Planeamento e documentação ✅
- **Dia 2 (15/09):** Backend (Serviços + Agendamentos)
- **Dia 3 (16/09):** Frontend (Catálogo de serviços)
- **Dia 4 (17/09):** Wizard LOJA FÍSICA completo
- **Dia 5 (18/09):** **Wizard CARRINHA AMBULANTE + OTP (OBRIGATÓRIO)**
- **Dia 6 (19/09):** **Algoritmo Viabilidade + Backoffice Rotas (OBRIGATÓRIO)**
- **Dia 7 (20/09):** Integração final + Testes + Refinamentos

**Data Entrega:** 21/09/2026 (Sábado)

### 🎯 Funcionalidades OBRIGATÓRIAS para Entrega
- ✅ Agendamento Loja Física (100%)
- ✅ **Agendamento Carrinha Ambulante (100%)**
- ✅ **Algoritmo de Viabilidade de Rotas**
- ✅ **Backoffice completo (Agendamentos + Rotas)**

---

## 📞 SUPORTE

Para questões sobre o projeto, consultar:
- Documentação técnica (`tecnologias_projeto.md`)
- Fluxos funcionais (`fluxo_funcionalidades.md`)
- Plano de desenvolvimento (`plano_desenvolvimento.md`)

---

## 📜 LICENÇA

Projeto académico - Todos os direitos reservados © 2026

---

**Versão:** 1.0 | **Data:** 14/09/2026 | **Status:** 🟢 EM DESENVOLVIMENTO
