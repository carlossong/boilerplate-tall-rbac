<div>
    <div class="space-y-6">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')" icon="home" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Administration') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Audit Logs') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-zinc-200/80 dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl" class="font-bold text-zinc-900 dark:text-zinc-50">
                        {{ __('Permission Audit Logs') }}
                    </flux:heading>
                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $stats['total'] }}</flux:badge>
                </div>
                <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Who granted or revoked a role or permission, to whom, and when.') }}
                </flux:subheading>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Events') }}</span>
                    <div class="size-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-600 dark:text-zinc-300">
                        <flux:icon icon="clipboard-document-list" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['total'] }}</span>
                    <span class="text-xs text-zinc-500">{{ __('logged') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Assigned') }}</span>
                    <div class="size-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <flux:icon icon="plus" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['assigned'] }}</span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('grants') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Revoked') }}</span>
                    <div class="size-9 rounded-lg bg-red-50 dark:bg-red-950/40 flex items-center justify-center text-red-600 dark:text-red-400">
                        <flux:icon icon="minus" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['revoked'] }}</span>
                    <span class="text-xs text-red-600 dark:text-red-400">{{ __('removals') }}</span>
                </div>
            </flux:card>

            <flux:card class="!p-6 bg-zinc-50/60 dark:bg-zinc-900/60 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Attributed') }}</span>
                    <div class="size-9 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <flux:icon icon="user" class="size-4" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $stats['with_actor'] }}</span>
                    <span class="text-xs text-indigo-600 dark:text-indigo-400">{{ __('with actor') }}</span>
                </div>
            </flux:card>
        </div>

        <flux:card class="!p-5 bg-zinc-50/40 dark:bg-zinc-900/40 border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
            <div class="flex flex-col md:flex-row gap-3 items-center justify-between">
                <div class="flex flex-1 flex-col sm:flex-row gap-3 w-full">
                    <div class="w-full sm:w-80">
                        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search actor, subject, or grant...')" clearable />
                    </div>

                    <div class="w-full sm:w-44">
                        <flux:select wire:model.live="actionFilter" :placeholder="__('All actions')">
                            <flux:select.option value="">{{ __('Action: All') }}</flux:select.option>
                            <flux:select.option value="assigned">{{ __('Assigned') }}</flux:select.option>
                            <flux:select.option value="revoked">{{ __('Revoked') }}</flux:select.option>
                        </flux:select>
                    </div>

                    <div class="w-full sm:w-44">
                        <flux:select wire:model.live="grantableFilter" :placeholder="__('All types')">
                            <flux:select.option value="">{{ __('Type: All') }}</flux:select.option>
                            <flux:select.option value="role">{{ __('Role') }}</flux:select.option>
                            <flux:select.option value="permission">{{ __('Permission') }}</flux:select.option>
                        </flux:select>
                    </div>
                </div>

                @if (filled($search) || filled($actionFilter) || filled($grantableFilter))
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">
                        {{ __('Reset') }}
                    </flux:button>
                @endif
            </div>
        </flux:card>

        <flux:card class="overflow-hidden p-0 border-zinc-200/80 dark:border-zinc-800 shadow-xs">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="ps-6! py-3.5!">{{ __('When') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Actor') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Action') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Grant') }}</flux:table.column>
                    <flux:table.column class="py-3.5!">{{ __('Subject') }}</flux:table.column>
                    <flux:table.column class="pe-6! py-3.5!">{{ __('Department') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($logs as $log)
                        <flux:table.row :key="$log->id" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition-colors">
                            <flux:table.cell class="ps-6! py-4!">
                                <div class="grid leading-tight">
                                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $log->created_at?->diffForHumans() }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                @if ($log->actor_name)
                                    <div class="grid leading-tight">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $log->actor_name }}</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $log->actor_email }}</span>
                                    </div>
                                @else
                                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ __('System') }}</flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <flux:badge size="sm" :color="$log->action->color()" inset="top bottom">
                                    {{ $log->action->label() }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <div class="grid leading-tight">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $log->grantable_name }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $log->grantableKindLabel() }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="py-4!">
                                <div class="grid leading-tight">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $log->subject_name }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $log->subjectKindLabel() }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="pe-6! py-4!">
                                @if ($log->department_name)
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $log->department_name }}</span>
                                @else
                                    <span class="text-xs text-zinc-400">{{ __('Global') }}</span>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="py-12! text-center">
                                <div class="flex flex-col items-center gap-2 text-zinc-500 dark:text-zinc-400">
                                    <flux:icon icon="clipboard-document-list" class="size-8 text-zinc-300 dark:text-zinc-600" />
                                    <span class="text-sm font-medium">{{ __('No audit events recorded yet.') }}</span>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($logs->hasPages())
                <div class="px-6 py-4 border-t border-zinc-200/80 dark:border-zinc-800">
                    {{ $logs->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</div>
