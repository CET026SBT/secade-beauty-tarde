# TEMPLATE — GUIA DE TESTE MANUAL

> Molde. Copiar para `_dev/docs/out/guia_teste_manual.md`. Preencher com dados **lidos do schema** e do
> código; apagar o que não se aplica. Legibilidade aqui **é** prioridade (é lido por humanos).
> Regra: cada passo tem **Ação → Esperado → ☐ OK**; quando toca API/BD, acrescenta a prova.

**Projeto:** Secade Beauty · **Âmbito:** <<fluxos cobertos>> · **Data:** <<AAAA-MM-DD>>
**Gerado com:** `_dev/docs/rules/build_test_guide_on_demand.md` · **Nota:** ficheiro descartável — regenerar em vez de corrigir.

## 1. PRÉ-REQUISITOS

| Requisito                                   | Como verificar       |
| :------------------------------------------ | :------------------- |
| Laragon com Apache + MySQL ativos           | <<como>>             |
| BD `<<nome>>` importada — **<<N>> tabelas** | <<comando>>          |
| **Internet** ativa (autocomplete de morada) | <<efeito se faltar>> |
| Browser com DevTools (F12)                  | <<porquê>>           |

### 1.1 Dados de partida (contagens reais)

```sql
-- contagens que o guia assume; correr antes de começar
SELECT (SELECT COUNT(*) FROM <<tabela>>) AS <<nome>>;
```

## 2. FLUXO — <<NOME DO FLUXO>>

**Perfil:** <<cliente / funcionário / gestor>> · **Ponto de entrada:** `<<URL>>`

### 2.1 <<Passo>>

- **Ação:** <<o que fazer>>
- **Esperado:** <<o que tem de aparecer>>
- 🅰 `<<MÉTODO ?action=endpoint>>` → **<<HTTP>>**
- 🗄️

```sql
SELECT <<colunas>> FROM <<tabela>> WHERE <<condição>>;  -- esperado: <<valor>>
```

- ☐ OK

## 3. VERIFICAÇÃO FINAL

| #   | Verificação    | Esperado  | ☐  |
| :-- | :------------- | :-------- | :-- |
| 1   | <<invariante>> | <<valor>> | ☐  |

## 4. PROBLEMAS CONHECIDOS (não são avarias)

| Sintoma     | Porquê (regra)                 |
| :---------- | :----------------------------- |
| <<sintoma>> | <<§ref ou limitação assumida>> |

**Gate:** `ascii-align` → `md-align-tables` → `php _dev/tools/health-check.php` = **TUDO OK**
