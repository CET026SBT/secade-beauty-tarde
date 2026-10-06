# FASE 7 — O QUE FALTA (só pendências)

<!-- md-wrap-tables:max=220 -->

**Data:** 2026-10-03 · **Atualizado:** v4 (tudo fechado — arranque autorizado) · **Artefacto:** `_dev/docs/out/relatorio_fase7-a-validar.md`
**Natureza:** apoio (**não normativo**) · **Para quê:** mostrar **só o que ainda depende de ti**.
**Onde vive o resto:** o plano completo (`relatorio_plano-fase7-bugs-melhorias.md`) — é o **documento de
implementação** (contexto, evidência, desenho e o registo das respostas em **§14.4**).

> ✅ **Estado: NADA PENDENTE.** As 3 últimas decisões foram dadas e o **arranque foi autorizado** → a
> implementação começou pela **F1** (base de dados).

## 1. PENDENTE DE TI

| ID  | O que falta                                                                                  | Estado |
| :-- | :------------------------------------------------------------------------------------------- | :----- |
| —   | **Nada.** Todas as decisões (`D-01…D-26`, `C-01…C-14`, `G-01…G-07`, `NF-01`) estão fechadas. | ✅     |

## 2. ÚLTIMAS 3 DECISÕES (fechadas nesta ronda)

| ID              | Decisão do cliente                                                                                                 | Efeito                                                                            |
| :-------------- | :----------------------------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------------------- |
| **D-25 / D-13** | **`servico_foto` volta a ter uso**; fotos em **`uploads/services/service-{id}.jpg`**; **migrar** as já existentes. | *Substitui* o `D-15`. Upload gerido em `/gestao/catalogo` (F3.1).                 |
| **D-26**        | **+3 fotos** (total **6**: `service-1..6.jpg`); **1 template genérico** para as que faltam (**placeholders**).     | 35 serviços → 6 com foto real + 1 placeholder genérico.                           |
| **NF-01**       | `config_recibo_verde` **deixa de fazer sentido**: passa a **`config_percentagem_padrao`**, com os                  | Responde à tua pergunta: os *defaults/initiais* vivem **nessa tabela**, editável. |
|                 | **defaults por tipo de contrato**, **configuráveis no backoffice** (não estáticos).                                |                                                                                   |

### 2.1 Resposta à tua pergunta do `NF-01` («onde guardamos as percentagens?»)

**Na tabela renomeada.** Não se perde nada — muda de nome e de âmbito:

```text
config_percentagem_padrao              (ex-config_recibo_verde)
├── tipo_contrato        enum('efetivo_contratado','recibo_verde')   <- a chave
├── percentagem_comissao decimal(5,2)   <- o "default/inicial" (0 % efetivo · 70 % RV)
├── data_vigencia        date           <- histórico (mantém-se)
└── configurado_por      int -> utilizador
```

- **É configurável** no backoffice (a página `/gestao/recibos-verdes` passa a «Configurações de percentagens»).
- **Não é estática**: o gestor altera os defaults; os funcionários **novos** nascem com o valor em vigor.
- A `percentagem_plataforma` **desaparece** (é `100 − funcionário`).

## 3. JÁ FECHADO — onde está (não se repete aqui)

| Assunto                                            | Fonte única            |
| :------------------------------------------------- | :--------------------- |
| Decisões `C-01…C-14` · `G-01…G-07`                 | Plano **§1.1**         |
| Respostas desta ronda (`D-01…D-26` · `NF-01`)      | Plano **§14.4**        |
| Modelo final (estados · percentagens · `uploads/`) | Plano **§4**           |
| Comissões · RH · Fases                             | Plano **§6 · §7 · §5** |
