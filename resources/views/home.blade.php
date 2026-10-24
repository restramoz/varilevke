@extends('layouts.app')

@section('title', 'Varilevke - Pelatihan K3 & Jasa Lingkungan Kerja Terkurasi')

@push('meta_seo')
<link rel="canonical" href="{{ url('/') }}">
<meta property="og:title" content="Varilevke - Pelatihan K3 &amp; Jasa Lingkungan Kerja Terkurasi">
<meta property="og:url" content="{{ url('/') }}">
@endpush

@section('content')
<!-- Hero Section: Industrial Command & Compliance Center -->
<section class="border-b border-grid bg-gradient-to-b from-regulatory-700 to-regulatory-900 py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">

            <div class="lg:col-span-7 space-y-6">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge-accent">{{ $countPelatihan }} PROGRAM PELATIHAN</span>
                    <span class="badge-caution">{{ $countJasa }} PROGRAM JASA</span>
                    <span class="badge-sky">{{ $countCities }} WILAYAH LAYANAN</span>
                </div>

                <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl font-bold text-fg leading-tight tracking-tight">
                    Pusat Katalog Pelatihan K3 &amp; Jasa Lingkungan Kerja Nasional
                </h1>

                <p class="text-base sm:text-lg text-muted leading-relaxed max-w-2xl font-body">
                    Platform terpadu katalog pelatihan keselamatan kerja, kajian kelayakan teknis, dan jasa perizinan SLF/AMDAL untuk perusahaan di <strong class="text-accent-bright">{{ $countCities }} Kota/Kabupaten</strong> di seluruh Indonesia.
                </p>

                <!-- Quick Action Buttons -->
                <div class="pt-2 flex flex-wrap items-center gap-4">
                    <a href="{{ route('catalog.index', ['category' => 'pelatihan']) }}" class="btn-primary">
                        Jelajahi {{ $countPelatihan }} Program Pelatihan
                    </a>
                    <a href="{{ route('catalog.index', ['category' => 'jasa']) }}" class="btn-secondary">
                        Lihat {{ $countJasa }} Program Jasa
                    </a>
                    <a href="https://wa.me/{{ \App\Support\WhatsApp::normalize($hotline) }}?text={{ urlencode('Halo Admin Varilevke, saya ingin konsultasi layanan K3.') }}" target="_blank" class="btn-whatsapp">
                        Hubungi Hotline WhatsApp
                    </a>
                </div>

                <div class="pt-4 flex flex-wrap items-center gap-6 text-xs text-dim font-display uppercase tracking-wider">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-accent inline-block"></span>
                        {{ $countPelatihan }} Pelatihan K3
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-accent inline-block"></span>
                        {{ $countJasa }} Jasa Teknis
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-accent inline-block"></span>
                        {{ $countCities }} Wilayah Layanan
                    </div>
                </div>
            </div>

            <!-- Quick Inquiry Card -->
            <div class="lg:col-span-5">
                <div class="panel-k3 p-6 space-y-5">
                    <div class="border-b border-grid pb-3 flex items-center justify-between">
                        <div class="font-display font-bold text-xs uppercase tracking-wider text-fg flex items-center gap-2">
                            <span class="w-2.5 h-2.5 bg-accent-bright inline-block"></span>
                            KONSULTASI CEPAT VIA WHATSAPP
                        </div>
                        <span class="text-[11px] font-display text-accent-bright uppercase font-semibold">RESPON &lt; 5 MENIT</span>
                    </div>

                    <form action="#" method="POST" onsubmit="event.preventDefault(); window.open('https://wa.me/{{ \App\Support\WhatsApp::normalize($hotline) }}?text=' + encodeURIComponent('Halo Admin Varilevke, saya ingin konsultasi program K3 di kota saya.'), '_blank');" class="space-y-4">
                        <div>
                            <label class="block text-xs font-display uppercase text-muted mb-1 tracking-wider">Pilih Kategori Kebutuhan</label>
                            <select id="quick-category" class="input-k3 w-full" onchange="window.location.href=this.value">
                                <option value="{{ route('catalog.index', ['category' => 'pelatihan']) }}">Pelatihan K3 ({{ $countPelatihan }} Program)</option>
                                <option value="{{ route('catalog.index', ['category' => 'jasa']) }}">Jasa Teknis &amp; Lingkungan ({{ $countJasa }} Program)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-display uppercase text-muted mb-1 tracking-wider">Pilih Wilayah Kota/Kabupaten</label>
                            <select id="quick-city" class="input-k3 w-full" onchange="if(this.value) window.location.href=this.value">
                                <option value="">-- Cari atau Pilih Wilayah --</option>
                                @foreach ($cityOptions as $option)
                                    <option value="{{ $option['url'] }}">{{ $option['label'] }}</option>
                                @endforeach
                                <option value="{{ route('regional.cities') }}">Lihat Semua Wilayah →</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-display uppercase text-muted mb-1 tracking-wider">Nama &amp; Perusahaan</label>
                            <input type="text" placeholder="PT Industri Sejahtera / Bapak Abu" class="input-k3 w-full">
                        </div>

                        <button type="submit" class="btn-primary w-full py-3 text-xs tracking-wider">
                            Hubungkan ke Tim Teknis via WhatsApp
                        </button>
                    </form>
                    <p class="text-[11px] text-dim">Formulir ini hanya menyiapkan teks pesan WhatsApp — pengiriman tetap terjadi di aplikasi WhatsApp Anda dan tidak ada data yang tersimpan di situs ini.</p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Section: 2 Pilar Layanan Utama -->
