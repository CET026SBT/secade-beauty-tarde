# TESTES — SUITES AUTOMATIZADAS

**Fonte única** sobre as suites de teste do projeto: o que cobrem, como se executam e que
pré-requisitos têm. Referido no `.clinerules` §0 (protocolo de leitura) e §5 (fontes únicas) e na
especificação §26.

| Suite                 | Verificações | Pré-requisitos     | Âmbito                                                                                                                                   |
| :-------------------- | :----------- | :----------------- | :--------------------------------------------------------------------------------------------------------------------------------------- |
| `functional_test.php` | **105**      | MySQL              | Camadas Service/Repository: catálogo, disponibilidade, conflitos, OTP, decisão manual de rotas, backoffice, perfil/moradas, transações e |
|                       |              |                    | integridade relacional                                                                                                                   |
| `http_test.php`       | **119**      | **Apache + MySQL** | Stack real (roteamento + sessões): autenticação dos 3 perfis, APIs REST, códigos de erro, fluxo end-to-end de carrinha e registo/login   |
| `asset_test.php`      | **65**       | **Apache**         | Assets (HTTP 200), injeção de scripts por página e contrato de nomes do formulário de registo                                            |
| `js_syntax_check.php` | 15 ficheiros | —                  | Estrutura e sintaxe de todos os ficheiros JavaScript (sem Node)                                                                          |

**Total: 289 verificações.**

## Execução

```bash
php tests/js_syntax_check.php   # SINTAXE JS: OK                        (sem dependências)
php tests/functional_test.php   # 105 pass, 0 fail                     (requer MySQL)
php tests/http_test.php         # 119 pass, 0 fail                     (requer Apache + MySQL)
php tests/asset_test.php        #  65 pass, 0 fail                     (requer Apache)
```

Cada suite imprime o resumo final (`N pass, M fail`) e termina com *exit code* `0` (tudo a passar)
ou `1` (existe falha).

## Garantias

- **Repetíveis:** cada suite **limpa os dados que cria** (agendamentos, rotas, execuções, feedbacks,
  fiscal, utilizadores E2E) no início e no fim — podem ser corridas em sequência sem resíduos.
- **End-to-end onde importa:** o registo e o login dos três perfis (cliente, funcionário, gestor)
  estão cobertos de ponta a ponta.
- **Guard de contrato de nomes:** o `asset_test` **falha** se reaparecer uma chave em português no
  formulário de registo (`.clinerules` §2).
- **Só leitura sobre o schema:** nenhuma suite altera a estrutura da BD — apenas dados de teste.

## Fora do âmbito destas suites

- **Testes manuais** (fluxos completos por interface, navegação mobile, responsividade):
  `mapaMentalMVP/guia_teste_manual.md`.
- **Critérios de aceitação do MVP** (o que tem de estar concluído, por fase): `especificacao_mvp.md`
  §28.
- **Cobertura em falta** (funcionalidades da §24 por implementar): `especificacao_mvp.md` §26.4.

> As suites são independentes de `tools/` e da documentação: um `health-check` verde **não** valida
> testes, e vice-versa.
