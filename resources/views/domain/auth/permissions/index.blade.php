<div>
    <div class="space-y-6">
        <!-- Breadcrumbs -->
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Administration') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Permissions') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-zinc-200/80 dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl" class="font-bold text-zinc-900 dark:text-zinc-50">
                        {{ __('Permissions Management') }}
                    </flux:heading>
                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $stats['total'] }}</flux:badge>
                </div>
                <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Define fine-grained gate abilities using the "resource.action" convention for granular access control.') }}
                </flux:subheading>
            </div>

            @can('create', App\Domain\Auth\Models\Permission::class)
                <div class="flex items-center gap-2">
                    <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                        {{ __('New Permission') }}
                    </flux:button>
                </div>
            @endcan
        </div>

        @if (session('status'))
            <div class="flex items-center gap-3 rounded-lg bg-emerald-50/80 dark:bg-emerald-950/30 p-3.5 border border-emerald-200/80 dark:border-emerald-800/60 text-emerald-900 dark:text-emerald-200 text-sm font-medium">
                <flux:icon icon="check-circle" class="size-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Total Abilities') }}</span>
                    <div class="size-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-600 dark:text-zinc-300">
                        <flux:icon icon="key" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['total'] }}</span>
                    <span class="text-xs text-zinc-500">{{ __('permissions') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Active Abilities') }}</span>
                    <div class="size-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <flux:icon icon="check-badge" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['active'] }}</span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('in use') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Resources') }}</span>
                    <div class="size-9 rounded-lg bg-amber-50 dark:bg-amber-950/40 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <flux:icon icon="folder" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['resources_count'] }}</span>
                    <span class="text-xs text-amber-600 dark:text-amber-400">{{ __('categories') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Soft-Deleted') }}</span>
                    <div class="size-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500">
                        <flux:icon icon="trash" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['trashed'] }}</span>
                    <span class="text-xs text-zinc-500">{{ __('archived') }}</span>
                </div>
            </flux:card>
        </div>

        <!-- Search & Filter Toolbar -->
        <flux:card class="!p-5 bg-zinc-50/40 dark:bg-zinc-900/40 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
            <div class="flex flex-col md:flex-row gap-3 items-center justify-between">
                <div class="flex flex-1 flex-col sm:flex-row gap-3 w-full">
                    <div class="w-full sm:w-80">
                        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search permissions...')" clearable />
                    </div>

                    <div class="w-full sm:w-60">
                        <flux:select wire:model.live="resourceFilter" :placeholder="__('All Resources')">
                            <flux:select.option value="">{{ __('Resource: All') }}</flux:select.option>
                            @foreach ($resources as $res)
                                <flux:select.option value="{{ $res }}">{{ ucfirst($res) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end">
                    <flux:checkbox wire:model.live="showDeleted" :label="__('Show Trashed')" />

                    @if (filled($search) || filled($resourceFilter) || $showDeleted)
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">
                            {{ __('Reset') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        </flux:card>

        <!-- Table -->
        <flux:card class="overflow-hidden p-0 border-zinc-200/80 dark:border-zinc-800 shadow-xs">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="ps-6! py-3.5! w-1/3">{{ __('Ability') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Slug (Gate Key)') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Resource') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Assigned Roles') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="center" class="pe-6! py-3.5! w-28 text-center">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($permissions as $permission)
                        @php
                            $parts = explode('.', $permission->slug);
                            $resource = count($parts) > 1 ? $parts[0] : 'other';
                            $action = count($parts) > 1 ? $parts[1] : $permission->slug;
                        @endphp
                        <flux:table.row :key="$permission->id" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition-colors">
                            <flux:table.cell class="ps-6! py-4!">
                                <div class="grid leading-tight">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $permission->name }}</span>
                                    @if ($permission->description)
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $permission->description }}</span>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <flux:badge size="sm" color="zinc" class="font-mono">{{ $permission->slug }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <flux:badge size="sm" color="purple" inset="top bottom">{{ ucfirst($resource) }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon icon="shield-check" class="size-3.5 text-zinc-400" />
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $permission->roles_count }}
                                    </span>
                                    <span class="text-xs text-zinc-400">{{ __('roles') }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                @if ($permission->trashed())
                                    <flux:badge size="sm" color="red" inset="top bottom">
                                        <span class="inline-block size-1.5 rounded-full bg-red-500 mr-1.5"></span>{{ __('Deleted') }}
                                    </flux:badge>
                                @else
                                    <flux:badge size="sm" color="emerald" inset="top bottom">
                                        <span class="inline-block size-1.5 rounded-full bg-emerald-500 mr-1.5"></span>{{ __('Active') }}
                                    </flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell align="center" class="pe-6! py-4! text-center">
                                <div class="flex justify-center">
                                    @php
                                        $canRestore = $permission->trashed() && auth()->user()?->can('restore', $permission);
                                        $canUpdate = ! $permission->trashed() && auth()->user()?->can('update', $permission);
                                        $canDelete = ! $permission->trashed() && auth()->user()?->can('delete', $permission);
                                    @endphp

                                    @if ($canRestore || $canUpdate || $canDelete)
                                        <flux:dropdown align="end">
                                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" inset="top bottom" />

                                            <flux:menu class="min-w-36">
                                                @if ($canRestore)
                                                    <flux:menu.item icon="arrow-path" wire:click="restorePermission('{{ $permission->id }}')">
                                                        {{ __('Restore Permission') }}
                                                    </flux:menu.item>
                                                @endif

                                                @if ($canUpdate)
                                                    <flux:menu.item icon="pencil-square" wire:click="openEditModal('{{ $permission->id }}')">
                                                        {{ __('Edit Permission') }}
                                                    </flux:menu.item>
                                                @endif

                                                @if ($canDelete)
                                                    @if ($canUpdate)
                                                        <flux:menu.separator />
                                                    @endif
                                                    <flux:menu.item variant="danger" icon="trash" wire:click="confirmDelete('{{ $permission->id }}')">
                                                        {{ __('Delete Permission') }}
                                                    </flux:menu.item>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    @else
                                        <span class="text-xs text-zinc-400 dark:text-zinc-500">—</span>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-12 px-6">
                                <div class="flex flex-col items-center justify-center text-center">
                                    <div class="size-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 mb-3">
                                        <flux:icon icon="key" class="size-6" />
                                    </div>
                                    <flux:heading size="md">{{ __('No permissions found') }}</flux:heading>
                                    <flux:subheading class="max-w-sm mt-1">
                                        {{ __('No gate abilities matched your search or resource filter.') }}
                                    </flux:subheading>
                                    @if (filled($search) || filled($resourceFilter) || $showDeleted)
                                        <div class="mt-4">
                                            <flux:button size="sm" variant="filled" wire:click="resetFilters">
                                                {{ __('Clear all filters') }}
                                            </flux:button>
                                        </div>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($permissions->hasPages())
                <div class="p-4 border-t border-zinc-200/80 dark:border-zinc-800">
                    {{ $permissions->links() }}
                </div>
            @endif
        </flux:card>
    </div>

    <!-- Create / Edit Permission Modal -->
    <flux:modal wire:model.self="showingModal" class="max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $form->id ? __('Edit Permission') : __('Create New Permission') }}
                </flux:heading>
                <flux:subheading>
                    {{ $form->id ? __('Modify permission name, gate slug, or description.') : __('Define a new authorization ability available for role assignment and @can checks.') }}
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="form.name" :label="__('Permission Name')" :placeholder="__('e.g. Export Reports')" required />

                <div>
                    <flux:input
                        wire:model="form.slug"
                        :label="__('Slug / Gate Key')"
                        :placeholder="__('e.g. reports.export')"
                        :description="__('Must follow the lowercase \'resource.action\' format (e.g., users.create, invoices.view).')"
                        required
                    />
                </div>

                <flux:textarea wire:model="form.description" :label="__('Description')" :placeholder="__('Explain when this permission should be granted.')" rows="2" />
            </div>

            <div class="flex justify-end gap-2.5 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button variant="filled" wire:click="$set('showingModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit">
                    {{ $form->id ? __('Save Changes') : __('Create Permission') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.self="showingDeleteModal" class="max-w-md">
        <div class="space-y-5">
            <div class="flex items-start gap-3.5">
                <div class="size-10 rounded-full bg-red-100 dark:bg-red-950/60 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                    <flux:icon icon="exclamation-triangle" class="size-5" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Confirm Permission Deletion') }}</flux:heading>
                    <flux:subheading class="mt-1 text-sm">
                        {{ __('Are you sure you want to delete permission ":name"? This action will perform a soft delete and can be restored later.', ['name' => $deletingPermissionName]) }}
                    </flux:subheading>
                </div>
            </div>

            <div class="flex justify-end gap-2.5 pt-2">
                <flux:button variant="filled" wire:click="$set('showingDeleteModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="deletePermission">
                    {{ __('Delete Permission') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
