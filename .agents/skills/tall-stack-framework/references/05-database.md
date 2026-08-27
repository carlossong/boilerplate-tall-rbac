# 05 — Banco de Dados, Eloquent e Modelagem

## Convenções sempre aplicadas

- Foreign Keys explícitas com `constrained()->cascadeOnDelete()` (ou `restrict`, conforme a regra de negócio).
- Índices em colunas usadas em `WHERE`/`ORDER BY`/joins frequentes.
- Soft Deletes quando o registro tem valor histórico ou é referenciado por outras entidades (evitar soft delete "por padrão" em tabelas puramente transacionais/log).
- UUIDs como chave primária quando o ID pode vazar publicamente (URLs, APIs) ou em contexto multi-tenant/distribuído. Usar `HasUuids` do Laravel.
- Factories e Seeders para todo Model relevante a testes/demo.
- Valores monetários **nunca em `float`/`double`**. Duas representações válidas, escolhidas por projeto e mantidas consistentes:
  - **inteiro em centavos** (`bigInteger`) + Value Object `Money` — bom para produto internacional/multi-moeda e para evitar qualquer arredondamento implícito;
  - **`decimal(15,2)`** com cast `decimal:2` — bom quando o domínio é contábil/fiscal e os relatórios comparam valor a valor com documentos oficiais.
  Verificar o que o projeto já usa antes de escrever a migration e **não** misturar as duas na mesma base.

## Eager Loading e N+1

- Sempre analisar se uma listagem gera N+1 antes de considerar pronto.
- Usar `with()`/`load()` explicitamente; usar Laravel Debugbar/Telescope em desenvolvimento para validar contagem de queries.
- Evitar `withCount`/`with` desnecessário em relações não usadas na view.

## DTOs e Value Objects

- DTOs como `final readonly class` com método estático `fromArray()` (e `toArray()` quando necessário para serialização).
- Value Objects para conceitos com validação/comportamento próprio (ex: `Money`, `Cpf`, `Cnpj`, `Email`) — não representar esses conceitos como `string`/`int` soltos passando pelo sistema.
- Enums nativos do PHP (backed enums) para estados finitos, com métodos de domínio como `canTransitionTo(self $target): bool` implementando a máquina de estados no próprio enum.

## Mass assignment

Laravel 13 oferece atributos PHP como alternativa às propriedades (`Illuminate\Database\Eloquent\Attributes\`):

```php
#[Fillable(['name', 'email'])]   // equivale a protected $fillable
#[Guarded(['id'])]               // equivale a protected $guarded
#[Unguarded]                     // libera tudo — exige arrays montados à mão em fill/create/update
class User extends Model {}
```

Colunas JSON só são mass assignable listando a chave no `#[Fillable]` (`#[Fillable(['options->enabled'])]`); com `#[Guarded]` o Laravel **não** suporta atualizar atributo JSON aninhado.

- Escolher um estilo (atributo ou propriedade) e manter consistente no projeto inteiro.
- **Sempre validar via Form Request/Livewire Validation antes** de passar dados ao Model. Mass assignment aberto sem validação prévia é risco de segurança — `#[Unguarded]`/`$guarded = []` nunca substitui validação.

## Multi-tenancy

- Isolamento row-level via `workspace_id` + trait `BelongsToTenant` + `WorkspaceScope` (global scope) é o padrão adotado — aplicar em todo Model tenant-scoped por padrão em projetos SaaS novos.
- Garantir que o `WorkspaceScope` seja aplicado antes de qualquer query manual (`DB::table` bypassa o Eloquent e o scope — evitar ou aplicar `where('workspace_id', ...)` manualmente quando usado).

## Migrations

- Uma migration por mudança lógica, nomeada descritivamente.
- Nunca editar uma migration já executada em produção — criar uma nova migration de alteração.
- `down()` implementado de forma coerente (mesmo que o projeto raramente faça rollback em produção).
