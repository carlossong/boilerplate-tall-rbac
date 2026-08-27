@props([
    'variant' => 'outline',
    'size' => null,
])

@php
$classes = Flux::classes()
    ->add('border')
    ->add(match ($variant) {
        'soft' => 'bg-black/[0.02] border-black/[0.025] dark:bg-white/[0.06] dark:border-white/[0.065]',
        default => 'bg-white border-zinc-200/80 dark:bg-zinc-900/70 dark:border-zinc-800',
    })
    ->add(match ($size) {
        default => 'p-6 rounded-xl',
        'sm' => 'p-4 rounded-lg',
    })
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-card>
    {{ $slot }}
</div>
