# 10 — Fluxo de Trabalho para Agentes de IA

Fluxo a seguir ao trabalhar em qualquer tarefa não trivial dentro de um projeto TALL Stack, alinhado com o workflow de vibe coding já usado (PRDs, prompts de implementação, `AGENT.md`).

## 1. Planejamento

Antes de escrever código:
- Identificar o domínio de negócio afetado (ver `02-project-structure.md`).
- Verificar se já existe PRD/documentação da feature; se não existir e a tarefa for grande, propor um resumo estruturado (objetivo, escopo, fora de escopo, critérios de aceite) antes de implementar.
- Checar se existe recurso nativo do Laravel/TALL antes de propor algo customizado.
- **Confirmar as versões instaladas antes de escrever código** (`composer.lock`, `package.json`, ou `application-info` do Boost) e buscar a doc dessa versão — de preferência via `search-docs`. A diferença entre Livewire 3 e 4, ou Flux Free e Pro, muda o código gerado inteiro.
- Mapear quais camadas serão tocadas (Model, Action, Livewire, Policy, Job, etc.) e quais são realmente necessárias — evitar over-engineering.

## 2. Implementação

- Seguir as convenções de `09-coding-standards.md` desde a primeira linha.
- Escrever autorização e validação junto com a funcionalidade, não como etapa separada "depois".
- Escrever o teste junto (ou logo em seguida) da implementação, não deixar para o final da tarefa inteira.
- Explicar decisões arquiteturais relevantes ao usuário quando a escolha não é óbvia (ex: "usei Action em vez de Service porque é uma operação única").
- Apontar proativamente impactos de performance/segurança percebidos, mesmo que não pedidos.

## 3. Revisão

Antes de entregar como concluído, checar contra a "Definição de pronto" (`01-principles.md`):
- Autorização, validação, tratamento de erro presentes.
- Testes cobrindo caminho feliz e negativo.
- Sem N+1 introduzido.
- Sem código morto/comentário de debug esquecido.
- Pint executado.

## 4. Refatoração

- Ao tocar em código legado que viola as convenções desta skill, refatorar o trecho tocado sem expandir escopo além do necessário para a tarefa atual — não fazer refatoração ampla não solicitada dentro de uma tarefa pontual.
- Se identificar débito técnico relevante fora do escopo da tarefa, reportar ao usuário em vez de silenciosamente expandir a mudança.

## Regras finais do fluxo de IA

- Nunca inventar API inexistente ou usar funcionalidade removida. Em dúvida sobre um componente/atributo existir na versão instalada, conferir no `vendor/` antes de usar — é mais rápido do que descobrir pelo erro em tela.
- Sempre citar quando há alternativa oficial mais moderna ao que foi pedido.
- Sempre preferir a documentação oficial em caso de conflito com convenção antiga.
- Manter consistência com as convenções já estabelecidas no projeto em questão, mesmo quando divergem levemente do "ideal" desta skill — consistência interna do projeto > pureza teórica.
