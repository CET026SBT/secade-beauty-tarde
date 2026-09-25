# Especificação — Anexos

<!-- md-wrap-tables:max=220 -->

> Parte da especificação. Router e índice: [`especificacao_mvp.md`](../../../especificacao_mvp.md).
> Capítulos: §23 · §29

## 23. DEFEITOS HISTÓRICOS

> O registo detalhado dos defeitos corrigidos (v1→v3) **não se acumula aqui** — está no **histórico do
> Git** (§29.2). O que continua a valer na estrutura: a tabela `funcionario_categoria` foi **removida**
> na v3.0 (D-02 · §17).

## 29. ANEXOS

### 29.1 Glossário

| Termo Técnico / de Domínio | Significado e Contexto no Projeto                                                                                  |
| :------------------------- | :----------------------------------------------------------------------------------------------------------------- |
| **Ambulatório / Carrinha** | Serviço prestado na morada do cliente, utilizando a carrinha como meio de transporte e suporte técnico             |
| **Loja Física**            | Serviço prestado nas instalações em Évora (horário: Terça a Sábado, 09:00–19:00)                                   |
| **Consolidação**           | Momento exato em que o **último serviço** pendente é aceite, transitando para `totalmente_aceite_funcionarios`     |
| **Janela temporal**        | Intervalo (início mais duração total) reservado no sistema após a consolidação                                     |
| **Rota**                   | Agrupamento lógico de agendamentos de ambulatório por **dia e cidade**, sujeito a aprovação manual do gestor       |
| **`meetsReference`**       | Indicador **estritamente visual** de que a rentabilidade esperada da rota é ≥ 50 € (não toma decisões automáticas) |
| **`isSwap`**               | Marcação indicando que a aceitação de um serviço o transferiu de outro funcionário (operação de troca)             |
| **Dispensa de sinal**      | Isenção temporária do pagamento do sinal na 1.ª marcação em ambulatório                                            |
| **`quota_parte_cliente`**  | Componente de custo de deslocação partilhado com o cliente — presente na BD, mas mantido a 0 € no MVP              |
| **Estrutura por pessoa**   | Agrupamento obrigatório de serviços associados a cada Pessoa individual (1..N) no ambulatório                      |
| **Owl Carousel**           | Biblioteca de carrosséis integrada e disponível para utilização na página de detalhes de serviço (§24.2)           |
| **jq-preloader**           | Biblioteca interna responsável por gerir overlays, spinners e *skeletons* de carregamento visual                   |
| **Gate de contrato**       | Verificação estrita de que os atributos `name` dos formulários coincidem com os contratos da API (§18.5)           |

### 29.2 Mapa documental

| Ficheiro                                          | Papel                                                    | Autoridade                           |
| :------------------------------------------------ | :------------------------------------------------------- | :----------------------------------- |
| **`especificacao_mvp.md`**                        | **Router**: mapa `§ → ficheiro`, prevalência, fontes     | ✅ **máxima** — prevalece sobre tudo |
| **`_dev/docs/spec/*.md`**                         | Especificação por domínio (§1–§29) — **o conteúdo**      | ✅ **máxima** — prevalece sobre tudo |
| `_dev/docs/README.md`                             | Regras e mapa da documentação **on-demand**              | normativo (documentação)             |
| `_dev/docs/rules/` · `_dev/docs/templates/`       | Receitas e moldes para gerar documentos sob demanda      | apoio                                |
| `.clinerules`                                     | Operação: restrições, convenções **aplicadas**, Git      | normativo (operação)                 |
| `_dev/tests/README.md`                            | Suites de teste, execução e pré-requisitos               | normativo (testes)                   |
| `_dev/tools/README.md`                            | Catálogo e comportamento das ferramentas                 | normativo (ferramentas)              |
| `README.md`                                       | Instalação e uso rápido                                  | apoio                                |
| `_dev/mapaMentalMVP/analise_backoffice_gestor.md` | Análise do backoffice do gestor (requisitos do cliente)  | apoio (não normativo)                |
| `_dev/mapaMentalMVP/auditoria_plataforma.md`      | Auditoria: planeamento, código e cruzamento com o estado | apoio (não normativo)                |
| `_dev/mapaMentalMVP/mensagem_teams.txt`           | Comunicação e dúvidas para o grupo/Teams                 | apoio (não normativo)                |
| `_dev/mapaMentalMVP/Menu APOIO 3.docx`            | Entrada do cliente (*template*) — menus de contabilidade | apoio (não normativo)                |
| `_dev/mapaMentalMVP/CUSTOS RH 2.xlsx`             | Entrada do cliente (*template*) — custo de pessoal       | apoio (não normativo)                |

