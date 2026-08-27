# 02 — Estrutura de Projeto e Organização por Domínio

> **Antes de aplicar**: esta é a estrutura *default* para projeto novo. Se o repositório já organiza de outro jeito (ex.: `app/Services/{Dominio}/` + `app/Enums/{Modulo}/` + `app/Livewire/{Modulo}/` na raiz de `app/`), seguir o repositório. Nunca introduzir `app/Domain/` num projeto que não usa, nem mover código existente para "arrumar" a estrutura fora do escopo da tarefa.

## Estrutura por domínio de negócio

Organizar o projeto por domínio, não por tipo técnico de arquivo.

```
app/
  Domain/
    Billing/
      Models/
      Actions/
      Services/
      Policies/
      Jobs/
      Events/
      Listeners/
      Notifications/
      DTOs/
      ValueObjects/
      Livewire/
    CRM/
    Financial/
    Inventory/
    Scheduling/
```

Cada domínio deve conter apenas responsabilidades relacionadas ao próprio domínio. Evitar acoplamento entre módulos — comunicação entre domínios preferencialmente via Events, nunca por chamada direta a Models/Services de outro domínio quando evitável.

## Camadas — usar apenas quando agregam valor

- Models
- Livewire Components
- Actions (primeira escolha para uma operação única e nomeável, ex: `CreateInvoiceAction`)
- Services (quando há orquestração de múltiplos passos/Actions)
- Policies
- Jobs
- Events / Listeners
- Notifications / Mail
- DTOs / Value Objects

Evitar criar camadas artificiais só para "seguir o padrão" — cada camada precisa justificar sua existência no contexto do módulo.

## Onde ficam as views no Livewire 4

O Livewire 4 tem convenção própria, fora de `app/`:

```
resources/views/
  components/{modulo}/⚡nome.blade.php   # componentes reutilizáveis (SFC)
  components/{modulo}/⚡nome/            # componentes multi-file (MFC)
  pages/{modulo}/⚡nome.blade.php        # componentes de página (namespace pages::)
```

Componentes de classe continuam em `app/Livewire/{Modulo}/` com a view em `resources/views/livewire/{modulo}/`. Escolher um formato por projeto — ver `03-livewire.md`.

## Multi-tenancy (quando aplicável)

Convenção adotada em projetos multi-tenant: single-database, row-level isolation via:
- coluna `workspace_id` em toda tabela tenant-scoped
- trait `BelongsToTenant` no Model
- `WorkspaceScope` como global scope

Usar esse padrão como default em novos SaaS multi-tenant, salvo o usuário pedir isolamento por schema/database separado.

## Regras de camada

- **Blade**: apenas apresentação. Sem query, sem regra de negócio, sem loop complexo ou condicional extensa.
- **Livewire Components**: pequenos, uma responsabilidade cada. Lógica pesada vai para Actions/Services, não para o componente.
- **Alpine.js**: só para interação local (dropdown, modal, tabs, pequenas animações). Nunca duplicar regra de negócio do backend.
