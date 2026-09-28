# Especificação — Financeiro e fiscal

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../../especificacao_mvp.md).
> Capítulos: §11 · §13 · §14 · §15

## 11. SIMULADOR DE RECIBOS VERDES

- **Gatilho:** apresentado **no momento da aceitação** de cada serviço de **ambulatório**
  (na loja não há aceitação por funcionários).
- **Percentagens configuráveis pelo gestor** (por omissão: **70 % funcionário / 30 % plataforma**),
  com **vigência por data** (`config_recibo_verde`).
- **Base de cálculo:** `preco_praticado` do **serviço individual**, no momento da aceitação.
- **Fórmulas:**
  ```
  valor_recibo_verde_funcionario = preco_praticado × (pct_funcionario / 100)
  valor_recibo_verde_plataforma  = preco_praticado × (pct_plataforma  / 100)
  ```
  Exemplo: Barba 4,07 € a 70/30 → funcionário **2,85 €** · plataforma **1,22 €**.
- **Persistência:** os valores ficam gravados no próprio **`agendamento_servico`**
  (`percentagem_funcionario_aplicada`, `valor_recibo_verde_funcionario`,
  `valor_recibo_verde_plataforma`); as percentagens vivem em `config_recibo_verde`.
- **Recálculo:** alterar a percentagem afeta **apenas aceitações futuras**; as já registadas mantêm
  os valores gravados.
- **Validação:** a soma das percentagens tem de ser **100** → **422** caso contrário.
- **Âmbito académico:** é um **simulador** — **não há emissão real** na Segurança Social.
- **Configuração no backoffice:** endpoint `admin-green-receipt-config` e página
  `/gestao/recibos-verdes` (histórico de configurações + simulador de valores).
- **Nuance D-03 (§3.3):** a regra "recibo verde → ambulatório / contrato fixo → loja" **ainda não é
  aplicada** pelo sistema; hoje qualquer funcionário pode aceitar ambulatório.

## 13. CALENDÁRIO FISCAL

- **Centralizado no backoffice do gestor** (`/gestao/fiscal`).
- **Obrigações:** **IVA, IRC, Segurança Social, Seguros**.
- **Dados por obrigação:** designação, tipo, `valor_estimado` (**introduzido manualmente**),
  `periodicidade` (mensal / trimestral / anual), data de prazo, estado (pendente / pago),
  histórico e observações.
- **Alertas progressivos:** **30, 15, 7, 3 e 1 dia** antes do prazo + **diário em atraso**.
- **Geração on-demand e idempotente** (`generateAlerts()` ao abrir o calendário/alertas) — **sem CRON**.
- **Regra de nível de alerta (atenção à ordem):** iterar do **mais urgente para o mais largo**
  (menor nº de dias primeiro). `daysLeft < 0` → `em_atraso`; senão o **menor limiar** que satisfaz
  `daysLeft ≤ limiar`. *(Um defeito fazia 7 dias reportar como 30 dias.)*
- **Marcar como pago:** grava `data_pagamento` + observações; **sem anexos**. Se já estiver pago → **409**.
- **Tabelas:** `obrigacao_fiscal` e `alerta_fiscal`.
- **Estado inicial:** as tabelas ficam **vazias** no seed — o calendário enche-se ao criar obrigações.
- **Calendário de 2026 entregue pelo cliente** (`Obrigações Fiscais.xlsx`, 28/09/2026) — **substitui** a lista
  anterior e fixa **8 famílias** de obrigações: **SAF-T** (comunicação de faturas) · **DMR** (declaração
  mensal de remunerações) · **Retenções na fonte IRS/IRC** · **Segurança Social** · **IVA-declaração
  periódica** e **IVA-pagamento** (prazos distintos) · **Modelo 22 (IRC)**, pagamento **por conta** e
  **final** · **IES/DA**.
- **Padrão mensal:** SAF-T dias **5–9** · DMR dias **10–12** · retenções IRS/IRC dias **20–22** · Segurança
  Social dias **20–25**; o **IVA é trimestral**, com declaração no **dia 20** do 2.º mês seguinte e pagamento
  no **dia 25**; IRC e IES são **anuais** (Modelo 22 e pagamento final em **2027-06-30**, pagamento por conta
  em **2026-08-31**, IES/DA em **2027-07-15**).
- ⚠️ **O enum `obrigacao_fiscal.tipo` ({iva, irc, seguranca_social, seguros}) não cobre metade do
  calendário.** A **D-15** fixou o enum para não receber lembretes **de fornecedores**; esta evidência é
  **fiscal**, pelo que a decisão tem de ser **revista** (§3.15) — alargar os tipos — e **não** contornada.
**Calendário de 2026 — 2.º documento entregue (`imagem (2).png`, 28/09/2026).** É a vista **resumida** do
mesmo ano, e é a única que traz o **responsável interno** de cada família:

| Família                                                            | Prazo no 2.º documento                                        | Responsável        |
| :----------------------------------------------------------------- | :------------------------------------------------------------ | :----------------- |
| **IVA** (trimestral)                                               | entrega **dia 20** · pagamento **dia 25** do 2.º mês seguinte | Cassiana Miranda   |
| **IRC**                                                            | **31 de maio**                                                | Senebildes Cabamba |
| **Segurança Social**                                               | **dia 30 de cada mês**                                        | Senebildes Cabamba |
| **Seguros** (acidentes de trabalho, multirriscos, RC profissional) | **semestral — 1 e 31 de julho**                               | Defina Benjamin    |

