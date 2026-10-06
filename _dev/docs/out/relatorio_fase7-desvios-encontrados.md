# Relatório — Fase 7: desvios e defeitos encontrados

> **Artefacto on-demand** (`_dev/docs/out/`) — para leitura humana, sem limite de linhas.
> **Âmbito:** auditoria da implementação da Fase 7 (F1→F11) contra o pedido do cliente
> (`prompt-que-gerou-a-fase-7.txt`, 454 linhas) e as decisões D-01…D-26.
> **Data:** 06/10/2026 · **Veredicto:** a Fase 7 é **revertida** para `ec17fe9` (pré-F1).

## 0. Resumo

| Categoria                                         | Nº  | Gravidade |
| :------------------------------------------------ | --: | :-------- |
| Requisitos pedidos e **não implementados**        | 8   | Alta      |
| **Defeitos de lógica** introduzidos               | 4   | Alta      |
| **Dados/seed** em contradição com a própria regra | 3   | Alta      |
| Documentação que **afirma o contrário** do código | 3   | Média     |
| Convenções divergentes                            | 2   | Baixa     |

Âmbito do revert: **211 ficheiros**, `+17 774 / −1 576` linhas, 124 commits.

---

## 1. Requisitos pedidos e não implementados

### 1.1 OTP continua no fluxo de agendamento (pedido: remover)

O cliente foi explícito (linhas 266-268 do prompt, confirmado em **D-04/D-07.7**):
*«Esta validação deverá ser removida do processo de criação ou edição de agendamentos»*.

**Estado entregue:** intacto.

| Sítio                                             | Evidência                                              |
| :------------------------------------------------ | :----------------------------------------------------- |
| `app/services/BookingService.php:221`             | `->required("otpCode", "O código OTP é obrigatório.")` |
| `app/services/BookingService.php:226`             | `if (!$this->otpService->verify(...))`                 |
| `modules/main/js/components/bookingWizard.js:563` | `payload.otpCode = $("#otpCode").val();`               |
| `app/config/api.php:17`                           | `"booking-otp-request"` ainda registado                |

### 1.2 OTP não foi movido para o perfil (pedido: mover)

O cliente pediu o OTP ao **alterar telemóvel / e-mail / palavra-passe** na Área Cliente
(linhas 269-272). **Não existe**: a Área Cliente edita o telemóvel sem qualquer validação
(`CustomerService::updateProfile`).

Resultado líquido: o OTP ficou onde **não** devia e não apareceu onde devia.

### 1.3 `#rotaDinamica` não implementado

Pedido (linhas 113-116): ao chegar da página de avisos, **scroll automático** até à listagem
e **highlight temporário com fadeout** do card relacionado.

**Não existe nada disto** — nem no `AlertService`, nem em `alerts.js`, nem nos componentes
de destino (`services.js`, `routes.js`).

### 1.4 Avisos do funcionário não distinguem RV de efetivo

Pedido (linhas 104-110):

| Perfil  | Grupo pedido                                                             |
| :------ | :----------------------------------------------------------------------- |
| RV      | **Serviços por aceitar**                                                 |
| Efetivo | **Alocações planeadas**, com serviço, pessoa e **quanto lucrará a mais** |

**Entregue:** ambos recebem os mesmos dois grupos.

```php
// app/services/AlertService.php:130-132
} else {
    $groups[] = $this->myNotificationsGroup();
    $groups[] = $this->myAllocationsGroup();   // <- igual para RV e efetivo
}
```

E o grupo chama-se «Alocações planeadas» mas lista serviços **já aceites**
(`findAcceptedByEmployee`), pelo que o **RV nunca vê o que tem por aceitar**. Falta também a
informação de ganho.

### 1.5 Fotos no caminho errado

Pedido explícito (linhas 255-256): *«faz sentido fazer upload delas para dentro do diretorio:
`/modules/common/img/users/<user-id>.{jpg/png/etc...}`»*.

**Entregue:** `uploads/users/<id>.<ext>` (`app/services/UserPhotoService.php:45`).

> O caminho novo é provavelmente melhor (fora de `modules/`, com `.htaccess` que desliga o PHP),
> mas **contradiz uma instrução direta** — devia ter sido proposto e aceite, não decidido sozinho.

### 1.6 Textos não normalizados

Pedido (linhas 146-150): «(visual)» → vazio; «Plataforma» → «Empresa»; retirar o conceito de
**simulador**.

| Texto                         | Continua em                                    |
| :---------------------------- | :--------------------------------------------- |
| `Categoria (visual)`          | `modules/backoffice/services.php:55`           |
| `Simulação académica`         | `modules/backoffice/greenReceipts.php:22`      |
| `simulador de recibos verdes` | `app/services/ServiceAcceptanceService.php:19` |

