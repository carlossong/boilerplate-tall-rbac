# 06 — Segurança

## Obrigatório em toda funcionalidade

- Policies como forma preferencial de autorização; Gates para regras que não giram em torno de um Model específico.
- CSRF protection (padrão do Laravel — nunca desabilitar em formulários que modificam estado).
- Validação sempre no backend (Form Requests ou Livewire Validation) — nunca confiar apenas em validação de frontend/HTML5.
- Password Hashing via `Hash::make` / `bcrypt` — nunca armazenar senha em texto plano ou hash customizado.
- Rate Limiting em rotas sensíveis (login, reset de senha, endpoints públicos de API).
- Sanitização e Escaping — Blade escapa por padrão (`{{ }}`); nunca usar `{!! !!}` com conteúdo vindo de usuário sem sanitizar.
- Signed URLs para links temporários/sensíveis (ex: confirmação de e-mail, unsubscribe, download temporário).

## Autorização

Toda operação que lê ou modifica dado de outro contexto que não seja público deve ter autorização explícita:

1. Preferência: `Policy` (`$this->authorize('update', $invoice)`).
2. Alternativa: `Gate` para regras não ligadas a um Model.
3. Nunca confiar apenas na interface (esconder botão não é autorização).

Em Livewire, revalidar autorização **dentro do método da ação**, não apenas no `mount()` — o componente pode ser re-hidratado com payload manipulado pelo client entre requests.

Formas declarativas (equivalentes ao `authorize()` manual, não substituem `@can` na view):

```php
use Livewire\Attributes\Authorize;                          // Livewire 4, no método da ação
#[Authorize('update', 'post')]                              // 403 se falhar
#[Authorize('create', [Comment::class, 'post'])]            // policy + parâmetros

use Illuminate\Routing\Attributes\Controllers\Authorize;    // Laravel 13, em Controller
#[Authorize('update', 'post', only: ['edit', 'update'])]
```

**Propriedade pública de componente Livewire é input do usuário.** Quem controla o navegador pode injetar `<input wire:model="postId">` e trocar o valor entre requests. Defesas, em ordem: guardar o Model (`public Post $post`) em vez do ID; `#[Locked]`; ou autorizar dentro da ação. Ver `03-livewire.md`.

## Proteções OWASP a considerar por padrão

- **SQL Injection**: usar Query Builder/Eloquent parametrizado; nunca concatenar input do usuário em `DB::raw`/queries brutas.
- **XSS**: Blade escapa por padrão; cuidado especial em conteúdo renderizado via `x-html` do Alpine ou `{!! !!}`.
- **Mass Assignment**: ver `05-database.md` — validar antes de persistir.
- **Broken Access Control**: toda rota/ação precisa checar tenant/ownership, não só autenticação (usuário autenticado ≠ autorizado a ver aquele recurso específico).
- **Secure Headers/CSP**: considerar em projetos com conteúdo de terceiros embutido.
- **Secure Cookies**: `secure`, `httponly`, `samesite` configurados adequadamente em produção (HTTPS).
- **Encryption**: dados sensíveis em repouso (ex: documentos, tokens de integração) usando `Crypt`/`encrypted` cast do Eloquent.

## Logs

- Registrar exceções, falhas críticas, integrações e processamentos importantes.
- **Nunca** registrar senhas, tokens, dados de cartão ou PII sensível em log — mascarar quando o log precisa referenciar o registro (ex: últimos 4 dígitos).
