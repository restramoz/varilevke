@extends('layouts.app')

@php
    $categoryName = $item->category === 'pelatihan' ? 'Pelatihan' : 'Jasa';
    $badgeClass = $item->category === 'pelatihan' ? 'badge-accent' : 'badge-caution';
    $nonPublished = $content && $content->status !== \App\Models\CatalogContent::STATUS_PUBLISHED;

    // Regional context: kecamatan context wins (its city is the parent), then
    // city context; without either the page is a valid generic detail page.
    $hasKecamatan = (bool) ($kecamatan ?? false);
    $hasCity = (bool) ($city ?? false) || $hasKecamatan;
    $indexRoute = $hasKecamatan ? 'regional.kecamatan.catalog.index' : ($hasCity ? 'regional.city.catalog.index' : 'catalog.index');
    $showRoute = $hasKecamatan ? 'regional.kecamatan.catalog.show' : ($hasCity ? 'regional.city.catalog.show' : 'catalog.show');

    // Regional display strings
    $cityInfix = $hasKecamatan
        ? ' di Kecamatan '.$kecamatan->name.', '.$city->label()
        : ($hasCity ? ' di '.$city->label() : '');
    $cityRegionText = $hasKecamatan
        ? ' Untuk wilayah Kecamatan '.$kecamatan->name.', '.$city->label().' — ketersediaan jadwal dan metode pelaksanaan dikonfirmasi saat konsultasi.'
        : ($hasCity ? ' Tersedia untuk wilayah '.$city->label().' dan sekitarnya.' : '');
    $cityAreaServed = $hasKecamatan
        ? 'Kecamatan '.$kecamatan->name.', '.$city->label().', '.$city->province.', Indonesia'
        : ($hasCity ? $city->label().', '.$city->province.', Indonesia' : 'Indonesia');
    $pageRegion = $hasKecamatan
        ? 'Kecamatan '.$kecamatan->name.', '.$city->label()
        : ($hasCity ? $city->label() : '');

    $indexRouteParams = $hasKecamatan
        ? ['city' => $city->slug, 'kecamatan' => $kecamatan->slug, 'category' => $item->category]
        : ($hasCity ? ['city' => $city->slug, 'category' => $item->category] : ['category' => $item->category]);

    $waRegionSuffix = $pageRegion !== '' ? ' untuk wilayah '.$pageRegion : '';
@endphp

@push('meta_seo')
<meta name="description" content="{{ $content?->text('intro') ?: 'Layanan ' . $item->name . ' tersedia dalam katalog Varilevke.' }}{{ $cityRegionText }}">
<meta property="og:title" content="{{ $item->name }} - {{ $categoryName }}{{ $cityInfix }} | Varilevke">
<meta property="og:description" content="{{ $content?->text('intro') ?: 'Layanan ' . $item->name . ' tersedia dalam katalog Varilevke.' }}{{ $cityRegionText }}">
<meta property="og:type" content="website">
<link rel="canonical" href="{{ url()->current() }}">
@endpush

@push('schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "{{ $item->category === 'pelatihan' ? 'Course' : 'Service' }}",
  "name": "{{ $item->name }}{{ $cityInfix }}",
  "description": "{{ $content?->text('intro') ?: 'Layanan ' . $item->name . ' tersedia dalam katalog Varilevke.' }}{{ $cityRegionText }}",
  "provider": {
    "@@type": "Organization",
    "name": "Varilevke",
    "url": "{{ route('home') }}"
  },
  "areaServed": "{{ $cityAreaServed }}",
  "serviceType": "{{ $categoryName }}"
}
</script>
@endpush

@section('title', $item->name.' - '.$categoryName.$cityInfix.' | Varilevke')