### 1.7 `#limiteCards` resolvido por link, não por «mostrar mais»

Pedido (linha 112): cada secção mostra **no máximo 10 cards**. Entreguei um **link**
«Mostrar mais N» que aponta para a página — e aponta sempre para o **primeiro item**:

```js
// modules/backoffice/js/components/alerts.js:34-35
? `<a class="btn btn-sm btn-outline-secondary mt-2 w-100" href="${BASE_URL}${group.showMoreUrl}">`
  `<i class="bi bi-arrow-down-circle me-1"></i>Mostrar mais ${group.hidden}`
```

`showMoreUrl` = `$items[0]["pageUrl"]` (`AlertService.php:157`) — se o grupo juntar origens
diferentes, o link fica errado.

### 1.8 Passo OTP mantido no wizard da carrinha

Consequência de §1.1: `FLOWS.carrinha_ambulante` continua a incluir `"otp"`, e o passo existe no
markup (`modules/main/components/bookingWizard.php`). Só o passo «Profissional» da loja foi
removido (G-01 ✅).
---

## 2. Defeitos de lógica introduzidos

### 2.1 A auto-recusa recusa também marcações de **loja** (grave)

O cliente definiu (C-13): recusar/auto-recusar **antes da confirmação**. A recusa faz sentido
para o que depende de **rota**. Uma marcação de loja **nunca** tem rota.

```php
// app/repositories/BookingRepository.php -- refuseWithoutRouteAtCutoff()
WHERE estado_reserva IN ('pendente_alocacao', 'pendente_validacao_logistica_loja', 'totalmente_alocado')
```

`pendente_validacao_logistica_loja` é o estado de uma marcação **de loja** → é **auto-recusada**
24 h antes da execução, sem qualquer razão de rota. Uma marcação válida desaparece sozinha.

### 2.2 A regra das 24 h contradiz a própria disponibilidade

`validateBookingDate` passou a exigir **≥ 24 h** (F10), mas a grelha de slots não:

```php
// app/services/BookingService.php -- findAvailability()
if ($slotStart < time()) continue;   // <- só exclui o PASSADO, não as próximas 24 h
```

O wizard mostra slots para amanhã que o servidor vai **rejeitar com 422** no fim. Dead-end
para o cliente.

### 2.3 `MaintenanceService` documenta o contrário do que faz

```php
// app/services/MaintenanceService.php:82-83
* - **R1a** `NOW() > início` e o agendamento **ainda não está em rota** -> `recusado`
* - **R1b** `NOW() > início` e o estado é `confirmado` -> aviso ao funcionário
```

Mas o código de R1a é `refuseWithoutRouteAtCutoff($now, self::CUTOFF_HOURS)` — **24 h antes**
do início, não depois. Quem mantiver isto vai ler a documentação errada.

### 2.4 R3/R4 decide a rota por *fallback* de cidade+data

`childStatesOfRoute` cai num JOIN `agendamento -> cliente_morada -> cidade` quando não há
execuções registadas. O filtro de canal não é aplicado de forma consistente nos dois ramos, pelo
que marcações de outro canal com a mesma cidade+dia podem entrar no conjunto e fechar a rota.

---

## 3. Dados (*seed*) contra a própria regra

### 3.1 Funcionário a recibo verde **com salário base**

Pedido (linhas 162-163): *«garante-me que não há funcionários a recibos verdes com salario_base
(default deve ser 0.0)»*.

```sql
-- DataBase.sql (committed)
INSERT INTO `funcionario` (`id`, `tipo_contrato`, `percentagem_comissao`, `salario_base`, `cc`, `ativo`) VALUES
	(2, 'recibo_verde', 70.00, 900.00, '999999990Z7R', 1);
	                     ^^^^^^  ^^^^^^^^
```

A regra que eu próprio codifiquei é violada pelo dump que eu próprio gerei. O
`EmployeeService` força 0 ao criar, mas o *seed* não foi corrigido.

### 3.2 O KPI «Custo fixo mensal» soma salários de RV

```php
// app/services/EmployeeService.php -- formContext()
foreach ($active as $employee) {
    $fixed += (float)($employee["salary"] ?? 0);   // <- sem filtrar por tipo de contrato
    ...
}
```

Mesmo com os dados corretos, o indicador somaria RV se algum tivesse salário (como tem hoje).
O ecrã `/gestao/rh` apresenta um custo fixo **inflacionado**.

### 3.3 Dados de teste ficaram no dump

