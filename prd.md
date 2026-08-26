# PRD & Especificação Técnica — RBAC Nativo (TALL Stack)

## 1. Contexto e Objetivo

Implementar, no boilerplate TALL Stack (Laravel 13 + Livewire 4 + Flux UI + Alpine.js + Tailwind CSS v4), um sistema completo de **RBAC (Role-Based Access Control)** com gerenciamento de:

1. **Usuários (Users)**
2. **Papéis (Roles)**
3. **Permissões (Permissions)**

A autorização é **100% nativa do Laravel** (`Gate`, `Policy`, `Authorizable`, `Gate::before`, `can()`/`@can`), sem qualquer dependência de pacotes externos (como `spatie/laravel-permission` ou `bouncer`).

### Diretriz Arquitetural (Síntese Opções A + B)
- **Estrutura Orientada a Domínio:** Código centralizado em `app/Domain/Auth/`.
- **Camada de Aplicação Limpa:** Livewire 4 utilizando **Form Objects** (`Livewire\Form`) para validação reativa e manipulação do estado da UI.
- **Camada de Domínio Desacoplada:** Form Objects convertem dados para **DTOs** (`final readonly`), que são processados por **Actions** (`final readonly`) invocáveis.
- **Banco de Dados:** Padronização completa com **UUID** como chave primária, **Soft Deletes** em todas as entidades e `declare(strict_types=1)` em todos os arquivos PHP.

---

## 2. Escopo do Módulo

- **CRUD de Usuários:** Listagem com busca e paginação, criação, edição, atribuição de múltiplas roles, exclusão suave (soft delete) e restauração.
- **CRUD de Roles:** Listagem, criação, edição, vinculação de múltiplas permissions (many-to-many), soft delete e restauração.
- **CRUD de Permissions:** Listagem, criação, edição com validação de formato de slug (ex: `users.view`, `roles.create`), soft delete e restauração.
- **Autorização Granular Nativa:**
  - `Gate::before` para bypass de usuários super-administradores (`is_super_admin = true`).
  - Registro dinâmico de Gates com cache resiliente a migrações e bootstrap.
  - Invalidação atômica de cache ao persistir ou remover permissions.
  - Policies específicas para cada model (`UserPolicy`, `RolePolicy`, `PermissionPolicy`).
  - Dupla checagem no Livewire (no ciclo `mount()` e em cada método de ação de escrita).
- **Interface com Flux UI:**
  - Tabelas de listagem com busca reativa, badges de status/roles e paginação nativa Flux.
  - Formulários com validação imediata e feedback visual de erros.
  - Modais de confirmação para soft delete e restauração de registros.
- **Seeders & Bootstrap:**
  - Seeder com roles essenciais (`admin`, `viewer`) e permissions padrão para todos os recursos.
  - Criação/suporte a usuário Super Admin via configuração local/ambiente.
- **Suíte de Testes Pest:**
  - Cobertura completa de Policies, Gates, Actions e componentes Livewire (caminhos positivos e negativos com 403 Forbidden).

---

## 3. Fora de Escopo

- Pacotes externos de autorização (Spatie Permission, Silber/Bouncer, etc.).
- Multi-tenancy (o boilerplate é single-tenant).
- Autenticação e fluxo de login/registro (já provido nativamente pelo Laravel Fortify com Passkeys e 2FA).
- Telas em painel Filament (o foco deste módulo são componentes Livewire 4 nativos com Flux UI).

---

## 4. Modelagem de Dados

### Convenções
- Chave primária: **UUID** (`$table->uuid('id')->primary()`).
- Migrações iniciais de `users` e `passkeys` ajustadas para chave primária UUID.
- Todas as tabelas de entidades incluem `softDeletes()` e `timestamps()`.
- Models Eloquent utilizam `$guarded = []` com atributos estritamente validados via DTO/Form Objects.

### Schema Relacional

