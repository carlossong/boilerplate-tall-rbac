# 07 — Testes

## Framework preferencial

**Pest 4** como padrão — roda sobre **PHPUnit 12** e exige **PHP 8.3+**; testes PHPUnit e Pest coexistem na mesma suíte. Usar PHPUnit puro apenas se o projeto legado já estiver nesse padrão e a migração não fizer sentido no momento.

Recursos do Pest 3/4 a considerar quando agregam valor:
- **Arch Presets** (`arch()->preset()->php()`): presets disponíveis — `php`, `security`, `laravel`, `strict`, `relaxed`. Vale habilitar `php` e `security` em todo projeto novo.
- **Mutation Testing** (`pest --mutate`): valida que os testes realmente cobrem a lógica, não só executam o código — usar pontualmente em módulos críticos (cálculo fiscal, financeiro), não como gate obrigatório de CI a menos que o projeto já exija.
- **Browser Testing** (Pest 4): end-to-end real, via Playwright. Exige `composer require pestphp/pest-plugin-browser --dev` + `npm install playwright@latest` + `npx playwright install`, e `tests/Browser/Screenshots` no `.gitignore`. Rodar com `--parallel`; `--debug` abre modo headed. API: `visit('/')->click(...)->fill(...)->assertSee(...)`, `visit(['/','/about'])` para várias páginas, e asserções de saúde `assertNoSmoke()`, `assertNoJavaScriptErrors()`, `assertNoConsoleLogs()`, `assertNoAccessibilityIssues()`.
- **Datasets** (`->with([...])`) em vez de duplicar o mesmo teste com inputs diferentes.

## Tipos de teste a criar

- **Unit Tests**: Actions, Services, Value Objects, Enums (ex: `canTransitionTo()`), regras de negócio isoladas.
- **Feature Tests**: fluxo HTTP completo (rota → controller/Livewire → resposta), incluindo autorização e validação.
- **Livewire Tests**: `Livewire::test(Component::class)` cobrindo estados, validação, eventos, autorização dentro do componente. Componentes SFC/MFC do Livewire 4 não têm classe importável — o teste fica junto do componente (`nome.test.php`) e o Pest precisa ser configurado para enxergar `resources/views`; ver `03-livewire.md`. Se o projeto veio do Volt, `Volt::test()` vira `Livewire::test()`.

## Regras obrigatórias

- Toda correção de bug **deve** incluir um teste que reproduz o problema antes do fix (teste vermelho → fix → teste verde).
- Cobertura mínima recomendada: 80% em código de domínio novo (não é meta absoluta em todo legado, mas é o alvo para código novo).
- Testar o caminho negativo (usuário não autorizado, dado inválido) tanto quanto o caminho feliz.
- Testes de multi-tenancy: garantir explicitamente que um workspace não acessa dado de outro (teste de isolamento, não só teste de CRUD dentro do mesmo tenant).

## Estrutura Pest típica

```php
it('impede que um usuário edite fatura de outro workspace', function () {
    $owner = User::factory()->for(Workspace::factory())->create();
    $invoice = Invoice::factory()->for($owner->workspace)->create();
    $intruder = User::factory()->for(Workspace::factory())->create();

    actingAs($intruder)
        ->put(route('invoices.update', $invoice), [...])
        ->assertForbidden();
});
```

## Antes de considerar uma tarefa concluída

- Pint executado (formatação).
- Testes relevantes passando (não só os novos — rodar a suíte afetada).
- Sem código morto ou comentário desnecessário deixado para debug.
- Sem duplicação evidente de lógica já coberta em outro teste/Action.
