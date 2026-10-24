@extends('layouts.app')

@php
    $categoryTitle = $category === 'pelatihan' ? 'Pelatihan K3 & Sertifikasi Kemnaker RI' : 'Jasa Teknis, Lingkungan & Administrasi';
    $categorySubtitle = $category === 'pelatihan'
        ? 'Katalog 227 program pelatihan kompetensi K3: Ahli K3, Operator Alat Berat, Migas, Kelistrikan, Higiene Industri, dan lainnya — urutan mengikuti dokumen sumber resmi.'
        : 'Katalog 45 program jasa teknis, lingkungan, dan administrasi: UKL-UPL, AMDAL, SLF, SLO, Riksa Uji, ISO, Publikasi Jurnal, dan layanan perizinan lainnya.';
    $badgeClass = $category === 'pelatihan' ? 'badge-accent' : 'badge-caution';
    $categoryUpper = strtoupper($category);
    $categoryName = $category === 'pelatihan' ? 'Pelatihan' : 'Jasa';

    // Regional context: kecamatan context wins (its city is the parent).
    $hasKecamatan = (bool) ($kecamatan ?? false);
    $hasCity = (bool) ($city ?? false) || $hasKecamatan;
    $indexRoute = 'catalog.index';
    $showRoute = 'catalog.show';
    $otherCategoryRoute = 'catalog.index';
    if ($hasKecamatan) {
        $indexRoute = 'regional.kecamatan.catalog.index';
        $showRoute = 'regional.kecamatan.catalog.show';
        $otherCategoryRoute = 'regional.kecamatan.catalog.index';
    } elseif ($hasCity) {
        $indexRoute = 'regional.city.catalog.index';
        $showRoute = 'regional.city.catalog.show';
        $otherCategoryRoute = 'regional.city.catalog.index';
    }

    $indexParams = fn (string $cat): array => $hasKecamatan
        ? ['city' => $city->slug, 'kecamatan' => $kecamatan->slug, 'category' => $cat]
        : ($hasCity ? ['city' => $city->slug, 'category' => $cat] : ['category' => $cat]);
    $showParams = fn (\App\Models\CatalogItem $item): array => $hasKecamatan
        ? ['city' => $city->slug, 'kecamatan' => $kecamatan->slug, 'category' => $category, 'item' => $item]
        : ($hasCity ? ['city' => $city->slug, 'category' => $category, 'item' => $item] : ['category' => $category, 'item' => $item]);

    // Regional display strings
    $regionLabel = $hasKecamatan
        ? 'Kecamatan '.$kecamatan->name.', '.$city->label()
        : ($hasCity ? $city->label() : '');
    $cityLabel = $regionLabel;
    $cityUpperLabel = strtoupper($regionLabel);
    $cityInfix = $regionLabel !== '' ? ' di '.$regionLabel : '';
    $cityUpperInfix = $regionLabel !== '' ? ' DI '.strtoupper($regionLabel) : '';
    $cityRegionText = $regionLabel !== ''
        ? ($hasKecamatan
            ? ' Semua layanan dapat dikonsultasikan untuk wilayah Kecamatan '.$kecamatan->name.', '.$city->label().' — jadwal dan pelaksanaan dikonfirmasi saat konsultasi.'
            : ' Semua layanan tersedia untuk wilayah '.$city->label().' dan sekitarnya.')
        : '';
@endphp

@push('meta_seo')
<meta name="description" content="Katalog {{ $categoryUpper }}{{ $cityInfix }}: {{ $items->count() }} program terkurasi dari dokumen sumber resmi.">
<meta property="og:title" content="Katalog {{ $categoryUpper }}{{ $cityInfix }} | Varilevke">
<meta property="og:description" content="{{ $categorySubtitle }}{{ $cityRegionText }}">
@endpush

@section('title', 'Katalog '.$categoryName.$cityInfix.' | Varilevke')

@section('content')

