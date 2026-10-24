@extends('layouts.app')

@php
    $hotline = \App\Support\WhatsApp::numbers()[0] ?? '';

    // Featured catalog items (deterministic: source order). The full catalog is
    // NOT duplicated here — cards link to city-contextual detail pages, and the
    // complete list lives on the city catalog index pages.
    $featuredPelatihan = \App\Models\CatalogItem::query()->category('pelatihan')->orderBy('source_order')->limit(6)->get();
    $featuredJasa = \App\Models\CatalogItem::query()->category('jasa')->orderBy('source_order')->limit(6)->get();
    $pelatihanCount = \App\Models\CatalogItem::query()->category('pelatihan')->count();
    $jasaCount = \App\Models\CatalogItem::query()->category('jasa')->count();

    $waCity = 'Halo Admin Varilevke, saya ingin konsultasi layanan K3 untuk area '.$city->label().', '.$city->province.'.';
@endphp

@push('meta_seo')
<meta name="description" content="Layanan pelatihan K3 dan jasa lingkungan kerja di {{ $city->label() }}, {{ $city->province }}. Katalog 272 item terkurasi, konsultasi via WhatsApp.">
<meta property="og:title" content="Layanan di {{ $city->label() }}, {{ $city->province }} | Varilevke">
<meta property="og:description" content="Pelatihan K3 & Jasa Lingkungan di {{ $city->label() }} dan sekitarnya.">
<meta property="og:type" content="website">
<link rel="canonical" href="{{ url()->current() }}">
@endpush

@push('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "AdministrativeArea",
  "name": "Varilevke - Layanan di {{ $city->label() }}",
  "url": "{{ url()->current() }}",
  "description": "Katalog pelatihan K3 dan jasa lingkungan kerja di {{ $city->label() }}, {{ $city->province }}.",
  "address": {
    "@@type": "PostalAddress",
    "addressLocality": "{{ $city->label() }}",
    "addressRegion": "{{ $city->province }}",
    "addressCountry": "ID"
  },
  "telephone": "+{{ $hotline }}",
  "openingHours": "Mo-Sa 08:00-17:00",
  "sameAs": ["{{ route('home') }}"]
}
</script>
@endpush

@section('title', 'Layanan di '.$city->label().', '.$city->province.' | Varilevke')

