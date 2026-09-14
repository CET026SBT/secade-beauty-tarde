# FLUXOS FUNCIONAIS - SECADE BEAUTY
## Projeto Académico - Entrega: 21/09/2026

---

## CONTEXTO

**Secade Beauty:** Salão híbrido (Loja Física em Évora + Carrinha Ambulante)  
**Público:** Idosos com mobilidade reduzida, famílias rurais  
**Horário Loja:** Terça-Sábado, 09:00-19:00

---

## FLUXO 1: CATÁLOGO DE SERVIÇOS

### Interface
- Grid com 35 serviços (Bootstrap cards)
- Filtros: Categoria, Preço, Duração
- Modal de detalhes + botão "Agendar"
- Badge "Apenas Loja" se `requer_espaco_fisico=1`

### API
```
GET /api?action=service-list
Response: {
  "success": true,
  "data": [{
    "id": 1,
    "nome": "Box Braids",
    "preco_base": 32.52,
    "duracao_estimada_minutos": 240,
    "requer_espaco_fisico": 0,
    "categoria": "Cabelereiro"
  }, ...]
}
```

---

## FLUXO 2: WIZARD DE AGENDAMENTO (LOJA FÍSICA)

### Step 1: Seleção de Serviços
- Checkboxes múltiplos
- Cálculo automático: duração total + valor total
- Validação: mínimo 1 serviço

### Step 2: Escolha de Canal
- Card "Loja Física" sempre disponível
- Card "Carrinha" desabilitado se `requer_espaco_fisico=1`

### Step 3: Data e Hora
- Calendário: apenas Terça-Sábado
- Slots de 30min (09:00-19:00)
- API valida disponibilidade de funcionários

```
GET /api?action=booking-availability&date=2026-09-20&duracao=240
Response: {
  "slots_disponiveis": [
    {"hora": "09:00", "funcionarios": 2},
    {"hora": "14:00", "funcionarios": 3}
  ]
}
```

### Step 4: Profissional (Opcional)
- Listar funcionários disponíveis no slot
- Opção "Sem Preferência"
- Validar se cobre todas as categorias necessárias

### Step 5: Resumo e Confirmação
- Exibir: serviços, data/hora, local, profissional, valores
- **Sinal (10%):** €8.50 - Simulação de pagamento
- **Restante (90%):** €76.50 - Pagar no dia

### Backend: Criar Agendamento
```php
DB::beginTransaction();
// 1. Criar agendamento (estado='confirmado')
// 2. Associar serviços (agendamento_servico)
// 3. Registar transação de sinal (SIMULADA)
DB::commit();
```

---

## FLUXO 3: AGENDAMENTO CARRINHA AMBULANTE (OBRIGATÓRIO - ALTA PRIORIDADE)

### Diferenças vs Loja Física
1. **Passo Extra: Morada**
   - Dropdown com 10 cidades
   - Campos: rua, número, código postal

2. **Validação OTP (Simulada)**
   - Gerar código 6 dígitos
   - Mostrar na tela: "OTP: 123456 (simulação SMS)"
   - Validar input do cliente

3. **Estado Pendente**
   - `estado_reserva = 'pendente_aprovacao_viabilidade'`
   - Aguarda validação de rentabilidade

### Algoritmo de Viabilidade (OBRIGATÓRIO - Implementação Completa)

**Trigger:** Botão manual no backoffice "Validar Rotas do Dia"  
**Endpoint:** `POST /api?action=admin-validate-routes`