<section id="tiga-pilar" class="py-16 lg:py-24 border-b border-grid">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="mb-12">
            <div class="label-caps text-accent-bright mb-2 flex items-center gap-2">
                <span class="w-2 h-2 bg-accent inline-block"></span>
                STRUKTUR KATALOG
            </div>
            <h2 class="font-display text-2xl lg:text-3xl font-bold text-fg">
                2 Pilar Layanan Varilevke
            </h2>
            <p class="text-sm text-muted mt-2 max-w-2xl">
                Katalog terbagi dua kategori: program pelatihan kompetensi K3, serta jasa teknik, lingkungan, dan administrasi — dengan urutan yang tetap mengikuti dokumen sumber.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            @foreach ($pillars as $pillar)
                <div class="card-k3 flex flex-col justify-between border-t-4 border-t-accent p-6 hover:border-accent transition-all">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="{{ $pillar['badge'] }}">{{ $pillar['count'] }} PROGRAM</span>
                            <span class="label-caps text-accent-bright">TERKURASI</span>
                        </div>
                        <h3 class="font-display text-xl font-bold text-fg">{{ $pillar['title'] }}</h3>
                        <p class="text-xs text-muted leading-relaxed">{{ $pillar['desc'] }}</p>
                        <div class="border-t border-grid-subtle pt-4 space-y-2">
                            @foreach ($pillar['items'] as $item)
                                <a href="{{ route('catalog.show', ['category' => $pillar['category'], 'item' => $item]) }}" class="block text-xs text-muted hover:text-accent-bright flex items-center justify-between gap-3">
                                    <span class="truncate">&bull; {{ $item->name }}</span>
                                    <span class="shrink-0 font-mono text-[10px] text-dim">{{ str_pad((string) $item->source_order, 3, '0', STR_PAD_LEFT) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="pt-6 mt-6 border-t border-grid">
                        <a href="{{ route('catalog.index', ['category' => $pillar['category']]) }}" class="btn-primary w-full text-xs py-2.5 text-center">
                            Lihat Semua {{ $pillar['count'] }} Program →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Section: Interactive Widget - All Regions -->
<section id="widget-wilayah" class="py-16 lg:py-24 bg-regulatory-800/50 border-b border-grid">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <div class="label-caps text-accent-bright mb-2 flex items-center gap-2">
                    <span class="w-2 h-2 bg-accent inline-block"></span>
                    JARINGAN WILAYAH NASIONAL
                </div>
                <h2 class="font-display text-2xl lg:text-3xl font-bold text-fg">
                    Widget {{ $countCities }} Kota &amp; Kabupaten
                </h2>
                <p class="text-sm text-muted mt-1">
                    Cari wilayah Anda untuk membuka halaman layanan pelatihan dan jasa lokal.
                </p>
            </div>

            <!-- Instant Live Search Bar -->
            <div class="w-full md:w-96">
                <label for="city-search-input" class="sr-only">Cari Wilayah</label>
                <div class="relative">
                    <input
                        type="text"
                        id="city-search-input"
                        placeholder="Ketik nama kota/kabupaten (misal: Malang, Cikarang, Balikpapan)..."
                        class="input-k3 w-full pl-10 pr-4 text-xs font-display"
                        oninput="filterCities(this.value)"
                    >
                    <div class="absolute left-3 top-3 text-dim">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cities Grid Grouped by Province -->
        <div id="cities-container" class="space-y-8">
            @php
                $citiesGrouped = App\Models\City::query()
                    ->orderBy('province')
                    ->orderBy('name')
                    ->get()
                    ->groupBy('province');
            @endphp
            @foreach($citiesGrouped as $province => $cities)
                <div class="province-group bg-regulatory-900 border border-grid p-6">
                    <div class="border-b border-grid pb-3 mb-4 flex items-center justify-between">
                        <h3 class="font-display font-bold text-sm uppercase text-accent-bright tracking-wider flex items-center gap-2 cursor-pointer" id="province-header-{{ $loop->index }}" onclick="toggleProvince({{ $loop->index }})">
                            <span class="w-2 h-2 bg-accent inline-block"></span>
                            {{ $province }} ({{ $cities->count() }} Wilayah) <svg class="ml-2 w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </h3>
                    </div>

                    <div id="cities-{{ $loop->index }}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2" style="display:none;">
                        @foreach($cities as $city)
                            <a
                                href="{{ route('regional.city', $city) }}"
                                class="city-item block px-3 py-2 bg-regulatory-800 hover:bg-regulatory-700 border border-grid-subtle hover:border-accent text-xs text-muted hover:text-white transition-colors font-body truncate"
                                data-name="{{ strtolower($city->name) }}"
                            >
                                {{ $city->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Empty search state -->
        <div id="no-city-found" class="hidden p-8 panel-k3 text-center text-muted font-display text-sm">
            Tidak ditemukan wilayah dengan kata kunci tersebut. Silakan hubungi admin kami untuk permintaan layanan di wilayah Anda.
        </div>
    </div>
</section>

<!-- Client-side City Filter Script -->
<script>
function filterCities(query) {
    query = query.toLowerCase().trim();
    const cityItems = document.querySelectorAll('.city-item');
    const provinceGroups = document.querySelectorAll('.province-group');
    let totalVisible = 0;

    cityItems.forEach(item => {
        const name = item.getAttribute('data-name');
        if (!query || name.includes(query)) {
            item.style.display = 'block';
            totalVisible++;
        } else {
            item.style.display = 'none';
        }
    });

    provinceGroups.forEach(group => {
        const visibleInGroup = group.querySelectorAll('.city-item:not([style*="display: none"])').length;
        group.style.display = visibleInGroup > 0 ? 'block' : 'none';
    });

    document.getElementById('no-city-found').classList.toggle('hidden', totalVisible > 0);
}

function toggleProvince(index) {
    var el = document.getElementById('cities-' + index);
    if (el) {
        el.style.display = el.style.display === 'none' ? 'grid' : 'none';
    }
    var header = document.getElementById('province-header-' + index);
    if (header) {
        var icon = header.querySelector('svg');
        if (icon) {
            icon.classList.toggle('rotate-180');
        }
    }
}
</script>

<!-- Advantage Section -->
<section id="advantage" class="py-16 bg-regulatory-800/20 border-b border-grid">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <h2 class="label-caps text-accent-bright mb-2">KEUNGGULAN VARILEVKE</h2>
        <h3 class="font-display text-2xl font-bold text-fg mb-4">Kenapa Pilih Varilevke?</h3>
        <ul class="list-disc pl-5 space-y-2 text-muted">
            <li>Katalog terkurasi dari dokumen sumber resmi (272 item: 227 pelatihan + 45 jasa)</li>
            <li>Jaringan wilayah meliputi {{ $countCities }} kota/kabupaten seluruh Indonesia</li>
            <li>Layanan lengkap: Pelatihan K3, Jasa Teknis &amp; Lingkungan, Artikel Panduan</li>
            <li>Solusi end-to-end dari pencarian katalog hingga konsultasi via WhatsApp</li>
            <li>Platform modern, responsif, dan terstruktur</li>
        </ul>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-16 bg-regulatory-800/20 border-b border-grid">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <h2 class="label-caps text-accent-bright mb-2">TENTANG VARILEVKE</h2>
        <h3 class="font-display text-2xl font-bold text-fg mb-4">Tentang Kami</h3>
        <p class="text-muted max-w-2xl">
            Varilevke adalah platform terintegrasi untuk katalog pelatihan keselamatan kerja, jasa teknis lingkungan, dan layanan administrasi perusahaan di seluruh Indonesia. Kami menghubungkan kebutuhan industri dengan informasi layanan terkurasi, memastikan akses cepat ke katalog resmi dan kanal konsultasi langsung.
        </p>
    </div>
</section>

<!-- Home Articles Section -->
@if ($latestArticles->isNotEmpty())
<section id="home-articles" class="py-16 bg-regulatory-800/20 border-b border-grid">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <h2 class="label-caps text-accent-bright mb-2">ARTIKEL</h2>
        <h3 class="font-display text-2xl font-bold text-fg mb-4">Artikel Terbaru</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($latestArticles as $article)
            <div class="card-k3 p-4 flex flex-col h-full">
                <h4 class="font-display text-lg font-bold text-fg hover:text-accent-bright transition-colors">
                    <a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a>
                </h4>
                <p class="text-muted text-sm flex-grow mt-2">{{ $article->metaDescription() }}</p>
                <a href="{{ route('articles.show', $article) }}" class="mt-3 btn-primary text-xs py-1.5 text-center">Baca Selengkapnya →</a>
            </div>
            @endforeach
        </div>
        <div class="mt-6">
            <a href="{{ route('articles.index') }}" class="btn-secondary text-xs py-2 px-4">Semua Artikel →</a>
        </div>
    </div>
</section>
@endif

<div class="mx-auto max-w-7xl px-4 pb-16 pt-12 lg:px-8">
    <x-whatsapp-contacts :callout="$callout" />
</div>

@php
    $quickWa = \App\Support\WhatsApp::numbers()[0] ?? '';
@endphp
@if ($quickWa !== '')
<script>
    (function () {
        var waBase = 'https://wa.me/' + '{{ \App\Support\WhatsApp::normalize($quickWa) }}';
        var submit = document.getElementById('quick-wa-submit');
        var jumps = document.querySelectorAll('#quick-category, #quick-city');
        if (submit) {
            submit.addEventListener('click', function () {
                var cat = document.getElementById('quick-category');
                var city = document.getElementById('quick-city');
                var name = document.getElementById('quick-name');
                var parts = ['Halo, saya ingin konsultasi katalog Varilevke.'];
                var cityText = city && city.selectedOptions[0] && city.value ? city.selectedOptions[0].text : '';
                if (cityText) { parts.push('Wilayah: ' + cityText); }
                if (name && name.value) { parts.push('Saya: ' + name.value); }
                if (cat && cat.selectedOptions[0]) { parts.push('Topik: ' + cat.selectedOptions[0].text.replace(/ \(.*\)$/, '')); }
                window.open(waBase + '?text=' + encodeURIComponent(parts.join(' ')), '_blank');
            });
        }
        jumps.forEach(function (sel) {
            sel.addEventListener('change', function () {
                if (this.value) { window.location.href = this.value; }
            });
        });
    })();
</script>
@endif
@endsection