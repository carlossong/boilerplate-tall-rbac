# 03 — Livewire (v4)

> Atualizado a partir de https://livewire.laravel.com/docs/4.x. Se o projeto ainda estiver em Livewire 3, ignore as seções marcadas "v4" e use componentes de classe + view separada — validação, autorização e componentes pequenos valem para as duas versões. A seção "Mudanças de comportamento v3 → v4" lista o que **quebra** ao migrar.

## Formatos de componente

Livewire 4 tem três formatos. O padrão do `make:livewire` é **single-file (SFC)**: classe anônima PHP + template Blade no mesmo arquivo.

```bash
php artisan make:livewire post.create            # SFC → resources/views/components/post/⚡create.blade.php
php artisan make:livewire pages::post.create     # SFC de página → resources/views/pages/post/⚡create.blade.php
php artisan make:livewire post.create --mfc      # multi-file → resources/views/components/post/⚡create/
php artisan make:livewire post.create --class    # classe tradicional (estilo v3)
```

Opções: `--sfc` | `--mfc` | `--class` | `--type=sfc|mfc|class` | `--emoji=true|false` | `--test` | `--js` | `--css` (as duas últimas só em MFC).

O `⚡` no nome do arquivo é só para reconhecimento visual no editor e pode ser desligado. Para restaurar o comportamento v3 no projeto inteiro, em `config/livewire.php`:

```php
'make_command' => [
    'type' => 'class',
    'emoji' => false,
],
```

Exemplo de SFC:

```php
<?php

use Livewire\Component;

new class extends Component {
    public string $title = '';

    public function save()
    {
        $this->validate(['title' => 'required|max:255']);
        // ...
    }
};
?>

<form wire:submit="save">
    <input type="text" wire:model="title">
    @error('title') <span>{{ $message }}</span> @enderror
    <button type="submit">Salvar</button>
</form>
```

Estrutura de um MFC:

```
resources/views/components/post/⚡create/
├── create.php          # classe PHP
├── create.blade.php    # template
├── create.js           # JS opcional
├── create.css          # estilos com escopo
├── create.global.css   # estilos globais
└── create.test.php     # teste Pest (--test)
```

`php artisan livewire:convert post.create` converte entre SFC e MFC (`--sfc`/`--mfc`). Converter MFC → SFC **apaga** o arquivo de teste.

**Qual usar**: SFC para a maioria dos componentes; MFC quando o componente é grande ou tem JS/CSS significativo; classe quando a equipe/projeto já padronizou assim. Definir um critério por projeto e ser consistente — não misturar sem regra dentro do mesmo módulo.

**Regra que não muda**: exatamente **um elemento raiz** no template. Comentários HTML fora da raiz também quebram. Exceção: named slots do layout, em componentes de página, podem ficar fora da raiz.

## Pages

Componentes que respondem a uma rota usam o namespace `pages::` e são registrados com `Route::livewire()`:

```php
Route::livewire('/posts/create', 'pages::post.create');
```

Pages têm acesso a layouts (`#[Layout]`), título (`#[Title]`), parâmetros de rota e model binding, e named slots. Reserve o namespace padrão (`components`) para componentes reutilizáveis embutidos em outras views.

## Islands (`@island`)

Regiões isoladas **dentro** de um componente que atualizam sem re-renderizar o resto:

```blade
@island(name: 'revenue', lazy: false, defer: false)
    <div>Receita: {{ $this->revenue }}</div>
@endisland

<button type="button" wire:click="$refresh" wire:island="revenue">Atualizar receita</button>
```

Parâmetros: `$name` (alvo do `wire:island`), `$lazy` (só renderiza quando entra no viewport), `$defer` (renderiza logo após o load da página). Use islands para isolar um cálculo caro sem pagar o custo de criar componente filho com props e eventos.

> `@island` **não** é a mesma coisa que `#[Isolate]` — ver abaixo.

## Atributos PHP (v4)

`#[Async]`, `#[Authorize]`, `#[Computed]`, `#[Defer]`, `#[Isolate]`, `#[Js]`, `#[Json]`, `#[Layout]`, `#[Lazy]`, `#[Locked]`, `#[Modelable]`, `#[On]`, `#[Reactive]`, `#[Renderless]`, `#[Rule]`, `#[Session]`, `#[Title]`, `#[Transition]`, `#[Url]`, `#[Validate]` — todos em `Livewire\Attributes\`.

Os que mais mudam decisão de arquitetura:

- **`#[Authorize('update', 'post')]`** — roda o Gate/Policy antes da ação, respondendo 403 se falhar. Aceita array para passar parâmetros à policy: `#[Authorize('create', [Comment::class, 'post'])]`. **Não esconde botão** — continue usando `@can` na view.
- **`#[Isolate]`** — atributo de **classe**: impede que as requisições daquele componente sejam agrupadas (bundled) com as dos outros, deixando-as rodar em paralelo. Use quando a atualização é cara e seguraria o request dos demais. Componentes `#[Lazy]` já são isolados por padrão (`#[Lazy(isolate: false)]` desliga).
- **`#[Computed]`** — dados derivados, com cache dentro do request.
- **`#[Locked]`** — propriedade que o frontend não pode alterar (IDs de owner/tenant).
- **`#[Renderless]`** — ação que roda lógica sem re-renderizar (log, analytics).
- **`#[Modelable]`** — expõe propriedade para `wire:model` do componente pai.
- **`#[Reactive]`** — propriedade que reage a mudança vinda do pai sem evento manual.
- **`#[Async]`** — a ação não bloqueia a interação com o resto do componente.
- **`#[Transition(type: 'forward')]`** — define o tipo de view transition da ação (ver abaixo).