@section('content')
<article itemscope itemtype="https://schema.org/AdministrativeArea">

    <!-- Regional City Header -->
    <section class="bg-gradient-to-b from-regulatory-700 to-regulatory-900 border-b border-grid py-10 sm:py-14">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="flex items-center space-x-2 text-xs font-display uppercase text-muted mb-4 flex-wrap">
                <a href="{{ route('home') }}" class="hover:text-fg">Beranda</a>
                <span>/</span>
                <a href="{{ route('regional.cities') }}" class="hover:text-fg">Wilayah</a>
                <span>/</span>
                <span class="text-accent-bright font-semibold">{{ $city->label() }}</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-8 space-y-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge-accent">WILAYAH LAYANAN</span>
                        <span class="badge-neutral">{{ strtoupper($city->classification ?? 'KOTA/KABUPATEN') }} {{ strtoupper($city->name) }}</span>
                    </div>

                    <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl font-bold text-fg leading-tight">
                        Layanan Pelatihan K3 &amp; Jasa Lingkungan di {{ $city->label() }}
                    </h1>

                    <p class="text-base sm:text-lg text-muted leading-relaxed max-w-3xl">
                        Jaringan layanan Varilevke menjangkau wilayah <strong class="text-fg">{{ $city->label() }}</strong>, {{ $city->province }}, melalui pelatihan kompetensi K3 dan jasa teknis/lingkungan yang dikurasi dari dokumen sumber resmi. Pilih program di bawah atau hubungi tim kami untuk konsultasi kebutuhan spesifik — jadwal dan metode pelaksanaan di wilayah ini dikonfirmasi saat konsultasi.
                    </p>

                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        <a href="{{ \App\Support\WhatsApp::link($hotline, $waCity) }}" target="_blank" rel="noopener" class="btn-whatsapp">
                            Hubungi Koordinator Area {{ $city->label() }}
                        </a>
                        <a href="#katalog-wilayah" class="btn-secondary">
                            Lihat Katalog di {{ $city->label() }}
                        </a>
                    </div>
                </div>

                <!-- Regional Dispatch Meta Card -->
                <div class="lg:col-span-4">
                    <div class="panel-k3 p-6 space-y-4">
                        <div class="border-b border-grid pb-3 flex items-center justify-between">
                            <span class="font-display font-bold text-xs uppercase text-fg">STATUS WILAYAH</span>
                            <span class="w-2 h-2 bg-accent-bright inline-block animate-pulse"></span>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div class="flex justify-between border-b border-grid-subtle pb-2">
                                <span class="text-muted">Kota / Kabupaten:</span>
                                <span class="font-display font-bold text-fg">{{ $city->label() }}</span>
                            </div>
                            <div class="flex justify-between border-b border-grid-subtle pb-2">
                                <span class="text-muted">Provinsi:</span>
                                <span class="font-display text-fg">{{ $city->province }}</span>
                            </div>
                            <div class="flex justify-between border-b border-grid-subtle pb-2">
                                <span class="text-muted">Kecamatan tercatat:</span>
                                <span class="font-display font-bold text-fg">{{ $city->kecamatan_count ?? $city->kecamatans_count ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between border-b border-grid-subtle pb-2">
                                <span class="text-muted">Artikel wilayah:</span>
                                <span class="font-display font-bold text-accent-bright">{{ $articles->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted">Metode Pelaksanaan:</span>
                                <span class="font-display text-accent-bright">Tatap Muka &amp; In-House</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Google Maps Section (query built only from verified BPS region names) -->
    <section class="border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 py-6 sm:py-10">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <span class="label-caps text-accent-bright block mb-1">PETA AREA OPERASIONAL</span>
                    <h2 class="font-display text-lg font-bold text-fg">
                        Jangkauan Layanan di {{ $city->label() }} &amp; Sekitarnya
                    </h2>
                </div>
            </div>

            <div class="border border-grid overflow-hidden aspect-[4/3] sm:aspect-[16/9] max-h-[380px]">
                <iframe
                    src="https://www.google.com/maps?q={{ urlencode($city->label() . ', ' . $city->province) }}&output=embed"
                    width="100%"
                    height="100%"
                    style="border:0; filter: invert(90%) hue-rotate(180deg) saturate(0.8) brightness(0.9);"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Peta Area Operasional Varilevke di {{ $city->label() }}">
                </iframe>
            </div>
            <p class="text-[11px] text-dim font-display mt-2">
                ▲ Area operasional {{ $city->label() }} dan sekitarnya. Tim kami siap mobilisasi ke fasilitas industri Anda.
            </p>
        </div>
    </section>

    <!-- Service-category overview cards -->
    <section class="py-10 sm:py-12 bg-regulatory-800/40 border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="mb-6">
                <span class="label-caps text-accent-bright block mb-2">PILAR LAYANAN</span>
                <h2 class="font-display text-2xl font-bold text-fg">Dua Pilar Layanan untuk {{ $city->label() }}</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="{{ route('regional.city.catalog.index', ['city' => $city->slug, 'category' => 'pelatihan']) }}" class="card-k3 p-6 hover:border-accent transition-colors group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="badge-accent">PELATIHAN</span>
                        <span class="font-display text-xs text-muted group-hover:text-accent-bright">{{ $pelatihanCount }} PROGRAM</span>
                    </div>
                    <h3 class="font-display text-lg font-bold text-fg mb-2">Pelatihan &amp; Sertifikasi Kompetensi K3</h3>
                    <p class="text-sm text-muted leading-relaxed">Program kompetensi untuk personel keselamatan kerja, operator alat berat, migas, kelistrikan, hingga higiene industri — sesuai dokumen sumber.</p>
                </a>
                <a href="{{ route('regional.city.catalog.index', ['city' => $city->slug, 'category' => 'jasa']) }}" class="card-k3 p-6 hover:border-accent transition-colors group">
                    <div class="flex items-center justify-between mb-3">
                        <span class="badge-caution">JASA</span>
                        <span class="font-display text-xs text-muted group-hover:text-caution-bright">{{ $jasaCount }} PROGRAM</span>
                    </div>
                    <h3 class="font-display text-lg font-bold text-fg mb-2">Jasa Teknis, Lingkungan &amp; Administrasi</h3>
                    <p class="text-sm text-muted leading-relaxed">Perizinan, kajian risiko, riksa uji, publikasi ilmiah, dan layanan administrasi perusahaan — dikurasi per dokumen sumber.</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Services from this City -->
    <section id="katalog-wilayah" class="py-10 sm:py-14 border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="label-caps text-accent-bright block mb-2">PILIHAN PROGRAM</span>
                    <h2 class="font-display text-2xl font-bold text-fg">
                        Katalog Pelatihan &amp; Jasa di {{ $city->label() }}
                    </h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('regional.city.catalog.index', ['city' => $city->slug, 'category' => 'pelatihan']) }}" class="btn-secondary text-xs py-2 px-4">
                        Semua Pelatihan ({{ $pelatihanCount }})
                    </a>
                    <a href="{{ route('regional.city.catalog.index', ['city' => $city->slug, 'category' => 'jasa']) }}" class="btn-secondary text-xs py-2 px-4">
                        Semua Jasa ({{ $jasaCount }})
                    </a>
                </div>
            </div>

            <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @php $featuredItems = $featuredPelatihan->concat($featuredJasa); @endphp
                @foreach($featuredItems as $serv)
                    <li class="card-k3 p-5 flex flex-col justify-between hover:border-accent transition-colors">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="{{ $serv->category === 'pelatihan' ? 'badge-accent' : 'badge-caution' }} text-[10px]">{{ $serv->category === 'pelatihan' ? 'Pelatihan' : 'Jasa' }}</span>
                                <span class="font-display text-[11px] text-dim">#{{ str_pad((string) $serv->source_order, 3, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <h3 class="font-display font-bold text-sm text-fg leading-snug">
                                <a href="{{ route('regional.city.catalog.show', ['city' => $city->slug, 'category' => $serv->category, 'item' => $serv]) }}" class="hover:text-accent-bright">
                                    {{ $serv->name }}
                                </a>
                            </h3>
                            <p class="mt-2 text-[11px] text-muted line-clamp-2">
                                {{ $serv->description ?? 'Layanan tersedia dalam katalog kami. Hubungi kami untuk detail.' }}
                            </p>
                        </div>
                        <div class="pt-3 mt-3 border-t border-grid-subtle flex items-center justify-between">
                            <a href="{{ route('regional.city.catalog.show', ['city' => $city->slug, 'category' => $serv->category, 'item' => $serv]) }}" class="font-display text-[11px] text-accent-bright hover:underline">
                                Detail &rarr;
                            </a>
                            <a href="{{ route('regional.city.catalog.index', ['city' => $city->slug, 'category' => $serv->category]) }}" class="font-display text-[11px] text-dim hover:text-fg">
                                Katalog {{ $serv->category }} &rarr;
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>

            <p class="mt-6 text-xs text-dim font-display">
                Menampilkan 12 program pilihan dari {{ ($pelatihanCount + $jasaCount) }} program. Lihat katalog lengkap {{ $city->label() }} melalui tombol "Semua Pelatihan / Semua Jasa" di atas.
            </p>
        </div>
    </section>

    <!-- How to inquire -->
    <section class="py-10 sm:py-12 bg-regulatory-800/30 border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="mb-6">
                <span class="label-caps text-accent-bright block mb-2">ALUR KONSULTASI</span>
                <h2 class="font-display text-2xl font-bold text-fg">Cara Menanyakan Layanan di {{ $city->label() }}</h2>
            </div>
            <ol class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <li class="card-k3 p-5">
                    <div class="font-display text-[11px] text-accent-bright mb-2">LANGKAH 01</div>
                    <h3 class="font-display font-bold text-sm text-fg mb-1">Hubungi Koordinator Area</h3>
                    <p class="text-xs text-muted leading-relaxed">Kirim kebutuhan Anda via WhatsApp: nama program, jumlah peserta/perkiraan lokasi, dan periode waktu yang diinginkan.</p>
                </li>
                <li class="card-k3 p-5">
                    <div class="font-display text-[11px] text-accent-bright mb-2">LANGKAH 02</div>
                    <h3 class="font-display font-bold text-sm text-fg mb-1">Konfirmasi Lingkup &amp; Jadwal</h3>
                    <p class="text-xs text-muted leading-relaxed">Tim kami memverifikasi ketersediaan jadwal batch atau in-house untuk wilayah {{ $city->label() }} beserta persyaratan peserta dan dokumen.</p>
                </li>
                <li class="card-k3 p-5">
                    <div class="font-display text-[11px] text-accent-bright mb-2">LANGKAH 03</div>
                    <h3 class="font-display font-bold text-sm text-fg mb-1">Proposal &amp; Pelaksanaan</h3>
                    <p class="text-xs text-muted leading-relaxed">Proposal dan jadwal final dikirim setelah kesepakatan lingkup; pelaksanaan mengikuti rencana yang disepakati.</p>
                </li>
            </ol>
        </div>
    </section>

    <!-- Related Articles Section -->
    @if($articles->count() > 0)
    <section id="artikel-terkait" class="py-10 sm:py-12 border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="font-display text-2xl sm:text-3xl font-bold text-fg">
                        Artikel Terkait {{ $city->label() }}
                    </h2>
                    <p class="text-muted font-body text-sm mt-1">
                        Panduan, regulasi, dan informasi terbaru seputar pelatihan K3 dan jasa lingkungan di wilayah {{ $city->label() }}.
                    </p>
                </div>
                <a href="{{ route('articles.index') }}" class="btn-secondary text-xs py-2 px-4">
                    Semua Artikel &rarr;
                </a>
            </div>

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
        </div>
    </section>
    @endif

    <!-- FAQ Accordion (Alpine.js) -->
    <section id="faq-kota" class="py-10 sm:py-12 bg-regulatory-800/30 border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                <!-- FAQ Header -->
                <div class="lg:col-span-4">
                    <span class="label-caps text-accent-bright block mb-3">TANYA JAWAB LAYANAN</span>
                    <h2 class="font-display text-2xl font-bold text-fg leading-tight mb-4">
                        FAQ: Layanan K3 &amp; Lingkungan<br>di {{ $city->label() }}
                    </h2>
                    <p class="text-sm text-muted leading-relaxed mb-6">
                        Pertanyaan yang paling sering ditanyakan terkait layanan pelatihan dan jasa di wilayah {{ $city->label() }}.
                    </p>
                    <a href="{{ \App\Support\WhatsApp::link($hotline, 'Halo Admin Varilevke, saya punya pertanyaan tentang layanan di '.$city->label().'.') }}" target="_blank" rel="noopener" class="btn-whatsapp text-xs">
                        Pertanyaan Lainnya via WA
                    </a>
                </div>

                <!-- FAQ Accordion Items -->
                <div class="lg:col-span-8">
                    <div class="space-y-2" x-data="{ openIndex: null }">
                        @php
                            $faqs = [
                                ['q' => 'Apa saja program pelatihan K3 yang tersedia di ' . $city->label() . '?', 'a' => 'Seluruh ' . $pelatihanCount . ' program pelatihan dalam katalog kami dapat dikonsultasikan untuk dilaksanakan di ' . $city->label() . ' dan sekitarnya, termasuk Ahli K3 Umum, Operator Alat Berat, Kelistrikan, Migas, Higiene Industri, dan lainnya. Jadwal batch dapat dikonfirmasi via WhatsApp.'],
                                ['q' => 'Apakah jasa lingkungan (UKL-UPL, AMDAL, SLF) dapat dikonsultasikan untuk wilayah ' . $city->label() . '?', 'a' => 'Ya. Kami menyediakan ' . $jasaCount . ' program jasa teknis dan lingkungan termasuk UKL-UPL, AMDAL, SLF Gedung, SLO Instalasi Listrik, Riksa Uji Alat Berat, serta perizinan ISO dan publikasi jurnal. Semua layanan dapat dikonsultasikan untuk wilayah ' . $city->label() . '.'],
                                ['q' => 'Bagaimana cara booking pelatihan In-House di pabrik kami di ' . $city->label() . '?', 'a' => 'Hubungi tim kami via WhatsApp dengan menyebutkan: (1) Nama program pelatihan, (2) Jumlah peserta, (3) Lokasi pabrik/kapal, (4) Periode waktu yang diinginkan. Tim kami akan menyiapkan proposal dan jadwal.'],
                                ['q' => 'Apakah sertifikat pelatihan yang diterbitkan berlaku nasional?', 'a' => 'Ya. Program pelatihan mengacu pada standar pelatihan keselamatan dan kesehatan kerja. Detail kepada pihak penerbit sertifikat dan masa berlaku masing-masing program dijelaskan saat konsultasi, supaya tidak ada klaim berlebihan.'],
                                ['q' => 'Berapa biaya jasa perizinan (SLF, SLO, AMDAL) di ' . $city->label() . '?', 'a' => 'Biaya bervariasi tergantung luas bangunan, kompleksitas instalasi, dan jenis izin. Hubungi tim kami via WhatsApp untuk mendapatkan estimasi biaya yang akurat dan transparan untuk fasilitas spesifik Anda.'],
                                ['q' => 'Apakah ada jadwal batch reguler pelatihan di ' . $city->label() . ' bulan ini?', 'a' => 'Jadwal batch diperbarui secara berkala. Silakan hubungi tim registrasi kami via WhatsApp untuk informasi jadwal terbaru, kuota tersisa, dan prosedur pendaftaran.'],
                            ];
                        @endphp
                        @foreach($faqs as $idx => $faq)
                        <div
                            class="border border-grid overflow-hidden transition-colors"
                            :class="openIndex === {{ $idx }} ? 'border-accent' : 'hover:border-grid'"
                        >
                            <button
                                @click="openIndex = openIndex === {{ $idx }} ? null : {{ $idx }}"
                                class="w-full flex items-start justify-between text-left px-5 py-4 bg-regulatory-800 gap-4"
                                :class="openIndex === {{ $idx }} ? 'bg-regulatory-700' : ''"
                            >
                                <div class="flex items-start gap-3">
                                    <span class="font-display font-bold text-xs text-accent-bright shrink-0 pt-0.5">{{ sprintf('%02d', $idx + 1) }}</span>
                                    <span class="font-display font-semibold text-sm text-fg leading-snug">{{ $faq['q'] }}</span>
                                </div>
                                <div class="shrink-0 mt-0.5">
                                    <svg
                                        class="w-4 h-4 text-accent-bright transition-transform duration-200"
                                        :class="openIndex === {{ $idx }} ? 'rotate-180' : ''"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            <div
                                x-show="openIndex === {{ $idx }}"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-2"
                                class="px-5 pb-5 pt-3 bg-regulatory-900 border-t border-grid"
                            >
                                <p class="text-sm text-slate-300 leading-relaxed font-body">{{ $faq['a'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Other Cities in Same Province -->
    <section class="py-12 border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-display font-bold text-sm uppercase text-fg">
                    Wilayah Lainnya di {{ $city->province }}
                </h3>
                <a href="{{ route('regional.cities') }}" class="text-xs font-display text-link hover:underline uppercase">Semua provinsi &rarr;</a>
            </div>
            <div class="flex flex-wrap gap-2">
                @php
                    $otherCities = \App\Models\City::query()
                        ->where('province', $city->province)
                        ->where('id', '!=', $city->id)
                        ->orderBy('name')
                        ->take(12)
                        ->get();
                @endphp
                @foreach($otherCities as $other)
                    <a href="{{ route('regional.city', $other) }}" class="px-3 py-1.5 bg-regulatory-800 border border-grid hover:border-accent text-muted hover:text-fg text-xs font-display uppercase rounded-card">
                        {{ $other->name }} &rarr;
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Kecamatan Section -->
    @if($kecamatans->count() > 0)
    <section class="py-12 bg-regulatory-800/20 border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <h3 class="font-display font-bold text-sm uppercase text-fg mb-4">
                Kecamatan di {{ $city->label() }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($kecamatans as $kec)
                    <div class="card-k3 p-5 flex flex-col justify-between hover:border-accent transition-colors">
                        <div class="mb-3">
                            <h4 class="font-display font-bold text-sm text-fg">
                                Kecamatan {{ $kec->name }}
                            </h4>
                            <p class="text-xs text-muted">{{ $city->label() }}{{ $kec->articles_count > 0 ? ' · ' . $kec->articles_count . ' artikel' : '' }}</p>
                        </div>
                        <a href="{{ route('regional.kecamatan', [$city, $kec]) }}" class="btn-primary text-xs py-2 px-3 text-center">
                            Lihat layanan
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- WhatsApp CTA -->
    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <x-whatsapp-contacts :callout="$callout" title="Tanya layanan di {{ $city->label() }}" />
    </div>

</article>
@endsection