```
users
  - id (uuid, primary key)
  - name (string)
  - email (string, unique)
  - password (string)
  - is_super_admin (boolean, default false)
  - email_verified_at (timestamp, nullable)
  - remember_token (string, nullable)
  - created_at, updated_at, deleted_at (timestamps, soft deletes)

roles
  - id (uuid, primary key)
  - name (string)
  - slug (string, unique)
  - description (text, nullable)
  - created_at, updated_at, deleted_at (timestamps, soft deletes)

permissions
  - id (uuid, primary key)
  - name (string)
  - slug (string, unique)                 # ex: "users.view", "roles.create"
  - description (text, nullable)
  - created_at, updated_at, deleted_at (timestamps, soft deletes)

role_user (pivot)
  - user_id (uuid, foreign key -> users.id, cascade on delete)
  - role_id (uuid, foreign key -> roles.id, cascade on delete)
  - created_at, updated_at

permission_role (pivot)
  - role_id (uuid, foreign key -> roles.id, cascade on delete)
  - permission_id (uuid, foreign key -> permissions.id, cascade on delete)
  - created_at, updated_at
```

### Relacionamentos Eloquent
- `User`: `belongsToMany(Role::class, 'role_user')`
- `Role`: `belongsToMany(User::class, 'role_user')` e `belongsToMany(Permission::class, 'permission_role')`
- `Permission`: `belongsToMany(Role::class, 'permission_role')`

---

## 5. Camada de Autorização (100% Nativa)

### 5.1 Trait `HasPermissions` (em `User`)
- `hasRole(string|array $roles): bool`
- `hasPermissionTo(string $permissionSlug): bool`:
  - Carrega em memória as permissões agregadas via roles com eager-loading (`roles.permissions`).
  - Cache em memória por request para evitar N+1 queries em checagens repetidas.
- `isSuperAdmin(): bool`: retorna se o usuário é super administrador.

### 5.2 Registro de Gates Resiliente (`AuthServiceProvider` / `AppServiceProvider`)
- **Super-Admin Bypass:**
  ```php
  Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);
  ```
- **Carregamento Dinâmico Seguro:**
  - Evitar consultas ao banco antes da execução das migrations (verificação prévia de schema ou tratamento seguro no boot).
  - Cache atômico com tag ou chave perene: `Cache::rememberForever('auth.permissions.slugs', ...)`.
  - Para cada slug em cache, registrar `Gate::define($slug, fn (User $user) => $user->hasPermissionTo($slug));`.
  - Invalidação de cache disparada nos métodos de criação, atualização e exclusão de permissões.

### 5.3 Policies de Domínio
`UserPolicy`, `RolePolicy` e `PermissionPolicy` implementam os métodos canônicos do Laravel (`viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`):
- Cada método delega explicitamente para o Gate correspondente:
  ```php
  public function viewAny(User $user): bool
  {
      return $user->can('users.view');
  }
  ```
- Políticas impedem que um usuário exclua ou remova o privilégio do seu próprio usuário enquanto logado, e impedem remoção de roles protegidas de sistema.

---

## 6. Arquitetura de Software e Estrutura de Pastas

Organização modular em **`app/Domain/Auth/`**:

