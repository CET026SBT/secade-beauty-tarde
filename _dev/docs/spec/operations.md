# Especificação — Operação: perfis, catálogo, equipa, feedback

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../../especificacao_mvp.md).
> Capítulos: §6 · §7 · §10 · §16

## 6. DOMÍNIO: UTILIZADORES E PERFIS

| Entidade             | Descrição                                                                       | Campos-chave                                                                                                   |
| :------------------- | :------------------------------------------------------------------------------ | :------------------------------------------------------------------------------------------------------------- |
| **`utilizador`**     | Conta base dos 3 perfis                                                         | `nome`, `email`, `password_hash` (**bcrypt**), `telemovel`, `nif`, `tipo_perfil`, `foto` (F7/F9)               |
| **`cliente`**        | Herança de `utilizador` (1:1)                                                   | `telemovel_validado_otp`                                                                                       |
| **`funcionario`**    | Herança de `utilizador` (1:1)                                                   | `tipo_contrato` (`efetivo_contratado` / `recibo_verde`), `percentagem_comissao`, `salario_base`, `cc`, `ativo` |
| **`gestor`**         | ⚠️ **Não tem tabela própria** — vive em `utilizador` com `tipo_perfil='gestor'` | —                                                                                                              |
| **`cliente_morada`** | N moradas por cliente                                                           | `designacao`, `rua`, `numero_porta`, `andar_bloco`, `codigo_postal`, `principal`,                              |
|                      |                                                                                 | `cidade_id`                                                                                                    |

**Perfis em uso:** `cliente`, `funcionario`, `gestor` (enum em `utilizador.tipo_perfil`).

**Regra de ouro de acesso (§2.A):**
- Sem sessão → **401** nas APIs / **redirect** para `/login` nas páginas.
- Perfil errado → **403** nas APIs / **redirect** nas páginas.

**Nuance D-03 (§3.3):** funcionários com contrato fixo atuam predominantemente na **loja**;
a recibo verde, na vertente **ambulante**. Esta correspondência **ainda não é imposta** pelo sistema.

**Foto de perfil (F7/F9 · C-05 · §4.6):** vive em `uploads/users/<id>.{jpg|png|webp}` — o nome **nunca**
vem do utilizador e a imagem é **re-codificada com GD** (recorte quadrado central), o que destrói payloads
embutidos. `svg` é recusado (pode conter script). O **gestor** gere a foto de qualquer funcionário
(`/gestao/rh`); o **dono** gere a sua (Área Cliente). `uploads/.htaccess` desliga o motor PHP e nega
`php|html|svg|js` dentro da pasta.

**Recursos Humanos (F7):** a página **`/gestao/rh`** (só gestor) lista, cria, edita e **desativa**
funcionários. A desativação é **soft delete** (`funcionario.ativo=0`) e tem **fluxo de impacto**: se o
funcionário tiver serviços por executar, o gestor é avisado e, ao confirmar, esses serviços voltam a
`pendente` e o agendamento perde `totalmente_alocado`; os agendamentos **`confirmado`** nunca são mexidos
(D-07.3). Só o **efetivo** tem salário base — o **RV** nasce a **0** e com a percentagem do tipo de
contrato (C-09 · C-10).

## 7. CATÁLOGO DE SERVIÇOS E CATEGORIAS

- **35 serviços** em **3 categorias**: Cabeleireiro, Barbearia, Estética.
- **Preços:** 4,07 € – 48,78 €.
- **Campos-chave de `servico`:** `nome`, `descricao`, `categoria_id`, `duracao_estimada_minutos`,
  `preco_base`, `requer_espaco_fisico`, `ativo`.
- `requer_espaco_fisico=1` → disponível **apenas em loja** (1 serviço nestas condições).
- **`servico_local`** parametriza a disponibilidade por canal (`loja_fisica` / `carrinha_ambulante`).
- **`servico_foto`** alimenta a galeria de imagens do serviço (modal de detalhes) e é gerida em
  **`/gestao/catalogo`** (F3.1 · §24.2): carregar/remover, escolher a **foto do card** (`destaque`) e a
  ordem. A **capacidade** do carousel continua por confirmar com o cliente (D-16).
- **Categorias = filtros/agrupadores visuais** (nunca condicionam quem executa o quê).

**Apresentação:** cards no catálogo com filtros (categoria, preço, duração, pesquisa) e badge
"Apenas Loja"; **página de detalhes dedicada por serviço** (carousel + descrição + tempo estimado)
— D-06.

