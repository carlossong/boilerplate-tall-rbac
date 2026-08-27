<div>
    <div class="space-y-6">
        <!-- Breadcrumbs -->
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Administration') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Departments') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-zinc-200/80 dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl" class="font-bold text-zinc-900 dark:text-zinc-50">
                        {{ __('Departments & Sectors') }}
                    </flux:heading>
                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $stats['total'] }}</flux:badge>
                </div>
                <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Manage organizational units, departments, branches, and contextual roles per sector.') }}
                </flux:subheading>
            </div>

            @can('create', App\Domain\Auth\Models\Department::class)
                <div class="flex items-center gap-2">
                    <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                        {{ __('New Department') }}
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
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Total Departments') }}</span>
                    <div class="size-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-600 dark:text-zinc-300">
                        <flux:icon icon="building-office-2" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['total'] }}</span>
                    <span class="text-xs text-zinc-500">{{ __('units') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Active Units') }}</span>
                    <div class="size-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <flux:icon icon="check" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['active'] }}</span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('operational') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Archived') }}</span>
                    <div class="size-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500">
                        <flux:icon icon="trash" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['archived'] }}</span>
                    <span class="text-xs text-zinc-500">{{ __('soft-deleted') }}</span>
                </div>
            </flux:card>
        </div>

        <!-- Search & Filter Toolbar -->
        <flux:card class="!p-5 bg-zinc-50/40 dark:bg-zinc-900/40 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
            <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="w-full sm:w-80">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search department name or slug...')" clearable />
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
                    <flux:table.column class="ps-6! py-3.5! w-1/3">{{ __('Department') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Slug') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Allowed Roles') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Members') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="center" class="pe-6! py-3.5! w-28 text-center">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($departments as $department)
                        <flux:table.row :key="$department->id" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition-colors">
                            <flux:table.cell class="ps-6! py-4!">
                                <div class="grid leading-tight">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $department->name }}</span>
                                    </div>
                                    @if ($department->description)
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $department->description }}</span>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <flux:badge size="sm" color="zinc" class="font-mono">{{ $department->slug }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse ($department->roles as $role)
                                        <flux:badge size="sm" color="sky" inset="top bottom">
                                            {{ $role->name }} <span class="text-xs opacity-75">({{ $role->level }})</span>
                                        </flux:badge>
                                    @empty
                                        <span class="text-xs text-zinc-400">{{ __('All roles allowed') }}</span>
                                    @endforelse
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <div class="flex items-center gap-1.5">
                                    <flux:icon icon="users" class="size-3.5 text-zinc-400" />
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $department->users_count ?? 0 }}
                                    </span>
                                    <span class="text-xs text-zinc-400">{{ __('members') }}</span>
                                </div>
                            </flux:table.cell>


                            <flux:table.cell class="py-4!">
                                @if ($department->trashed())
                                    <flux:badge size="sm" color="red" inset="top bottom">
                                        <span class="inline-block size-1.5 rounded-full bg-red-500 mr-1.5"></span>{{ __('Deleted') }}
                                    </flux:badge>
                                @elseif ($department->is_active)
                                    <flux:badge size="sm" color="emerald" inset="top bottom">
                                        <span class="inline-block size-1.5 rounded-full bg-emerald-500 mr-1.5"></span>{{ __('Active') }}
                                    </flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc" inset="top bottom">
                                        <span class="inline-block size-1.5 rounded-full bg-zinc-400 mr-1.5"></span>{{ __('Inactive') }}
                                    </flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell align="center" class="pe-6! py-4!">
                                <div class="flex items-center justify-center gap-1">
                                    @if ($department->trashed())
                                        @can('restore', $department)
                                            <flux:button size="sm" variant="ghost" icon="arrow-path" class="text-zinc-600 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-400" wire:click="restoreDepartment('{{ $department->id }}')" title="{{ __('Restore') }}" />
                                        @endcan
                                    @else
                                        @can('update', $department)
                                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditModal('{{ $department->id }}')" title="{{ __('Edit') }}" />
                                        @endcan

                                        @can('delete', $department)
                                            <flux:button size="sm" variant="ghost" icon="trash" class="text-zinc-400 hover:text-red-600 dark:hover:text-red-400" wire:click="confirmDelete('{{ $department->id }}')" title="{{ __('Delete') }}" />
                                        @endcan
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="size-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400">
                                        <flux:icon icon="building-office-2" class="size-6" />
                                    </div>
                                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('No departments found') }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400 max-w-sm">
                                        {{ filled($search) ? __('No departments match your search criteria.') : __('Get started by creating your first department or branch.') }}
                                    </span>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($departments->hasPages())
                <div class="p-4 border-t border-zinc-200/80 dark:border-zinc-800">
                    {{ $departments->links() }}
                </div>
            @endif
        </flux:card>
    </div>

    <!-- Create / Edit Modal -->
    <flux:modal wire:model.self="showingModal" class="md:w-xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $form->id ? __('Edit Department') : __('Create New Department') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Define sector parameters, identity, and associated organizational roles.') }}
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Department Name') }}</flux:label>
                    <flux:input wire:model="form.name" :placeholder="__('e.g. Financial Department, Operations')" />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Slug (Identifier)') }}</flux:label>
                    <flux:input wire:model="form.slug" :placeholder="__('e.g. financial, operations')" />
                    <flux:description>{{ __('Unique system slug used for programmatic access and checks.') }}</flux:description>
                    <flux:error name="form.slug" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="form.description" rows="2" :placeholder="__('Briefly describe this department\'s responsibilities...')" />
                    <flux:error name="form.description" />
                </flux:field>

                <flux:checkbox wire:model="form.is_active" :label="__('Department is active and operational')" />

                <flux:field>
                    <flux:label>{{ __('Associated Roles for this Sector') }}</flux:label>
                    <flux:description>{{ __('Select roles typically available or assigned within this department.') }}</flux:description>
                    
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto p-2 border border-zinc-200 dark:border-zinc-800 rounded-lg">
                        @foreach ($availableRoles as $role)
                            <label class="flex items-center gap-2 p-2 rounded hover:bg-zinc-50 dark:hover:bg-zinc-800/60 cursor-pointer text-sm">
                                <input type="checkbox" wire:model="form.role_ids" value="{{ $role->id }}" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900" />
                                <div class="flex items-center justify-between w-full">
                                    <span class="text-zinc-800 dark:text-zinc-200">{{ $role->name }}</span>
                                    <span class="text-xs font-mono text-zinc-400">Lvl {{ $role->level }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="form.role_ids" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200/80 dark:border-zinc-800">
                <flux:button variant="ghost" wire:click="$set('showingModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit">
                    {{ $form->id ? __('Save Changes') : __('Create Department') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.self="showingDeleteModal" class="md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-600 dark:text-red-400 flex items-center gap-2">
                    <flux:icon icon="exclamation-triangle" class="size-5" />
                    {{ __('Delete Department') }}
                </flux:heading>
                <flux:subheading class="mt-2">
                    {{ __('Are you sure you want to delete the department') }} <strong class="text-zinc-900 dark:text-zinc-100">{{ $deletingDepartmentName }}</strong>?
                    {{ __('Users will no longer be mapped to this department until restored.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showingDeleteModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="deleteDepartment">
                    {{ __('Delete Department') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
