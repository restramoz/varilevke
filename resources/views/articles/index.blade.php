@extends('layouts.app')

@push('meta_seo')
<link rel="canonical" href="{{ url()->current() }}">
<meta name="description" content="Panduan, referensi K3, dan artikel layanan pelatihan serta jasa lingkungan per wilayah.">
<meta property="og:title" content="Artikel &amp; Panduan | Varilevke">
<meta property="og:type" content="website">
@endpush

@section('title', 'Artikel & Panduan | Varilevke')

@section('content')
<x-breadcrumb :breadcrumbs="['Artikel' => null]">

    <div class="mb-8 border-b border-grid pb-8">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <span class="badge-sky">PANDUAN & WAWASAN</span>
        </div>
        <h1 class="font-display text-3xl font-bold text-fg">Artikel</h1>
        <p class="mt-2 text-muted">Panduan, referensi K3, dan artikel layanan per wilayah.</p>
    </div>

    <ul class="grid gap-4 md:grid-cols-2">
        @forelse ($articles as $article)
            <li class="card-k3 flex flex-col p-5 hover:border-accent transition-colors">
                <a class="font-display text-base font-bold text-fg hover:text-accent-bright leading-snug"
                   href="{{ route('articles.show', $article) }}">{{ $article->title }}</a>
                <p class="mt-2 line-clamp-2 text-sm text-muted leading-relaxed">{{ $article->metaDescription() }}</p>
                <p class="mt-auto pt-4 font-display text-[11px] uppercase tracking-wider text-dim">
                    {{ optional($article->published_at instanceof \Carbon\Carbon ? $article->published_at : ($article->published_at ? \Carbon\Carbon::parse($article->published_at) : null))->translatedFormat('d M Y') ?? '' }}
                    @if ($article->reading_time)
                        · {{ $article->reading_time }} menit baca
                    @endif
                    @if ($article->city)
                        · <a class="text-muted hover:text-accent-bright" href="{{ route('regional.city', $article->city) }}">{{ $article->city->label() }}</a>
                    @endif
                </p>
            </li>
        @empty
            <li class="text-muted col-span-2 py-8 text-center">Belum ada artikel terbit.</li>
        @endforelse
    </ul>

    @if ($articles->hasPages())
        <div class="mt-8">{{ $articles->links() }}</div>
    @endif

    <div class="mt-12">
        <x-whatsapp-contacts :callout="$callout" />
    </div>

</x-breadcrumb>
@endsection
