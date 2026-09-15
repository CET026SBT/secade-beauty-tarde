# INVENTÁRIO TÉCNICO - SECADE BEAUTY
## Projeto Académico - Entrega: 21/09/2026

---

## 1. STACK TECNOLÓGICA UTILIZADA

### Backend
- **Linguagem:** PHP 7.4+ (Laragon)
- **Base de Dados:** MySQL 8.4.3 (MySQL Community Server - GPL)
- **Acesso a Dados:** PDO (PHP Data Objects) com prepared statements
- **Arquitetura:** MVC Custom (sem frameworks externos)
- **Servidor Web:** Apache 2.4.x (via Laragon)

### Frontend
- **Markup:** HTML5
- **Estilos:** CSS3 + Bootstrap 5.0.0
- **Scripts:** JavaScript Vanilla (ES6+)
- **Biblioteca AJAX:** jQuery 3.x
- **Responsividade:** Bootstrap Grid System

### Bibliotecas UI Existentes
- **Bootstrap 5.0.0** - Framework CSS principal
- **Bootstrap Icons / Font Awesome** - Iconografia
- **Owl Carousel 2** - Carrosséis de imagens
- **WOW.js + Animate.css** - Animações de scroll
- **Lightbox** - Galeria de imagens
- **Isotope** - Filtros dinâmicos

### Ambiente de Desenvolvimento
- **Servidor Local:** Laragon (Windows)
- **Base URL:** http://localhost/secade-beauty-tarde
- **Charset:** UTF-8 (universal)
- **Timezone:** Europe/Lisbon
- **Ferramentas:** HeidiSQL 12.8.0.6908, VS Code

---

## 2. ESTRUTURA DE BASE DE DADOS (RESUMO)

### Tabelas Críticas Implementadas

#### Utilizadores e Autenticação
- **`utilizador`** - Tabela mãe (id, nome, email, password_hash, telemovel, nif, tipo_perfil)
- **`cliente`** - Herança de utilizador (morada, telemovel_validado_otp)
- **`funcionario`** - Herança de utilizador (tipo_contrato, salario_base, cc)

#### Catálogo de Serviços
- **`categoria_profissional`** - 3 categorias: Cabeleireiro, Barbearia, Estética
- **`servico`** - 35 serviços cadastrados (preço €4.07 a €48.78)
- **`servico_local`** - Parametrização por canal (loja_fisica / carrinha_ambulante)
- **`servico_foto`** - Galeria de imagens

#### Sistema de Agendamentos
- **`agendamento`** - Registro principal (cliente_id, local_prestacao, data_hora_pretendida, estado_reserva, valor_total)
- **`agendamento_servico`** - Relação N:N com serviços e funcionários
- **`execucao_agendamento`** - Registro de execução
- **`execucao_servico`** - Detalhes por serviço

#### Logística Ambulante
- **`cidade`** - 10 cidades do Alentejo
- **`base_partida`** - Évora (ponto fixo)
- **`matriz_deslocacao`** - Custos de combustível por cidade
- **`rota_ambulante`** - Planejamento (estado_rota, valor_rentabilidade_calculado)
- **`rota_funcionario`** - Alocação de funcionários

#### Financeiro
- **`transacao_financeira`** - Pagamentos (suporte offline com recibo_manual_numero)
- **`fecho_caixa_diario`** - Auditoria (diferença entre esperado e recolhido)
- **`gorjeta`** - Registro opcional

#### Feedback
- **`feedback_cliente`** - Avaliações (1-5 estrelas, comentário)

**Total:** 17 tabelas relacionadas com integridade referencial (InnoDB)

---

## 3. ARQUITETURA MVC

```
secade-beauty-tarde/
├── app/                          # Aplicação
│   ├── config/                  # Configurações
│   ├── controllers/             # Controlo (recebe requests)
│   ├── services/                # Lógica de negócio
│   ├── repositories/            # Acesso a dados (SQL)
│   └── utils/                   # Validator, Session
│
├── modules/                     # Apresentação
│   ├── common/                  # Recursos partilhados
│   └── main/                    # Interface pública
│
├── index.php                    # Front Controller
├── .htaccess                    # Rewrite rules
└── Database.sql                 # Schema BD
```

---

## 4. FUNCIONALIDADES JÁ IMPLEMENTADAS ✅

- ✅ Autenticação (login/logout com password hashing)
- ✅ Registo de clientes (wizard multi-step)
- ✅ Validação robusta (email, password, NIF, telefone PT/BR)
- ✅ Gestão de sessões seguras
- ✅ API REST básica (auth-login, auth-register, city-supported)
- ✅ Interface pública (home, sobre, contacto)

---

## 5. FUNCIONALIDADES MVP (A IMPLEMENTAR)

### PRIORIDADE ALTA (Obrigatório)
- [ ] Catálogo de Serviços completo (filtros, detalhes)
- [ ] Wizard de Agendamento Loja Física (5 steps)
- [ ] Backend de Agendamentos (validação de slots, transações)
- [ ] Backoffice básico (lista, cancelamento)

### PRIORIDADE MÉDIA
- [ ] Agendamento Carrinha (pendente validação)
- [ ] Histórico do cliente
- [ ] Feedback simples

### FORA DE ESCOPO
- OTP real via SMS
- Gateway de pagamento
- Job CRON automático
- App móvel

---

## 6. RESTRIÇÕES TÉCNICAS

❌ **Proibido:** Instalar pacotes, usar frameworks externos, alterar `/admin`  
✅ **Permitido:** PHP nativo, PDO, Bootstrap 5, jQuery, libs existentes

---

## 7. CONVENÇÕES

- **Classes:** PascalCase (`ScheduleService`)
- **Métodos:** camelCase (`createSchedule()`)
- **Tabelas:** snake_case (`agendamento_servico`)
- **Controllers/Services/Repositories** As classes, especialmente os Services e Repositories, devem reflectir fielmente o modelo na BD, 
- também deve ser adotada uma convenção o mais uniforme possivel para os nomes dos endpoints e dos metodos (ver implementação atual)
- nos repositories, evitar usar inner joins entre tabelas, preferir usar o transaction e tratar de cada tabela com metodos especificos na sua respectiva Repository class
- os Services geralmente invocam outros Services (ver fluxo do endpoint de registo de customers desde o controller até ao Repository)
- **Segurança:** Prepared statements obrigatórios
- **Idioma do código** O código deve estar todo em inglês (á exceção da BD que ficará em português)

---

**Versão:** 1.0 Académica | **Data:** 14/09/2026
