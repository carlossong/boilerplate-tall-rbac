---
name: tall-stack-framework
description: Framework de engenharia completo para desenvolvimento com a TALL Stack (Laravel, Alpine.js, Livewire, Tailwind CSS), incluindo Flux UI e FilamentPHP. Use esta skill SEMPRE que o usuário pedir para criar, revisar, refatorar ou planejar qualquer aplicação, módulo, componente Livewire, painel Filament, migration, policy, teste Pest ou funcionalidade em projetos Laravel/TALL — mesmo que ele não mencione "TALL Stack" explicitamente, mas apenas "Laravel", "Livewire", "Filament", "Flux UI" ou "PHP com Laravel". Cobre princípios de arquitetura, estrutura de pastas por domínio, padrões Livewire, UI/design system, modelagem de banco de dados e Eloquent, segurança (OWASP), testes com Pest, performance/observabilidade, convenções de código PHP e o fluxo de trabalho para agentes de IA (planejamento → implementação → revisão → refatoração). Esta skill é obrigatória como padrão de qualidade e arquitetura em todo o ciclo de desenvolvimento TALL Stack.
---

# TALL Stack Framework

Framework de engenharia de referência para construir aplicações production-ready com **T**ailwind CSS, **A**lpine.js, **L**aravel e **L**ivewire — incluindo Flux UI e FilamentPHP como camada de UI/admin.

Versões de referência (conferidas na documentação oficial em 05/08/2026):

| Peça | Versão | Requisito |
|---|---|---|
| Laravel | 13.x | PHP ^8.3 |
| Livewire | 4.x | Laravel 11+ |
| Flux UI | 2.x | Livewire 3.7+, Tailwind CSS 4.2+ |
| Tailwind CSS | 4.x | CSS-first, sem `tailwind.config.js` |
| Filament | 5.x | painel/admin |
| Pest | 4.x | PHP 8.3+, sobre PHPUnit 12 |

Se o projeto usa versões mais antigas (ex: Livewire 3), avisar e ajustar — `03-livewire.md`, `04-ui.md` e `07-testing.md` marcam o que muda entre versões.

Este é o padrão de arquitetura, segurança e qualidade a ser seguido em qualquer tarefa relacionada a projetos TALL Stack, salvo instrução explícita em contrário do usuário.

**Precedência**: convenções do projeto atual (`CLAUDE.md`/`AGENTS.md`, código existente) > esta skill > preferência pessoal. Esta skill descreve o padrão *default*; quando o repositório já decidiu diferente (estrutura de pastas, tipo de coluna monetária, formato de componente), seguir o repositório e não "corrigir" o código para o ideal teórico.

## Como usar esta skill

1. **Identifique o domínio da tarefa** (arquitetura, Livewire, UI, banco de dados, segurança, testes, performance, convenções de código, ou planejamento/fluxo de IA) e abra o(s) documento(s) de referência correspondente(s) antes de escrever código.
2. **Consulte sempre a documentação oficial** como fonte prioritária em caso de dúvida ou conflito. Em projetos com Laravel Boost instalado, usar a ferramenta `search-docs` — ela retorna a documentação da versão exata instalada, o que vale mais do que memória de treinamento. Links canônicos em `01-principles.md`.
3. **Aplique o fluxo de IA** descrito em `10-ai-workflow.md` para qualquer tarefa não trivial: planejar → implementar → revisar → refatorar.
4. **Nunca pule autorização, validação ou testes** só porque a tarefa parece pequena — os critérios de "definição de pronto" em `01-principles.md` são obrigatórios.

## Mapa dos documentos de referência

Leia apenas o(s) arquivo(s) relevante(s) para a tarefa — não é necessário carregar todos de uma vez.

| Arquivo | Quando consultar |
|---|---|
| `references/01-principles.md` | Antes de qualquer tarefa — princípios gerais, filosofia, stack, fontes oficiais, definição de pronto |
| `references/02-project-structure.md` | Criar um novo projeto/módulo, decidir onde um arquivo deve morar, organizar por domínio |
| `references/03-livewire.md` | Criar ou revisar componentes Livewire (SFC/MFC/classe), Pages, Islands, Forms, lifecycle, eventos, migração v3 → v4 |
| `references/04-ui.md` | Trabalhar com Flux UI, Tailwind, Alpine.js, acessibilidade, dark mode, design system |
| `references/05-database.md` | Migrations, models Eloquent, relacionamentos, DTOs, Value Objects, multi-tenancy |
| `references/06-security.md` | Autenticação, autorização (Policies/Gates), validação, proteção OWASP |
| `references/07-testing.md` | Escrever ou revisar testes Pest/PHPUnit/Livewire, correção de bugs |
| `references/08-performance.md` | Cache, filas, otimização de queries, observabilidade (Pulse/Telescope/Horizon) |
| `references/09-coding-standards.md` | Convenções de código PHP, tipagem, formatação, Pint, nomenclatura |
| `references/10-ai-workflow.md` | Planejar uma feature grande, sequenciar o trabalho, checklist de revisão |

## Regras que nunca mudam (resumo)

- Nunca inventar APIs inexistentes ou usar recursos depreciados — confirmar a versão instalada antes de escrever código para ela.
- Toda operação precisa de autorização (Policy/Gate) e validação — nunca confiar em dados do cliente, incluindo propriedades públicas de componente Livewire.
- Toda correção de bug inclui um teste que reproduz o problema.
- Views (Blade) contêm apenas apresentação — sem query, sem regra de negócio.
- Evitar N+1: sempre eager loading quando necessário.
- Código tipado (`declare(strict_types=1)`, return types) sempre que a linguagem suportar.
- Antes de considerar uma tarefa concluída: Pint executado, testes passando, sem código morto.

Ver `01-principles.md` para a lista completa de restrições e a definição de pronto.