@section('content')
<article itemscope itemtype="https://schema.org/{{ $item->category === 'pelatihan' ? 'Course' : 'Service' }}">

    <!-- Breadcrumbs -->
    <div class="bg-regulatory-800 border-b border-grid py-3 text-xs font-display text-muted uppercase tracking-wider">
        <div class="max-w-7xl mx-auto px-4 lg:px-8 flex items-center gap-2 flex-wrap">
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
            <a href="{{ route($indexRoute, $indexRouteParams) }}" class="hover:text-fg">{{ $categoryName }}</a>
            <span>/</span>
            <span class="text-accent-bright font-semibold truncate">{{ $item->name }}</span>
        </div>
    </div>

    <!-- Header Section -->
    <section class="bg-gradient-to-b from-regulatory-700 to-regulatory-900 border-b border-grid py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                <div class="lg:col-span-8 space-y-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="{{ $badgeClass }}">{{ $categoryName }}</span>
                        <span class="badge-neutral">ITEM #{{ str_pad((string) $item->source_order, 3, '0', STR_PAD_LEFT) }}</span>
                        @if ($hasKecamatan)
                            <span class="badge-caution">KECAMATAN {{ strtoupper($kecamatan->name) }}</span>
                        @endif
                        @if ($hasCity)
                            <span class="badge-sky">{{ strtoupper($city->label()) }}</span>
                        @endif
                        @if ($item->review_required)
                            <span class="badge-caution">Klasifikasi perlu dikonfirmasi</span>
                        @endif
                        @if ($nonPublished)
                            <span class="badge-sky">Konten: {{ $content->status }}</span>
                        @endif
                    </div>

                    <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl font-bold text-fg leading-tight" itemprop="name">{{ $item->name }}</h1>

                    <p class="text-base sm:text-lg text-muted leading-relaxed max-w-3xl font-body" itemprop="description">
                        {{ $content?->text('intro') ?: 'Layanan "' . $item->name . '" tersedia dalam katalog kami. Silakan hubungi kontak WhatsApp di bawah untuk konsultasi, jadwal, dan penawaran.' }}
                    </p>

                    @if ($hasCity)
                        <p class="text-sm sm:text-base leading-relaxed max-w-3xl font-body border-l-2 border-accent pl-4 text-fg">
                            Layanan <strong class="text-accent-bright">{{ $item->name }}</strong> ditanyakan untuk wilayah
                            <strong>{{ $pageRegion }}</strong>{{ $city->province ? ', '.$city->province : '' }}.
                            Halaman ini merangkum informasi layanan dari katalog nasional — ketersediaan jadwal, kuota,
                            dan metode pelaksanaan pada wilayah tersebut dikonfirmasi langsung saat konsultasi WhatsApp.
                        </p>
                    @endif

                    @php
                        // Random per render: CTA didistribusikan ke seluruh pool nomor layanan.
                        $waBaseNumber = \App\Support\WhatsApp::random();
                        $waQuestion = \App\Support\WhatsApp::link($waBaseNumber, 'Halo, saya ingin bertanya tentang '.$item->name.$waRegionSuffix.'.');
                        $waProposal = \App\Support\WhatsApp::link($waBaseNumber, 'Halo, saya ingin meminta proposal dan silabus untuk '.$item->name.$waRegionSuffix.'.');
                        $waPenawaran = \App\Support\WhatsApp::link($waBaseNumber, 'Halo, saya ingin meminta penawaran biaya resmi untuk '.$item->name.$waRegionSuffix.'.');
                    @endphp

                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        @if ($item->category === 'pelatihan')
                            <a href="{{ $waQuestion }}" target="_blank" rel="noopener" class="btn-primary">
                                Booking Kursi Pelatihan Sekarang
                            </a>
                            <a href="{{ $waProposal }}" target="_blank" rel="noopener" class="btn-whatsapp">
                                Minta Proposal &amp; Silabus
                            </a>
                        @else
                            <a href="{{ $waQuestion }}" target="_blank" rel="noopener" class="btn-primary">
                                Ajukan Permohonan Jasa
                            </a>
                            <a href="{{ $waPenawaran }}" target="_blank" rel="noopener" class="btn-whatsapp">
                                Minta Penawaran Biaya Resmi
                            </a>
                        @endif

                        @if ($hasCity)
                            <a href="{{ route($indexRoute, $indexRouteParams) }}" class="btn-secondary">
                                Lihat Katalog {{ $categoryName }} di {{ $pageRegion }}
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Spec Box -->
                <div class="lg:col-span-4">
                    <div class="panel-k3 p-6 space-y-4">
                        <div class="border-b border-grid pb-3">
                            <span class="label-caps block text-dim">Spesifikasi Layanan</span>
                            <div class="font-display text-lg font-bold uppercase text-accent-bright">{{ $categoryName }} K3</div>
                            <span class="text-[10px] text-muted">*Sesuai dokumen sumber resmi</span>
                        </div>

                        <dl class="space-y-2 text-xs font-body">
                            <div class="flex justify-between py-1.5 border-b border-grid-subtle">
                                <dt class="text-muted">Kategori:</dt>
                                <dd class="font-display font-semibold uppercase text-fg">{{ $item->category }}</dd>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-grid-subtle">
                                <dt class="text-muted">Urutan sumber:</dt>
                                <dd class="font-display font-semibold text-fg">#{{ $item->source_order }}</dd>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-grid-subtle">
                                <dt class="text-muted">Kontak WhatsApp:</dt>
                                <dd class="font-display font-semibold text-wa">{{ count(\App\Support\WhatsApp::numbers()) }} kanal</dd>
                            </div>
                            <div class="flex justify-between py-1.5">
                                <dt class="text-muted">Status:</dt>
                                <dd class="font-display font-semibold {{ $content?->status === \App\Models\CatalogContent::STATUS_PUBLISHED ? 'text-accent-bright' : 'text-caution-bright' }}">{{ strtoupper($content?->status ?? 'REVIEW') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($item->review_required)
        <div class="max-w-7xl mx-auto px-4 lg:px-8 mt-6">
            <p class="border border-caution bg-regulatory-800 p-3 text-sm text-caution-bright">
                Klasifikasi item ini masih dalam peninjauan: {{ $item->review_note }}. Mohon konfirmasi melalui WhatsApp sebelum mengambil keputusan.
            </p>
        </div>
    @endif

    <!-- Technical Visual Archetype Component -->
    <section class="max-w-7xl mx-auto px-4 lg:px-8">
        <x-service-visual :item="$item" :category="$item->category" />
    </section>

    <!-- Content Details: Category-Specific Structure -->
    <section class="py-12 border-b border-grid">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">

                <!-- Main Content Column -->
                <div class="lg:col-span-8 space-y-12">

                    <!-- Section 1: Pengantar & Tujuan -->
                    <div class="card-k3 p-6 space-y-6">
                        <h2 class="font-display text-xl lg:text-2xl font-bold text-fg flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 bg-accent" aria-hidden="true"></span>
                            Pengantar &amp; Tujuan
                        </h2>
                        <p class="text-sm leading-relaxed text-muted font-body">{{ $content?->text('purpose') ?: 'Hubungi kami untuk menanyakan tujuan dan kisaran lingkup program "' . $item->name . '", siap dijawab lewat kanal WhatsApp resmi di halaman ini.' }}</p>
                        @if ($content && $content->text('intro') !== '')
                            <p class="border-t border-grid-subtle pt-4 text-sm leading-relaxed text-slate-300 font-body">{{ $content->text('intro') }}</p>
                        @endif
                    </div>

                    <!-- Section 2: Sasaran Peserta / Klien Potensial -->
                    <div class="card-k3 p-6">
                        <h2 class="font-display text-lg font-bold text-fg mb-3 flex items-center gap-2">
                            <span class="inline-block h-2 w-2 bg-accent-bright" aria-hidden="true"></span>
                            {{ $item->category === 'pelatihan' ? 'Sasaran Peserta' : 'Klien Potensial &amp; Penggunaan' }}
                        </h2>
                        <p class="text-sm leading-relaxed text-muted font-body">
                            {{ $content?->text('audience') ?: 'Instansi/perusahaan yang memerlukan layanan "' . $item->name . '", sesuai lingkup yang disepakati saat konsultasi.' }}
                        </p>
                    </div>

                    @if ($item->category === 'pelatihan')
                        <!-- PELATIHAN: Tujuan Pembelajaran -->
                        @if ($content && $content->list('objectives') !== [])
                        <div class="card-k3 p-6">
                            <h2 class="font-display text-xl lg:text-2xl font-bold text-fg mb-4 flex items-center gap-2">
                                <span class="inline-block h-2.5 w-2.5 bg-accent-bright" aria-hidden="true"></span>
                                Tujuan Pembelajaran
                            </h2>
                            <ul class="space-y-2 text-sm text-muted font-body">
                                @foreach ($content->list('objectives') as $objective)
                                    <li class="flex items-start gap-2"><span class="font-bold text-accent" aria-hidden="true">&#10003;</span> {{ $objective }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <!-- PELATIHAN: Materi Pokok (Kerangka) -->
                        @if ($content && $content->list('curriculum') !== [])
                        <div class="card-k3 p-6">
                            <h2 class="font-display text-xl lg:text-2xl font-bold text-fg mb-4 flex items-center gap-2">
                                <span class="inline-block h-2.5 w-2.5 bg-link" aria-hidden="true"></span>
                                Materi Pokok (Kerangka Bidang)
                            </h2>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm font-body">
                                    <thead class="bg-regulatory-700 border-b border-grid text-muted font-display uppercase text-[10px] tracking-wider">
                                        <tr>
                                            <th class="p-3 w-12">No</th>
                                            <th class="p-3">Bidang Bahasan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-grid-subtle">
                                        @foreach ($content->list('curriculum') as $index => $topic)
                                        <tr class="hover:bg-regulatory-900 transition">
                                            <td class="p-3 font-display font-bold text-link whitespace-nowrap">
                                                {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                                            </td>
                                            <td class="p-3 text-muted">{{ $topic }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="mt-4 border-t border-grid-subtle pt-3 text-[11px] text-dim">Merupakan kerangka bidang bahasan program — bukan rincian silabus resmi. Silakan minta detail silabus terkini lewat WhatsApp.</p>
                        </div>
                        @endif

                    @elseif ($item->category === 'jasa')
                        <!-- JASA: Ruang Lingkup Kerja -->
                        @if ($content && $content->list('scope') !== [])
                        <div class="space-y-8">
                            <div>
                                <h2 class="font-display text-xl lg:text-2xl font-bold text-fg mb-4 flex items-center gap-2">
                                    <span class="inline-block h-2.5 w-2.5 bg-caution-bright" aria-hidden="true"></span>
                                    Ruang Lingkup Kerja (Umum)
                                </h2>
                                <p class="text-sm text-muted leading-relaxed font-body mb-4">
                                    Gambaran umum mengenai hal-hal yang biasanya masuk dalam lingkup layanan ini. Rincian teknis akhir ditentukan bersama saat konsultasi.
                                </p>
                                <ul class="space-y-2 text-sm text-muted font-body">
                                    @foreach ($content->list('scope') as $point)
                                        <li class="flex items-start gap-2"><span class="font-bold text-caution-bright" aria-hidden="true">&#10003;</span> {{ $point }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @endif

                    @endif

                    <!-- Section: Prasyarat Umum (both categories) -->
                    @if ($content && $content->text('prerequisites') !== '')
                    <div class="card-k3 border-l-4 border-l-caution p-6">
                        <h2 class="font-display mb-3 text-lg font-bold text-fg">Prasyarat Umum</h2>
                        <p class="text-sm leading-relaxed text-muted font-body">{{ $content->text('prerequisites') }}</p>
                    </div>
                    @endif

                    <!-- Section: Dependensi & Hal yang Perlu Dikonfirmasi -->
                    @if ($content && $content->text('dependencies') !== '')
                    <div class="card-k3 border-l-4 border-l-caution p-6">
                        <h2 class="font-display mb-3 text-lg font-bold text-fg">Dependensi &amp; Hal yang Perlu Dikonfirmasi</h2>
                        <p class="text-sm leading-relaxed text-muted font-body">{{ $content->text('dependencies') }}</p>
                    </div>
                    @endif

                    <!-- Section: Hasil Kerja / Deliverables -->
                    @if ($content && $content->list('deliverables') !== [])
                    <div class="card-k3 p-6">
                        <h2 class="font-display text-xl lg:text-2xl font-bold text-fg mb-4 flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 bg-caution-bright" aria-hidden="true"></span>
                            Hasil Kerja yang Biasanya Diterima Klien
                        </h2>
                        <ul class="space-y-2 text-sm text-muted font-body">
                            @foreach ($content->list('deliverables') as $deliverable)
                                <li class="flex items-start gap-2"><span class="font-bold text-caution-bright" aria-hidden="true">&#10003;</span> {{ $deliverable }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <!-- Section: Persiapan & Proses (Two Column) -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="card-k3 p-6">
                            <h2 class="font-display mb-3 text-base font-bold uppercase text-fg">Yang Perlu Anda Siapkan</h2>
                            <ul class="space-y-2 text-sm text-muted font-body">
                                @foreach ($content?->list('documents') ?: ['Dokumen identitas usaha/perusahaan', 'Data dasar kebutuhan Anda (bentuk layanan, perkiraan waktu)'] as $doc)
                                    <li class="flex items-start gap-2"><span class="font-bold text-accent" aria-hidden="true">&bull;</span> {{ $doc }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="card-k3 p-6">
                            <h2 class="font-display mb-3 text-base font-bold uppercase text-fg">Proses Hubungi-Kerja Sama</h2>
                            <ol class="list-inside list-decimal space-y-2 text-sm text-muted font-body">
                                @foreach ($content?->list('process') ?: ['Hubungi kanal WhatsApp resmi di halaman ini', 'Bahas kebutuhan &amp; lingkup dengan tim kami', 'Kesepakatan lingkup + jadwal', 'Pelaksanaan sesuai rencana yang disepakati'] as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                        </div>
                    </div>

                    <!-- Section: Format & Assessment -->
                    @if ($content && ($content->text('format') !== '' || $content->text('assessment') !== ''))
                    <div class="card-k3 p-6 space-y-4">
                        <h2 class="font-display text-xl lg:text-2xl font-bold text-fg flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 bg-accent-bright" aria-hidden="true"></span>
                            Format &amp; Keterangan Pelaksanaan
                        </h2>
                        @if ($content->text('format') !== '')
                            <p class="text-sm text-muted font-body"><strong class="text-fg">Format pelaksanaan:</strong> {{ $content->text('format') }}</p>
                        @endif
                        @if ($content->text('assessment') !== '')
                            <p class="text-sm text-muted font-body"><strong class="text-fg">Evaluasi/keterangan:</strong> {{ $content->text('assessment') }}</p>
                        @endif
                    </div>
                    @endif

                    <!-- Section: FAQ -->
                    @if ($content && $content->faqs() !== [])
                    <div class="space-y-4">
                        <h2 class="font-display text-xl lg:text-2xl font-bold text-fg flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 bg-accent-bright" aria-hidden="true"></span>
                            Tanya Jawab Seputar Layanan Ini
                        </h2>
                        <div class="space-y-3 font-display text-sm">
                            @foreach ($content->faqs() as $index => $faq)
                                <details class="card-k3 group">
                                    <summary class="flex cursor-pointer items-center justify-between gap-4 p-4 font-bold text-fg hover:text-accent-bright transition-colors list-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-bright">
                                        <span>{{ $faq['question'] }}</span>
                                        <span class="w-6 h-6 rounded-full bg-regulatory-700 flex items-center justify-center shrink-0 group-open:rotate-180 transition-transform duration-200 text-accent-bright font-mono">
                                            +
                                        </span>
                                    </summary>
                                    <div class="p-4 pt-3 text-sm leading-relaxed text-muted font-body border-t border-grid-subtle">
                                        {{ $faq['answer'] }}
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Section: Artikel Terkait -->
                    @if ($articles->isNotEmpty())
                    <div class="card-k3 p-6">
                        <h2 class="font-display mb-4 text-xl font-bold text-fg">Artikel Terkait</h2>
                        <ul class="space-y-2">
                            @foreach ($articles as $article)
                                <li><a class="text-link hover:underline" href="{{ route('articles.show', $article) }}">{{ $article->title }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <meta itemprop="serviceType" content="{{ $item->category }}">
                    <meta itemprop="areaServed" content="Indonesia">
                </div>

                <!-- Sidebar -->
                <aside class="lg:col-span-4 space-y-6">
                    <!-- Form Kontak WhatsApp -->
                    <div class="card-k3 p-6">
                        <h3 class="font-display font-bold text-sm uppercase text-fg mb-4 flex items-center gap-2">
                            <span class="inline-block h-2 w-2 bg-accent" aria-hidden="true"></span>
                            Hubungi Tim Teknis
                        </h3>

                        <form action="#" method="POST" onsubmit="event.preventDefault(); window.open('https://wa.me/{{ \App\Support\WhatsApp::normalize(\App\Support\WhatsApp::numbers()[0]) }}?text=' + encodeURIComponent('Halo, saya berminat konsultasi program ' + '{{ $item->name }}' + ' untuk wilayah: ' + document.getElementById('reg-city').value + '. Nama: ' + document.getElementById('reg-name').value + '. Perusahaan: ' + document.getElementById('reg-company').value), '_blank');" class="space-y-4">
                            <div>
                                <label class="block label-caps mb-1 text-muted" for="reg-city">Pilih Wilayah</label>
                                <select id="reg-city" class="input-k3 w-full">
                                    @if ($hasKecamatan)
                                        <option value="Kecamatan {{ $kecamatan->name }}, {{ $city->label() }}" selected>Kecamatan {{ $kecamatan->name }}, {{ $city->label() }} (terpilih)</option>
                                    @endif
                                    <option value="">{{ $hasCity ? '— Ganti kota/kabupaten —' : '— Pilih kota/kabupaten —' }}</option>
                                    @foreach ($cities ?? [] as $regionOption)
                                        <option value="{{ $regionOption->label() }}" @if ($hasCity && ! $hasKecamatan && $regionOption->slug === $city->slug) selected @endif>{{ $regionOption->label() }}</option>
                                    @endforeach
                                    <option value="In-House Site Industri">In-House di Pabrik/Kantor Kami</option>
                                </select>
                            </div>

                            <div>
                                <label class="block label-caps mb-1 text-muted" for="reg-name">Nama Lengkap / Jabatan</label>
                                <input type="text" id="reg-name" placeholder="Nama Anda / HSE Lead" class="input-k3 w-full" required>
                            </div>

                            <div>
                                <label class="block label-caps mb-1 text-muted" for="reg-company">Perusahaan / Instansi</label>
                                <input type="text" id="reg-company" placeholder="Nama Perusahaan / Instansi" class="input-k3 w-full" required>
                            </div>

                            <button type="submit" class="btn-primary w-full py-3 text-xs tracking-wider">
                                Kirim Permohonan via WA &rarr;
                            </button>
                        </form>
                    </div>

                    <!-- Program Serupa / Related Items -->
                    <div class="card-k3 p-6">
                        <h4 class="font-display font-bold text-xs uppercase text-fg mb-4">Program Serupa di Katalog</h4>
                        <div class="space-y-3">
                            @forelse ($relatedItems as $rel)
                                <a href="{{ route($showRoute, $hasKecamatan
                                        ? ['city' => $city->slug, 'kecamatan' => $kecamatan->slug, 'category' => $rel->category, 'item' => $rel]
                                        : ($hasCity ? ['city' => $city->slug, 'category' => $rel->category, 'item' => $rel] : ['category' => $rel->category, 'item' => $rel])) }}"
                                   class="block border border-grid-subtle bg-regulatory-900 p-3 transition-colors hover:border-accent">
                                    <div class="font-display text-xs font-bold text-fg hover:text-accent-bright">{{ $rel->name }}</div>
                                    <div class="mt-1 font-display text-[11px] text-dim">item #{{ $rel->source_order }}</div>
                                </a>
                            @empty
                                <p class="text-xs text-muted">Belum ada relasi otomatis untuk item ini.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Kategori Link -->
                    <div class="card-k3 p-6">
                        <h2 class="label-caps mb-2 text-xs uppercase text-fg">Atas kategori ini</h2>
                        <a href="{{ route($indexRoute, $indexRouteParams) }}" class="text-sm text-link hover:underline">
                            @if ($hasCity)
                                Lihat katalog {{ $item->category }} {{ $pageRegion !== '' && $hasKecamatan ? 'di ' . $pageRegion : 'di ' . $city->label() }} &rarr;
                            @else
                                Lihat seluruh katalog {{ $item->category }} &rarr;
                            @endif
                        </a>
                        @if ($nonPublished)
                            <p class="mt-4 border-t border-grid-subtle pt-3 text-[11px] leading-relaxed text-dim">
                                Konten halaman ini bersifat editorial otomatis (status: {{ $content->status }}) dan belum disetujui manusia. Detail teknis penting harus selalu dikonfirmasi lewat kanal resmi.
                            </p>
                        @endif
                    </div>
                </aside>

            </div>
        </div>
    </section>
</article>
@endsection