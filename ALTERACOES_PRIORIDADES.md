# RESUMO DAS ALTERAÇÕES DE PRIORIDADES
**Data:** 14/09/2026 23:00  
**Motivo:** Agendamento Carrinha Ambulante promovido a PRIORIDADE ALTA OBRIGATÓRIA

---

## 🔄 MUDANÇAS APLICADAS

### 📄 Documentos Atualizados

1. **`plano_desenvolvimento.md`**
   - Dia 4: Wizard Loja Física COMPLETO (5 steps em 1 dia)
   - Dia 5: **WIZARD CARRINHA + OTP (OBRIGATÓRIO)**
   - Dia 6: **ALGORITMO VIABILIDADE + BACKOFFICE ROTAS (OBRIGATÓRIO)**
   - Checklist MVP atualizado com ambos canais obrigatórios

2. **`fluxo_funcionalidades.md`**
   - Fluxo 3 renomeado: "CARRINHA AMBULANTE (OBRIGATÓRIO - ALTA PRIORIDADE)"
   - Algoritmo de viabilidade expandido com implementação completa (150 linhas de código)
   - Regras de negócio detalhadas (RN01-RN06)

3. **`.clinerules`**
   - Prioridades reordenadas (6 itens)
   - Critério de sucesso expandido (7 itens)
   - Ambos fluxos (Loja + Carrinha) em critérios obrigatórios

4. **`README.md`**
   - Seção "Em Desenvolvimento" atualizada
   - Cronograma ajustado (Dias 5-6 focados em Carrinha)
   - Nova seção "Funcionalidades OBRIGATÓRIAS para Entrega"

5. **`CARRINHA_SPEC.md`** (NOVO)
   - Guia rápido de implementação
   - Checklist por dia (Dia 5 e 6)
   - Arquivos a criar listados

---

## ✅ CHECKLIST COMPLETO MVP (ATUALIZADO)

### OBRIGATÓRIO (Prioridade ALTA)
- [ ] Catálogo de serviços funcional
- [ ] **Wizard LOJA FÍSICA** (5 steps)
- [ ] **Wizard CARRINHA AMBULANTE** (7 steps + OTP) ⭐ NOVO
- [ ] **Algoritmo Viabilidade de Rotas** ⭐ NOVO
- [ ] **Backoffice Agendamentos**
- [ ] **Backoffice Rotas** ⭐ NOVO
- [ ] Validações completas (ambos canais)
- [ ] Interface responsiva

### Nice-to-Have (SE SOBRAR TEMPO)
- [ ] Histórico cliente
- [ ] Dashboard estatísticas
- [ ] Notificações email

---

## 📅 CRONOGRAMA AJUSTADO

| Dia | Data | Foco | Prioridade |
|-----|------|------|------------|
| 1 | 14/09 | Planeamento | ✅ COMPLETO |
| 2 | 15/09 | Backend Core | ALTA |
| 3 | 16/09 | Catálogo Serviços | ALTA |
| 4 | 17/09 | Wizard LOJA (completo) | CRÍTICA |
| 5 | 18/09 | **Wizard CARRINHA + OTP** | **CRÍTICA ⭐** |
| 6 | 19/09 | **Algoritmo + Backoffice** | **CRÍTICA ⭐** |
| 7 | 20/09 | Integração + Testes | CRÍTICA |

---

## 🎯 OBJETIVOS CLARIFICADOS

### Funcionalidades 100% Funcionais na Entrega:

1. **FLUXO LOJA FÍSICA**
   - Seleção serviços → Data/Hora → Profissional → Confirmação
   - Estado: `confirmado` imediato
   - Sinal 10% (simulado)

2. **FLUXO CARRINHA AMBULANTE** ⭐
   - Seleção serviços → Morada → OTP → Data → Confirmação
   - Estado: `pendente_aprovacao_viabilidade`
   - Validação posterior via backoffice

3. **ALGORITMO VIABILIDADE** ⭐
   - Cálculo: receita - (combustível + €50)
   - Decisão: >= €100 → aprovar | < €100 → cancelar
   - Trigger: Botão manual no backoffice

4. **BACKOFFICE COMPLETO**
   - Página Agendamentos (lista, filtros, cancelar)
   - Página Rotas (lista, validar viabilidade) ⭐

---

## 📊 COMPARAÇÃO ANTES vs DEPOIS

### ANTES (Versão 1.0)
- Carrinha: "Nice-to-have" (opcional)
- Algoritmo: "Documentar apenas"
- Backoffice: Apenas agendamentos

### DEPOIS (Versão 2.0) ⭐
- Carrinha: **OBRIGATÓRIO (Prioridade ALTA)**
- Algoritmo: **OBRIGATÓRIO (Implementação completa)**
- Backoffice: **Agendamentos + Rotas (ambos obrigatórios)**

---

## 🚨 AVISOS IMPORTANTES

1. **Dia 5 (18/09) é CRÍTICO:**
   - Wizard Carrinha COMPLETO
   - OTP Simulado funcional
   - Estado pendente funcionando

2. **Dia 6 (19/09) é CRÍTICO:**
   - Algoritmo viabilidade 100% funcional
   - Backoffice rotas operacional
   - Testes de aprovação/cancelamento

3. **Sem estes 2 dias, entrega está INCOMPLETA**

---

## ✍️ ASSINATURAS

**Alteração solicitada por:** Utilizador  
**Implementada por:** Sistema de Planeamento  
**Data/Hora:** 14/09/2026 23:00  
**Versão Documentos:** 2.0  
**Status:** ✅ APLICADO - PRONTO PARA DESENVOLVIMENTO

---

**PRÓXIMO PASSO:** Começar Dia 2 (15/09) - Backend Core
