# Boilerplate TALL + RBAC

Boilerplate Laravel 13 com Livewire 4, Flux UI, Alpine.js e Tailwind CSS v4, incluindo RBAC nativo (sem Spatie Permission).

O idioma padrão da interface é **português do Brasil** (`pt_BR`). A palavra **Dashboard** permanece em inglês.

## Quick Start

```bash
composer setup
php artisan migrate --seed
npm install && npm run dev
php artisan serve
```

Acesse `http://localhost:8000`.

## Contas de demonstração

Senha de todos: `password`

| E-mail | Nome | Acesso |
|--------|------|--------|
| `admin@example.com` | Superadministrador | Bypass total (`is_super_admin`) |
| `manager@example.com` | Gerente de Operações | Função Gerente de Departamento |
| `viewer@example.com` | Usuário Visualizador | Somente leitura |
| `test@example.com` | Test User | Usuário de fábrica, sem papéis |

## Stack

- PHP 8.3+, Laravel 13, Fortify (login, passkeys, 2FA)
- Livewire 4, Flux UI 2, Tailwind CSS 4
- Autorização nativa: `Gate`, `Policy`, `can()` / `@can`

## RBAC

O domínio fica em `app/Domain/Auth/`. Telas em `/admin`:

- Usuários, departamentos, funções, permissões
- Matriz de permissões
- Logs de auditoria

Slugs de permissão seguem `recurso.ação` (ex.: `users.view`, `roles.create`). Nomes exibidos e descrições do seeder estão em pt-BR.

### Funções e departamentos do seeder

| Slug | Nome | Nível |
|------|------|-------|
| `admin` | Administrador | 80 (sistema) |
| `manager` | Gerente de Departamento | 50 |
| `operator` | Operador | 20 |
| `viewer` | Visualizador | 10 |

Departamentos: Diretoria Executiva, Departamento Financeiro, Operações e Logística, Recursos Humanos.

## Idioma

| Item | Valor |
|------|--------|
| Locale da aplicação | `APP_LOCALE=pt_BR` |
| Fallback | `en` |
| Traduções JSON | `lang/pt_BR.json` |
| Validação / auth | `lang/pt_BR/*.php` |

Strings da UI passam por `__('English source')`. Títulos usam iniciais maiúsculas nas palavras principais e conectivos em minúscula (`Gestão de Usuários`, `Operações e Logística`). Termos técnicos como **Slug**, **2FA** e **Dashboard** não são traduzidos.

Para incluir uma string nova:

1. Envolva o texto em `__('...')` na view ou na Action.
2. Adicione a chave em `lang/pt_BR.json`.
3. O teste `Tests\Unit\PtBrLocalizationTest` falha se a chave não existir.

A suíte PHPUnit força `APP_LOCALE=en` para as asserções de interface em inglês.

```bash
php artisan test --filter=PtBrLocalizationTest
```

## Testes e qualidade

```bash
php artisan test
composer test          # Pint + PHPStan + testes
```
