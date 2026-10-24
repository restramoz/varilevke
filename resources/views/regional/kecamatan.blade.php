@extends('layouts.app')

@php
    $hotline = \App\Support\WhatsApp::numbers()[0] ?? '';

    // Featured catalog items (deterministic: source order). The full catalog
    // lives on the kecamatan catalog index pages — this page links there.
    $featuredPelatihan = \App\Models\CatalogItem::query()->category('pelatihan')->orderBy('source_order')->limit(6)->get();
    $featuredJasa = \App\Models\CatalogItem::query()->category('jasa')->orderBy('source_order')->limit(6)->get();
    $pelatihanCount = \App\Models\CatalogItem::query()->category('pelatihan')->count();
    $jasaCount = \App\Models\CatalogItem::query()->category('jasa')->count();

    $siblingKecamatans = \App\Models\Kecamatan::query()
        ->where('city_id', $kecamatan->city_id)
        ->where('id', '!=', $kecamatan->id)
        ->orderBy('name')
        ->take(12)
        ->get();

    // Kecamatan slugs are only unique per city, so the kecamatan-contextual
    // show route always needs BOTH identifiers.
    $kecShowParams = fn (App\Models\CatalogItem $item): array => [
        'city' => $kecamatan->city->slug,
        'kecamatan' => $kecamatan->slug,
        'category' => $item->category,
        'item' => $item,
    ];

    $seoTitle = "Layanan Pelatihan K3 & Jasa Lingkungan di Kecamatan {$kecamatan->name}, {$kecamatan->city->label()} - Varilevke";
    $metaDesc = "Katalog 272 layanan pelatihan K3 dan jasa lingkungan kerja untuk Kecamatan {$kecamatan->name}, {$kecamatan->city->label()}. Konsultasi via WhatsApp.";
    $waMessage = "Halo Admin Varilevke, saya ingin konsultasi layanan K3 di Kecamatan {$kecamatan->name}, {$kecamatan->city->label()}.";
@endphp

@push('meta_seo')
<meta name="description" content="{{ $metaDesc }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:type" content="website">
<link rel="canonical" href="{{ url()->current() }}">
@endpush

@push('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "AdministrativeArea",
  "name": "Varilevke - Kecamatan {{ $kecamatan->name }}",
  "url": "{{ url()->current() }}",
  "description": "{{ $metaDesc }}",
  "address": {
    "@@type": "PostalAddress",
    "addressLocality": "{{ $kecamatan->name }}",
    "addressRegion": "{{ $kecamatan->city->province }}",
    "addressCountry": "ID"
  },
  "telephone": "+{{ $hotline }}",
  "openingHours": "Mo-Sa 08:00-17:00"
}
</script>
@endpush

@section('title', $seoTitle)

