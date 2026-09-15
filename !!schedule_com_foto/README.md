# Secade Beauty integrado

Esta pasta junta o template publico Salone com o painel SB Admin 2 e liga ambos a base de dados `secade_beauty`.

## Instalar no Laragon

1. Copie a pasta `secade_beauty_integrado` para `C:\laragon\www\secade_beauty_integrado`.
2. Abra o Laragon e inicie Apache e MySQL.
3. Importe `DataBase.sql` no HeidiSQL/phpMyAdmin. O script cria a base `secade_beauty`.
4. Abra `http://localhost/secade_beauty_integrado/`.
5. Abra o painel em `http://localhost/secade_beauty_integrado/admin/`.

## Configuracao

As credenciais MySQL ficam em `config/database.php`.

Padrao para Laragon:

```php
const DB_HOST = '127.0.0.1';
const DB_NAME = 'secade_beauty';
const DB_USER = 'root';
const DB_PASS = '';
```

## O que foi integrado

- O site publico mostra servicos e categorias diretamente da tabela `servico`.
- O formulario de agendamento grava em `utilizador`, `cliente`, `cliente_morada`, `agendamento` e `agendamento_servico`.
- O admin SB Admin 2 mostra dashboard, servicos, agendamentos, utilizadores e deslocacoes.
- O admin permite criar, editar e eliminar servicos.
- O admin permite adicionar uma imagem por servico, guardada em `servico_foto`, que aparece nos cartoes do site.
- O admin permite alterar o estado da reserva em `agendamento`.
- O dashboard permite carregar uma imagem de destaque que aparece automaticamente na pagina inicial do site.

## Nota

Nao foi adicionado sistema de login porque a base enviada ainda nao traz utilizadores/gestores pre-carregados. Se quiser protecao do painel, crie primeiro um gestor em `utilizador` e acrescente autenticacao por sessao.
