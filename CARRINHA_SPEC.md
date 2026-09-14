# CARRINHA AMBULANTE - IMPLEMENTAÇÃO OBRIGATÓRIA
**Prioridade:** ALTA | **Prazo:** Dia 5-6 (18-19/09)

---

## 🎯 OBJETIVO
Implementar sistema completo de agendamento via carrinha com:
1. Wizard 7 steps (+ OTP simulado)
2. Algoritmo de viabilidade de rotas
3. Backoffice de gestão

---

## 📋 WIZARD CARRINHA (7 STEPS)

1. **Step 1:** Seleção de Serviços
2. **Step 2:** Escolha "Carrinha Ambulante"
3. **Step 2B:** Formulário Morada
   - Cidade (dropdown 10 opções)
   - Rua, Número, Código Postal
4. **Step 2C:** OTP Simulado
   - Gerar código 6 dígitos
   - Mostrar na tela: "OTP: 123456"
   - Validar input
5. **Step 3:** Data pretendida
6. **Step 4:** Política de sinal (dispensar 1ª vez)
7. **Step 5:** Resumo → estado='pendente'

---

## 🧮 ALGORITMO VIABILIDADE

**Trigger:** Botão backoffice "Validar Rotas"

```
PARA cada cidade:
    receita = SOMA(agendamentos.valor_total)
    custo = combustivel + 50 (fixo)
    rentabilidade = receita - custo
    
    SE >= 100 → APROVAR (estado='confirmado')
    SE < 100 → CANCELAR (estado='cancelado')
```

---

## 📁 ARQUIVOS A CRIAR

### Backend (Dia 5)
- `app/repositories/RotaRepository.php`
- `app/services/RotaService.php`
- `app/services/OTPService.php`
- `app/controllers/RotaController.php`

### Frontend (Dia 5)
- Steps 2B e 2C em `bookingWizard.php`
- Lógica OTP em `bookingWizard.js`

### Backoffice (Dia 6)
- `modules/backoffice/routes.php`
- API: `admin-validate-routes` (POST)
- API: `admin-routes-list` (GET)

---

## ✅ CHECKLIST

**Dia 5 (18/09):**
- [ ] Formulário morada + OTP
- [ ] Backend criar agendamento pendente
- [ ] Testar fluxo completo

**Dia 6 (19/09):**
- [ ] Algoritmo viabilidade completo
- [ ] Backoffice rotas
- [ ] Testar aprovação/cancelamento

---

**Status:** 🔴 OBRIGATÓRIO | **Versão:** 1.0
