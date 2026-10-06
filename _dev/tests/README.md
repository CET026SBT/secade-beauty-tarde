# TESTES — SUITES AUTOMATIZADAS

**Fonte única** sobre as suites de teste do projeto: o que cobrem, como se executam e que
pré-requisitos têm. Referido no `.clinerules` §0 (protocolo de leitura) e §5 (fontes únicas) e na
especificação §26.

| Suite                 | Verificações | Pré-requisitos     | Âmbito                                                                                                                                |
| :-------------------- | :----------- | :----------------- | :------------------------------------------------------------------------------------------------------------------------------------ |
| `functional_test.php` | **153**      | MySQL              | Camadas Service/Repository: catálogo, disponibilidade, conflitos, OTP, decisão manual de rotas, backoffice, perfil/moradas, Fase 3/4, |
|                       |              |                    | painel/avisos/                                                                                                                        |
|                       |              |                    | agenda, fornecedores, comissões, transações e integridade relacional                                                                  |
| `http_test.php`       | **179**      | **Apache + MySQL** | Stack real (roteamento + sessões): autenticação dos 3 perfis, APIs REST, códigos de erro, fluxo end-to-end de carrinha, cancelamento  |
|                       |              |                    | pelo cliente e                                                                                                                        |
|                       |              |                    | registo/login                                                                                                                         |
| `asset_test.php`      | **98**       | **Apache + MySQL** | Assets (HTTP 200), injeção de scripts por página e contrato de nomes do formulário de registo                                         |
| `js_syntax_check.php` | 26 ficheiros | —                  | Estrutura e sintaxe de todos os ficheiros JavaScript (sem Node)                                                                       |

**Total: 430 verificações.**

## Execução

```bash
php _dev/tests/js_syntax_check.php   # SINTAXE JS: OK                        (sem dependências)
php _dev/tests/functional_test.php   # 153 pass, 0 fail                     (requer MySQL)
php _dev/tests/http_test.php         # 179 pass, 0 fail                     (requer Apache + MySQL)
php _dev/tests/asset_test.php        # 98 pass, 0 fail                     (requer Apache + MySQL)
```

Cada suite imprime o resumo final (`N pass, M fail`) e termina com *exit code* `0` (tudo a passar)
ou `1` (existe falha).
> ✅ **Medição de 28/09/2026 (ambiente local, já com a BD importada — §27.2):** as **4 suites passam**
> (`153 + 179 + 98 = 430`, sintaxe JS OK). As verificações do `asset_test` que abrem **páginas
> autenticadas** exigem sessão → requerem a BD importada; com a base de testes carregada passam todas.
> ⚠️ **Cuidado ao contar registos:** a BD de desenvolvimento tem **dados reais de cliente** (clientes com
> **id ≥ 100**, moradas **≥ 200** — §24.11) — as asserções que contam uma tabela inteira têm de se
> limitar ao que o teste criou (foi o que se corrigiu no `http_test.php`, secção 12).

## Garantias

- **Repetíveis:** cada suite **limpa os dados que cria** (agendamentos, rotas, execuções, feedbacks,
  fiscal, utilizadores E2E) no início e no fim — podem ser corridas em sequência sem resíduos.
- **End-to-end onde importa:** o registo e o login dos três perfis (cliente, funcionário, gestor)
  estão cobertos de ponta a ponta.
- **Guard de contrato de nomes:** o `asset_test` **falha** se reaparecer uma chave em português no
  formulário de registo (`.clinerules` §2).
- **Só leitura sobre o schema:** nenhuma suite altera a estrutura da BD — apenas dados de teste.

## Fora do âmbito destas suites

- **Testes manuais** (fluxos completos por interface, navegação mobile, responsividade): o guia é
  **gerado sob demanda** (`_dev/docs/rules/`) — não é ficheiro mantido.
- **Critérios de aceitação do MVP** (o que tem de estar concluído, por fase): `especificacao_mvp.md`
  §28.
- **Cobertura em falta** (funcionalidades da §24 por implementar): `especificacao_mvp.md` §26.4.

> As suites são independentes de `_dev/tools/` e da documentação: um `health-check` verde **não** valida
> testes, e vice-versa.
