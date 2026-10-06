# SECADE BEAUTY — TRABALHO FUTURO PRIORIZADO (§25)
**Especificação — ficheiro de domínio** · branch **`agent-workspace`**
**Âmbito:** §25 — prioridades vinculativas, consolidações técnicas, nice-to-have e a **estrutura de menus**
do backoffice que toda a implementação futura tem de respeitar.
**Router:** `especificacao_mvp.md` · **Gap analysis (§24):** `backlog.md` (ficheiro irmão).
## 25. TRABALHO FUTURO PRIORIZADO

> Ordem **vinculativa**: as implementações futuras devem seguir esta prioridade.
> **A prioridade máxima entre todas é Fornecedores** (indicação explícita dos esclarecimentos de retificações, §3).

### 25.1 Prioridade 1 — Fornecedores ⭐ ✅ *(feito a 28/09/2026)*
Gestão de fornecedores (produtos/consumíveis, custos, contactos) integrada no backoffice,
seguindo a **estrutura de menus** existente (§25.5).
- ✅ **Módulo implementado:** `/gestao/fornecedores` (listagem com pesquisa e estado, criar, editar,
  ativar/desativar) sobre a tabela `fornecedor` com os **43 fornecedores reais** (§24.11); endpoints
  `admin-supplier-list|store|update|set-active` (`SupplierController` · RF-85).
  Não há eliminação física: «remover» **desativa** (pode estar citado em despesas).
- Nota: alinhar com `transacao_financeira` (custos) e com o calendário fiscal (encargos) — depende da
  contabilidade (6.2).

### 25.2 Prioridade 2 — Requisitos adicionais (§24)
| Ordem   | Item de Desenvolvimento                                                     | Referência |
| :------ | :-------------------------------------------------------------------------- | :--------- |
| **2.1** | **Cancelamento pelo cliente** + **janela de 24 h** + **lembrete**           | §24.6      |
| **2.2** | **Sinal configurável** no backoffice + **10/90** + **métodos de pagamento** | §24.5      |
| **2.3** | **Re-avaliação dinâmica dos slots** (prevenção de conflitos de UX)          | §24.1      |
| **2.4** | **Multicidades** + **flexibilidade horária** + **convenção de alertas**     | §24.4      |
| **2.5** | **Página de detalhes de serviço + carousel**                                | §24.2      |
| **2.6** | **Encaminhamento por tipo de contrato**                                     | §24.3      |

### 25.3 Prioridade 3 — Consolidações técnicas
- **Migrar o backoffice** de `modules/backoffice/` para a pasta raiz **`admin/`** prevista no
  prevista no planeamento v3.0 (bloqueado pela instrução de não tocar em `/admin`).
- **UI para `transacao_financeira`, `fecho_caixa_diario` e `gorjeta`** (existem na BD, sem UI).
- **Notificações** (SMS/e-mail) reais ou persistidas, em vez de simuladas.
- **Recibo manual** (se não existir) — a alinhar com as restantes melhorias.
- **Estimar o custo de deslocação intra-cidade:** a `matriz_deslocacao` só cobre o percurso
  **base → cidade**; o combustível **dentro da cidade** **não está modelado**. Era esse o papel
  provisório que os 50 € fixos desempenhavam (removidos em 24/09/2026). Avaliar uma estimativa real
  — eventualmente com serviço de geolocalização, o que exigiria centralizar o do `AddressAutocomplete`
  numa utilidade própria (padrão `apiClient.js`/`api.js`).

### 25.4 Prioridade 4 — Nice-to-have
- **Notificações centralizadas** (página única de avisos, por perfil) — o *dashboard* deixou de ser nice-to-have: é **RF-77** (§24.7).
- Histórico/auditoria de decisões e alterações.
- Anexos/documentos nas obrigações fiscais.
- Algoritmos/simuladores sobre os dados retidos (agendamentos auto-cancelados — §15.2).

### 25.5 Estrutura de menus do backoffice (convenção a manter)
```
/gestao                → gestor      (dashboard: KPIs no topo + gráficos + sininho)  (NOVO — §24.7)
/gestao/agendamentos   → gestor      (lista, filtros, detalhe, execução, cancelamento, pagamentos*)
/gestao/rotas          → gestor      (dia+cidade, decisão manual, alertas padronizados)
/gestao/fiscal         → gestor      (calendário, obrigações, alertas progressivos)
/gestao/recibos-verdes → gestor      (config. de percentagens + [config. do sinal*] + histórico)
/gestao/servicos       → funcionário (aceitação/desfazer em **listagem**) — o gestor vê em supervisão
/gestao/agenda         → funcionário (agenda em **calendário**: rotas confirmadas)   (NOVO — §24.7)
/gestao/fornecedores*  → gestor      (PRIORIDADE 1 do futuro)
```
`*` = por implementar. **Toda a implementação futura deve encaixar nesta estrutura** (não criar
menus paralelos).
**Notas (28/09/2026):** a navbar do backoffice está no limite de lotação — a migração para **sidebar**
(componente exclusivo do backoffice) entra na **Fase 6**, com a página das **comissões**; a renomeação de
`/gestao/agendamentos` **não avança** (o nome fica) e a das restantes rotas só se fizer com decisão
própria, junto com a sidebar.
