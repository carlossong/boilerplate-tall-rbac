# 08 — Performance, Filas e Observabilidade

## Sempre analisar

- Cache (Redis/array conforme ambiente) para dados custosos e pouco voláteis.
- Eager Loading para evitar N+1 (ver `05-database.md`).
- Query Optimization: índices, `select()` de colunas específicas em listagens grandes, evitar `SELECT *` em relatórios pesados.
- Paginação em toda listagem que pode crescer sem limite.
- Lazy Loading (de assets/componentes, não confundir com lazy loading de relação Eloquent, que deve ser evitado) apenas quando apropriado.
- Otimização e minificação de assets via Vite.

## Filas — usar Jobs para

- Envio de e-mails e notificações
- Importações e exportações
- Geração de relatórios
- Qualquer processamento pesado ou que dependa de serviço externo lento

Nunca executar processamento pesado de forma síncrona numa request HTTP que o usuário está aguardando.

## Eventos — usar para

- Auditoria
- Integrações entre módulos/domínios
- Notificações
- Automações

Eventos ajudam a evitar acoplamento direto entre domínios — preferir a um Service de um domínio chamar diretamente uma classe de outro domínio.

## Performance no Livewire 4

- **Bundling**: por padrão o Livewire agrupa as atualizações de vários componentes num único request. Um componente caro segura os demais — marque-o com `#[Isolate]` para ele rodar em paralelo. `#[Lazy]` já isola por padrão.
- **Islands** (`@island`): isola uma região dentro do componente, evitando re-renderizar a página inteira. Mais barato do que quebrar em componente filho só por performance. `@island(lazy: true)` adia até entrar no viewport; `defer: true` renderiza logo após o load.
- **`#[Computed]`**: evita recalcular o mesmo dado em `render()` e na view.
- **`#[Renderless]`**: ações que não precisam re-renderizar (log, analytics) não pagam o custo do render.
- `wire:poll` no v4 não bloqueia mais outros requests, mas continua sendo tráfego constante — para tempo real preferir broadcasting (Reverb).

## Observabilidade (quando disponível no projeto)

- **Pulse**: métricas de performance em produção (queries lentas, jobs, exceptions).
- **Telescope**: debug detalhado em desenvolvimento/staging (nunca habilitado sem proteção em produção pública).
- **Horizon**: monitoramento e gerência de filas Redis.

## Checklist rápido antes de dar como pronto

- Nenhuma query N+1 óbvia na listagem/tela criada.
- Nenhum processamento lento rodando de forma síncrona numa request web.
- Cache invalidado corretamente quando o dado subjacente muda (evitar cache "eternamente stale").
