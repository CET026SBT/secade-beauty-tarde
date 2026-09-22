# RELATÓRIO DE ALTERAÇÕES — PLANEAMENTO v3.0
> 📌 **CONSOLIDAÇÃO DOCUMENTAL (21/09/2026):** o conteúdo relevante deste ficheiro foi
> **centralizado em `especificacao_mvp.md`** (documento-mestre, que **prevalece**).
> Este ficheiro mantém-se **apenas como registo histórico** — **não** deve ser usado como fonte de
> requisitos. Mapa documental completo: `especificacao_mvp.md` §29.2.

**Data:** fase de replaneamento documental | **Âmbito:** apenas documentação (sem alterações de código)

> ✅ **ATUALIZAÇÃO (21/09/2026):** este relatório descreve o replaneamento. O **estado final da
> implementação** (incluindo a decisão final sobre as rotas — **manual**, com 50 € apenas indicador
> visual — e as Fases 2/3/4 completas) está em **`relatorio_implementacao.md`**.
> Os documentos v1.0 foram entretanto **marcados como revogados** nos pontos em conflito.

---

## O QUE FOI FEITO
1. Estudo completo da documentação existente: `README.md`, `fluxo_funcionalidades.md`, `plano_desenvolvimento.md`, `CARRINHA_SPEC.md`, `ALTERACOES_PRIORIDADES.md`, `tecnologias_projeto.md`, `LOGIN_PROFILE_TODO.md`, `.clinerules` e estrutura da `DataBase.sql` (21 tabelas). A pasta `admin` foi **descartada por completo** da análise (backoffice futuro).
2. Criado **`planeamento_geral.md`** — fusão completa de todos os planeamentos anteriores com as novas Regras de Ouro, marcado como documento de prevalência.
3. Criado **`duvidas_planeamento.md`** — dúvidas e pontos de atenção identificados.
4. Este ficheiro (`relatorio_alteracoes.md`) — registo curto das alterações.

---

## AJUSTES INTEGRADOS (RPG — Regras de Ouro do novo prompt)

| # | Regra Nova | O que mudou face ao planeamento anterior |
|---|---|---|
| 1 | **Separação Main vs. Backoffice na pasta `admin`** | O backoffice deixou de ser um `modules/backoffice` genérico; passa a ser planeado na pasta raiz `admin/`, com módulos distintos para Gestores (rotas, calendário fiscal, agendamentos) e Funcionários (aceitação/troca de serviços). O cliente não tem qualquer acesso ao backoffice. |
| 2 | **Loja: sem morada, sem estrutura por pessoa, aceitação automática** | O wizard de loja anterior (profissional opcional + estado confirmado imediato) foi substituído: serviços ficam **automaticamente aceites** à criação, pendentes apenas de **validação logística da loja**. Sem morada, sem pessoas. |
| 3 | **Ambulatório: estrutura obrigatória por pessoa** | Novidade face a todos os documentos anteriores. Os serviços são agrupados por **Pessoa 1, 2, ...** como agrupador logístico/visual para estimar a duração do slot no terreno. Várias pessoas podem partilhar os mesmos serviços. Exige novas entidades BD (`agendamento_pessoa`, `pessoa_id` em `agendamento_servico`). |
| 4 | **Funcionários: categorias apenas como filtros** | Eliminada a regra anterior "funcionário deve cobrir todas as categorias" (RN04 antiga). Liberdade total de aceitação; categorias são apenas filtros visuais. |
| 5 | **Aceitação individual + desfazer/troca + consolidação** | Novo modelo: aceitação serviço a serviço; desfazer/troca permitida **até** o agendamento ficar **"Totalmente Aceite por Funcionários"** (último serviço aceite), momento em que bloqueia agendamentos concorrentes na janela temporal. Não existia antes. |
| 6 | **Simulador de Recibos Verdes** | Novo módulo. Integrado no momento da aceitação de serviços de ambulatório, com percentagem configurável (ex.: 70% funcionário / 30% plataforma). Não constava de nenhum planeamento anterior. |
| 7 | **Rotas: decisão manual e livre, sem limiar automático** | **Substitui o algoritmo automático de viabilidade** anterior (limiar 100€, aprovação/cancelamento automáticos de `fluxo_funcionalidades.md`/`CARRINHA_SPEC.md`). O **50€** passa a ser apenas **indicador visual de referência**. A rota mostra custos de combustível, quota-parte do cliente, lucro dos serviços e lucro total, agrupada por dia+cidade. |
| 8 | **Gestão de agendamentos do gestor** | Listagem por defeito = ambulatório totalmente aceite; pendentes acessíveis por opção; detalhe com funcionários por serviço. |
| 9 | **Calendário Fiscal** | Novo módulo centralizado no backoffice: IVA, IRC, Segurança Social, Seguros, com alertas progressivos 30/15/7/3/1 dia e diários em atraso. Exige novas tabelas. |

---

## CONFLITOS RESOLVIDOS (prevalece `planeamento_geral.md`)
- ~~Limiar automático de 100€ (RN05 antiga)~~ → decisão manual, referência 50€.
- ~~"Funcionário deve cobrir todas as categorias" (RN04 antiga)~~ → categorias como filtros.
- ~~Estado `pendente_aprovacao_viabilidade` por rentabilidade~~ → `pendente_aceitacao_funcionarios` → `totalmente_aceite_funcionarios` → decisão manual do gestor.
- ~~Wizard carrinha de 7 steps com "profissional" e sinal~~ → wizard com morada, OTP, estrutura por pessoa; sinal sujeito a esclarecimento (ver dúvidas).
- ~~Cronograma de 7 dias~~ → substituído por roadmap por fases (Fase 1–5) em `planeamento_geral.md`.

---

## ATUALIZAÇÃO: CONVENÇÕES REPOSITORY / MAPPER / SERVICE (v3.1)
Revisão da secção 13.1 do `planeamento_geral.md` com base no código real implementado:
- **Repositórios podem usar JOINs** em SELECTs — revoga a regra antiga "sem JOINs cross-context". Permitido para enriquecer a tabela principal com dados de lookup (N:1: `servico ← categoria_profissional`, `cliente_morada ← cidade`), mantendo escrita limitada à própria tabela e composição 1:N nos Services.
- **Camada de Mappers formalizada** (`app/mappers/` + `BaseMapper`): tradução snake_case PT → camelCase EN com casting de tipos, aplicada automaticamente pelo `BaseRepository` ou invocada diretamente pelos Services.
- **Convenções de Services** documentadas com base no código: `BaseService` (Validator + `executeTransactional` aninhável), composição entre Services e fluxo Controller → Service → Repository (+ Mapper).

## DOCUMENTOS ANTERIORES
Mantidos na raiz como histórico, mas **revogados nos pontos em conflito** com `planeamento_geral.md` v3.0: `fluxo_funcionalidades.md`, `CARRINHA_SPEC.md`, `plano_desenvolvimento.md`, `ALTERACOES_PRIORIDADES.md`, `tecnologias_projeto.md` (este último mantém-se válido nas convenções técnicas).

**Status:** ✅ Documentação criada e atualizada — sem qualquer alteração de código.
