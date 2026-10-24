<nav aria-label="Breadcrumb" class="bg-regulatory-800 border-b border-grid py-3 font-display text-xs text-muted">
    <ol class="max-w-7xl mx-auto flex flex-wrap items-center gap-2 px-4 lg:px-8">
        <li class="shrink-0"><a href="{{ route('home') }}" class="hover:text-fg">Beranda</a></li>
        @foreach ($breadcrumbs ?? [] as $label => $href)
            <li aria-hidden="true" class="text-grid shrink-0">/</li>
            <li class="min-w-0 shrink-1">
                @if ($href)
                    <a href="{{ $href }}" class="hover:text-fg truncate block">{{ $label }}</a>
                @else
                    <span class="truncate block font-semibold text-accent-bright">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
<div class="max-w-7xl mx-auto px-4 lg:px-8">
    {{ $slot }}
</div>