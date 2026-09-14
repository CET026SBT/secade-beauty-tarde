# PLANO DE DESENVOLVIMENTO - 7 DIAS
## Projeto Académico Secade Beauty

**Data Entrega:** 21/09/2026 | **Objetivo:** MVP Funcional Completo

---

## 🎯 OBJETIVO

Entregar sistema funcional com:
1. CRUD de Serviços
2. **Agendamento Loja Física** (wizard completo)
3. **Agendamento Carrinha Ambulante** (wizard + validação OTP + algoritmo viabilidade) - **OBRIGATÓRIO**
4. Interface Cliente + Backoffice completo
5. Regras de negócio aplicadas (ambos canais)

---

## 📅 CRONOGRAMA

### **DIA 1 (14/09): PLANEAMENTO** ✅
- [x] Análise BD e stack
- [x] Definição escopo MVP
- [x] Documentação técnica

### **DIA 2 (15/09): LOGIN + BACKEND CORE**
**8h | Prioridade: ALTA**

**Manhã (3h): Sistema de Login e Perfil** ⭐ NOVO
- [ ] ✅ login.js (já criado)
- [ ] Atualizar login.php (invalid-feedback)
- [ ] Atualizar navbar.php (menu dinâmico user)
- [ ] Criar profile.php (perfil + gestão moradas)
- [ ] CustomerService::fetchCustomerProfile()
- [ ] CustomerAddressService::fetchCustomerAddresses()
- [ ] Testar: Login → Navbar → Perfil → Moradas

**Tarde (5h): Serviços + Agendamentos**
- [ ] ServiceRepository + ServiceService
- [ ] ServiceController (endpoints: service-list, service-details)
- [ ] AgendamentoRepository + AgendamentoService (início)
- [ ] BookingController (endpoints: booking-availability, booking-create)

**Teste:** Login funcional + Criar agendamento via Postman

### **DIA 3 (16/09): CATÁLOGO DE SERVIÇOS**
**8h | Prioridade: ALTA**

**Manhã (4h):**
- [ ] modules/main/services.php (view)
- [ ] components/serviceGrid.php (cards Bootstrap)

**Tarde (4h):**
- [ ] js/components/services.js (fetch API, filtros, modal)
- [ ] CSS customizado

**Teste:** Navegar, filtrar, ver detalhes

### **DIA 4 (17/09): WIZARD LOJA FÍSICA**
**8h | Prioridade: CRÍTICA**

**Manhã (4h):**
- [ ] modules/main/booking.php
- [ ] components/bookingWizard.php (Steps 1-2 HTML)
- [ ] Step 3: Data e Hora (calendário + API disponibilidade)

**Tarde (4h):**
- [ ] Step 4: Profissional (lista + validação categorias)
- [ ] Step 5: Resumo e confirmação (sinal 10%)
- [ ] js/components/bookingWizard.js (navegação, validação)

**Teste:** Completar agendamento loja física

### **DIA 5 (18/09): WIZARD CARRINHA AMBULANTE**
**8h | Prioridade: ALTA (OBRIGATÓRIO)**

**Manhã (4h):**
- [ ] Step 2B: Formulário de Morada (cidade, rua, CP)
- [ ] CustomerAddressRepository (salvar morada)
- [ ] Validação de cidade suportada (matriz_deslocacao)
- [ ] Step 2C: Validação OTP Simulada
  - Gerar código 6 dígitos
  - Mostrar na tela (simulação SMS)
  - Validar input e marcar telemovel_validado_otp=1

**Tarde (4h):**
- [ ] Backend: Criar agendamento com estado='pendente_aprovacao_viabilidade'
- [ ] RotaRepository + RotaService
- [ ] Criar/associar rota_ambulante se não existir
- [ ] Testar agendamento carrinha completo

**Teste:** Agendamento carrinha com OTP e estado pendente

### **DIA 6 (19/09): ALGORITMO VIABILIDADE + BACKOFFICE**
**8h | Prioridade: ALTA (OBRIGATÓRIO)**

**Manhã (4h):**
- [ ] **Algoritmo de Viabilidade de Rotas**
  - RotaService::validarViabilidadeRotas($data_rota)
  - Calcular receita (SOMA agendamentos pendentes por cidade)
  - Calcular custo (matriz_deslocacao + €50 fixo)
  - Rentabilidade = receita - custo
  - SE >= €100 → aprovar (estado='confirmado')
  - SE < €100 → cancelar (estado='cancelado')
- [ ] Endpoint: POST /api?action=admin-validate-routes

**Tarde (4h):**
- [ ] **Backoffice: Gestão de Agendamentos**
  - modules/backoffice/appointments.php (tabela + filtros)
  - API: admin-appointments-list (com paginação)
  - API: admin-appointment-cancel
- [ ] **Backoffice: Gestão de Rotas**
  - Página: modules/backoffice/routes.php
  - Listar rotas (data, cidade, estado, rentabilidade)
  - Botão "Validar Rotas do Dia" → chama algoritmo

**Teste:** Validar rotas manualmente, ver estados mudarem

### **DIA 7 (20/09): INTEGRAÇÃO FINAL + REFINAMENTOS**
**8h | Prioridade: CRÍTICA**

**Manhã (4h):**
- [ ] Testar FLUXO COMPLETO Loja Física (end-to-end)
- [ ] Testar FLUXO COMPLETO Carrinha (end-to-end com validação)
- [ ] modules/main/bookingSuccess.php (página de confirmação)
- [ ] Notificações simuladas (log ou alert)

**Tarde (4h):**
- [ ] Validações finais (client + server)
- [ ] Responsividade mobile (ambos wizards)
- [ ] Tratamento de erros consistente
- [ ] Polimento UI/UX
- [ ] Screenshots e documentação final

**Teste:** Percorrer todas as funcionalidades sem erros

---

## ✅ CHECKLIST MVP

**Core (Obrigatório - ALTA PRIORIDADE):**
- [ ] Catálogo de serviços funcional (filtros + modal)
- [ ] **Wizard Agendamento LOJA FÍSICA** (5 steps completos)
- [ ] **Wizard Agendamento CARRINHA AMBULANTE** (7 steps com OTP)
- [ ] **Algoritmo de Viabilidade de Rotas** (validação manual)
- [ ] Backend completo (ambos canais com transações)
- [ ] **Backoffice Agendamentos** (lista, filtros, cancelar)
- [ ] **Backoffice Rotas** (lista, validar viabilidade)
- [ ] Validações (client + server) em ambos fluxos
- [ ] Interface responsiva (mobile-first)

**Nice-to-Have (SE SOBRAR TEMPO):**
- [ ] Histórico de agendamentos do cliente
- [ ] Dashboard com estatísticas básicas
- [ ] Notificações por email (simuladas)

**Fora de Escopo:**
- OTP real via SMS (usar simulação)
- Gateway de pagamento real (simulação)
- CRON job automático (validação manual)
- App móvel nativa

---

## 📦 ENTREGÁVEIS

1. Código-fonte completo
2. Database.sql
3. Documentação (README + docs técnicos)
4. Diagrama BD
5. Screenshots (opcional)

---

## ⚡ DICAS

- Reutilizar código existente
- Funcionalidade > Perfeição
- Testar frequentemente

---

**Versão:** 1.0 | **Status:** 🟢 EM EXECUÇÃO
