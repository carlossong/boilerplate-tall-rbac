# 01 — Princípios de Arquitetura

## Objetivo

Padrão de engenharia para aplicações TALL Stack, priorizando simplicidade, legibilidade, segurança, escalabilidade, testabilidade e facilidade de manutenção.

## Stack principal (atualizado)

Sempre utilizar as versões estáveis mais recentes compatíveis entre si.

- **Backend**: Laravel 13 (requer PHP `^8.3`), Composer
- **Frontend**: Livewire 4, Alpine.js (embarcado no bundle do Livewire), Tailwind CSS v4, Vite
- **UI**: Flux UI 2 (preferencialmente — feito para Livewire, exige Tailwind CSS 4.2+), Heroicons ou Lucide
- **Admin/Painéis**: FilamentPHP v5
- **Testes**: Pest 4 (sobre PHPUnit 12, requer PHP 8.3+)

Laravel 13 manteve **zero breaking changes** relevantes em relação ao Laravel 12 e ampliou o uso de **PHP Attributes** como alternativa opcional a propriedades de classe (`#[Fillable]`, `#[Guarded]`, `#[Unguarded]` em Models; `#[Middleware]` em Controllers; `#[Tries]` em Jobs; `#[Singleton]`/`#[Scoped]` no container) — nomes e namespaces exatos em `09-coding-standards.md`. Livewire 4 é uma revisão maior: componentes single-file (`⚡`), **Islands** e **Pages** — ver `03-livewire.md` antes de gerar qualquer componente novo.

## Ferramentas suportadas

Utilizar quando fizer sentido: Fortify, Sanctum, Reverb, Pulse, Horizon, Scout, Pennant, Telescope, Boost, Pint, Sail, Echo, Debugbar, IDE Helper, Pest, PHPUnit, Filament, pacotes Spatie, Ziggy.

> **Volt**: absorvido pelo Livewire 4 — a sintaxe de classe anônima do Volt virou o formato single-file nativo, e o guia oficial de upgrade manda trocar `Livewire\Volt\Component` por `Livewire\Component`, `Volt::route()` por `Route::livewire()`, `Volt::test()` por `Livewire::test()`, remover o `VoltServiceProvider` e desinstalar `livewire/volt`. Não instalar Volt em projeto novo.
>
> **Folio**: continua sendo um pacote separado e mantido (roteamento por arquivo para páginas Blade). Livewire 4 não o substitui — para páginas Livewire, use Pages + `Route::livewire()`.
>
> **Jetstream/Breeze**: substituídos pelos starter kits oficiais do Laravel (`laravel new` com Livewire/React/Vue). Só mencione Jetstream/Breeze em projeto legado que já os usa.

## Fontes oficiais (referência prioritária)

- https://laravel.com/docs/13.x
- https://livewire.laravel.com/docs/4.x
- https://tailwindcss.com/docs
- https://alpinejs.dev
- https://fluxui.dev/docs
- https://filamentphp.com/docs/5.x
- https://vite.dev
- https://pestphp.com/docs
- https://php.net/docs.php

Em caso de conflito entre fontes, a documentação oficial prevalece sobre memória de treinamento ou convenções antigas. Quando o projeto tem Laravel Boost, `search-docs` é a via preferencial: devolve a doc da versão instalada, não a da versão mais nova publicada.

## Filosofia

Priorizar sempre:
- Simplicidade e código expressivo
- Recursos nativos do Laravel antes de soluções personalizadas
- Componentização e reutilização
- Performance e segurança

Evitar abstrações desnecessárias e complexidade artificial.

## Arquitetura de referência

- SOLID, Clean Code, Clean Architecture
- Domain Driven Design quando fizer sentido (não obrigatório em todo módulo pequeno)
- Action Pattern e Service Pattern como primeira escolha
- Repository Pattern apenas quando realmente agrega valor (não usar por padrão em cima do Eloquent sem necessidade real de abstração de fonte de dados)
- DTOs, Value Objects, Form Objects quando reduzem ambiguidade
- Events/Listeners, Jobs, Policies, Gates conforme necessário

> Nota de convenção pessoal: em projetos anteriores (ex: ServiceOne ERP), Service Layer + Repository Pattern foi adotado de forma consistente mesmo sendo mais verboso — use esse padrão quando o projeto já seguir essa convenção; para projetos novos, prefira Actions/Services simples e só introduza Repository se houver múltiplas fontes de dados ou necessidade real de teste com fakes.

## Sequência de raciocínio da IA antes de responder

1. Identificar o domínio do problema.
2. Escolher a abordagem oficial do Laravel/ecossistema TALL.
3. Verificar se existe recurso nativo antes de criar solução personalizada.
4. Consultar a documentação oficial relevante e evitar sintaxe/API depreciada.
5. Explicar decisões arquiteturais quando relevante.
6. Alertar sobre impactos de desempenho e segurança.
7. Produzir código pronto para produção.
8. Evitar soluções experimentais sem justificativa.

## Restrições (nunca fazer)

- Inventar APIs inexistentes.
- Utilizar recursos depreciados ou sintaxe obsoleta.
- Ignorar validação ou autorização.
- Duplicar lógica de negócio (ex: replicar regra de backend no Alpine.js).
- Misturar responsabilidades (query/regra de negócio dentro de Blade).
- Escrever código sem tipagem quando a linguagem oferecer suporte.
- Adicionar dependências sem necessidade clara.
- Registrar senhas ou dados sensíveis em logs.

## Definição de pronto

Uma funcionalidade só é considerada concluída quando:

- Atende aos requisitos.
- Está autorizada por Policies/Gates.
- Está validada (backend, nunca só frontend).
- Possui tratamento de erros.
- Possui testes (idealmente Pest) — toda correção de bug inclui teste que reproduz o problema.
- É responsiva e acessível.
- Não possui problemas de segurança conhecidos.
- Segue as convenções desta skill.
- Está formatada (Pint executado), sem código morto, sem duplicação evidente.
- Está pronta para produção.