## Preferências obrigatórias (v3 e v4)

- Componentes pequenos, uma responsabilidade por componente.
- Computed Properties para dados derivados — não recalcular em `render()` e na view.
- Form Objects (`Livewire\Form`) para formulários com mais de 2-3 campos ou validação complexa.
- `#[Lazy]` para componentes pesados fora do viewport inicial.
- Paginação nativa do Livewire para listagens.
- Validação sempre no componente/Form Object, nunca só no frontend.
- Autorização **dentro do método da ação** (`#[Authorize]` ou `$this->authorize()`), não só escondendo o botão.
- Eventos (`dispatch`/`#[On]`) só quando é preciso comunicar entre componentes.
- `wire:key` em todo elemento e componente dentro de `@foreach`/`@switch` — inclusive em componente Livewire aninhado fundo no loop. Prefixar a chave (`post-{{ $id }}`, `author-{{ $id }}`) quando dois conjuntos podem colidir de ID.
- `wire:poll` só quando necessário — para tempo real, preferir Reverb/broadcasting.

## Segurança de propriedades públicas

Propriedade pública é **input não confiável**: o usuário pode injetar `<input wire:model="postId">` e trocar o valor. Três defesas oficiais, em ordem de preferência:

1. Guardar o **model** (`public Post $post`) em vez do ID solto — Livewire garante que o ID não é adulterado.
2. `#[Locked]` na propriedade (ainda alterável pelo backend — cuidado ao atribuir input do usuário a ela).
3. Autorizar dentro da ação (`$this->authorize('delete', $post)`), aceitando que o valor pode ter mudado.

Manter as propriedades públicas no mínimo necessário e serializáveis — ver `06-security.md`.

## Estilos e scripts no componente (v4)

- `<style>` na raiz de um SFC (ou `nome.css` no MFC) fica **com escopo** no componente.
- `<style global>` (ou `nome.global.css`) escapa o escopo — use só quando o estilo precisa vazar de propósito.
- `@assets ... @endassets` para CSS/JS de terceiros (CDN), carregado uma vez por página.

## Mudanças de comportamento v3 → v4 (verificar ao migrar)

| Assunto | v3 | v4 |
|---|---|---|
| `wire:model.blur` / `.change` | controlava só quando ia request; o estado do cliente sincronizava na hora | controla **também** o sync client-side. Para o comportamento antigo: `wire:model.live.blur` |
| `wire:transition` | wrapper do `x-transition` do Alpine, com modificadores `.opacity`, `.scale`, `.duration.200ms` | usa a **View Transitions API** nativa; **todos os modificadores foram removidos** |
| `$this->stream()` | `stream(to: '#el', content: '...')` | `stream(content: '...', replace: true, el: '#el')` |
| `wire:poll` | podia bloquear/ser bloqueado por outros requests | não bloqueia mais; `wire:model.live` também passa a rodar em paralelo |
| Volt | pacote `livewire/volt` | absorvido pelo SFC nativo — ver `01-principles.md` |

`wire:model.lazy` continua compatível.

## Testes de componentes view-based

SFC/MFC não têm classe importável, então o teste vive junto do componente. Para o Pest enxergar:

```php
// tests/Pest.php
pest()->extend(Tests\TestCase::class)->in('Feature', '../resources/views');
```

```xml
<!-- phpunit.xml -->
<testsuite name="Components">
    <directory suffix=".test.php">resources/views</directory>
</testsuite>
```

Componentes de classe continuam com `Livewire::test(Component::class)` normalmente — ver `07-testing.md`.

## Evitar

- Lógica pesada de negócio dentro do template do componente.
- Componentes "god object" que fazem CRUD de várias entidades ao mesmo tempo.
- Duplicar validação de regra de negócio que já existe em outra camada.
- Confundir `@island` (região dentro do componente) com `#[Isolate]` (não agrupar requisições do componente).
- Usar `wire:transition` esperando modificadores do v3 — eles não existem mais.