**Exemplo de referência para testes:** Barba (20 min, 4,07 €) · Design de Sobrancelha com Linha
(30 min, 8,13 €).

## 10. DINÂMICA DOS FUNCIONÁRIOS (BACKOFFICE)

### 10.1 Duas apresentações — listagem (aceitar/desfazer) e calendário (agenda)
- **Por aceitar/desfazer — listagem** em `/gestao/servicos`, com a lógica actual intacta: o funcionário vê
  os serviços de ambulatório **pendentes** de agendamentos **ainda não incluídos em rota confirmada**
  (RN-32), agrupados por **agendamento → pessoa → serviço**.
- **Agenda — calendário** em `/gestao/agenda` (**só funcionário**): **apenas** agendamentos de **rotas
  confirmadas** pelo gestor (RN-33); por definição (RN-31) são agendamentos com **todos** os serviços já
  aceites por funcionários.
- Filtros: **categoria** e **data** — **apenas visuais/agrupadores** (RN-04).

### 10.2 Aceitação individual
- A aceitação é **por serviço individual** (não por agendamento nem por pessoa).
- No momento da aceitação é apresentado o **Simulador de Recibos Verdes** (§11).
- Grava em `agendamento_servico`: `funcionario_id`, `estado_aceitacao='aceite'`, `aceito_em`,
  `percentagem_funcionario_aplicada` (o *snapshot* da percentagem do funcionário). Os **valores** por
  funcionário/empresa são **calculados na leitura** (§11 · C-03/D-10).
- **Troca:** aceitar um serviço já aceite por **outro** funcionário **transfere-o** (`isSwap`).

### 10.3 Desfazer / trocar
- Permitido **enquanto** o agendamento não estiver **`totalmente_alocado`**.
- Só o funcionário que aceitou pode desfazer (**403** se não for ele).
- Se o agendamento já estiver consolidado → **409** ("não permite desfazer nem trocar").

### 10.4 Consolidação
- Assim que o **último serviço** é aceite, o agendamento transita para
  **`totalmente_alocado`** (transacional).
- Nesse momento **(a)** bloqueia trocas/desistências. **O bloqueio da janela temporal deixou de
  acontecer aqui** (F4 · C-02/D-01): a consolidação já **não** impede marcações concorrentes — o recurso
  finito é a **rota confirmada** (§12.5).
- **Alocação (F4 · C-08):** no backoffice, o **gestor ALOCA** (dropdown de funcionário, `employeeId`) e o
  **funcionário ACEITA**. A alocação valida **R-ALOC** (o funcionário não pode estar em duas cidades no
  mesmo dia; cidade `NULL` não conta).

### 10.5 Concorrência de janela temporal
- A janela é validada na **confirmação da rota** (`findCityWindowConflicts` → **409**): **mesma cidade +
  janela sobreposta** (R-CONF · §12.5). Era aqui que o bloqueio fazia sentido — a carrinha não pode estar
  em dois locais à mesma hora.
- Estados considerados como "ocupantes": `totalmente_alocado` e `confirmado`.

### 10.6 Capacidade de funcionários por slot
- A disponibilidade baseia-se na **ausência de conflito de janela** na rota, e **não** no número de
  funcionários — a equipa é atribuída por alocação/aceitação.
- Consequência conhecida: não há limite de aceitações por funcionário no mesmo slot; a coordenação
  é operacional (a equipa que vai na carrinha é que executa — D-08).

## 16. FEEDBACK DO CLIENTE

- **Pré-condições (todas obrigatórias):**
  1. O agendamento pertence ao cliente autenticado (senão **403**).
  2. O estado é `executado` ou `concluido` (senão **409**).
  3. Existe **registo de execução** (`execucao_agendamento`) — senão **409**.
  4. **Não existe** feedback anterior para o agendamento (senão **409**) — **1 por agendamento**.
- **Dados:** `classificacao_estrelas` (**1–5**, validado; fora do intervalo → **422**) + `comentario`.
- **Visibilidade:** o feedback é **público assim que registado** (sem moderação — simplificação
  académica). Não existe filtro por nota mínima: o repositório considera apenas
  `classificacao_estrelas IS NOT NULL`.
- **Consumo:** alimenta os **testemunhos da home** (com *fallback* estático se não houver feedback),
  incluindo o nome do cliente e o serviço; devolve também a **média** e a contagem.
- **Elegibilidade na UI:** o formulário só é mostrado quando
  `canLeaveFeedback = estado ∈ {executado, concluido} && !hasFeedback`.
- **Gatilho no ciclo de vida:** só depois de o gestor registar a **execução** (§10/§12 e §20).
