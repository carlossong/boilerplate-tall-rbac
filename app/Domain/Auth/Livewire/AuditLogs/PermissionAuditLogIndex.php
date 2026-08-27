<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\AuditLogs;

use App\Domain\Auth\Enums\PermissionAuditAction;
use App\Domain\Auth\Models\PermissionAuditLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Audit Logs')]
class PermissionAuditLogIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $actionFilter = '';

    public string $grantableFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', PermissionAuditLog::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedGrantableFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'actionFilter', 'grantableFilter']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = PermissionAuditLog::query()->latest('created_at');

        if (filled($this->search)) {
            $query->search($this->search);
        }

        if (filled($this->actionFilter)) {
            $query->where('action', $this->actionFilter);
        }

        if (filled($this->grantableFilter)) {
            $query->where('grantable_type', $this->grantableFilter);
        }

        $logs = $query->paginate(15);

        $stats = [
            'total' => PermissionAuditLog::count(),
            'assigned' => PermissionAuditLog::where('action', PermissionAuditAction::Assigned)->count(),
            'revoked' => PermissionAuditLog::where('action', PermissionAuditAction::Revoked)->count(),
            'with_actor' => PermissionAuditLog::whereNotNull('actor_id')->count(),
        ];

        return view('domain.auth.audit-logs.index', [
            'logs' => $logs,
            'stats' => $stats,
        ]);
    }
}
