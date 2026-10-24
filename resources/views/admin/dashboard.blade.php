@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <h1 class="font-display text-3xl font-bold text-fg">Dashboard</h1>

    <dl class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ([
            'Katalog' => $counts['catalog'],
            'Artikel' => $counts['articles'],
            'Kota' => $counts['cities'],
            'Kecamatan' => $counts['kecamatans'],
        ] as $label => $value)
            <div class="card-k3">
                <dt class="text-sm text-muted">{{ $label }}</dt>
                <dd class="mt-1 text-2xl font-bold text-fg">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($reviewRequiredCount > 0)
        <p class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            {{ $reviewRequiredCount }} item katalog menandai konfirmasi klasifikasi (termasuk "Jasa Lingkungan Karbon") — periksa sebelum mengubah kategori.
        </p>
    @endif

    @if (! $aiEnabled)
        <p class="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800">
            Generator artikel nonaktif: <code>AI_API_KEY</code> belum diset di .env. Fitur CRUD artikel tetap berfungsi.
        </p>
    @endif
@endsection
