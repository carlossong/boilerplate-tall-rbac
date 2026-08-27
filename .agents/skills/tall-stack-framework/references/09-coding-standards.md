# 09 — Convenções de Código

## Laravel 13 — PHP Attributes (opcional)

Laravel 13 oferece atributos PHP como alternativa não-obrigatória a propriedades/interfaces de classe. É não-breaking: as propriedades continuam funcionando. Namespaces (importar o certo, os nomes se repetem entre eles):

| Atributo | Namespace | Substitui |
|---|---|---|
| `#[Fillable]`, `#[Guarded]`, `#[Unguarded]`, `#[Hidden]`, `#[Visible]`, `#[Appends]`, `#[Table]`, `#[Connection]`, `#[DateFormat]`, `#[ObservedBy]`, `#[ScopedBy]`, `#[UsePolicy]`, `#[UseFactory]`, `#[CollectedBy]`, `#[WithoutTimestamps]` | `Illuminate\Database\Eloquent\Attributes\` | `$fillable`, `$guarded`, `$hidden`, `$table`, `booted()` com observer/scope, etc. |
| `#[Middleware('auth', only: [...], except: [...])]`, `#[Authorize('update', 'post')]`, `#[WithoutMiddleware]` | `Illuminate\Routing\Attributes\Controllers\` | `HasMiddleware` + `static middleware()` |
| `#[Tries(5)]`, `#[Timeout]`, `#[Backoff]`, `#[MaxExceptions]`, `#[DeleteWhenMissingModels]`, `#[WithoutRelations]` | `Illuminate\Queue\Attributes\` | `$tries`, `$timeout`, `$backoff`, … |
| `#[Singleton]`, `#[Scoped]` | `Illuminate\Container\Attributes\` | `bind`/`singleton` no ServiceProvider |
| `#[Auth]`, `#[Cache]`, `#[Config]`, `#[Context]`, `#[DB]`, `#[Give]`, `#[Log]`, `#[RouteParameter]`, `#[Tag]`, `#[CurrentUser]`, `#[Storage]` | `Illuminate\Container\Attributes\` | contextual binding manual |

- Em projetos **novos**, é aceitável adotar o estilo de atributos para reduzir boilerplate, desde que aplicado de forma consistente na classe (não misturar metade em atributo, metade em propriedade no mesmo Model).
- Em projetos **existentes**, manter o padrão já usado — consistência do projeto vem antes da novidade da sintaxe.
- Atributos do Livewire (`#[Computed]`, `#[Authorize]`, `#[Locked]`, …) são outra família, em `Livewire\Attributes\` — ver `03-livewire.md`.

## Sempre utilizar

- `declare(strict_types=1);` no topo de todo arquivo PHP novo.
- Tipagem completa: parâmetros, propriedades e **Return Types** explícitos sempre que a linguagem suportar.
- Constructor Property Promotion (`public function __construct(private readonly X $x)`) em vez de declarar propriedade + atribuir manualmente.
- Enums nativos (backed enums) em vez de constantes soltas ou strings mágicas para representar estados finitos.
- Collections do Laravel em vez de arrays manipulados com `array_*` quando a legibilidade melhora.
- Carbon para qualquer manipulação de data/hora.
- Helpers oficiais do Laravel/PHP — evitar reinventar utilitário que já existe no framework.

## Evitar

- Código legado ou helpers obsoletos/depreciados.
- Facades quando injeção de dependência explícita for mais adequada (especialmente em Services/Actions testáveis) — Facades são aceitáveis em Controllers/Livewire por conveniência, mas prefira injeção em código de domínio puro.
- Queries dentro de Blade.
- Comentários que só repetem o que o código já diz — comentar o "porquê", não o "o quê".

## Nomenclatura

- Actions: verbo + substantivo, sufixo `Action` (`CreateInvoiceAction`).
- DTOs: substantivo, sufixo `Data`/`DTO` conforme convenção do projeto, sempre `final readonly class`.
- Enums de estado: substantivo + `Status`/`State` (`InvoiceStatus`), com métodos de transição no próprio enum quando aplicável (`canTransitionTo()`).
- Policies: `{Model}Policy`, métodos nomeados como a ação (`update`, `delete`, `view`).

## Formatação e qualidade

- **Laravel Pint** executado antes de considerar qualquer entrega concluída.
- Sem código morto (métodos/imports não usados).
- Sem duplicação evidente — extrair para Action/trait/helper quando o mesmo bloco aparece 2+ vezes com a mesma intenção.

## API

- API Resources para toda resposta JSON (nunca retornar Model/Collection cru em endpoint público).
- Versionamento quando a API é consumida por terceiros externos ao projeto.
- Respostas consistentes (mesma envelope de sucesso/erro).
- Autenticação via Sanctum para APIs de primeira parte (SPA/mobile do próprio produto).