@section('content')
<article itemscope itemtype="https://schema.org/AdministrativeArea">

    <!-- Breadcrumbs -->
    <div class="bg-regulatory-800 border-b border-grid py-3 text-xs font-display text-muted">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 flex flex-wrap items-center space-x-2">
            <a href="{{ route('home') }}" class="hover:text-fg">Beranda</a>
            <span>/</span>
            <a href="{{ route('regional.cities') }}" class="hover:text-fg">Wilayah</a>
            <span>/</span>
            <a href="{{ route('regional.city', $kecamatan->city) }}" class="hover:text-fg">{{ $kecamatan->city->label() }}</a>
            <span>/</span>
            <span class="text-accent-bright font-semibold">Kecamatan {{ $kecamatan->name }}</span>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="bg-gradient-to-b from-regulatory-700 to-regulatory-900 border-b border-grid py-8 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 space-y-6">
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge-accent">WILAYAH LAYANAN</span>
                <span class="badge-caution">KECAMATAN {{ strtoupper($kecamatan->name) }}</span>
                <span class="badge-neutral">{{ strtoupper($kecamatan->city->label()) }}</span>
            </div>

            <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl font-bold text-fg leading-tight">
                Layanan K3 di Kecamatan {{ $kecamatan->name }}, {{ $kecamatan->city->label() }}
            </h1>

            <p class="text-base sm:text-lg text-muted leading-relaxed max-w-3xl font-body">
                Katalog {{ $pelatihanCount + $jasaCount }} layanan ({{ $pelatihanCount }} pelatihan + {{ $jasaCount }} jasa) Varilevke menjangkau Kecamatan
                <strong class="text-fg">{{ $kecamatan->name }}</strong>, {{ $kecamatan->city->label() }}, {{ $kecamatan->city->province }}.
                Pilih program di bawah atau hubungi tim kami — ketersediaan jadwal dan metode pelaksanaan untuk wilayah ini dikonfirmasi saat konsultasi.
            </p>

            <div class="flex flex-wrap items-center gap-4 pt-2">
                <a href="{{ \App\Support\WhatsApp::link($hotline, $waMessage) }}" target="_blank" rel="noopener" class="btn-primary">
                    Konsultasi Layanan K3
                </a>
                <a href="#locations" class="btn-secondary">
                    Lihat Titik Lokasi &amp; Area
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Services Grid -->
    <section class="py-8 sm:py-12 border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-grid pb-4">
                <div>
                    <h2 class="font-display text-xl lg:text-2xl font-bold text-fg flex items-center gap-2">
                        <span class="w-2.5 h-2.5 bg-accent inline-block"></span>
                        Program Pelatihan &amp; Jasa untuk Kecamatan {{ $kecamatan->name }}
                    </h2>
                    <p class="text-xs text-muted mt-1 font-body">Pilih program untuk melihat detail, persyaratan, dan hubungi tim kami.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('regional.kecamatan.catalog.index', ['city' => $kecamatan->city->slug, 'kecamatan' => $kecamatan->slug, 'category' => 'pelatihan']) }}" class="btn-secondary text-xs py-2 px-4">
                        Semua Pelatihan ({{ $pelatihanCount }})
                    </a>
                    <a href="{{ route('regional.kecamatan.catalog.index', ['city' => $kecamatan->city->slug, 'kecamatan' => $kecamatan->slug, 'category' => 'jasa']) }}" class="btn-secondary text-xs py-2 px-4">
                        Semua Jasa ({{ $jasaCount }})
                    </a>
                </div>
            </div>

            <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @php $featuredItems = $featuredPelatihan->concat($featuredJasa); @endphp
                @foreach($featuredItems as $srv)
                    <li class="card-k3 p-5 flex flex-col justify-between hover:border-accent transition-colors">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="{{ $srv->category === 'pelatihan' ? 'badge-accent' : 'badge-caution' }} text-[10px]">{{ $srv->category === 'pelatihan' ? 'Pelatihan' : 'Jasa' }}</span>
                                <span class="font-display text-[11px] text-dim">#{{ str_pad((string) $srv->source_order, 3, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <h3 class="font-display font-bold text-base text-fg">
                                <a href="{{ route('regional.kecamatan.catalog.show', $kecShowParams($srv)) }}" class="hover:text-accent-bright">
                                    {{ $srv->name }}
                                </a>
                            </h3>
                            <p class="text-xs text-muted leading-relaxed line-clamp-3 font-body">
                                {{ $srv->description ?? 'Layanan tersedia dalam katalog kami. Hubungi kami untuk detail teknis.' }}
                            </p>
                        </div>

                        <div class="pt-4 border-t border-grid-subtle mt-4 flex items-center justify-between">
                            <a href="{{ route('regional.kecamatan.catalog.show', $kecShowParams($srv)) }}" class="text-xs font-display text-accent-bright hover:underline font-semibold">
                                Detail Layanan
                            </a>
                            <a href="{{ route('regional.kecamatan.catalog.index', ['city' => $kecamatan->city->slug, 'kecamatan' => $kecamatan->slug, 'category' => $srv->category]) }}" class="text-xs font-display text-dim hover:text-fg">
                                Katalog kecamatan &rarr;
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="text-xs text-dim font-display">
                Menampilkan 12 program pilihan dari {{ $pelatihanCount + $jasaCount }} program. Katalog lengkap Kecamatan {{ $kecamatan->name }} tersedia pada tombol "Semua Pelatihan / Semua Jasa".
            </p>
        </div>
    </section>

    <!-- Locations & Google Maps Section (query built only from verified BPS region names) -->
    <section id="locations" class="py-8 sm:py-12 border-b border-grid bg-regulatory-900/50">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 space-y-8">
            <div class="border-b border-grid pb-4">
                <span class="text-[11px] font-display uppercase tracking-wider text-accent-bright block mb-1">AREA LAYANAN</span>
                <h2 class="font-display text-xl lg:text-2xl font-bold text-fg">
                    Area Layanan Kecamatan {{ $kecamatan->name }}
                </h2>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left: Location Details -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="panel-k3 p-6 space-y-4">
                        <div class="border-b border-grid pb-3">
                            <h3 class="font-display font-bold text-sm text-fg uppercase">Area Koordinasi Kecamatan {{ $kecamatan->name }}</h3>
                            <p class="text-xs text-muted mt-1 font-body">
                                {{ $kecamatan->city->label() }}, {{ $kecamatan->city->province }}
                            </p>
                        </div>

                        <div class="pt-2">
                            <a href="https://maps.google.com/?q={{ urlencode($kecamatan->name . ', ' . $kecamatan->city->label()) }}" target="_blank" rel="noopener" class="btn-whatsapp w-full text-center text-xs flex justify-center">
                                Buka di Google Maps Langsung &nearr;
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right: Interactive Google Maps Embed -->
                <div class="lg:col-span-7">
                    <div class="bg-regulatory-800 border border-grid p-2">
                        <div class="aspect-[4/3] sm:aspect-[16/9] max-h-[400px]">
                            <iframe
                                src="https://www.google.com/maps?q={{ urlencode($kecamatan->name . ', ' . $kecamatan->city->label()) }}&output=embed"
                                width="100%"
                                height="100%"
                                style="border:0; filter: invert(90%) hue-rotate(180deg) saturate(0.8) brightness(0.9);"
                                allowfullscreen
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Google Maps Kecamatan {{ $kecamatan->name }}">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Articles Section -->
    <section class="py-8 sm:py-12 border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="font-display text-2xl sm:text-3xl font-bold text-fg">
                        Artikel Terkait Kecamatan {{ $kecamatan->name }}
                    </h2>
                    <p class="text-muted font-body text-sm mt-1">
                        Panduan, regulasi, dan informasi terbaru seputar pelatihan K3 dan jasa lingkungan di wilayah {{ $kecamatan->name }} dan {{ $kecamatan->city->label() }}.
                    </p>
                </div>
            </div>

            @if($articles->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($articles as $relArticle)
                        <article class="group card-k3 border border-grid overflow-hidden hover:border-accent transition-all duration-300 flex flex-col">
                            @if($relArticle->image_url)
                            <div class="aspect-video overflow-hidden relative">
                                <img src="{{ $relArticle->image_url }}" alt="{{ $relArticle->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute top-3 left-3">
                                    <span class="badge-accent">{{ $relArticle->category }}</span>
                                </div>
                            </div>
                            @endif
                            <div class="p-5 flex flex-col flex-grow">
                                <h3 class="font-display text-lg font-bold text-fg leading-snug group-hover:text-accent-bright transition-colors mb-3">
                                    <a href="{{ route('articles.show', $relArticle->slug) }}">{{ $relArticle->title }}</a>
                                </h3>
                                <p class="text-muted font-body text-xs line-clamp-3 mb-4 flex-grow">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($relArticle->content), 120) }}
                                </p>
                                <div class="flex items-center justify-between mt-auto pt-4 border-t border-grid">
                                    <span class="text-[10px] text-dim font-mono">
                                        {{ $relArticle->published_at->format('d M Y') }}
                                    </span>
                                    <a href="{{ route('articles.show', $relArticle->slug) }}" class="text-accent-bright text-xs font-bold hover:underline">
                                        Baca Selengkapnya &rarr;
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="card-k3 border-dashed border-grid p-12 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-grid text-muted mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <p class="text-muted font-body text-sm">Belum ada artikel khusus untuk wilayah ini. Silakan jelajahi artikel kota {{ $kecamatan->city->label() }}.</p>
                </div>
            @endif
        </div>
    </section>

    <!-- Other Kecamatans in the same city -->
    @if($siblingKecamatans->count() > 0)
    <section class="py-10 border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-display font-bold text-sm uppercase text-fg">
                    Kecamatan Lainnya di {{ $kecamatan->city->label() }}
                </h2>
                <a href="{{ route('regional.city', $kecamatan->city) }}" class="text-xs font-display text-link hover:underline uppercase">
                    Halaman {{ $kecamatan->city->label() }} &rarr;
                </a>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($siblingKecamatans as $sibling)
                    <a href="{{ route('regional.kecamatan', [$kecamatan->city, $sibling]) }}" class="px-3 py-1.5 bg-regulatory-800 border border-grid hover:border-accent text-muted hover:text-fg text-xs font-display uppercase rounded-card">
                        {{ $sibling->name }} &rarr;
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Dynamic FAQ / Q&A Section with Alpine.js Accordion -->
    <section class="py-8 sm:py-12 border-b border-grid bg-regulatory-900" x-data="{ activeAccordion: null }">
        <div class="max-w-4xl mx-auto px-4 lg:px-8 space-y-8">
            <div class="border-b border-grid pb-4">
                <span class="text-[11px] font-display uppercase tracking-wider text-accent-bright block mb-1">TANYA JAWAB TEKNIS (FAQ)</span>
                <h2 class="font-display text-xl lg:text-2xl font-bold text-fg">
                    Pertanyaan Umum Seputar Layanan K3 di {{ $kecamatan->name }}
                </h2>
            </div>

            <div class="space-y-3 font-display text-xs">
                @php
                    $faqs = [
                        ['q' => 'Apakah semua program pelatihan tersedia di Kecamatan ' . $kecamatan->name . '?', 'a' => 'Seluruh ' . $pelatihanCount . ' program pelatihan dalam katalog kami dapat dikonsultasikan untuk peserta dari Kecamatan ' . $kecamatan->name . ' dan sekitarnya. Pelaksanaan bisa tatap muka di sentra terdekat atau In-House di fasilitas Anda — ketersediaan dijamin dikonfirmasi saat konsultasi.'],
                        ['q' => 'Bagaimana cara mengajukan jasa perizinan (SLF, AMDAL, SLO) untuk pabrik di Kecamatan ' . $kecamatan->name . '?', 'a' => 'Hubungi tim kami via WhatsApp dengan data: (1) Nama & lokasi pabrik, (2) Jenis izin yang dibutuhkan, (3) Luas bangunan & jenis industri. Kami akan menyiapkan proposal dan alur pengurusan.'],
                        ['q' => 'Apakah tersedia pelatihan In-House di lokasi pabrik kami di Kecamatan ' . $kecamatan->name . '?', 'a' => 'Pelatihan In-House dapat dikonsultasikan untuk lokasi di Kecamatan ' . $kecamatan->name . ' dan sekitarnya. Syarat jumlah peserta, jadwal, dan peralatan dibahas langsung dengan tim registrasi via WhatsApp.'],
                        ['q' => 'Bagaimana cara menghubungi tim untuk kecamatan ini?', 'a' => 'Gunakan tombol WhatsApp di halaman ini dengan menyebutkan Kecamatan ' . $kecamatan->name . ', ' . $kecamatan->city->label() . ' agar muatan pesan yang Anda terima berikut jadwal dan metode pelaksanaan yang disediakan.'],
                    ];
                @endphp
                @foreach($faqs as $index => $faq)
                    <div class="bg-regulatory-800 border border-grid">
                        <button
                            @click="activeAccordion = (activeAccordion === {{ $index }} ? null : {{ $index }})"
                            class="w-full text-left p-4 flex items-center justify-between text-fg font-bold hover:text-accent-bright transition"
                        >
                            <span>{{ $faq['q'] }}</span>
                            <span class="text-base text-accent-bright ml-4 font-mono" x-text="activeAccordion === {{ $index }} ? '−' : '+'">+</span>
                        </button>
                        <div x-show="activeAccordion === {{ $index }}" x-collapse class="p-4 pt-0 text-muted font-body text-xs leading-relaxed border-t border-grid-subtle">
                            {{ $faq['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Conversion CTA -->
    <section class="bg-gradient-to-r from-regulatory-700 via-regulatory-800 to-regulatory-900 py-10 sm:py-14">
        <div class="max-w-4xl mx-auto px-4 lg:px-8 text-center space-y-6">
            <h2 class="font-display text-2xl sm:text-3xl font-bold text-fg">
                Butuh Pelayanan In-House atau Sertifikasi di Kecamatan {{ $kecamatan->name }}?
            </h2>
            <p class="text-sm text-muted max-w-xl mx-auto font-body">
                Tim kami siap membahas kebutuhan pelatihan dan jasa lingkungan untuk lokasi kerja Anda di {{ $kecamatan->city->label() }}.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
                <a href="{{ \App\Support\WhatsApp::link($hotline, $waMessage) }}" target="_blank" rel="noopener" class="btn-whatsapp">
                    Hubungi Konsultan Wilayah (WhatsApp)
                </a>
                <a href="{{ route('regional.city', $kecamatan->city) }}" class="btn-secondary">
                    Kembali ke {{ $kecamatan->city->label() }}
                </a>
            </div>
        </div>
    </section>

    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@graph": [
        {
          "@@type": "BreadcrumbList",
          "itemListElement": [
            {
              "@@type": "ListItem",
              "position": 1,
              "name": "Beranda",
              "item": "{{ route('home') }}"
            },
            {
              "@@type": "ListItem",
              "position": 2,
              "name": "Wilayah",
              "item": "{{ route('regional.cities') }}"
            },
            {
              "@@type": "ListItem",
              "position": 3,
              "name": "{{ $kecamatan->city->label() }}",
              "item": "{{ route('regional.city', $kecamatan->city) }}"
            },
            {
              "@@type": "ListItem",
              "position": 4,
              "name": "Kecamatan {{ $kecamatan->name }}",
              "item": "{{ url()->current() }}"
            }
          ]
        }
      ]
    }
    </script>

</article>
@endsection