<!-- Filter & Search in Category -->
<section class="py-6 bg-regulatory-800 border-b border-grid">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-xs font-display text-muted uppercase">
            MENAMPILKAN <span class="text-fg font-bold">{{ $items->count() }}</span> LAYANAN DI KATALOG {{ $categoryUpper }}{{ $cityUpperInfix }}
        </div>
        <div class="w-full sm:w-80">
            <input
                type="text"
                id="service-search-input"
                placeholder="Cari program layanan ini..."
                class="input-k3 w-full text-xs font-display"
                oninput="filterServices(this.value)"
            >
        </div>
    </div>
</section>

<!-- Header Category Banner -->
<section class="bg-gradient-to-b from-regulatory-700 to-regulatory-900 border-b border-grid py-14">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="flex items-center space-x-2 text-xs font-display uppercase text-muted mb-4 flex-wrap">
            <a href="{{ route('home') }}" class="hover:text-fg">Beranda</a>
            <span>/</span>
            @if($hasCity)
                <a href="{{ route('regional.city', $city) }}" class="hover:text-fg">{{ $city->label() }}</a>
                <span>/</span>
            @endif
            @if($hasKecamatan)
                <a href="{{ route('regional.kecamatan', [$city, $kecamatan]) }}" class="hover:text-fg">Kecamatan {{ $kecamatan->name }}</a>
                <span>/</span>
            @endif
            <span class="text-accent-bright font-semibold">{{ $categoryUpper }}</span>
        </div>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="{{ $badgeClass }}">{{ $categoryUpper }} TERKURASI</span>
                    <span class="text-xs font-display text-muted">{{ $items->count() }} Program Terdaftar</span>
                </div>
                <h1 class="font-display text-3xl sm:text-4xl font-bold text-fg">
                    {{ $categoryTitle }}{{ $cityInfix }}
                </h1>
                <p class="text-sm sm:text-base text-muted mt-2 max-w-3xl font-body">
                    {{ $categorySubtitle }}{{ $cityRegionText }}
                </p>
            </div>

            <!-- Switch category pills -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route($otherCategoryRoute, $indexParams('pelatihan')) }}" class="px-3 py-1.5 text-xs font-display uppercase {{ $category === 'pelatihan' ? 'bg-accent text-white border border-accent' : 'bg-regulatory-800 text-muted border border-grid hover:text-fg' }}">
                    Pelatihan (227)
                </a>
                <a href="{{ route($otherCategoryRoute, $indexParams('jasa')) }}" class="px-3 py-1.5 text-xs font-display uppercase {{ $category === 'jasa' ? 'bg-accent text-white border border-accent' : 'bg-regulatory-800 text-muted border border-grid hover:text-fg' }}">
                    Jasa (45)
                </a>
                @if ($hasKecamatan)
                    <a href="{{ route('regional.city.catalog.index', ['city' => $city->slug, 'category' => $category]) }}" class="px-3 py-1.5 text-xs font-display uppercase bg-regulatory-800 text-link border border-grid hover:border-accent">
                        Level kota: {{ $city->label() }}
                    </a>
                @endif
                @if ($hasCity && ! $hasKecamatan)
                    <a href="{{ route('regional.city', $city) }}" class="px-3 py-1.5 text-xs font-display uppercase bg-regulatory-800 text-link border border-grid hover:border-accent">
                        Halaman {{ $city->label() }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- Services Grid -->
<section class="py-12 lg:py-16">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div id="services-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($items as $item)
                <div class="service-card card-k3 p-6 flex flex-col justify-between hover:border-accent transition-all" data-name="{{ strtolower($item->name) }}" @if($loop->index >= 6) style="display:none;" @endif>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="{{ $badgeClass }} text-[10px]">{{ $item->category === 'pelatihan' ? 'Pelatihan' : 'Jasa' }}</span>
                            <span class="text-xs font-display text-accent-bright font-semibold">{{ str_pad((string) $item->source_order, 3, '0', STR_PAD_LEFT) }}</span>
                        </div>

                        <h2 class="font-display text-lg font-bold text-fg">
                            <a href="{{ route($showRoute, $showParams($item)) }}" class="hover:text-accent-bright transition-colors">
                                {{ $item->name }}
                            </a>
                        </h2>

                        <p class="text-xs text-muted leading-relaxed line-clamp-3">
                            {{ $item->description ?? 'Layanan "' . $item->name . '" tersedia dalam katalog kami. Silakan hubungi kontak WhatsApp untuk konsultasi, jadwal, dan penawaran.' }}
                        </p>

                        @if ($item->review_required)
                            <div class="border-t border-grid-subtle pt-3 text-[11px] text-caution-bright font-display">
                                &bull; Klasifikasi perlu dikonfirmasi: {{ $item->review_note }}
                            </div>
                        @endif
                    </div>

                    <div class="pt-6 mt-6 border-t border-grid-subtle flex items-center justify-between">
                        <div class="flex items-center gap-1.5 text-accent-bright font-display text-xs">
                            <span class="w-2 h-2 bg-accent inline-block"></span>
                            <span class="text-[11px] uppercase font-bold">{{ $item->category === 'pelatihan' ? 'Pelatihan' : 'Jasa' }}</span>
                        </div>
                        <a href="{{ route($showRoute, $showParams($item)) }}" class="btn-primary text-xs py-2 px-3.5">
                            Detail Layanan →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div id="no-service-found" class="hidden p-8 panel-k3 text-center text-muted font-display text-sm mt-6">
            Program tidak ditemukan. Silakan hubungi tim kami untuk pengajuan kebutuhan kustom.
        </div>
    </div>
</section>

@if($items->count() > 6)
<section class="py-8 bg-regulatory-800/20 border-t border-grid">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-fg font-display text-lg font-bold">
            Semua Layanan {{ ucfirst($category) }}
        </div>
        <div class="text-muted font-display">
            {{ $items->count() }} program tersedia
        </div>
        <button id="show-all-services" class="btn-primary text-sm py-2 px-4" onclick="showAllServices()">Lihat Semua</button>
    </div>
</section>
@endif

<!-- Quick Region Selector for this Category -->
@if(!$hasCity)
<section class="py-12 bg-regulatory-700 border-t border-grid">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="border-b border-grid pb-4 mb-6 flex items-center justify-between">
            <h3 class="font-display font-bold text-sm uppercase text-fg tracking-wider flex items-center gap-2">
                <span class="w-2 h-2 bg-accent inline-block"></span>
                Pelaksanaan {{ ucfirst($category) }} di Wilayah Pilihan Anda
            </h3>
            <a href="{{ route('home') }}#widget-wilayah" class="text-xs text-link font-display hover:underline">
                Lihat Semua {{ $countCities ?? '500+' }} Wilayah →
            </a>
        </div>

        <div class="flex flex-wrap gap-2">
            @php
                $hubCities = App\Models\City::query()->orderBy('name')->take(12)->get();
            @endphp
            @foreach($hubCities as $hub)
                <a href="{{ route('regional.city', $hub) }}" class="px-3 py-1.5 bg-regulatory-900 border border-grid hover:border-accent text-fg hover:text-accent-bright text-xs font-display uppercase">
                    {{ ucfirst($category) }} di {{ $hub->name }} →
                </a>
            @endforeach
        </div>
    </div>
</section>
@else
<section class="py-12 bg-regulatory-700 border-t border-grid">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="border-b border-grid pb-4 mb-6 flex items-center justify-between">
            <h3 class="font-display font-bold text-sm uppercase text-fg tracking-wider flex items-center gap-2">
                <span class="w-2 h-2 bg-accent inline-block"></span>
                Wilayah Lainnya di {{ $city->province }}
            </h3>
            <a href="{{ route('regional.cities') }}" class="text-xs text-link font-display hover:underline">
                Lihat Semua Wilayah →
            </a>
        </div>

        <div class="flex flex-wrap gap-2">
            @php
                $otherCities = App\Models\City::query()
                    ->where('province', $city->province)
                    ->where('id', '!=', $city->id)
                    ->orderBy('name')
                    ->take(12)
                    ->get();
            @endphp
            @foreach($otherCities as $other)
                <a href="{{ route('regional.city.catalog.index', ['city' => $other->slug, 'category' => $category]) }}" class="px-3 py-1.5 bg-regulatory-900 border border-grid hover:border-accent text-fg hover:text-accent-bright text-xs font-display uppercase">
                    {{ ucfirst($category) }} di {{ $other->name }} →
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- FAQ Section -->
<section id="faq-section" class="py-12 bg-regulatory-800/20 border-b border-grid" x-data="{ openFaq: null }">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <h3 class="text-accent-bright mb-2">FAQ</h3>
        <h2 class="font-display text-[24px] font-bold text-fg mb-4">Pertanyaan Umum {{ ucfirst($category) }}</h2>
        <div class="space-y-2">
            <div class="border border-grid overflow-hidden transition-colors" :class="openFaq === 0 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openFaq = openFaq === 0 ? null : 0"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openFaq === 0 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Apa itu layanan {{ $category }}?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openFaq === 0 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 0" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Layanan {{ $category }} menyediakan solusi resmi untuk {{ $category === 'pelatihan' ? 'pelatihan kompetensi K3' : 'jasa teknis, lingkungan, dan administrasi' }} di seluruh Indonesia.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openFaq === 1 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openFaq = openFaq === 1 ? null : 1"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openFaq === 1 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Siapa yang dapat menggunakan layanan ini?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openFaq === 1 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Layanan ini terbuka untuk perusahaan, institusi, dan individu yang membutuhkan {{ $category === 'pelatihan' ? 'sertifikasi atau pelatihan resmi' : 'jasa teknis dan perizinan' }}.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openFaq === 2 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openFaq = openFaq === 2 ? null : 2"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openFaq === 2 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Bagaimana cara mendaftar/meminta jasa?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openFaq === 2 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Anda dapat {{ $category === 'pelatihan' ? 'mendaftar melalui tombol "Booking" di halaman layanan' : 'mengajukan permohonan jasa' }} atau menghubungi via WhatsApp.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openFaq === 3 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openFaq = openFaq === 3 ? null : 3"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openFaq === 3 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Berapa lama proses layanan?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openFaq === 3 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Durasi bervariasi tergantung layanan, biasanya 2-8 minggu kerja untuk {{ $category === 'pelatihan' ? 'pelatihan dan sertifikasi' : 'kajian teknis dan perizinan' }}.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openFaq === 4 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openFaq = openFaq === 4 ? null : 4"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openFaq === 4 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Apakah layanan tersedia di wilayah lain?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openFaq === 4 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Ya, layanan {{ $category }} tersebar di lebih dari {{ $countCities ?? '500' }} wilayah di Indonesia.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openFaq === 5 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openFaq = openFaq === 5 ? null : 5"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openFaq === 5 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Apakah layanan dapat diakses online?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openFaq === 5 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 5" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>{{ $category === 'pelatihan' ? 'Beberapa layanan pelatihan tersedia secara online melalui LMS kami.' : 'Konsultasi awal dapat dilakukan online, pelaksanaan teknis memerlukan kehadiran di lokasi.' }}</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openFaq === 6 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openFaq = openFaq === 6 ? null : 6"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openFaq === 6 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Bagaimana proses pembayaran?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openFaq === 6 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 6" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Pembayaran dapat dilakukan via transfer bank atau melalui WhatsApp setelah konfirmasi.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openFaq === 7 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openFaq = openFaq === 7 ? null : 7"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openFaq === 7 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Bagaimana cara menghubungi tim?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openFaq === 7 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 7" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Hubungi kami via WhatsApp di <a href="https://wa.me/{{ \App\Support\WhatsApp::normalize(\App\Support\WhatsApp::numbers()[0]) }}" class="text-accent-bright underline">+{{ \App\Support\WhatsApp::display(\App\Support\WhatsApp::numbers()[0]) }}</a>.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Q&A Section -->
<section id="qa-section" class="py-12 bg-regulatory-800/20 border-b border-grid" x-data="{ openQna: null }">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <h2 class="text-accent-bright mb-2">Q&amp;A</h2>
        <h3 class="font-display text-4xl font-bold text-fg mb-4">Tanya Jawab {{ ucfirst($category) }}</h3>
        <div class="space-y-2">
            <div class="border border-grid overflow-hidden transition-colors" :class="openQna === 0 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openQna = openQna === 0 ? null : 0"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openQna === 0 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Saya masih bingung memilih layanan, mulai dari mana?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openQna === 0 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openQna === 0" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Anda dapat mulai dengan membaca deskripsi layanan di katalog atau menghubungi tim kami untuk rekomendasi.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openQna === 1 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openQna = openQna === 1 ? null : 1"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openQna === 1 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Apakah bisa konsultasi sebelum booking/permohonan?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openQna === 1 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openQna === 1" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Ya, tim kami siap konsultasi via WhatsApp atau telepon untuk menyesuaikan kebutuhan.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openQna === 2 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openQna = openQna === 2 ? null : 2"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openQna === 2 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Bagaimana kalau kebutuhan saya tidak ada di katalog?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openQna === 2 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openQna === 2" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Kami dapat merancang layanan khusus setelah diskusi detail kebutuhan Anda.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openQna === 3 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openQna = openQna === 3 ? null : 3"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openQna === 3 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Apakah bisa request jadwal?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openQna === 3 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openQna === 3" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Jadwal dapat diatur sesuai ketersediaan Anda dan tim kami.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openQna === 4 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openQna = openQna === 4 ? null : 4"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openQna === 4 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Apakah layanan tersedia untuk perusahaan?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openQna === 4 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openQna === 4" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Ya, layanan kami dirancang untuk mendukung kebutuhan perusahaan serta organisasi.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openQna === 5 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openQna = openQna === 5 ? null : 5"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openQna === 5 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Bagaimana jika saya perlu informasi tambahan?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openQna === 5 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openQna === 5" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Hubungi tim kami melalui WhatsApp atau telepon untuk detail lebih lanjut.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openQna === 6 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openQna = openQna === 6 ? null : 6"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openQna === 6 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Apakah bisa meminta penawaran khusus?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openQna === 6 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openQna === 6" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Kami dapat menyiapkan penawaran khusus sesuai skala dan kebutuhan Anda.</p>
                </div>
            </div>
            <div class="border border-grid overflow-hidden transition-colors" :class="openQna === 7 ? 'border-accent' : 'hover:border-grid'">
                <button @click="openQna = openQna === 7 ? null : 7"
                    class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                    :class="openQna === 7 ? 'bg-regulatory-700' : ''">
                    <span class="font-display font-semibold text-sm text-fg leading-snug">Bagaimana cara menghubungi tim?</span>
                    <svg class="w-4 h-4 text-accent-bright transition-transform duration-200"
                        :class="openQna === 7 ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openQna === 7" x-collapse class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid text-muted font-body text-xs leading-relaxed">
                    <p>Hubungi via WhatsApp <a href="https://wa.me/{{ \App\Support\WhatsApp::normalize(\App\Support\WhatsApp::numbers()[0]) }}" class="text-accent-bright underline">+{{ \App\Support\WhatsApp::display(\App\Support\WhatsApp::numbers()[0]) }}</a> atau telepon.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function filterServices(query) {
    query = query.toLowerCase().trim();
    const cards = document.querySelectorAll('.service-card');
    let visible = 0;

    cards.forEach(c => {
        const name = c.getAttribute('data-name');
        if (!query || name.includes(query)) {
            c.style.display = 'flex';
            visible++;
        } else {
            c.style.display = 'none';
        }
    });

    document.getElementById('no-service-found').classList.toggle('hidden', visible > 0);
}

function showAllServices() {
    const cards = document.querySelectorAll('.service-card');
    cards.forEach(c => {
        c.style.display = 'flex';
    });
    document.getElementById('show-all-services').style.display = 'none';
}
</script>

@endsection