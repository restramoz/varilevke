<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-regulatory-900 text-fg antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Varilevke')</title>

    <!-- Preconnect Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Compiled Assets via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js v3 — UI interaktif (FAQ Accordion, TOC toggle, dll).
         Plugin collapse harus dimuat SEBELUM core Alpine. -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Per-page SEO Meta -->
    @stack('meta_seo')

    <!-- JSON-LD Structured Data -->
    @stack('schema')
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="bg-regulatory-900 text-fg font-body min-h-screen flex flex-col selection:bg-accent selection:text-white">
    <!-- Regulatory Top Operational Bar -->
    <div class="bg-regulatory-800 border-b border-grid text-[11px] font-display tracking-wider uppercase text-muted px-4 lg:px-8 py-2">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center text-accent-bright">
                    <span class="w-2 h-2 bg-accent-bright inline-block mr-2 animate-pulse"></span>
                    SISTEM OPERASIONAL K3 NASIONAL
                </span>
                <span class="hidden sm:inline text-grid">|</span>
                <span class="hidden sm:inline text-muted">{{ $countPelatihan ?? 0 }} PROGRAM &bull; {{ $countJasa ?? 0 }} JASA &bull; {{ $countCities ?? 0 }} WILAYAH</span>
            </div>
            <div class="flex items-center space-x-4">
                @if ($hotline ?? false)
                    <a href="https://wa.me/{{ \App\Support\WhatsApp::normalize($hotline) }}" target="_blank" class="text-wa hover:underline flex items-center gap-1 font-semibold">
                        <span class="w-1.5 h-1.5 bg-wa inline-block"></span>
                        HOTLINE: {{ \App\Support\WhatsApp::display($hotline) }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Navigation Header (Sticky on all pages) -->
    <header class="bg-regulatory-700/95 backdrop-blur border-b border-grid sticky top-0 z-50 shadow-md" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 py-3 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center space-x-3 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 50" fill="none" class="h-9 w-auto">
                    <g transform="translate(4, 5)">
                        <rect x="0" y="2" width="36" height="36" fill="#0B1526" stroke="#1E324E" stroke-width="1.5"/>
                        <path d="M18 8L30 14V22C30 28.5 24.9 34.6 18 36C11.1 34.6 6 28.5 6 22V14L18 8Z" stroke="#10B981" stroke-width="2" stroke-linejoin="round" fill="none"/>
                        <path d="M14 22L17 25L23 18" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="28" cy="8" r="3" fill="#F59E0B"/>
                    </g>
                    <text x="52" y="25" font-family="'Space Grotesk', sans-serif" font-size="20" font-weight="700" letter-spacing="-0.5px" fill="#F1F5F9">Varil<tspan fill="#0D7A5F">evke</tspan></text>
                    <text x="53" y="37" font-family="'Space Grotesk', sans-serif" font-size="8.5" font-weight="600" letter-spacing="1px" fill="#94A3B8">PELATIHAN K3 &amp; JASA LINGKUNGAN</text>
                </svg>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-1 font-display text-xs uppercase tracking-wider">
                <a href="{{ route('home') }}" class="px-3.5 py-2 {{ request()->is('/') ? 'bg-grid-subtle text-accent-bright border border-accent' : 'text-fg hover:bg-grid-subtle border border-transparent hover:border-grid' }} transition-colors">
                    Home
                </a>
                <a href="{{ route('catalog.index', ['category' => 'pelatihan']) }}" class="px-3.5 py-2 {{ request()->is('pelatihan*') ? 'bg-grid-subtle text-accent-bright border border-accent' : 'text-fg hover:bg-grid-subtle border border-transparent hover:border-grid' }} transition-colors">
                    Pelatihan
                </a>
                <a href="{{ route('catalog.index', ['category' => 'jasa']) }}" class="px-3.5 py-2 {{ request()->is('jasa*') ? 'bg-grid-subtle text-accent-bright border border-accent' : 'text-muted hover:text-fg hover:bg-grid-subtle border border-transparent hover:border-grid' }} transition-colors">
                    Jasa
                </a>
                <a href="{{ route('articles.index') }}" class="px-3.5 py-2 {{ request()->routeIs('articles.index') ? 'bg-grid-subtle text-accent-bright border border-accent' : 'text-muted hover:text-fg hover:bg-grid-subtle border border-transparent hover:border-grid' }} transition-colors">
                    Artikel
                </a>
                <a href="{{ route('regional.cities') }}" class="px-3.5 py-2 {{ request()->routeIs('regional.cities') ? 'bg-grid-subtle text-accent-bright border border-accent' : 'text-muted hover:text-fg hover:bg-grid-subtle border border-transparent hover:border-grid' }} transition-colors">
                    Wilayah
                </a>
            </nav>

            <!-- Right: Button WhatsApp & Mobile Toggle -->
            <div class="flex items-center gap-3">
                @if ($hotline ?? false)
                    <a href="https://wa.me/{{ \App\Support\WhatsApp::normalize($hotline) }}?text={{ urlencode('Halo Admin Varilevke, saya ingin konsultasi layanan K3.') }}" target="_blank" class="btn-whatsapp text-xs py-2 px-4 inline-flex items-center gap-2 shadow">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.062-2.115-.527-1.748-.724-2.883-2.493-2.97-2.607-.088-.114-.709-.942-.709-1.796 0-.853.447-1.274.607-1.446.16-.172.352-.215.469-.215.118 0 .235.001.338.006.109.005.255-.041.399.304.149.356.508 1.239.553 1.329.045.09.076.196.015.315-.06.12-.091.196-.18.301-.091.106-.19.237-.272.318-.09.09-.184.188-.079.369.105.18.468.772 1.004 1.249.69.614 1.272.805 1.452.895.18.09.286.076.392-.045.106-.12.454-.528.575-.708.121-.18.243-.15.406-.09.164.06 1.034.488 1.212.577.177.09.296.135.34.21.045.075.045.436-.099.841z"/></svg>
                        WhatsApp
                    </a>
                @endif

                <!-- Hamburger Button (mobile) -->
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden flex flex-col justify-center items-center w-9 h-9 bg-regulatory-800 border border-grid hover:border-accent transition-colors gap-1.5"
                    :aria-expanded="mobileMenuOpen" aria-label="Buka menu">
                    <span class="w-5 h-0.5 bg-fg block transition-all duration-300" :class="mobileMenuOpen ? 'rotate-45 translate-y-2' : ''"></span>
                    <span class="w-5 h-0.5 bg-fg block transition-all duration-300" :class="mobileMenuOpen ? 'opacity-0 scale-x-0' : ''"></span>
                    <span class="w-5 h-0.5 bg-fg block transition-all duration-300" :class="mobileMenuOpen ? '-rotate-45 -translate-y-2' : ''"></span>
                </button>
            </div>
        </div>

        <!-- Mobile Dropdown Menu -->
        <div x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="md:hidden border-t border-grid bg-regulatory-800">
            <nav class="max-w-7xl mx-auto px-4 py-3 flex flex-col space-y-1 font-display text-xs uppercase tracking-wider">
                <a href="{{ route('home') }}" @click="mobileMenuOpen=false"
                   class="px-4 py-3 flex items-center gap-3 {{ request()->is('/') ? 'text-accent-bright border-l-2 border-accent bg-grid-subtle' : 'text-fg border-l-2 border-transparent hover:border-accent hover:bg-grid-subtle' }} transition-colors">
                    <span class="w-1.5 h-1.5 bg-accent inline-block shrink-0"></span>
                    Home
                </a>
                <a href="{{ route('catalog.index', ['category' => 'pelatihan']) }}" @click="mobileMenuOpen=false"
                   class="px-4 py-3 flex items-center gap-3 {{ request()->is('pelatihan*') ? 'text-accent-bright border-l-2 border-accent bg-grid-subtle' : 'text-fg border-l-2 border-transparent hover:border-accent hover:bg-grid-subtle' }} transition-colors">
                    <span class="w-1.5 h-1.5 bg-accent inline-block shrink-0"></span>
                    Pelatihan
                </a>
                <a href="{{ route('catalog.index', ['category' => 'jasa']) }}" @click="mobileMenuOpen=false"
                   class="px-4 py-3 flex items-center gap-3 {{ request()->is('jasa*') ? 'text-accent-bright border-l-2 border-accent bg-grid-subtle' : 'text-muted border-l-2 border-transparent hover:border-accent hover:bg-grid-subtle' }} transition-colors">
                    <span class="w-1.5 h-1.5 bg-accent inline-block shrink-0"></span>
                    Jasa
                </a>
                <a href="{{ route('articles.index') }}" @click="mobileMenuOpen=false"
                   class="px-4 py-3 flex items-center gap-3 {{ request()->routeIs('articles.index') ? 'text-accent-bright border-l-2 border-accent bg-grid-subtle' : 'text-muted border-l-2 border-transparent hover:border-accent hover:bg-grid-subtle' }} transition-colors">
                    <span class="w-1.5 h-1.5 bg-accent inline-block shrink-0"></span>
                    Artikel
                </a>
                <a href="{{ route('regional.cities') }}" @click="mobileMenuOpen=false"
                   class="px-4 py-3 flex items-center gap-3 {{ request()->routeIs('regional.cities') ? 'text-accent-bright border-l-2 border-accent bg-grid-subtle' : 'text-muted border-l-2 border-transparent hover:border-accent hover:bg-grid-subtle' }} transition-colors">
                    <span class="w-1.5 h-1.5 bg-accent inline-block shrink-0"></span>
                    Wilayah ({{ $countCities ?? 0 }} Kota/Kab)
                </a>
                @if ($hotline ?? false)
                    <div class="pt-2">
                        <a href="https://wa.me/{{ \App\Support\WhatsApp::normalize($hotline) }}" target="_blank" class="btn-whatsapp w-full py-2.5 text-center text-xs flex items-center justify-center gap-2">
                            WhatsApp Hotline
                        </a>
                    </div>
                @endif
            </nav>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="flex-grow pb-10 sm:pb-20">
        @yield('content')
    </main>

    <!-- Footer: Industrial K3 Command & Compliance -->
    <footer class="border-t border-grid bg-regulatory-800 text-xs text-muted font-body">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
                <!-- Col 1 -->
                <div class="space-y-3">
                    <div class="font-display font-bold text-sm uppercase text-fg tracking-wider flex items-center gap-2">
                        <span class="w-2 h-2 bg-accent inline-block"></span>
                        VARILEVKE.MY.ID
                    </div>
                    <p class="text-muted leading-relaxed">
                        Pusat layanan pelatihan keselamatan kerja dan jasa lingkungan kerja terkurasi dari dokumen sumber resmi, melayani {{ ($countPelatihan ?? 0) + ($countJasa ?? 0) }} program di {{ $countCities ?? 0 }} kota/kabupaten.
                    </p>
                    <div class="flex items-center gap-2 pt-2">
                        <span class="badge-accent">{{ $countPelatihan ?? 0 }} PELATIHAN</span>
                        <span class="badge-caution">{{ $countJasa ?? 0 }} JASA</span>
                    </div>
                </div>

                <!-- Col 2 -->
                <div>
                    <div class="font-display font-bold text-xs uppercase text-fg tracking-wider mb-3">Pilar Layanan Utama</div>
                    <ul class="space-y-2 font-display text-[12px]">
                        <li><a href="{{ route('catalog.index', ['category' => 'pelatihan']) }}" class="hover:text-accent-bright transition-colors flex items-center justify-between">Pelatihan K3 <span>({{ $countPelatihan ?? 0 }} program)</span></a></li>
                        <li><a href="{{ route('catalog.index', ['category' => 'jasa']) }}" class="hover:text-accent-bright transition-colors flex items-center justify-between">Jasa Teknis &amp; Lingkungan <span>({{ $countJasa ?? 0 }} program)</span></a></li>
                        <li><a href="{{ route('articles.index') }}" class="hover:text-accent-bright transition-colors flex items-center justify-between">Artikel &amp; Panduan</a></li>
                    </ul>
                </div>

                <!-- Col 3 -->
                <div>
                    <div class="font-display font-bold text-xs uppercase text-fg tracking-wider mb-3">Wilayah Layanan</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (App\Models\City::query()->orderBy('name')->take(8)->get(['slug', 'name', 'province', 'classification']) as $city)
                            <a href="{{ route('regional.city', $city) }}"
                               class="px-2 py-1 bg-regulatory-900 border border-grid text-muted hover:text-accent-bright hover:border-accent text-[11px] font-display uppercase">
                                {{ $city->name }}
                            </a>
                        @endforeach
                        <a href="{{ route('regional.cities') }}" class="px-2 py-1 bg-regulatory-900 border border-grid text-fg hover:text-accent-bright hover:border-accent text-[11px] font-display uppercase">Semua wilayah →</a>
                    </div>
                </div>

                <!-- Col 4 -->
                <div>
                    <div class="font-display font-bold text-xs uppercase text-fg tracking-wider mb-3">Kontak Operasional</div>
                    <p class="mb-2">Respons via WhatsApp pada jam kerja.</p>
                    @foreach (App\Support\WhatsApp::callout() as $contact)
                        <p class="text-wa font-display font-semibold mb-1"><a href="{{ $contact['href'] }}" rel="noopener" class="hover:underline">WA: {{ $contact['raw'] }}</a></p>
                    @endforeach
                    <p class="text-dim text-[11px] mt-2">Senin - Sabtu: 08.00 - 17.00 WIB</p>
                </div>
            </div>

            <!-- Bottom Copyright & Compliance Notes -->
            <div class="pt-8 border-t border-grid-subtle flex flex-col md:flex-row items-center justify-between gap-4 text-[11px] text-dim font-display">
                <div>
                    &copy; {{ date('Y') }} varilevke.my.id
                </div>
                <div class="flex items-center space-x-6">
                    <span>KATALOG {{ ($countPelatihan ?? 0) + ($countJasa ?? 0) }} LAYANAN TERKURASI</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- Floating Action Button (Emerald) --}}
    <a href="https://wa.me/{{ \App\Support\WhatsApp::normalize('08118500177') }}?text={{ rawurlencode('Halo, saya ingin bertanya tentang layanan Anda.') }}"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="Hubungi kami via WhatsApp"
       title="Chat WhatsApp"
       class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500 text-white shadow-xl hover:bg-emerald-600 hover:scale-110 transition-all duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="currentColor" class="h-7 w-7">
            <path d="M16 0C7.163 0 0 7.163 0 16c0 2.827.737 5.484 2.027 7.797L.057 31.492a.5.5 0 0 0 .614.612l7.865-2.063A15.938 15.938 0 0 0 16 32c8.837 0 16-7.163 16-16S24.837 0 16 0zm0 29.333a13.27 13.27 0 0 1-6.773-1.853l-.486-.288-5.013 1.315 1.337-4.88-.317-.504A13.245 13.245 0 0 1 2.667 16C2.667 8.636 8.636 2.667 16 2.667S29.333 8.636 29.333 16 23.364 29.333 16 29.333zm7.307-9.907c-.4-.2-2.365-1.167-2.731-1.3-.367-.133-.633-.2-.9.2-.267.4-1.033 1.3-1.267 1.567-.233.267-.467.3-.867.1-.4-.2-1.689-.623-3.217-1.987-1.189-1.063-1.99-2.376-2.224-2.776-.233-.4-.025-.616.175-.816.18-.18.4-.467.6-.7.2-.233.267-.4.4-.667.133-.267.067-.5-.033-.7-.1-.2-.9-2.167-1.233-2.967-.325-.78-.655-.674-.9-.686-.233-.011-.5-.013-.767-.013-.267 0-.7.1-1.067.5-.367.4-1.4 1.367-1.4 3.333 0 1.967 1.433 3.867 1.633 4.133.2.267 2.82 4.307 6.833 6.033.955.413 1.7.66 2.28.844.958.305 1.831.262 2.52.159.769-.114 2.365-.967 2.699-1.9.333-.933.333-1.733.233-1.9-.1-.167-.367-.267-.767-.467z"/>
        </svg>
    </a>
</body>
</html>