```php
// RotaService::validarViabilidadeRotas($data_rota)

$data_alvo = $_POST['data'] ?? date('Y-m-d', strtotime('+1 day'));

// 1. Buscar agendamentos pendentes agrupados por cidade
$agendamentos_por_cidade = DB::query("
    SELECT 
        cm.cidade_id,
        c.nome as cidade_nome,
        SUM(a.valor_total) as receita_prevista,
        COUNT(a.id) as total_agendamentos,
        GROUP_CONCAT(a.id) as agendamentos_ids
    FROM agendamento a
    JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
    JOIN cidade c ON cm.cidade_id = c.id
    WHERE DATE(a.data_hora_pretendida) = ?
      AND a.local_prestacao = 'carrinha_ambulante'
      AND a.estado_reserva = 'pendente_aprovacao_viabilidade'
    GROUP BY cm.cidade_id, c.nome
", [$data_alvo]);

$resultados = [];

foreach ($agendamentos_por_cidade as $cidade) {
    // 2. Buscar custo de combustível na matriz
    $matriz = DB::queryOne("
        SELECT custo_estimado_combustivel 
        FROM matriz_deslocacao
        WHERE cidade_id = ? AND base_partida_id = 1
    ", [$cidade['cidade_id']]);
    
    // 3. Calcular rentabilidade
    $receita = $cidade['receita_prevista'];
    $custo_combustivel = $matriz['custo_estimado_combustivel'];
    $custo_operacional = 50.00; // Fixo (salários/depreciação diária)
    $custo_total = $custo_combustivel + $custo_operacional;
    $rentabilidade = $receita - $custo_total;
    $limiar_minimo = 100.00;
    
    // 4. Decisão de aprovação/cancelamento
    $agendamentos_ids = explode(',', $cidade['agendamentos_ids']);
    
    if ($rentabilidade >= $limiar_minimo) {
        // APROVAR ROTA
        DB::beginTransaction();
        
        // Atualizar estado dos agendamentos
        DB::query("
            UPDATE agendamento 
            SET estado_reserva = 'confirmado'
            WHERE id IN (" . implode(',', $agendamentos_ids) . ")
        ");
        
        // Criar/atualizar rota
        $rota = DB::queryOne("
            SELECT id FROM rota_ambulante
            WHERE data_rota = ? AND cidade_id = ?
        ", [$data_alvo, $cidade['cidade_id']]);
        
        if (!$rota) {
            DB::query("
                INSERT INTO rota_ambulante (
                    data_rota, base_partida_id, cidade_id, 
                    estado_rota, custo_estimado_combustivel,
                    valor_rentabilidade_calculado
                ) VALUES (?, 1, ?, 'aprovada_viabilidade', ?, ?)
            ", [$data_alvo, $cidade['cidade_id'], $custo_combustivel, $rentabilidade]);
        } else {
            DB::query("
                UPDATE rota_ambulante 
                SET estado_rota = 'aprovada_viabilidade',
                    valor_rentabilidade_calculado = ?
                WHERE id = ?
            ", [$rentabilidade, $rota['id']]);
        }
        
        DB::commit();
        
        $resultados[] = [
            'cidade' => $cidade['cidade_nome'],
            'status' => 'aprovada',
            'rentabilidade' => $rentabilidade,
            'agendamentos' => $cidade['total_agendamentos']
        ];
        
        // TODO: Notificar clientes (simulado)
        // Email: "Sua marcação foi confirmada!"
        
    } else {
        // CANCELAR ROTA
        DB::beginTransaction();
        
        DB::query("
            UPDATE agendamento 
            SET estado_reserva = 'cancelado'
            WHERE id IN (" . implode(',', $agendamentos_ids) . ")
        ");
        
        // Atualizar/criar rota como cancelada
        $rota = DB::queryOne("
            SELECT id FROM rota_ambulante
            WHERE data_rota = ? AND cidade_id = ?
        ", [$data_alvo, $cidade['cidade_id']]);
        
        if (!$rota) {
            DB::query("
                INSERT INTO rota_ambulante (
                    data_rota, base_partida_id, cidade_id,
                    estado_rota, custo_estimado_combustivel,
                    valor_rentabilidade_calculado
                ) VALUES (?, 1, ?, 'cancelada_por_rentabilidade', ?, ?)
            ", [$data_alvo, $cidade['cidade_id'], $custo_combustivel, $rentabilidade]);
        } else {
            DB::query("
                UPDATE rota_ambulante
                SET estado_rota = 'cancelada_por_rentabilidade',
                    valor_rentabilidade_calculado = ?
                WHERE id = ?
            ", [$rentabilidade, $rota['id']]);
        }
        
        DB::commit();
        
        $resultados[] = [
            'cidade' => $cidade['cidade_nome'],
            'status' => 'cancelada',
            'rentabilidade' => $rentabilidade,
            'motivo' => 'Rentabilidade insuficiente (mínimo €100)',
            'agendamentos' => $cidade['total_agendamentos']
        ];
        
        // TODO: Notificar clientes (simulado)
        // Email: "Infelizmente precisamos reagendar. Alternativas: ..."
    }
}

return [
    'success' => true,
    'data_validada' => $data_alvo,
    'resultados' => $resultados
];
```

### Regras de Negócio da Validação

**RN01:** Validação ocorre para rotas do dia seguinte (D+1)  
**RN02:** Receita = SOMA de todos os agendamentos pendentes da cidade  
**RN03:** Custo = Combustível (matriz) + €50 fixo operacional  
**RN04:** Limiar mínimo = €100 de rentabilidade  
**RN05:** Aprovada → `estado_reserva='confirmado'` + notificar clientes  
**RN06:** Cancelada → `estado_reserva='cancelado'` + oferecer alternativas

---

## FLUXO 4: BACKOFFICE

### Página: /gestao/agendamentos

**Funcionalidades:**
- Tabela: ID, Cliente, Data/Hora, Local, Estado, Valor
- Filtros: Data, Local, Estado
- Ações: Ver Detalhes, Cancelar

**API:**
```
GET /api?action=admin-appointments-list&page=1
Response: {
  "total": 45,
  "data": [{
    "id": 1,
    "cliente_nome": "João Silva",
    "data_hora": "2026-09-20 10:00",
    "estado_reserva": "confirmado",
    "valor_total": 85.00
  }, ...]
}
```

---

## SIMPLIFICAÇÕES ACADÉMICAS ✅

- **Pagamentos:** Simulação (sem gateway real)
- **SMS/Email:** Log em ficheiro ou alert()
- **OTP:** Código mostrado na tela
- **Validação Rotas:** Botão manual (sem CRON)
- **Fecho Caixa:** Interface mockup

---

## REGRAS DE NEGÓCIO

**RN01:** Serviços com `requer_espaco_fisico=1` → só loja  
**RN02:** Loja: Terça-Sábado 09:00-19:00  
**RN03:** Sinal 10% (exceto 1ª marcação ambulante)  
**RN04:** Funcionário deve cobrir todas categorias  
**RN05:** Rentabilidade mínima rotas: €100

---

**Versão:** 1.0 Académica | **Data:** 14/09/2026
