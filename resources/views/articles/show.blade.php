@extends('layouts.app')

@php
    $seoTitle = $article->seo_title ?: ($article->title.' — Artikel');
@endphp

@push('meta_seo')
<meta name="description" content="{{ $article->metaDescription() }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $article->metaDescription() }}">
<meta property="og:type" content="article">
<link rel="canonical" href="{{ url()->current() }}">
@endpush

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@'.'context' => 'https://schema.org',
    '@'.'type' => 'Article',
    'headline' => $article->title,
    'datePublished' => optional($article->published_at)->toIso8601String(),
    'dateModified' => $article->updated_at?->toIso8601String(),
    'inLanguage' => 'id-ID',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('title', $seoTitle)

@section('content')
<article itemscope itemtype="https://schema.org/Article">

    <!-- Breadcrumb Navigation -->
    <nav aria-label="Breadcrumb" class="bg-regulatory-800 border-b border-grid py-3 font-display text-xs text-muted">
        <ol class="max-w-7xl mx-auto flex items-center gap-2 px-4 lg:px-8">
            <li><a href="{{ route('home') }}" class="hover:text-fg">Beranda</a></li>
            <li aria-hidden="true" class="text-grid">/</li>
            <li class="min-w-0">
                <a href="{{ route('articles.index') }}" class="hover:text-fg">Artikel</a>
            </li>
            <li aria-hidden="true" class="text-grid">/</li>
            <li class="min-w-0">
                <span class="truncate font-semibold text-accent-bright">{{ $article->title }}</span>
            </li>
        </ol>
    </nav>

    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
            {{-- Main article column --}}
            <div class="lg:col-span-8">
                <header class="mb-8 border-b border-grid pb-8">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="badge-sky">ARTIKEL</span>
                        @if ($article->city)
                            <a href="{{ route('regional.city', $article->city) }}" class="badge-neutral hover:border-accent hover:text-fg">
                                {{ strtoupper($article->city->label()) }}
                            </a>
                        @endif
                    </div>
                    <h1 class="font-display text-3xl font-bold text-fg leading-tight sm:text-4xl" itemprop="headline">{{ $article->title }}</h1>
                    <p class="mt-3 text-sm text-muted">
                        {{ optional($article->published_at instanceof \Carbon\Carbon ? $article->published_at : ($article->published_at ? \Carbon\Carbon::parse($article->published_at) : null))->translatedFormat('d F Y') ?? '' }}
                        @if ($article->reading_time)
                            · {{ $article->reading_time }} menit baca
                        @endif
                        @if ($article->word_count)
                            · {{ number_format($article->word_count) }} kata
                        @endif
                        @if ($article->kecamatan && $article->kecamatan->city)
                            · <a class="hover:text-accent-bright hover:underline" href="{{ route('regional.kecamatan', [$article->kecamatan->city, $article->kecamatan]) }}">{{ $article->kecamatan->name }}</a>
                        @endif
                    </p>
                </header>

                <div class="article-table-scroll article-content article-prose" itemprop="articleBody">
                    {!! $article->content !!}
                </div>

                @if ($article->faqs)
                    <section class="mt-10 border-t border-grid pt-8" aria-labelledby="faq-heading">
                        <h2 id="faq-heading" class="font-display text-xl font-bold text-fg mb-4">Pertanyaan umum</h2>
                        <dl class="space-y-4">
                            @foreach ($article->faqs as $faq)
                                <div class="card-k3 p-5">
                                    <dt class="font-display font-semibold text-fg">{{ $faq['question'] }}</dt>
                                    <dd class="mt-2 text-sm text-muted leading-relaxed">{{ $faq['answer'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                <div class="mt-10">
                    <x-whatsapp-contacts :callout="$callout" title="Tanya via WhatsApp" />
                </div>

                @if ($related->isNotEmpty())
                    <section class="mt-10 border-t border-grid pt-8">
                        <h2 class="font-display text-lg font-bold text-fg mb-4">Artikel terkait</h2>
                        <ul class="space-y-2">
                            @foreach ($related as $item)
                                <li class="card-k3 p-3 hover:border-accent transition-colors">
                                    <a class="text-sm font-display font-medium text-fg hover:text-accent-bright"
                                       href="{{ route('articles.show', $item) }}">{{ $item->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($catalogLinks->isNotEmpty())
                    <section class="mt-8 border-t border-grid pt-6">
                        <h2 class="font-display text-sm font-bold uppercase tracking-wider text-fg mb-3">Layanan terkait di katalog</h2>
                        <ul class="space-y-2">
                            @foreach ($catalogLinks as $catalogItem)
                                <li class="flex items-center gap-2">
                                    <span class="{{ $catalogItem->category === 'pelatihan' ? 'badge-accent' : 'badge-caution' }}">{{ strtoupper($catalogItem->category) }}</span>
                                    <a class="text-sm text-muted hover:text-accent-bright hover:underline"
                                       href="{{ route('catalog.show', ['category' => $catalogItem->category, 'item' => $catalogItem]) }}">{{ $catalogItem->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="lg:col-span-4 space-y-6">
                <div class="panel-k3 p-5 sticky top-20">
                    <div class="border-b border-grid pb-3 mb-4">
                        <span class="label-caps text-xs uppercase text-fg">Detail Artikel</span>
                    </div>
                    <dl class="space-y-2 text-xs">
                        @if ($article->published_at)
                            <div class="flex justify-between py-1.5 border-b border-grid-subtle">
                                <dt class="text-muted">Diterbitkan:</dt>
                                <dd class="font-display text-fg">{{ optional($article->published_at instanceof \Carbon\Carbon ? $article->published_at : \Carbon\Carbon::parse($article->published_at))->translatedFormat('d M Y') ?? '' }}</dd>
                            </div>
                        @endif
                        @if ($article->reading_time)
                            <div class="flex justify-between py-1.5 border-b border-grid-subtle">
                                <dt class="text-muted">Waktu baca:</dt>
                                <dd class="font-display text-fg">{{ $article->reading_time }} menit</dd>
                            </div>
                        @endif
                        @if ($article->city)
                            <div class="flex justify-between py-1.5 border-b border-grid-subtle">
                                <dt class="text-muted">Wilayah:</dt>
                                <dd class="font-display text-accent-bright">
                                    <a href="{{ route('regional.city', $article->city) }}" class="hover:underline">{{ $article->city->label() }}</a>
                                </dd>
                            </div>
                        @endif
                        <div class="flex justify-between py-1.5">
                            <dt class="text-muted">Status:</dt>
                            <dd class="font-display font-bold text-accent-bright">TERBIT</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
</article>
@endsection