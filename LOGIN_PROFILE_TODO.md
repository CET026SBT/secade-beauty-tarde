# LOGIN E PERFIL - TAREFAS
**Implementar DIA 2 (15/09) ANTES de Agendamentos**

## ✅ FEITO

### Login Component (100% Completo com Convenções)
- ✅ **`modules/common/js/validators/login.validator.js`** - Validator externo criado
  - Validadores de email e password seguindo padrão `user.validator.js`
  
- ✅ **`modules/main/js/components/login.js`** - REFATORADO (convenções .clinerules)
  - Usa `loginValidators` importado
  - Usa classe `Form` do form.utils.js
  - Usa `API.auth.login()` do api.js
  - Usa `jq-preloader` corretamente (com promises, não .show/.hide)
  - Redireciona para home após sucesso (igual customerRegister.js)
  - **Apenas 27 linhas** (código limpo e simples)

- ✅ **`modules/main/components/login.php`** - Atualizado
  - Registra `validators/login.validator` antes do componente
  - Adicionado `<div class="invalid-feedback"></div>` em cada campo
  - Botão alterado para `type="submit"`
  - Template `<div preloader-overlay>` adicionado com spinner Bootstrap

## ✅ IMPLEMENTADO (Dia 1 - 14/09)

### 1. ✅ Navbar Dinâmica (`modules/main/includes/navbar.php`)
- Menu hamburguer dropdown para utilizadores logados
- Mostra: Primeiro nome + Último nome
- Avatar circular (40x40px) com borda dourada (#d4a574)
- Dropdown com opções: Perfil, Agendamentos, Sair
- Função logout() com confirmação e chamada AJAX
- Fallback para botão "Iniciar Sessão" quando não logado

### 2. ✅ Rotas Adicionadas (`index.php`)
- Rota "perfil" → `/modules/main/profile.php`
- Rota "agendamentos" → `/modules/main/appointments.php`

### 3. ✅ Backend Repositories/Services Atualizados (revisão de convenções)

Seguindo as regras de granularidade: cada Repository fica limitado à sua própria
tabela (sem JOIN cross-context) e a composição/transações passam a ser
responsabilidade do respetivo Service.

#### `UserRepository.php`:
- Método `findById(int $userId)` - Busca utilizador por id

#### `UserService.php`:
- Método `findById(int $userId)` - Expõe o repository ao resto da aplicação

#### `CustomerRepository.php`:
- (sem alterações de queries - apenas `create` e `findById`, ambos scoped à tabela `cliente`)

#### `CustomerService.php`:
- Método `fetchCustomerProfile(int $userId)` - Compõe dados de `utilizador` (via UserService) + `cliente` (via CustomerRepository)

#### `CustomerAddressRepository.php` (métodos granulares, sem JOIN):
- `findByCustomerId(int $customerId)` - Lista moradas (sem nome da cidade)
- `findByIdAndCustomerId(int $addressId, int $customerId)` - Busca 1 morada
- `unsetPrincipalForCustomer(int $customerId)` - Remove flag principal de todas
- `setPrincipal(int $addressId, int $customerId)` - Define 1 morada como principal
- `delete(int $addressId, int $customerId)` - Remove morada

#### `CustomerAddressService.php` (composição + transações):
- `fetchCustomerAddresses(int $customerId)` - Junta moradas com nome da cidade (via CityRepository)
- `setPrincipalAddress(int $addressId, int $customerId)` - Transação: unset + set
- `deleteAddress(int $addressId, int $customerId)` - Valida existência + remove

### 4. ✅ Páginas Placeholder Criadas

#### `modules/main/profile.php`:
- Página protegida com `Session::requireLogin()`
- Card com foto de perfil e dados básicos
- Seção "Informações Pessoais" (readonly por enquanto)
- Seção "Minhas Moradas" (placeholder)
- Alertas indicando "Em desenvolvimento"

#### `modules/main/appointments.php`:
- Página protegida com `Session::requireLogin()`
- Layout preparado para histórico de agendamentos
- Mensagem "Em desenvolvimento"

---

## 📋 FALTA FAZER (Especificação Necessária)

**ESPECIFICAÇÃO NECESSÁRIA:**

A página `profile.php` atualmente é um placeholder. Para implementação completa, precisa definir:

1. **Edição de Perfil:**
   - Quais campos podem ser editados? (nome, email, telemovel, password?)
   - Validações específicas?
   - Confirmação por email/OTP ao alterar email?

2. **Gestão de Moradas:**
   - Interface para adicionar nova morada (wizard? modal? página separada?)
   - Edição de moradas existentes?
   - Exclusão de moradas (com confirmação?)
   - Sistema de morada "principal" (estrela/toggle?)
   - Limite máximo de moradas por cliente?

3. **Upload de Foto de Perfil:**
   - Permitir upload? (necessita de nova coluna na BD: `utilizador.foto_perfil`)
   - Tamanho máximo? Formatos aceites?
   - Ou usar apenas placeholders das imagens existentes?

4. **Outras Funcionalidades:**
   - Histórico de agendamentos na página de perfil?
   - Estatísticas (total gasto, serviços favoritos)?
   - Opção de eliminar conta?

---

**Sugestão:** Implementar apenas visualização + edição básica de dados pessoais no MVP.  
Gestão de moradas pode ficar para **Dia 3** (após catálogo de serviços).