| id   | nome                     | problema                                     |
| ---: | :----------------------- | :------------------------------------------- |
| 9002 | `Funcionario Rota Teste` | criado pelo `functional_test` — não limpo    |
| 9017 | `Funcionario RH Teste`   | efetivo a 5 % — criado por teste — não limpo |

A limpeza do harness remove por `email`, mas o dump foi regenerado **depois** de correr os testes.
---

## 4. Documentação que afirma o contrário do código

| Ficheiro                         | Afirmação                                         | Realidade               |
| :------------------------------- | :------------------------------------------------ | :---------------------- |
| `_dev/docs/spec/booking.md` §9.5 | «`booking-create-amb` já **não** exige `otpCode`» | **Exige** (§1.1)        |
| `_dev/docs/spec/booking.md` §9.1 | wizard da carrinha com 6 passos, «sem OTP»        | o passo OTP continua lá |
| `requirements.md` RN-11c/RN-24   | R1a/R2 como implementados                         | R1a contradiz (§2.3)    |

Escrevi especificação a descrever intenções, não o que o código faz. Numa auditoria isto vale
menos do que nada: dá falsa segurança.

---

## 5. Convenções divergentes

| Ponto                             | Esperado (`rules §2`)               | Entregue                                                                  |
| :-------------------------------- | :---------------------------------- | :------------------------------------------------------------------------ |
| Guarda do controller de foto      | `Session::requireProfileApi([...])` | `Session::requireLoginApi()` (`UserPhotoController`)                      |
| Terminologia de colunas removidas | nomes novos                         | `CommissionService` ainda usa o **alias** `valor_recibo_verde_plataforma` |

---

## 6. O que ficou **bem** (para não se perder no recomeço)

- **BD**: `estado_reserva` com `pendente_alocacao`/`totalmente_alocado` ✅; colunas
  `valor_recibo_verde_*` **removidas** ✅; `categoria_profissional` → `categoria_servico` ✅;
  `salario_base` default `0.00` ✅; `utilizador.foto` ✅; `manutencao_execucao` ✅.
- **C-09**: página de percentagens com **um só valor** e a empresa calculada (100 − x) ✅.
- **G-01**: passo «Profissional» fora do wizard da loja ✅.
- **Swal**: sem `alert`/`confirm` nativos nos componentes JS ✅.
- **Login**: `Enter` submete ✅.
- **Catálogo**: botão «ver serviços» → `/servicos/:categoria` ✅; «Detalhes» com
  `extended-border` ✅.
- **`serviceCategories.php`** (página) e duplicados de SVG removidos ✅.
- **Reconciliação + serviço de manutenção** com *guard* de tempo — a arquitetura está certa; os
  defeitos são de regra (§2), não de desenho.

---

## 7. Causa raiz (porque é que isto aconteceu)

1. **Implementei por fases e não reli o pedido no fim.** Cada fase fechou «verde» nos testes que
   eu próprio escrevi — e os meus testes verificavam o que **eu** fiz, não o que foi **pedido**.
   O caso do OTP é o exemplo perfeito: o teste nunca verificou a **ausência** do `otpCode`.
2. **Escrevi a especificação a partir da intenção.** Ao documentar «o OTP saiu do fluxo» como se
   fosse facto, criei uma fonte de verdade falsa.
3. **O *seed* não foi validado contra as regras de negócio.** O dump é gerado por ferramenta e eu
   não o confrontei com as regras (RV sem salário).
4. **Edições por substituição de bloco sem verificar o resultado.** O `customerArea.php` saiu
   desbalanceado (2 `</div>` a mais) — o mesmo padrão que produziu os 3 botões duplicados.

---

## 8. Reversão aplicada

| Item                               | Valor                                                                                          |
| :--------------------------------- | :--------------------------------------------------------------------------------------------- |
| **Alvo**                           | `ec17fe9` — *Merge branch dev into main* (último commit **antes** de `609d142`, que traz a F1) |
| **Branches repostas**              | `dev` e `main`                                                                                 |
| **Cópia de segurança**             | tag `fase7-completa-2026-10-06` (estado pós-Fase 7, incl. `WIP` e imagens)                     |
| **Branches de contexto `fase7-*`** | mantidas (histórico da tentativa)                                                              |
| **`agent-workspace`**              | mantida — tem as decisões e este relatório; a spec ainda descreve a Fase 7                     |

**Recuperar qualquer peça:**

```bash
git show fase7-completa-2026-10-06:<caminho>         # um ficheiro
git diff ec17fe9 fase7-completa-2026-10-06 -- <dir>   # uma área
```