- ⚠️ **Os dois documentos de 2026 divergem, e a divergência não se resolve por inferência.** O
  `Obrigações Fiscais.xlsx` coloca a **Segurança Social nos dias 20–25** e o **IRC em 2027-06-30**
  (pagamento final) / **2026-08-31** (por conta); a imagem dá **dia 30** à Segurança Social e
  **31 de maio** ao IRC. O único ponto em que **coincidem** é o **IVA trimestral (entrega 20 · pagamento 25)**
  — o mesmo padrão que já estava na especificação → **perguntar** qual documento manda em cada família.
- ✅ **Nada a mudar no enum:** estas 4 famílias são exatamente `{iva, irc, seguranca_social, seguros}`
  (D-15). A imagem **confirma** o enum atual; o `Obrigações Fiscais.xlsx` é que o excede (§24.10).

- **Fim de semana não é tratado de forma uniforme no ficheiro:** o IVA do 2.º trimestre aparece como
  **2026-09-20** (domingo) no resumo e **2026-09-21** (segunda) no detalhe mensal, mas a Segurança Social de
  junho fica em **2026-07-25** (sábado) e o IVA do 4.º trimestre em **2027-02-20** (sábado). ⚠️ **Regra a
  confirmar** (data nominal ou dia útil seguinte) — não se infere.
- **Gralhas e estados do ficheiro:** os estados variam (`_`, `pendente`, `Pendente`) e há uma DMR escrita
  *"DRM"* e um prazo *"25/09/20206"* → normalizar para `pendente`/`pago` no que for importado.
- **Importação (RF-83):** o ano passa a poder ser carregado **de uma vez** a partir deste ficheiro, mantendo
  a **criação manual** e o `valor_estimado` como estão (D-12 · RN-30).

## 14. PAGAMENTOS, SINAL E RECIBOS

### 14.1 Estado atual (implementado)
- **Sinal de 10 % na loja**, calculado na criação (`valor_sinal`) com **constante de código**
  (`DEPOSIT_PERCENTAGE = 10`). Pagamento **simulado**: `sinal_pago` permanece `0`.
- **Ambulatório:** `valor_sinal = 0` (dispensado).
- Sem gateway, sem cobrança real, sem escolha de método.

### 14.2 Regras a implementar (D-05 / D-10 · ver §24.5)
| #   | Regra                                                                                                                                            |
| :-- | :----------------------------------------------------------------------------------------------------------------------------------------------- |
| P-1 | **Configuração do sinal** numa **secção dedicada do backoffice** (generalizando uma secção existente, ex.: recibos verdes) — substitui constante |
| P-2 | **Cobrança dos 90 %** restantes **no término do serviço**                                                                                        |
| P-3 | **Escolha simulada do método de pagamento:** **Dinheiro · Multibanco · MB Way**                                                                  |
| P-4 | Em **falha de internet** no terreno → pagamento simulado **apenas em numerário**                                                                 |
| P-5 | **Recibo manual** — avaliar se já existe; caso não, registar como **implementação futura**                                                       |

### 14.3 Simplificação académica
Todos os pagamentos são **simulados de forma realista** (*dummy*), mas a **experiência de escolha do
método** deve ser apresentada como se fosse real (opções visíveis, confirmação, estado registado).

## 15. CANCELAMENTOS E JANELA DE 24 HORAS

> Consolidado de D-11 (§3.11). **Nenhum destes pontos está implementado** — ver §24.6.

### 15.1 Quem pode cancelar
| Ator        | Estado atual                                                                           | Regra final                             |
| :---------- | :------------------------------------------------------------------------------------- | :-------------------------------------- |
| **Gestor**  | ✅ `admin-appointment-cancel` — bloqueado se `cancelado`/`executado`/`concluido` (409) | Mantém-se                               |
| **Cliente** | ❌ **não existe** qualquer endpoint/página de cancelamento no Main                     | **Deve poder cancelar pela plataforma** |

### 15.2 Janela das 24 horas
- **Nenhum gestor pode criar rotas** com agendamentos a **menos de 24 h** da execução; esses
  agendamentos são **automaticamente descartados/cancelados** da rota.
- Um agendamento que **chega às 24 h sem estar numa rota** é **automaticamente cancelado** e
  **deixa de aparecer nas listagens ativas**:
  - **não** aparece na aceitação por funcionários,
  - **não** aparece para inclusão em rotas,
  - **mantém-se na base de dados** (retenção para algoritmos/simuladores futuros).

### 15.3 Lembrete ao cliente
- Gerado quando falta **≤ 24 h** para a execução **e** o agendamento **não foi incluído em rota**.
- Conteúdo: **impossibilidade de execução** + **alternativas** sugeridas:
  deslocação à **loja física** ou **reagendamento**.
- Notificação **simulada** (âmbito académico).

### 15.4 Penalizações
- **Sem penalização financeira** para o cliente, mesmo quando cancela após o agendamento já estar
  associado a uma rota (respeitando a antecedência).

### 15.5 Impacto nos estados
```
[agendamento sem rota, faltam ≤ 24 h]
        │
        ├── é incluído numa rota pelo gestor  → segue o fluxo normal (§20)
        └── continua sem rota                 → AUTO-CANCELAMENTO (soft)
                                                 estado = 'cancelado'
                                                 retirado das listagens ativas
                                                 retido na BD + lembrete ao cliente
```
