# Especificação — Operação: perfis, catálogo, equipa, feedback

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../especificacao_mvp.md).
> Capítulos: §6 · §7 · §10 · §16

## 6. DOMÍNIO: UTILIZADORES E PERFIS

| Entidade             | Descrição                                                                       | Campos-chave                                                                           |
| :------------------- | :------------------------------------------------------------------------------ | :------------------------------------------------------------------------------------- |
| **`utilizador`**     | Conta base dos 3 perfis                                                         | `nome`, `email`, `password_hash` (**bcrypt**), `telemovel`, `nif`, `tipo_perfil`       |
| **`cliente`**        | Herança de `utilizador` (1:1)                                                   | `telemovel_validado_otp`                                                               |
| **`funcionario`**    | Herança de `utilizador` (1:1)                                                   | `tipo_contrato` (`efetivo_contratado` / `recibo_verde`), `salario_base`, `cc`, `ativo` |
| **`gestor`**         | ⚠️ **Não tem tabela própria** — vive em `utilizador` com `tipo_perfil='gestor'` | —                                                                                      |
| **`cliente_morada`** | N moradas por cliente                                                           | `designacao`, `rua`, `numero_porta`, `andar_bloco`, `codigo_postal`, `principal`,      |
|                      |                                                                                 | `cidade_id`                                                                            |

**Perfis em uso:** `cliente`, `funcionario`, `gestor` (enum em `utilizador.tipo_perfil`).

**Regra de ouro de acesso (§2.A):**
- Sem sessão → **401** nas APIs / **redirect** para `/login` nas páginas.
- Perfil errado → **403** nas APIs / **redirect** nas páginas.

**Nuance D-03 (§3.3):** funcionários com contrato fixo atuam predominantemente na **loja**;
a recibo verde, na vertente **ambulante**. Esta correspondência **ainda não é imposta** pelo sistema.

## 7. CATÁLOGO DE SERVIÇOS E CATEGORIAS

- **35 serviços** em **3 categorias**: Cabeleireiro, Barbearia, Estética.
- **Preços:** 4,07 € – 48,78 €.
- **Campos-chave de `servico`:** `nome`, `descricao`, `categoria_id`, `duracao_estimada_minutos`,
  `preco_base`, `requer_espaco_fisico`, `ativo`.
- `requer_espaco_fisico=1` → disponível **apenas em loja** (1 serviço nestas condições).
- **`servico_local`** parametriza a disponibilidade por canal (`loja_fisica` / `carrinha_ambulante`).
- **`servico_foto`** existe para a galeria de imagens (a alimentar — ver D-06 / §24.2).
- **Categorias = filtros/agrupadores visuais** (nunca condicionam quem executa o quê).

**Apresentação:** cards no catálogo com filtros (categoria, preço, duração, pesquisa) e badge
"Apenas Loja"; **página de detalhes dedicada por serviço** (carousel + descrição + tempo estimado)
— D-06.

**Exemplo de referência para testes:** Barba (20 min, 4,07 €) · Design de Sobrancelha com Linha
(30 min, 8,13 €).

## 10. DINÂMICA DOS FUNCIONÁRIOS (BACKOFFICE)

### 10.1 Listagem e filtros
- O funcionário vê os serviços de ambulatório **pendentes**, agrupados por
  **agendamento → pessoa → serviço**.
- Filtros: **categoria** e **data** — **apenas visuais/agrupadores** (RN-04).

### 10.2 Aceitação individual
- A aceitação é **por serviço individual** (não por agendamento nem por pessoa).
- No momento da aceitação é apresentado o **Simulador de Recibos Verdes** (§11).
- Grava em `agendamento_servico`: `funcionario_id`, `estado_aceitacao='aceite'`, `aceito_em`,
  `percentagem_funcionario_aplicada`, `valor_recibo_verde_funcionario`, `valor_recibo_verde_plataforma`.
- **Troca:** aceitar um serviço já aceite por **outro** funcionário **transfere-o** (`isSwap`).

### 10.3 Desfazer / trocar
- Permitido **enquanto** o agendamento não estiver **`totalmente_aceite_funcionarios`**.
- Só o funcionário que aceitou pode desfazer (**403** se não for ele).
- Se o agendamento já estiver consolidado → **409** ("não permite desfazer nem trocar").

### 10.4 Consolidação
- Assim que o **último serviço** é aceite, o agendamento transita para
  **`totalmente_aceite_funcionarios`** (transacional).
- Nesse momento: **(a)** bloqueia trocas/desistências e **(b)** bloqueia **agendamentos
  concorrentes na mesma janela temporal**.

### 10.5 Concorrência de janela temporal
- Ao consolidar, o sistema reserva o slot (data/hora + duração total) e recusa agendamentos novos
  ou aceitações que colidam com essa janela (`assertNoWindowConflict` → **409**).
- Estados considerados como "ocupantes": `pendente_validacao_logistica_loja`,
  `totalmente_aceite_funcionarios`, `confirmado`.

### 10.6 Capacidade de funcionários por slot
- A disponibilidade baseia-se na **ausência de conflito de janela**, e **não** no número de
  funcionários — porque a equipa é atribuída por **aceitação**, não por alocação prévia.
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
