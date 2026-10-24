@php
    $siteName = App\Models\Setting::get('site_name', config('app.name'));
    $navItem = fn (string $active): string => $active
        ? 'bg-grid-subtle text-accent-bright border border-accent'
        : 'text-fg hover:text-fg hover:bg-grid-subtle border border-transparent hover:border-grid';
    $countPelatihan = App\Models\CatalogItem::category(App\Models\CatalogItem::CATEGORY_PELATIHAN)->count();
    $countJasa = App\Models\CatalogItem::category(App\Models\CatalogItem::CATEGORY_JASA)->count();
    $hotline = App\Support\WhatsApp::numbers()[0] ?? '';
    $navLinks = [
        ['href' => route('catalog.index', ['category' => 'pelatihan']), 'label' => 'Pelatihan', 'active' => request()->is('pelatihan')],
        ['href' => route('catalog.index', ['category' => 'jasa']), 'label' => 'Jasa', 'active' => request()->is('jasa')],
        ['href' => route('articles.index'), 'label' => 'Artikel', 'active' => request()->routeIs('articles.index')],
        ['href' => route('regional.cities'), 'label' => 'Wilayah', 'active' => request()->routeIs('regional.cities')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-regulatory-900 text-fg font-body antialiased flex flex-col selection:bg-accent selection:text-white">
    <!-- Top operational bar -->
    <div class="bg-regulatory-800 border-b border-grid px-4 py-2 text-[11px] font-display uppercase tracking-wider text-muted lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2">
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center gap-2 text-accent-bright">
                    <span class="inline-block h-2 w-2 animate-pulse bg-accent-bright" aria-hidden="true"></span>
                    {{ mb_strtoupper($siteName) }}
                </span>
                <span class="hidden text-grid sm:inline" aria-hidden="true">|</span>
                <span class="hidden sm:inline">{{ $countPelatihan }} PELATIHAN &bull; {{ $countJasa }} JASA</span>
            </div>
            @if ($hotline)
                <a href="{{ \App\Support\WhatsApp::link($hotline, 'Halo, saya ingin konsultasi layanan Varilevke.') }}"
                   class="font-semibold text-wa hover:underline">
                    <span class="inline-block h-1.5 w-1.5 bg-wa" aria-hidden="true"></span>
                    WHATSAPP: {{ $hotline }}
                </a>
            @endif
        </div>
    </div>

    <!-- Sticky main navigation -->
    <header class="sticky top-0 z-50 border-b border-grid bg-regulatory-700/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 44" fill="none" class="h-9 w-auto" aria-hidden="true">
                    <rect x="0" y="0" width="36" height="36" fill="#0B1526" stroke="#1E324E" stroke-width="1.5"/>
                    <path d="M18 6 L28 11 V19 C28 24.5 23.4 29.6 18 31 C12.6 29.6 8 24.5 8 19 V11 L18 6 Z" stroke="#10B981" stroke-width="2" fill="none"/>
                    <path d="M14 19 L17 22 L22.5 16" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="27" cy="7" r="3" fill="#F59E0B"/>
                    <text x="48" y="22" font-family="'Space Grotesk',sans-serif" font-size="19" font-weight="700" letter-spacing="-0.5px" fill="#F1F5F9">Varil<tspan fill="#0D7A5F">evke</tspan></text>
                    <text x="48" y="33" font-family="'Space Grotesk',sans-serif" font-size="7.5" font-weight="600" letter-spacing="1.5px" fill="#94A3B8">PELATIHAN K3 &amp; JASA LINGKUNGAN</text>
                </svg>
            </a>

            <nav class="hidden items-center space-x-1 font-display text-xs uppercase tracking-wider md:flex" aria-label="Navigasi utama">
                <a href="{{ route('home') }}" class="{{ $navItem(request()->is('/')) }} px-3.5 py-2 transition-colors">Home</a>
                @foreach ($navLinks as $nav)
                    <a href="{{ $nav['href'] }}" class="{{ $navItem($nav['active']) }} px-3.5 py-2 transition-colors">{{ $nav['label'] }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3">
                @if ($hotline)
                    <a href="{{ \App\Support\WhatsApp::link($hotline, 'Halo, saya ingin konsultasi layanan Varilevke.') }}"
                       class="btn-whatsapp hidden py-2 !px-4 text-xs md:inline-flex">
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5 14.2c-.2.6-1.2 1.2-1.7 1.2-.4.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.5a10.6 10.6 0 0 1-4-3.6c-.3-.5-.9-1.4-.7-2.3.1-.5.5-.9.8-1.1.2-.2.4-.2.5-.2h.5c.2 0 .3 0 .5.4l.7 1.6c.1.2.1.4 0 .5l-.4.6c-.1.2-.3.4-.1.6.5.9 1 1.3 2 1.8.3.2.5.1.7-.1l.6-.7c.2-.2.3-.2.6-.1l1.6.8c.3.1.4.3.3.5 0 0 0 .6-.2 1Z"/></svg>
                        WhatsApp
                    </a>
                @endif

                <button id="mobile-menu-toggle" type="button"
                        class="flex h-9 w-9 flex-col items-center justify-center gap-1.5 bg-regulatory-800 border border-grid hover:border-accent transition-colors md:hidden"
                        aria-expanded="false" aria-controls="mobile-menu" aria-label="Buka menu">
                    <span class="block h-0.5 w-5 bg-fg transition-all duration-300"></span>
                    <span class="block h-0.5 w-5 bg-fg transition-all duration-300"></span>
                    <span class="block h-0.5 w-5 bg-fg transition-all duration-300"></span>
                </button>
            </div>
        </div>

        <!-- Mobile dropdown menu -->
        <nav id="mobile-menu" class="hidden border-t border-grid bg-regulatory-800 md:hidden" aria-label="Navigasi seluler">
            <div class="mx-auto flex max-w-7xl flex-col space-y-1 px-4 py-3 font-display text-xs uppercase tracking-wider">
                <a href="{{ route('home') }}" class="border-l-2 {{ request()->is('/') ? 'border-accent bg-grid-subtle text-accent-bright' : 'border-transparent hover:border-accent hover:bg-grid-subtle' }} flex items-center gap-3 px-4 py-3 transition-colors">
                    <span class="inline-block h-1.5 w-1.5 shrink-0 bg-accent" aria-hidden="true"></span> Home
                </a>
                @foreach ($navLinks as $nav)
                    <a href="{{ $nav['href'] }}" class="border-l-2 {{ $nav['active'] ? 'border-accent bg-grid-subtle text-accent-bright' : 'border-transparent hover:border-accent hover:bg-grid-subtle' }} flex items-center gap-3 px-4 py-3 transition-colors">
                        <span class="inline-block h-1.5 w-1.5 shrink-0 bg-accent" aria-hidden="true"></span> {{ $nav['label'] }}
                    </a>
                @endforeach
                @if ($hotline)
                    <div class="pt-2">
                        <a href="{{ \App\Support\WhatsApp::link($hotline, 'Halo, saya ingin konsultasi layanan Varilevke.') }}" class="btn-whatsapp w-full justify-center py-2.5 text-center text-xs">
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5 14.2c-.2.6-1.2 1.2-1.7 1.2-.4.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.5a10.6 10.6 0 0 1-4-3.6c-.3-.5-.9-1.4-.7-2.3.1-.5.5-.9.8-1.1.2-.2.4-.2.5-.2h.5c.2 0 .3 0 .5.4l.7 1.6c.1.2.1.4 0 .5l-.4.6c-.1.2-.3.4-.1.6.5.9 1 1.3 2 1.8.3.2.5.1.7-.1l.6-.7c.2-.2.3-.2.6-.1l1.6.8c.3.1.4.3.3.5 0 0 0 .6-.2 1Z"/></svg>
                            WhatsApp Hotline
                        </a>
                    </div>
                @endif
            </div>
        </nav>
    </header>

    <main id="content" class="flex-grow pb-10 sm:pb-16">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="border-t border-grid bg-regulatory-800 text-xs text-muted">
        <div class="mx-auto max-w-7xl px-4 py-12 lg:px-8">
            <div class="mb-10 grid grid-cols-1 gap-8 md:grid-cols-4">
                <div class="space-y-3">
                    <div class="label-caps flex items-center gap-2 text-sm uppercase text-fg">
                        <span class="inline-block h-2 w-2 bg-accent" aria-hidden="true"></span>
                        {{ mb_strtoupper($siteName) }}
                    </div>
                    <p class="leading-relaxed">
                        Katalog pelatihan K3 dan jasa lingkungan kerja terkurasi: {{ $countPelatihan + $countJasa }} item dari dokumen sumber resmi, dengan halaman wilayah DSP layanan per kota/kabupaten.
                    </p>
                    <div class="flex flex-wrap gap-2 pt-2">
                        <span class="badge-accent">{{ $countPelatihan }} PELATIHAN</span>
                        <span class="badge-caution">{{ $countJasa }} JASA</span>
                    </div>
                </div>

                <div>
                    <div class="label-caps mb-3 text-xs uppercase text-fg">Pilar Layanan</div>
                    <ul class="space-y-2 font-display text-[12px]">
                        <li><a class="flex items-center justify-between hover:text-accent-bright" href="{{ route('catalog.index', ['category' => 'pelatihan']) }}">Pelatihan K3 <span>({{ $countPelatihan }} program)</span></a></li>
                        <li><a class="flex items-center justify-between hover:text-accent-bright" href="{{ route('catalog.index', ['category' => 'jasa']) }}">Jasa Teknis &amp; Lingkungan <span>({{ $countJasa }} program)</span></a></li>
                        <li><a class="flex items-center justify-between hover:text-accent-bright" href="{{ route('articles.index') }}">Artikel &amp; Panduan</a></li>
                    </ul>
                </div>

                <div>
                    <div class="label-caps mb-3 text-xs uppercase text-fg">Wilayah Layanan</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (App\Models\City::query()->orderBy('name')->take(8)->get(['slug', 'name', 'province', 'classification']) as $city)
                            <a href="{{ route('regional.city', $city) }}"
                               class="border border-grid bg-regulatory-900 px-2 py-1 font-display text-[11px] uppercase text-muted hover:border-accent hover:text-accent-bright">
                                {{ $city->name }}
                            </a>
                        @endforeach
                        <a href="{{ route('regional.cities') }}" class="border border-grid bg-regulatory-900 px-2 py-1 font-display text-[11px] uppercase text-fg hover:border-accent hover:text-accent-bright">Semua wilayah &rarr;</a>
                    </div>
                </div>

                <div>
                    <div class="label-caps mb-3 text-xs uppercase text-fg">Kontak Operasional</div>
                    <p class="mb-2">Respons via WhatsApp pada jam kerja.</p>
                    <ul class="space-y-1">
                        @foreach (App\Support\WhatsApp::callout() as $contact)
                            <li><a class="font-display font-semibold text-wa hover:underline" href="{{ $contact['href'] }}" rel="noopener">WA: {{ $contact['raw'] }}</a></li>
                        @endforeach
                    </ul>
                    <p class="mt-2"><a class="hover:underline" href="{{ route('sitemap') }}">Sitemap</a> &middot; <a class="hover:underline" href="{{ route('robots') }}">robots.txt</a></p>
                </div>
            </div>

            <div class="flex flex-col items-center justify-between gap-3 border-t border-grid-subtle pt-8 font-display text-[11px] text-dim md:flex-row">
                <div>&copy; {{ now()->year }} {{ $siteName }}. Semua hak dilindungi.</div>
                <div class="flex items-center gap-2">
                    <span class="inline-block h-1.5 w-1.5 bg-accent" aria-hidden="true"></span>
                    KATALOG {{ $countPelatihan + $countJasa }} LAYANAN TERKURASI
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
