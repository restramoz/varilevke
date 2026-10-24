<nav aria-label="Breadcrumb" class="bg-regulatory-800 border-b border-grid py-3 font-display text-xs text-muted">
    <ol class="max-w-7xl mx-auto flex items-center gap-2 px-4 lg:px-8">
        <li><a href="{{ route('home') }}" class="hover:text-fg">Beranda</a></li>
        @foreach ($breadcrumbs ?? [] as $label => $href)
            <li aria-hidden="true" class="text-grid">/</li>
            <li class="min-w-0">
                @if ($href)
                    <a href="{{ $href }}" class="hover:text-fg">{{ $label }}</a>
                @else
                    <span class="truncate font-semibold text-accent-bright">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
<div class="max-w-7xl mx-auto px-4 lg:px-8">
    {{ $slot }}
</div>