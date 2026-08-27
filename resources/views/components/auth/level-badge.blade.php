@props([
    'level' => 0,
])

<flux:badge
    size="sm"
    :color="\App\Domain\Auth\Models\Role::badgeColorForLevel((int) $level)"
    inset="top bottom"
    {{ $attributes }}
>
    {{ __('Lvl :level', ['level' => (int) $level]) }}
</flux:badge>