**Histórico — regras revogadas e decisões antigas:** os `.pdf` iniciais e os `.md` de planeamento
anteriores foram **eliminados** e **não se acumulam aqui**. O arquivo é o **histórico do Git**:

```powershell
git log --diff-filter=D --oneline -- "*.md"   # localizar a remoção
git show <revisão>^:<ficheiro>                # ver o conteúdo original
```

> **Guias de teste manual e mapas de fluxo** já **não** são ficheiros mantidos: **geram-se sob demanda**
> a partir de `_dev/docs/rules/` + `_dev/docs/templates/` (ver `_dev/docs/README.md`).

### 29.3 Regras de manutenção deste documento

1. **Fonte única:** qualquer alteração de requisito, regra de negócio ou convenção é feita **aqui**
   e só depois refletida no código.
2. **Numerar antes de implementar:** novos requisitos entram como `RF-nn`; novas regras como `RN-nn`;
   decisões como `D-nn` (com a respetiva entrada em §3).
3. **Estado sempre explícito:** usar ✅ / 🟡 / ⬜ — nunca deixar um requisito sem estado.
4. **Regras revogadas não se acumulam aqui:** o arquivo é o **histórico do Git** (§29.2);
   uma regra que deixe de valer é **substituída**, e a substituição é descrita na mensagem de commit.
5. **Evidência obrigatória:** ao marcar um gap, indicar **onde** foi verificado (ficheiro/linha) —
   como nas §24.1–§24.6.
6. **Fecho do ciclo:** quando uma implementação da §24/§25 é concluída, atualizar
   **§4 (estado)**, **§24 (gap)**, **§28.2 (critério)** e **§26 (testes)** na mesma alteração.
7. **Fonte única por assunto:** este documento é a **autoridade** sobre requisitos, regras, dados,
   API e arquitetura. As **convenções de código aplicadas** e a **utilização do Git** vivem no
   `.clinerules` (§2 e §4), as **ferramentas** em `_dev/tools/README.md` e os **testes** em
   `_dev/tests/README.md`. **Não se duplica: aponta-se.**
8. **Respeitar o fluxo de Git:** a especificação de branches, commits e integração está em
   **`.clinerules` §4** — que define também a branch `agent-workspace`, que **nunca** é integrada.
9. **Usar escrita UTF-8 segura:** a regra e as vias corretas estão em **`.clinerules` §3** e a causa
   medida em **`_dev/tools/README.md` §1**. Antes de finalizar, correr `php _dev/tools/health-check.php`.

### 29.4 Perguntas frequentes de implementação

| Pergunta                                       | Resposta (nesta especificação)                                                       |
| ---------------------------------------------- | ------------------------------------------------------------------------------------ |
| Porque não há limite de funcionários por slot? | A equipa é atribuída por aceitação, não por alocação — §10.6                         |
| Porque o gestor não pode aceitar serviços?     | `admin-service-*` exige perfil `funcionario` (403) — supervisão só na página — §22.2 |
| Porque a quota-parte é 0 €?                    | Não há regra de cálculo definida — §12.2 / §22.2                                     |
| Porque o feedback é público?                   | Simplificação académica assumida (sem moderação) — §16                               |
| Porque não há CRON?                            | Simplificação assumida; tudo é on-demand — §2.E / §22.1                              |
| Onde está a lógica de condução da carrinha?    | **Não existe** e não deve existir — §3.8                                             |
| Porque o backoffice não está em `admin/`?      | Instrução de não tocar em `/admin` — `.clinerules` §1 · §25.3                        |
| Posso commitar em `dev` ou `main`?             | **Não.** Só por merge da branch de contexto — `.clinerules` §4                       |
| Como devem ser as mensagens de commit?         | Resumidas, tipografia simples, sem emoji/markdown, `-` para bullets — §4             |

---

**Versão:** 2.0 · **Data:** 24/09/2026 · **Estado:** ✅ Fases 1-5 concluídas e validadas (289 verificações) ·
⬜ Fase 6 (requisitos adicionais — §24) em curso
**Prevalência:** este documento é a **única** fonte de requisitos. As fontes anteriores estão revogadas — arquivo no Git (§29.2)
