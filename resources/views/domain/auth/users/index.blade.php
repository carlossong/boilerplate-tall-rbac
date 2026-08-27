<div>
    <div class="space-y-6">
        <!-- Breadcrumbs -->
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Administration') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Users') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-zinc-200/80 dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl" class="font-bold text-zinc-900 dark:text-zinc-50">
                        {{ __('User Management') }}
                    </flux:heading>
                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $stats['total'] }}</flux:badge>
                </div>
                <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Manage accounts, assign security roles, and control granular application access.') }}
                </flux:subheading>
            </div>

            @can('create', App\Domain\Auth\Models\User::class)
                <div class="flex items-center gap-2">
                    <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                        {{ __('New User') }}
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
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Total Accounts') }}</span>
                    <div class="size-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-600 dark:text-zinc-300">
                        <flux:icon icon="users" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['total'] }}</span>
                    <span class="text-xs text-zinc-500">{{ __('registered') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Active Users') }}</span>
                    <div class="size-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <flux:icon icon="check-circle" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['active'] }}</span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('enabled') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Super Admins') }}</span>
                    <div class="size-9 rounded-lg bg-amber-50 dark:bg-amber-950/40 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <flux:icon icon="shield-check" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['super_admins'] }}</span>
                    <span class="text-xs text-amber-600 dark:text-amber-400">{{ __('full bypass') }}</span>
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

        <!-- Filters & Search Toolbar -->
        <flux:card class="!p-5 bg-zinc-50/40 dark:bg-zinc-900/40 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
            <div class="flex flex-col md:flex-row gap-3 items-center justify-between">
                <div class="flex flex-1 flex-col sm:flex-row gap-3 w-full">
                    <div class="w-full sm:w-80">
                        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by name or email...')" clearable />
                    </div>

                    <div class="w-full sm:w-60">
                        <flux:select wire:model.live="roleFilter" :placeholder="__('All Roles')">
                            <flux:select.option value="">{{ __('Filter by Role: All') }}</flux:select.option>
                            @foreach ($allRoles as $role)
                                <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end">
                    <flux:checkbox wire:model.live="showDeleted" :label="__('Show Trashed')" />

                    @if (filled($search) || filled($roleFilter) || $showDeleted)
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
                    <flux:table.column class="ps-6! py-3.5! w-1/3">{{ __('User') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Global Roles') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Departments & Sectors') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Status') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Created') }}</flux:table.column>
                    <flux:table.column align="center" class="pe-6! py-3.5! w-28 text-center">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($users as $user)
                        <flux:table.row :key="$user->id" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition-colors">
                            <flux:table.cell class="ps-6! py-4!">
                                <div class="flex items-center gap-3">
                                    <flux:avatar :name="$user->name" :initials="$user->initials()" size="md" class="border border-zinc-200 dark:border-zinc-700 shadow-2xs" />
                                    <div class="grid leading-tight min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ $user->name }}</span>
                                            @if ($user->is_super_admin)
                                                <flux:badge size="sm" color="amber" inset="top bottom" icon="shield-check">{{ __('Super Admin') }}</flux:badge>
                                            @endif
                                            <flux:badge size="sm" color="zinc" class="font-mono text-xs">Lvl {{ $user->highestRoleLevel() }}</flux:badge>
                                        </div>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $user->email }}</span>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <div class="flex flex-wrap gap-1.5 max-w-xs">
                                    @forelse ($user->roles as $role)
                                        <flux:badge size="sm" :color="$role->slug === 'admin' ? 'purple' : 'zinc'">
                                            {{ $role->name }} <span class="text-xs opacity-70">({{ $role->level }})</span>
                                        </flux:badge>
                                    @empty
                                        <span class="text-xs italic text-zinc-400 dark:text-zinc-500">{{ __('No global roles') }}</span>
                                    @endforelse
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <div class="flex flex-wrap gap-1.5 max-w-xs">
                                    @forelse ($user->departments as $dept)
                                        <flux:badge size="sm" :color="$dept->pivot->is_primary ? 'sky' : 'zinc'" inset="top bottom">
                                            <flux:icon icon="building-office-2" class="size-3 mr-1 inline" />
                                            {{ $dept->name }}
                                            @if ($dept->pivot->role_id)
                                                @php $dRole = $allRoles->firstWhere('id', $dept->pivot->role_id); @endphp
                                                @if ($dRole)
                                                    <span class="text-xs font-semibold opacity-80">({{ $dRole->name }})</span>
                                                @endif
                                            @endif
                                        </flux:badge>
                                    @empty
                                        <span class="text-xs italic text-zinc-400 dark:text-zinc-500">{{ __('None') }}</span>
                                    @endforelse
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                @if ($user->trashed())
                                    <flux:badge size="sm" color="red" inset="top bottom">
                                        <span class="inline-block size-1.5 rounded-full bg-red-500 mr-1.5"></span>{{ __('Deleted') }}
                                    </flux:badge>
                                @else
                                    <flux:badge size="sm" color="emerald" inset="top bottom">
                                        <span class="inline-block size-1.5 rounded-full bg-emerald-500 mr-1.5"></span>{{ __('Active') }}
                                    </flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <span class="text-xs text-zinc-500 dark:text-zinc-400" title="{{ $user->created_at->toIso8601String() }}">
                                    {{ $user->created_at->diffForHumans() }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell align="center" class="pe-6! py-4! text-center">
                                <div class="flex justify-center">
                                    @php
                                        $canRestore = $user->trashed() && auth()->user()?->can('restore', $user);
                                        $canUpdate = ! $user->trashed() && auth()->user()?->can('update', $user);
                                        $isSelf = auth()->id() === $user->id;
                                        $isLastActiveSuperAdmin = $user->is_super_admin && \App\Domain\Auth\Models\User::withoutTrashed()->where('is_super_admin', true)->count() <= 1;
                                        $canDelete = ! $user->trashed() && ! $isSelf && ! $isLastActiveSuperAdmin && auth()->user()?->can('delete', $user);
                                    @endphp

                                    @if ($canRestore || $canUpdate || $canDelete)
                                        <flux:dropdown align="end">
                                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" inset="top bottom" />

                                            <flux:menu class="min-w-36">
                                                @if ($canRestore)
                                                    <flux:menu.item icon="arrow-path" wire:click="restoreUser('{{ $user->id }}')">
                                                        {{ __('Restore User') }}
                                                    </flux:menu.item>
                                                @endif

                                                @if ($canUpdate)
                                                    <flux:menu.item icon="pencil-square" wire:click="openEditModal('{{ $user->id }}')">
                                                        {{ __('Edit Profile') }}
                                                    </flux:menu.item>
                                                @endif

                                                @if ($canDelete)
                                                    @if ($canUpdate)
                                                        <flux:menu.separator />
                                                    @endif
                                                    <flux:menu.item variant="danger" icon="trash" wire:click="confirmDelete('{{ $user->id }}')">
                                                        {{ __('Delete Account') }}
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
                            <flux:table.cell colspan="5" class="text-center py-12 px-6">
                                <div class="flex flex-col items-center justify-center text-center">
                                    <div class="size-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 mb-3">
                                        <flux:icon icon="users" class="size-6" />
                                    </div>
                                    <flux:heading size="md">{{ __('No users found') }}</flux:heading>
                                    <flux:subheading class="max-w-sm mt-1">
                                        {{ __('No user accounts matched your search criteria or filter settings.') }}
                                    </flux:subheading>
                                    @if (filled($search) || filled($roleFilter) || $showDeleted)
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

            @if ($users->hasPages())
                <div class="p-4 border-t border-zinc-200/80 dark:border-zinc-800">
                    {{ $users->links() }}
                </div>
            @endif
        </flux:card>
    </div>

    <!-- Create / Edit User Modal -->
    <flux:modal wire:model.self="showingModal" class="max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $form->id ? __('Edit User Account') : __('Create New User') }}
                </flux:heading>
                <flux:subheading>
                    {{ $form->id ? __('Modify account credentials, personal details, and assigned roles.') : __('Configure account details and grant initial administrative or system roles.') }}
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:input wire:model="form.name" :label="__('Full Name')" :placeholder="__('e.g. Jane Doe')" required />
                    <flux:input wire:model="form.email" :label="__('Email Address')" type="email" :placeholder="__('name@company.com')" required />
                </div>

                <flux:input
                    wire:model="form.password"
                    :label="__('Password')"
                    type="password"
                    :placeholder="$form->id ? __('Leave blank to keep existing password') : __('At least 8 characters')"
                    :description="$form->id ? __('Leave blank to preserve current password.') : __('Minimum 8 characters with mix of cases/numbers recommended.')"
                    :required="!$form->id"
                    viewable
                />

                <!-- Super Admin Privileges Callout -->
                @if (auth()->user()?->is_super_admin)
                    <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-3.5 dark:border-amber-900/50 dark:bg-amber-950/20">
                        <flux:checkbox
                            wire:model="form.is_super_admin"
                            :label="__('Super Administrator Privileges')"
                            :description="__('Grants full bypass of all gate policies across the entire system. Use with discretion.')"
                        />
                    </div>
                @endif

                <!-- Roles Selection -->
                <div>
                    <flux:label class="mb-2 block font-medium">{{ __('Assign Roles') }}</flux:label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 border border-zinc-200 dark:border-zinc-800 rounded-lg p-3 max-h-56 overflow-y-auto bg-zinc-50/30 dark:bg-zinc-900/30">
                        @forelse ($allRoles as $role)
                            <div class="flex items-start gap-2.5 p-2 rounded-md hover:bg-zinc-100/60 dark:hover:bg-zinc-800/60 transition-colors">
                                <flux:checkbox
                                    wire:model="form.role_ids"
                                    value="{{ $role->id }}"
                                    :label="$role->name"
                                    :description="$role->description"
                                />
                            </div>
                        @empty
                            <div class="col-span-2 text-center py-4 text-xs text-zinc-500">
                                {{ __('No roles defined yet. Create roles first to assign them.') }}
                            </div>
                        @endforelse
                    </div>
                    <flux:error name="form.role_ids" />
                </div>

                <!-- Direct permission exceptions -->
                @if ($groupedPermissions->isNotEmpty())
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <flux:label class="font-medium">{{ __('Direct Permissions') }}</flux:label>
                            <span class="text-xs text-zinc-500">
                                {{ count($form->permission_ids) }} {{ __('selected') }}
                            </span>
                        </div>
                        <flux:description class="mb-3 text-xs">
                            {{ __('One-off grants without creating a dedicated role. Use for isolated exceptions.') }}
                        </flux:description>

                        <div class="space-y-3 border border-zinc-200 dark:border-zinc-800 rounded-lg p-3 max-h-56 overflow-y-auto bg-zinc-50/30 dark:bg-zinc-900/30">
                            @foreach ($groupedPermissions as $group => $permissions)
                                <div class="rounded-md border border-zinc-200/60 dark:border-zinc-800/60 p-2.5 bg-white/70 dark:bg-zinc-900/70">
                                    <div class="flex items-center justify-between mb-1.5 pb-1 border-b border-zinc-100 dark:border-zinc-800/60">
                                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-600 dark:text-zinc-300">
                                            {{ ucfirst($group) }}
                                        </span>
                                        <span class="text-xs text-zinc-400">
                                            {{ count(array_intersect($permissions->pluck('id')->all(), $form->permission_ids)) }} / {{ $permissions->count() }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                        @foreach ($permissions as $permission)
                                            <div class="p-1 rounded hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
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
                @endif

                <!-- Department & Sector Assignment -->
                @if ($availableDepartments->isNotEmpty())
                    <div class="pt-2 border-t border-zinc-200/80 dark:border-zinc-800">
                        <div class="flex items-center justify-between mb-2">
                            <flux:label class="font-medium">{{ __('Departments & Sector Roles') }}</flux:label>
                            <span class="text-xs text-zinc-500">
                                {{ count($form->department_ids) }} {{ __('assigned') }}
                            </span>
                        </div>
                        <flux:description class="mb-3 text-xs">
                            {{ __('Select departments this user belongs to, assign sector-specific roles, and set the primary unit.') }}
                        </flux:description>

                        <div class="space-y-3 max-h-60 overflow-y-auto p-3 border border-zinc-200 dark:border-zinc-800 rounded-lg bg-zinc-50/30 dark:bg-zinc-900/30">
                            @foreach ($availableDepartments as $dept)
                                @php $isDeptSelected = in_array($dept->id, $form->department_ids, true); @endphp
                                <div class="p-2.5 rounded-lg border {{ $isDeptSelected ? 'border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800/80 shadow-2xs' : 'border-zinc-200/60 dark:border-zinc-800/40 bg-zinc-50/50 dark:bg-zinc-900/40' }}">
                                    <div class="flex items-center justify-between">
                                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            <input type="checkbox" wire:model.live="form.department_ids" value="{{ $dept->id }}" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900" />
                                            <span>{{ $dept->name }}</span>
                                        </label>

                                        @if ($isDeptSelected)
                                            <label class="flex items-center gap-1.5 text-xs text-zinc-500 cursor-pointer">
                                                <input type="radio" wire:model="form.primary_department_id" value="{{ $dept->id }}" name="primary_dept" class="text-sky-600 focus:ring-sky-500" />
                                                <span>{{ __('Primary') }}</span>
                                            </label>
                                        @endif
                                    </div>

                                    @if ($isDeptSelected)
                                        <div class="mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-700/60 flex items-center gap-2">
                                            <span class="text-xs text-zinc-500 shrink-0">{{ __('Sector Role:') }}</span>
                                            <select wire:model="form.department_roles.{{ $dept->id }}" class="text-xs rounded-md border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 py-1 px-2 w-full">
                                                <option value="">{{ __('Default (Inherit global roles)') }}</option>
                                                @foreach ($allRoles as $r)
                                                    <option value="{{ $r->id }}">{{ $r->name }} (Lvl {{ $r->level }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2.5 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button variant="filled" wire:click="$set('showingModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit">
                    {{ $form->id ? __('Save Changes') : __('Create Account') }}
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
                    <flux:heading size="lg">{{ __('Confirm User Deletion') }}</flux:heading>
                    <flux:subheading class="mt-1 text-sm">
                        {{ __('Are you sure you want to delete user ":name"? This action will perform a soft delete and can be restored at any time.', ['name' => $deletingUserName]) }}
                    </flux:subheading>
                </div>
            </div>

            <div class="flex justify-end gap-2.5 pt-2">
                <flux:button variant="filled" wire:click="$set('showingDeleteModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="deleteUser">
                    {{ __('Delete User') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
