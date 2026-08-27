<div>
    <div class="space-y-6">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Administration') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.roles.index')" wire:navigate>{{ __('Roles') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Permission Matrix') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-zinc-200/80 dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl" class="font-bold text-zinc-900 dark:text-zinc-50">
                        {{ __('Permission Matrix') }}
                    </flux:heading>
                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $roles->count() }} × {{ $groupedPermissions->flatten()->count() }}</flux:badge>
                </div>
                <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Audit who can do what. Each cell grants or revokes an ability on a role.') }}
                </flux:subheading>
            </div>

            <flux:button variant="filled" icon="arrow-left" :href="route('admin.roles.index')" wire:navigate>
                {{ __('Back to Roles') }}
            </flux:button>
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

        <flux:card class="!p-5 bg-zinc-50/40 dark:bg-zinc-900/40 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
            <div class="flex flex-col md:flex-row gap-3 items-center justify-between">
                <div class="flex flex-1 flex-col sm:flex-row gap-3 w-full">
                    <div class="w-full sm:w-80">
                        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search abilities...')" clearable />
                    </div>
                    <div class="w-full sm:w-60">
                        <flux:select wire:model.live="groupFilter" :placeholder="__('All Groups')">
                            <flux:select.option value="">{{ __('Group: All') }}</flux:select.option>
                            @foreach ($groups as $group)
                                <flux:select.option value="{{ $group }}">{{ ucfirst($group) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end text-xs text-zinc-500">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="size-3.5 rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800"></span>
                        {{ __('Revoked') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="size-3.5 rounded border border-emerald-500 bg-emerald-500"></span>
                        {{ __('Granted') }}
                    </span>
                    @if (filled($search) || filled($groupFilter))
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">
                            {{ __('Reset') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        </flux:card>

        <flux:card class="overflow-hidden p-0 border-zinc-200/80 dark:border-zinc-800 shadow-xs">
            @if ($roles->isEmpty() || $groupedPermissions->isEmpty())
                <div class="flex flex-col items-center justify-center text-center py-16 px-6">
                    <div class="size-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 mb-3">
                        <flux:icon icon="table-cells" class="size-6" />
                    </div>
                    <flux:heading size="md">{{ __('Nothing to map yet') }}</flux:heading>
                    <flux:subheading class="max-w-sm mt-1">
                        {{ __('Create roles and permissions first, then return here to assign abilities.') }}
                    </flux:subheading>
                </div>
            @else
                <div class="overflow-auto max-h-[70vh]">
                    <table class="min-w-full border-collapse text-sm">
                        <thead class="sticky top-0 z-20">
                            <tr class="bg-zinc-50 dark:bg-zinc-900">
                                <th scope="col" class="sticky left-0 z-30 min-w-56 bg-zinc-50 dark:bg-zinc-900 text-left font-semibold text-zinc-600 dark:text-zinc-300 px-4 py-3 border-b border-e border-zinc-200 dark:border-zinc-800">
                                    {{ __('Ability') }}
                                </th>
                                @foreach ($roles as $role)
                                    <th scope="col" class="min-w-28 px-2 py-3 border-b border-zinc-200 dark:border-zinc-800 text-center align-bottom">
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span class="font-semibold text-zinc-800 dark:text-zinc-100 leading-tight">{{ $role->name }}</span>
                                            <div class="flex items-center gap-1">
                                                <x-auth.level-badge :level="$role->level" />
                                                @if ($role->isSystem())
                                                    <flux:badge size="sm" color="purple" inset="top bottom">{{ __('System') }}</flux:badge>
                                                @endif
                                            </div>
                                            <span class="text-[11px] text-zinc-400 font-normal">
                                                {{ $role->permissions_count }} {{ __('granted') }}
                                            </span>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groupedPermissions as $group => $groupPermissions)
                                <tr class="bg-zinc-100/80 dark:bg-zinc-800/80">
                                    <th scope="colgroup" colspan="{{ $roles->count() + 1 }}" class="sticky left-0 z-10 px-4 py-1.5 text-left text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                                        {{ ucfirst($group) }}
                                    </th>
                                </tr>
                                @foreach ($groupPermissions as $permission)
                                    <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40" wire:key="perm-{{ $permission->id }}">
                                        <th scope="row" class="sticky left-0 z-10 bg-white dark:bg-zinc-900 px-4 py-2.5 text-left border-b border-e border-zinc-200/80 dark:border-zinc-800">
                                            <div class="grid leading-tight">
                                                <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $permission->name }}</span>
                                                <span class="font-mono text-[11px] text-zinc-400">{{ $permission->slug }}</span>
                                            </div>
                                        </th>
                                        @foreach ($roles as $role)
                                            @php
                                                $isGranted = (bool) ($granted[$role->id][$permission->id] ?? false);
                                                $isEditable = (bool) ($editable[$role->id] ?? false);
                                            @endphp
                                            <td class="px-2 py-2 text-center border-b border-zinc-200/80 dark:border-zinc-800" wire:key="cell-{{ $role->id }}-{{ $permission->id }}">
                                                <label class="inline-flex items-center justify-center {{ $isEditable ? 'cursor-pointer' : 'cursor-not-allowed' }}">
                                                    <input
                                                        type="checkbox"
                                                        class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-600 dark:border-zinc-600 dark:bg-zinc-800 disabled:opacity-40"
                                                        @checked($isGranted)
                                                        @disabled(! $isEditable)
                                                        @if ($isEditable)
                                                            wire:click="toggle('{{ $role->id }}', '{{ $permission->id }}')"
                                                        @endif
                                                        wire:loading.attr="disabled"
                                                        aria-label="{{ __(':permission on :role', ['permission' => $permission->slug, 'role' => $role->name]) }}"
                                                    />
                                                </label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </flux:card>
    </div>
</div>
