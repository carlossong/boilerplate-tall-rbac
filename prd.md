# Prompt de Implementação — CRUD de Usuários, Roles e Permissions (TALL Stack, autorização 100% nativa)

## Contexto e objetivo

Implementar, em um boilerplate TALL Stack (Laravel 13 + Livewire 4 + Flux UI + Alpine.js + Tailwind), um sistema de **RBAC (Role-Based Access Control)** completo com CRUD de:

1. **Usuários** (Users)
2. **Roles**
3. **Permissions**

A autorização deve usar **exclusivamente recursos nativos do Laravel** — `Gate`, `Policy`, `Authorizable`, `Gate::before`, `can()`/`@can` — **sem nenhum pacote de terceiros** (nada de `spatie/laravel-permission` ou similar). O objetivo é ter controle total do modelo de dados e da lógica de autorização, sem a camada de abstração de um pacote externo.

## Escopo

- CRUD completo (listar, criar, editar, excluir com soft delete, restaurar) para Users, Roles e Permissions.
- Atribuição de múltiplas roles a um usuário (many-to-many).
- Atribuição de múltiplas permissions a uma role (many-to-many).
- Registro dinâmico de Gates a partir das permissions cadastradas no banco (não hardcoded no `AuthServiceProvider`).
- Um usuário "super-admin" com bypass total via `Gate::before`.
- Policies para os três models (`UserPolicy`, `RolePolicy`, `PermissionPolicy`), delegando a checagem de permissão granular para o Gate dinâmico.
- Seeder com roles/permissions padrão para bootstrapping do projeto.
- Componentes Livewire (full-page, não Volt) usando Flux UI para listagem (com busca/paginação), formulário de criação/edição e confirmação de exclusão.
- Testes Pest cobrindo Policies, Gates e os componentes Livewire (caminho feliz e negativo).

## Fora de escopo

- Qualquer pacote de terceiros para autorização (spatie/permission, bouncer, etc).
- Multi-tenancy — este boilerplate é single-tenant; se o projeto de destino for multi-tenant, tratar como ajuste posterior fora deste prompt.
- Autenticação (login/registro/2FA) — assume-se que já existe (Fortify/Breeze/Jetstream ou custom).
- Painel Filament — este prompt cobre componentes Livewire puros. Se o projeto usar Filament, adaptar a UI para Resources do Filament reaproveitando a mesma modelagem de dados, Policies e Gates.

## Modelagem de dados

Seguir convenções do projeto: **UUID como chave primária**, **soft deletes** em todos os models, `$guarded = []` com `Model::unguard()` restrito ao contexto de seeding/factories quando aplicável, `declare(strict_types=1)` em todos os arquivos PHP.

```
users
  - id (uuid, pk)
  - name
  - email (unique)
  - password
  - is_super_admin (boolean, default false)
  - timestamps, soft deletes

roles
  - id (uuid, pk)
  - name
  - slug (unique)
  - description (nullable)
  - timestamps, soft deletes

permissions
  - id (uuid, pk)
  - name
  - slug (unique)                  # ex: "users.view", "users.create", "roles.delete"
  - description (nullable)
  - timestamps, soft deletes

role_user (pivot)
  - role_id (uuid, fk)
  - user_id (uuid, fk)
  - timestamps

permission_role (pivot)
  - permission_id (uuid, fk)
  - role_id (uuid, fk)
  - timestamps
```

Relacionamentos:
- `User belongsToMany Role`
- `Role belongsToMany User`
- `Role belongsToMany Permission`
- `Permission belongsToMany Role`

## Camada de autorização (100% nativa)

### 1. Trait `HasPermissions` no model `User`

Criar um trait com os métodos:
- `hasRole(string $slug): bool`
- `hasPermissionTo(string $slug): bool` — verifica se alguma das roles do usuário possui a permission com o slug informado (usar eager loading / cache em memória por request para evitar N+1).
- `isSuperAdmin(): bool` — retorna `is_super_admin`.

### 2. Registro dinâmico de Gates (`AuthServiceProvider::boot()`)