```
app/
  Domain/
    Auth/
      Models/
        User.php
        Role.php
        Permission.php
        Concerns/
          HasPermissions.php
      Policies/
        UserPolicy.php
        RolePolicy.php
        PermissionPolicy.php
      DTOs/
        UserData.php
        RoleData.php
        PermissionData.php
      Actions/
        CreateUserAction.php
        UpdateUserAction.php
        DeleteUserAction.php
        RestoreUserAction.php
        AssignRolesToUserAction.php
        CreateRoleAction.php
        UpdateRoleAction.php
        DeleteRoleAction.php
        SyncRolePermissionsAction.php
        CreatePermissionAction.php
        UpdatePermissionAction.php
        DeletePermissionAction.php
      Livewire/
        Forms/
          UserForm.php          # Livewire\Form para validação e estado de Users
          RoleForm.php          # Livewire\Form para validação e estado de Roles
          PermissionForm.php    # Livewire\Form para validação e estado de Permissions
        Users/
          UserIndex.php
          UserCreate.php
          UserEdit.php
        Roles/
          RoleIndex.php
          RoleCreate.php
          RoleEdit.php
        Permissions/
          PermissionIndex.php
          PermissionCreate.php
          PermissionEdit.php
database/
  migrations/
    0001_01_01_000000_create_users_table.php       # Ajustada para UUID
    2024_01_01_000000_create_passkeys_table.php    # Ajustada para FK UUID
    xxxx_xx_xx_xxxxxx_create_roles_table.php
    xxxx_xx_xx_xxxxxx_create_permissions_table.php
    xxxx_xx_xx_xxxxxx_create_role_user_table.php
    xxxx_xx_xx_xxxxxx_create_permission_role_table.php
  seeders/
    RolePermissionSeeder.php
```

### Fluxo de Dados UI → Domínio
1. **Livewire Form Object (`Livewire\Form`):** Recebe o input do usuário na tela, aplica validações com atributos `#[Validate]` e mantém o estado sincronizado com o Flux UI.
2. **DTO (`final readonly`):** O Form Object invoca `toDTO()`, gerando um DTO imutável e fortemente tipado.
3. **Action (`final readonly`):** A Action executa a regra de negócio e persistência no banco dentro de transações de banco de dados (`DB::transaction`).

---

## 7. Componentes de Interface (Flux UI)

Para cada recurso (`Users`, `Roles`, `Permissions`):
- **Index:** Tabela Flux UI com filtro de pesquisa instantâneo, paginação assíncrona, badges informativas e ações restritas via `@can`.
- **Formulários (Create/Edit):** Campos com componentes nativos Flux (`<flux:input>`, `<flux:textarea>`, `<flux:checkbox.group>` ou select múltiplo para roles e permissions).
- **Modais de Ação:** Diálogos Flux (`<flux:modal>`) para confirmar soft deletes e restauração sem reload de página.
- **Proteção Reativa:** `$this->authorize(...)` tanto no carregamento (`mount()`) quanto na execução de cada método de ação do componente.

---

## 8. Estratégia de Testes (Pest)

Localização dos testes: `tests/Feature/Domain/Auth/`.

- **Testes de Policies e Gates:**
  - Verificação de acesso concedido com permissão específica.
  - Verificação de acesso negado (403) para usuário sem a devida role/permissão.
  - Verificação do bypass automático do Super Admin em todos os recursos.
  - Verificação de invalidação do cache de permissões após criar/atualizar/excluir.
- **Testes de Actions:**
  - Testes unitários para garantir que cada Action cria/atualiza/exclui registros e sincroniza pivots corretamente.
- **Testes de Componentes Livewire:**
  - `Livewire::test(UserIndex::class)` garantindo renderização correta de listagens e busca.
  - Validação de formulários e mensagens de erro do Flux UI.
  - Teste de submissão não autorizada abortando com 403 Forbidden.

---

## 9. Critérios de Aceite e Definição de Pronto (DoD)

- [ ] Autorização e validação presentes em 100% dos métodos de escrita.
- [ ] Nenhuma dependência externa adicionada no `composer.json` para autorização.
- [ ] Chave primária UUID e soft deletes funcionando em todas as tabelas do domínio.
- [ ] Cache de permissões resiliente e com invalidação atômica garantida.
- [ ] Prevenção de queries N+1 comprovada via testes e profiling.
- [ ] Todos os testes Pest passando (`vendor/bin/pest` ou `composer test`).
- [ ] Análise estática do PHPStan / Larastan sem erros (`composer types:check`).
- [ ] Formatação consistente executada pelo Laravel Pint (`composer lint:check`).
- [ ] `declare(strict_types=1)` presente em todos os novos arquivos PHP.