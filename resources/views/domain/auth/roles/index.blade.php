<div>
    <div class="space-y-6">
        <!-- Breadcrumbs -->
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Administration') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Roles') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-zinc-200/80 dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl" class="font-bold text-zinc-900 dark:text-zinc-50">
                        {{ __('Roles Management') }}
                    </flux:heading>
                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $stats['total'] }}</flux:badge>
                </div>
                <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Configure user roles, organize security permission groups, and control team boundaries.') }}
                </flux:subheading>
            </div>

            @can('create', App\Domain\Auth\Models\Role::class)
                <div class="flex items-center gap-2">
                    <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                        {{ __('New Role') }}
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

        @if (session('error'))
            <div class="flex items-center gap-3 rounded-lg bg-red-50/80 dark:bg-red-950/30 p-3.5 border border-red-200/80 dark:border-red-800/60 text-red-900 dark:text-red-200 text-sm font-medium">
                <flux:icon icon="exclamation-triangle" class="size-5 text-red-600 dark:text-red-400 shrink-0" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Defined Roles') }}</span>
                    <div class="size-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-600 dark:text-zinc-300">
                        <flux:icon icon="shield-check" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['total'] }}</span>
                    <span class="text-xs text-zinc-500">{{ __('roles') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Active Roles') }}</span>
                    <div class="size-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <flux:icon icon="check" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['active'] }}</span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('available') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('System Permissions') }}</span>
                    <div class="size-9 rounded-lg bg-amber-50 dark:bg-amber-950/40 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <flux:icon icon="key" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['total_permissions'] }}</span>
                    <span class="text-xs text-amber-600 dark:text-amber-400">{{ __('assignable') }}</span>
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
            <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="w-full sm:w-80">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by role name or slug...')" clearable />
                </div>

                <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                    <flux:checkbox wire:model.live="showDeleted" :label="__('Show Trashed')" />

                    @if (filled($search) || $showDeleted)
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
                    <flux:table.column class="ps-6! py-3.5! w-1/4">{{ __('Role') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Slug') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Level') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Departments') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Permissions') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Assigned Users') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="center" class="pe-6! py-3.5! w-28 text-center">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($roles as $role)
                        <flux:table.row :key="$role->id" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition-colors">
                            <flux:table.cell class="ps-6! py-4!">
                                <div class="grid leading-tight">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $role->name }}</span>
                                        @if ($role->slug === 'admin')
                                            <flux:badge size="sm" color="purple" inset="top bottom">{{ __('System Role') }}</flux:badge>
                                        @endif
                                    </div>
                                    @if ($role->description)
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $role->description }}</span>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <flux:badge size="sm" color="zinc" class="font-mono">{{ $role->slug }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <flux:badge size="sm" :color="$role->levelBadgeColor()" inset="top bottom">
                                    Lvl {{ $role->level }}
                                </flux:badge>
                            </flux:table.cell>


                            <flux:table.cell class="py-4!">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse ($role->departments as $dept)
                                        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $dept->name }}</flux:badge>
                                    @empty
                                        <span class="text-xs text-zinc-400">{{ __('Global') }}</span>
                                    @endforelse
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon icon="key" class="size-3.5 text-zinc-400" />
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $role->permissions_count }}
                                    </span>
                                    <span class="text-xs text-zinc-400">{{ __('abilities') }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon icon="users" class="size-3.5 text-zinc-400" />
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $role->users_count }}
                                    </span>
                                    <span class="text-xs text-zinc-400">{{ __('assigned') }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                @if ($role->trashed())
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
                                        $canRestore = $role->trashed() && auth()->user()?->can('restore', $role);
                                        $canUpdate = ! $role->trashed() && auth()->user()?->can('update', $role);
                                        $canDelete = ! $role->trashed() && $role->slug !== 'admin' && auth()->user()?->can('delete', $role);
                                    @endphp

                                    @if ($canRestore || $canUpdate || $canDelete)
                                        <flux:dropdown align="end">
                                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" inset="top bottom" />

                                            <flux:menu class="min-w-36">
                                                @if ($canRestore)
                                                    <flux:menu.item icon="arrow-path" wire:click="restoreRole('{{ $role->id }}')">
                                                        {{ __('Restore Role') }}
                                                    </flux:menu.item>
                                                @endif

                                                @if ($canUpdate)
                                                    <flux:menu.item icon="pencil-square" wire:click="openEditModal('{{ $role->id }}')">
                                                        {{ __('Edit Role') }}
                                                    </flux:menu.item>
                                                @endif

                                                @if ($canDelete)
                                                    @if ($canUpdate)
                                                        <flux:menu.separator />
                                                    @endif
                                                    <flux:menu.item variant="danger" icon="trash" wire:click="confirmDelete('{{ $role->id }}')">
                                                        {{ __('Delete Role') }}
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
                                        <flux:icon icon="shield-check" class="size-6" />
                                    </div>
                                    <flux:heading size="md">{{ __('No roles found') }}</flux:heading>
                                    <flux:subheading class="max-w-sm mt-1">
                                        {{ __('No system roles matched your search parameters.') }}
                                    </flux:subheading>
                                    @if (filled($search) || $showDeleted)
                                        <div class="mt-4">
                                            <flux:button size="sm" variant="filled" wire:click="resetFilters">
                                                {{ __('Clear filters') }}
                                            </flux:button>
                                        </div>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($roles->hasPages())
                <div class="p-4 border-t border-zinc-200/80 dark:border-zinc-800">
                    {{ $roles->links() }}
                </div>
            @endif
        </flux:card>
    </div>

    <!-- Create / Edit Role Modal -->
    <flux:modal wire:model.self="showingModal" class="max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $form->id ? __('Edit Role') : __('Create New Role') }}
                </flux:heading>
                <flux:subheading>
                    {{ $form->id ? __('Configure role attributes and fine-tune assigned permissions.') : __('Create a custom role and attach permissions across system resources.') }}
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <flux:input wire:model="form.name" :label="__('Role Name')" :placeholder="__('e.g. Moderator')" required />
                    <flux:input wire:model="form.slug" :label="__('Slug')" :placeholder="__('e.g. moderator')" :disabled="$form->slug === 'admin'" required />
                    <flux:input type="number" min="1" max="100" wire:model="form.level" :label="__('Level (1-100)')" :placeholder="10" required />
                </div>

                <flux:textarea wire:model="form.description" :label="__('Description')" :placeholder="__('Briefly describe what duties users with this role perform.')" rows="2" />

                <!-- Associated Departments -->
                @if ($availableDepartments->isNotEmpty())
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <flux:label class="font-medium">{{ __('Associated Departments') }}</flux:label>
                            <span class="text-xs text-zinc-500">
                                {{ count($form->department_ids) }} {{ __('selected (empty = Global)') }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 border border-zinc-200 dark:border-zinc-800 rounded-lg max-h-36 overflow-y-auto bg-zinc-50/30 dark:bg-zinc-900/30">
                            @foreach ($availableDepartments as $dept)
                                <label class="flex items-center gap-2 p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs cursor-pointer">
                                    <input type="checkbox" wire:model="form.department_ids" value="{{ $dept->id }}" class="rounded border-zinc-300 text-zinc-900" />
                                    <span class="text-zinc-800 dark:text-zinc-200">{{ $dept->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Grouped Permissions -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <flux:label class="font-medium">{{ __('Attached Permissions by Resource') }}</flux:label>
                        <span class="text-xs text-zinc-500">
                            {{ count($form->permission_ids) }} {{ __('selected') }}
                        </span>
                    </div>

                    <div class="space-y-4 border border-zinc-200 dark:border-zinc-800 rounded-lg p-4 max-h-80 overflow-y-auto bg-zinc-50/30 dark:bg-zinc-900/30">
                        @foreach ($groupedPermissions as $resource => $permissions)
                            <div class="rounded-md border border-zinc-200/60 dark:border-zinc-800/60 p-3 bg-white/70 dark:bg-zinc-900/70">
                                <div class="flex items-center justify-between mb-2 pb-1 border-b border-zinc-100 dark:border-zinc-800/60">
                                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-300">
                                        {{ ucfirst($resource) }}
                                    </span>
                                    <span class="text-xs text-zinc-400">
                                        {{ count(array_intersect($permissions->pluck('id')->all(), $form->permission_ids)) }} / {{ $permissions->count() }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach ($permissions as $permission)
                                        <div class="p-1.5 rounded hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                            <flux:checkbox
                                                wire:model="form.permission_ids"
                                                value="{{ $permission->id }}"
                                                :label="$permission->name"
                                                :description="$permission->slug"
                                            />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <flux:error name="form.permission_ids" />
                </div>
            </div>

            <div class="flex justify-end gap-2.5 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button variant="filled" wire:click="$set('showingModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit">
                    {{ $form->id ? __('Save Changes') : __('Create Role') }}
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
                    <flux:heading size="lg">{{ __('Confirm Role Deletion') }}</flux:heading>
                    <flux:subheading class="mt-1 text-sm">
                        {{ __('Are you sure you want to delete role ":name"? This action will perform a soft delete and can be restored later.', ['name' => $deletingRoleName]) }}
                    </flux:subheading>
                </div>
            </div>

            <div class="flex justify-end gap-2.5 pt-2">
                <flux:button variant="filled" wire:click="$set('showingDeleteModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="deleteRole">
                    {{ __('Delete Role') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