- `Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);` — bypass total para super-admin.
- Iterar sobre todas as permissions cadastradas (com cache — `Cache::rememberForever('permissions.slugs', ...)`, invalidado ao salvar/excluir uma permission) e registrar um `Gate::define($permission->slug, fn (User $user) => $user->hasPermissionTo($permission->slug));`.
- Documentar no código por que o cache é necessário (evitar query em toda request só para registrar Gates).

### 3. Policies

Criar `UserPolicy`, `RolePolicy`, `PermissionPolicy` com os métodos padrão (`viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`), cada um delegando para o Gate correspondente, por exemplo:

```php
public function viewAny(User $user): bool
{
    return $user->can('users.view');
}
```

Registrar as Policies explicitamente no `AuthServiceProvider` (ou via convenção de nomes do Laravel 13, se o projeto já seguir esse padrão).

### 4. Uso nos componentes Livewire

- `$this->authorize('viewAny', User::class)` no `mount()` **e** revalidação de autorização dentro de cada método de ação (create/update/delete) — não confiar apenas no `mount()`, conforme convenção de segurança do projeto (o componente pode ser re-hidratado com payload manipulado entre requests).
- `@can` nas views Blade/Livewire para esconder botões — lembrando que isso é UX, não autorização (a checagem real é sempre no backend).

## Estrutura de pastas (por domínio)

```
app/
  Domain/
    Auth/
      Models/{User,Role,Permission}.php
      Policies/{UserPolicy,RolePolicy,PermissionPolicy}.php
      Actions/{CreateUser,UpdateUser,DeleteUser,AssignRolesToUser,SyncRolePermissions}.php
      DTOs/{UserData,RoleData,PermissionData}.php   # final readonly, com fromArray()
      Livewire/
        Users/{UserIndex,UserForm}.php
        Roles/{RoleIndex,RoleForm}.php
        Permissions/{PermissionIndex,PermissionForm}.php
database/
  migrations/...
  seeders/{RolePermissionSeeder}.php
tests/
  Feature/Domain/Auth/...
```

Usar **Action classes** (`final readonly`, um `__invoke`) para as operações de escrita (criar/atualizar/excluir/atribuir), e **DTOs** (`final readonly` com `fromArray()`) para transportar dados validados até a Action — conforme padrão já usado no projeto.

## Seeder padrão

Criar `RolePermissionSeeder` com:
- Permissions básicas para cada recurso: `{resource}.view`, `{resource}.create`, `{resource}.update`, `{resource}.delete` para `users`, `roles`, `permissions`.
- Roles: `admin` (todas as permissions) e `viewer` (apenas `.view`).
- Um usuário `is_super_admin = true` para bootstrap local (via `.env`/factory, nunca hardcoded em produção).

## Componentes Livewire (Flux UI)

Para cada recurso (Users, Roles, Permissions):
- **Index**: tabela Flux UI com busca, paginação, badges de role/permission, ações condicionadas a `@can`.
- **Form**: modal ou página de criar/editar, com validação via Livewire (`#[Validate]` ou Form Objects), multi-select para roles (no form de User) e permissions (no form de Role).
- Confirmação de exclusão (soft delete) com modal Flux, e ação de restaurar para registros excluídos.

## Testes (Pest)

- `UserPolicyTest`, `RolePolicyTest`, `PermissionPolicyTest`: cobrir allow/deny para cada ability, incluindo o bypass do super-admin.
- Teste de que o Gate dinâmico é registrado corretamente a partir de uma permission recém-criada (e que o cache é invalidado ao criar/editar/excluir uma permission).
- Testes de Livewire (`Livewire::test(...)`) para cada componente: caminho feliz (usuário autorizado consegue operar) e caminho negativo (usuário sem a permission recebe 403).

## Definição de pronto

- [ ] Autorização, validação e tratamento de erro presentes em toda ação de escrita.
- [ ] Nenhuma dependência de pacote externo para autorização.
- [ ] Testes cobrindo caminho feliz e negativo para Policies, Gates e componentes Livewire.
- [ ] Sem N+1 ao checar permissions (cache de slugs + eager loading de `roles.permissions`).
- [ ] Pint executado.
- [ ] `declare(strict_types=1)`, UUID PK, soft deletes e `final readonly` DTOs aplicados consistentemente.