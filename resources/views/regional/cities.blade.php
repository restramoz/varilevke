@extends('layouts.app')

@push('meta_seo')
<link rel="canonical" href="{{ url()->current() }}">
<meta name="description" content="Direktori wilayah layanan Varilevke: 514 kota/kabupaten dan kecamatan berbasis data wilayah BPS di 38 provinsi Indonesia.">
<meta property="og:title" content="Wilayah Layanan | Varilevke">
<meta property="og:type" content="website">
@endpush

@section('title', 'Wilayah Layanan | Varilevke')

@section('content')
<x-breadcrumb :breadcrumbs="['Wilayah' => null]">

    <div class="mb-8 border-b border-grid pb-8">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <span class="badge-accent">JANGKAUAN NASIONAL</span>
        </div>
        <h1 class="font-display text-3xl font-bold text-fg">Wilayah Layanan</h1>
        <p class="mt-2 text-muted">Kota/kabupaten beserta kecamatan terdaftar pada data wilayah BPS. Klik kota untuk melihat layanan dan artikel setempat.</p>

        <div class="mt-6 max-w-md">
            <label class="label-caps block text-dim mb-2" for="wilayah-search">Cari kota/kabupaten</label>
            <input
                type="text"
                id="wilayah-search"
                placeholder="Contoh: Surabaya, Jawa Timur ..."
                class="input-k3 w-full"
                oninput="filterWilayah(this.value)"
            >
        </div>
        <p id="wilayah-no-result" class="hidden mt-4 text-sm text-muted font-display">Tidak ada kota/kabupaten yang cocok. Coba kata kunci lain.</p>
    </div>

    <div class="space-y-10">
        @php $citiesTotal = $cities->flatMap(fn ($list) => $list)->count(); @endphp
        @foreach ($cities as $province => $provinceCities)
            <section aria-labelledby="province-{{ \Illuminate\Support\Str::slug($province) }}" data-province="{{ strtolower($province) }}">
                <h2 id="province-{{ \Illuminate\Support\Str::slug($province) }}" class="font-display text-base font-bold uppercase tracking-widest text-fg mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 bg-accent" aria-hidden="true"></span>
                    {{ $province }}
                    <span class="text-dim normal-case tracking-normal font-body text-xs">({{ $provinceCities->count() }} kota/kabupaten)</span>
                </h2>
                <ul class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                    @foreach ($provinceCities as $city)
                        <li data-name="{{ strtolower($city->label().' '.$province) }}">
                            <a class="card-k3 flex flex-col p-3 hover:border-accent transition-colors"
                               href="{{ route('regional.city', $city) }}">
                                <span class="font-display text-xs font-semibold text-fg leading-snug">{{ $city->label() }}</span>
                                @if ($city->articles_count > 0)
                                    <span class="mt-1 text-[10px] text-accent-bright">{{ $city->articles_count }} artikel</span>
                                @else
                                    <span class="mt-1 text-[10px] text-dim">—</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    <div class="mt-12">
        <x-whatsapp-contacts :callout="$callout" />
    </div>

    <script>
    function filterWilayah(query) {
        query = query.toLowerCase().trim();
        let visible = 0;
        document.querySelectorAll('section[data-province]').forEach(function (section) {
            let provinceVisible = 0;
            section.querySelectorAll('li[data-name]').forEach(function (li) {
                const match = !query || li.getAttribute('data-name').includes(query);
                li.style.display = match ? '' : 'none';
                if (match) { provinceVisible++; }
            });
            section.style.display = provinceVisible > 0 ? '' : 'none';
            visible += provinceVisible;
        });
        document.getElementById('wilayah-no-result').classList.toggle('hidden', visible > 0);
    }
    </script>

</x-breadcrumb>
@endsection
