@extends('admin.layout')

@section('title', 'Edit Artikel: ' . ($article->title ?? 'Artikel') . ' — Admin')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/jodit@4.1.16/es2021/jodit.min.css">
<style>
    .jodit-container {
        border-radius: 10px !important;
        border: 1px solid #1E324E !important;
        background: #0B132B !important;
    }
    .jodit-toolbar__box {
        background: #111E38 !important;
        border-bottom: 1px solid #1E324E !important;
    }
    .jodit-toolbar-button__button {
        color: #5EEAD4 !important;
    }
    .jodit-toolbar-button__button:hover {
        background: #1E324E !important;
    }
    .jodit-workplace {
        background: #070D18 !important;
        color: #E2E8F0 !important;
    }
    .jodit-status-bar {
        background: #111E38 !important;
        border-top: 1px solid #1E324E !important;
        color: #94A3B8 !important;
    }
    .jodit-wysiwyg {
        background: #070D18 !important;
        color: #E2E8F0 !important;
        font-family: inherit !important;
        line-height: 1.7 !important;
        padding: 16px !important;
        overflow-x: auto;
    }
    .jodit-wysiwyg table {
        border-collapse: collapse;
        width: 100%;
        min-width: 600px;
        margin: 16px 0;
    }
    .jodit-wysiwyg th, .jodit-wysiwyg td {
        border: 1px solid #1E324E;
        padding: 8px 12px;
        white-space: nowrap;
    }
    .jodit-wysiwyg th {
        background: #111E38;
        color: #5EEAD4;
        font-weight: 700;
    }
    .jodit-wysiwyg .jodit-table {
        overflow-x: auto;
        width: 100%;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-regulatory-100 tracking-tight">
                Edit Artikel
            </h1>
            <p class="text-xs text-muted mt-1">
                Perbarui konten, optimasi SEO, tautkan wilayah/katalog, atau ubah status publikasi
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($article->status === 'published')
                <a href="{{ route('articles.show', $article->slug) }}" target="_blank" rel="noopener noreferrer"
                   class="px-3.5 py-2 text-xs font-bold text-accent bg-regulatory-800 border border-teal-500/40 hover:bg-regulatory-700 rounded-lg transition inline-flex items-center gap-1">
                    <span>Lihat di Web</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            @endif
            <a href="{{ route('admin.articles.index') }}"
               class="px-3.5 py-2 text-xs font-bold text-regulatory-200 hover:text-accent bg-regulatory-800 border border-grid rounded-lg transition">
                &larr; Kembali
            </a>
        </div>
    </div>

    {{-- AI Regenerator Card --}}
    <div class="card-k3 space-y-4">
        <div class="panel-k3 border-b border-grid pb-3">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-teal-500/20 text-accent flex items-center justify-center font-bold text-xs border border-teal-500/50">
                    AI
                </span>
                <h2 class="font-bold text-base text-regulatory-100">
                    Ollama AI Article Regenerator
                </h2>
                <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-amber-900/40 text-amber-300 font-bold border border-amber-700/50 ml-auto">
                    ⚠ Akan menimpa konten editor saat ini
                </span>
            </div>
        </div>

        <div class="p-4 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                {{-- Wilayah Target --}}
                <div class="sm:col-span-6 space-y-2">
                    <label for="ai_city_slug" class="block text-xs font-bold text-regulatory-200 uppercase tracking-wider">
                        Wilayah Target (Kota / Kabupaten) *
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <select id="filter_ai_province"
                                class="w-full px-2.5 py-1.5 text-xs rounded-lg input-k3 border-grid bg-regulatory-800 focus:outline-none focus:ring-2 focus:ring-accent">
                            <option value="">Semua Provinsi ({{ $provinces->count() }})</option>
                            @foreach($provinces as $prov)
                                <option value="{{ $prov }}">{{ $prov }}</option>
                            @endforeach
                        </select>
                        <div class="relative">
                            <input
                                type="text"
                                id="search_ai_city"
                                placeholder="Cari kota/kab..."
                                class="w-full pl-2.5 pr-6 py-1.5 text-xs rounded-lg input-k3 border-grid bg-regulatory-800 focus:outline-none focus:ring-2 focus:ring-accent"
                            >
                            <button type="button" id="btn_clear_ai_search"
                                    class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-muted hover:text-regulatory-200 text-xs cursor-pointer">&times;</button>
                        </div>
                    </div>
                    <select id="ai_city_slug"
                            class="w-full px-3.5 py-2 text-xs rounded-lg input-k3 border-grid bg-regulatory-800 focus:outline-none focus:ring-2 focus:ring-accent">
                        <option value="">-- Pilih Kota / Kabupaten --</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->slug }}" data-id="{{ $city->id }}"
                                    data-province="{{ $city->province }}" data-name="{{ $city->name }}"
                                    {{ $article->city?->slug === $city->slug ? 'selected' : '' }}>
                                {{ $city->label() }} ({{ $city->province }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Kecamatan Opsional --}}
                <div class="sm:col-span-6 space-y-2">
                    <label for="ai_district_slug" class="block text-xs font-bold text-regulatory-200 uppercase tracking-wider">
                        Kecamatan (Opsional)
                    </label>
                    <select id="ai_district_slug"
                            class="w-full px-3.5 py-2 text-xs rounded-lg input-k3 border-grid bg-regulatory-800 focus:outline-none focus:ring-2 focus:ring-accent"
                            disabled>
                        <option value="">-- Pilih Kota terlebih dahulu --</option>
                    </select>
                    <p id="ai_kecamatan_feedback" class="text-[10px] text-muted"></p>
                </div>

                {{-- Katalog Target --}}
                <div class="sm:col-span-6 space-y-2">
                    <label for="ai_service_slug" class="block text-xs font-bold text-regulatory-200 uppercase tracking-wider">
                        Katalog Target Pelatihan / Layanan (Opsional)
                    </label>
                    <select id="ai_service_slug"
                            class="w-full px-3.5 py-2 text-xs rounded-lg input-k3 border-grid bg-regulatory-800 focus:outline-none focus:ring-2 focus:ring-accent">
                        <option value="">-- Umum (Pilihan Otomatis) --</option>
                        @foreach($catalogItems as $item)
                            <option value="{{ $item->slug }}" data-id="{{ $item->id }}" data-category="{{ $item->category }}"
                                    {{ $article->catalog_item_id == $item->id ? 'selected' : '' }}>
                                {{ $item->name }} ({{ ucfirst($item->category) }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-muted">Bila dipilih, materi artikel terkonsentrasi pada kompetensi spesifik katalog ini.</p>
                </div>

                {{-- Target Word Count --}}
                <div class="sm:col-span-3 space-y-2">
                    <label for="ai_word_count" class="block text-xs font-bold text-regulatory-200 mb-1 uppercase tracking-wider">
                        Target Jumlah Kata *
                    </label>
                    <input type="number" id="ai_word_count" value="8000" min="6000" max="10000" step="500"
                           class="w-full px-3.5 py-2 text-xs rounded-lg input-k3 border-grid bg-regulatory-800 focus:outline-none focus:ring-2 focus:ring-accent"
                           required>
                    <p class="text-[10px] text-muted">Batas Varilevke: 6.000–10.000 kata (default: 8.000).</p>
                </div>

                {{-- Topik Kustom --}}
                <div class="sm:col-span-3 space-y-2">
                    <label for="ai_topic" class="block text-xs font-bold text-regulatory-200 mb-1 uppercase tracking-wider">
                        Topik Khusus (Opsional)
                    </label>
                    <input type="text" id="ai_topic" placeholder="Kosongkan untuk otomatis"
                           class="w-full px-3.5 py-2 text-xs rounded-lg input-k3 border-grid bg-regulatory-800 focus:outline-none focus:ring-2 focus:ring-accent">
                    <p class="text-[10px] text-muted">Otomatis dipilih dari katalog + wilayah bila kosong.</p>
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-grid">
                <p class="text-[11px] text-muted">
                    Menghasilkan: judul, slug, konten HTML semantik, ringkasan, FAQ, dan image brief Google Flow.
                </p>
                <button type="button" id="btn-generate-ai"
                        class="btn-primary text-xs shrink-0 inline-flex items-center gap-2 cursor-pointer">
                    <svg id="ai-spinner" class="hidden w-3.5 h-3.5 animate-spin mr-1" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    <span id="ai-btn-text">Regenerate dengan AI</span>
                </button>
            </div>

            <div id="ai-error-box" class="hidden p-3 rounded-lg bg-red-950/40 border border-red-700/60 text-xs text-red-300"></div>
        </div>
    </div>

    {{-- Structured Image Brief Display (if available) --}}
    @if(!empty($article->image_brief))
    <div id="image-brief-container" class="card-k3 bg-emerald-950/20 border-emerald-800/40 space-y-3 p-5">
    @else
    <div id="image-brief-container" class="hidden card-k3 bg-emerald-950/20 border-emerald-800/40 space-y-3 p-5">
    @endif
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h3 class="font-bold text-sm text-emerald-300">
                    Image Brief &amp; Prompt Google Flow
                </h3>
            </div>
            <span class="text-[10px] font-bold text-emerald-300 bg-emerald-900/40 border border-emerald-700/50 px-2.5 py-0.5 rounded-full">
                Aspek Rasio: <span id="brief-aspect-ratio">{{ $article->image_brief['aspect_ratio'] ?? '16:9' }}</span>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
            <div>
                <span class="font-bold text-emerald-300 block mb-0.5">Konsep Visual:</span>
                <p id="brief-concept" class="text-emerald-100 bg-regulatory-900 p-2.5 rounded-lg border border-emerald-900/50 leading-relaxed">
                    {{ $article->image_brief['concept'] ?? '-' }}
                </p>
            </div>
            <div>
                <span class="font-bold text-emerald-300 block mb-0.5">Rekomendasi Alt Text:</span>
                <p id="brief-alt" class="text-emerald-100 bg-regulatory-900 p-2.5 rounded-lg border border-emerald-900/50 leading-relaxed">
                    {{ $article->image_brief['recommended_alt'] ?? '-' }}
                </p>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <span class="font-bold text-xs text-emerald-300">English Prompt Siap Salin ke Google Flow:</span>
                <button type="button" id="btn-copy-prompt"
                        class="text-[11px] font-bold text-regulatory-950 bg-emerald-400 hover:bg-emerald-300 px-3 py-1 rounded-md transition inline-flex items-center gap-1 cursor-pointer">
                    <span id="copy-text">Salin Prompt Google Flow</span>
                </button>
            </div>
            <textarea id="brief-english-prompt" rows="3" readonly
                      class="w-full text-xs font-mono p-3 bg-regulatory-900 border border-emerald-800/40 rounded-lg text-emerald-100 focus:outline-none">{{ $article->image_brief['english_prompt'] ?? '' }}</textarea>
            <p class="text-[11px] text-emerald-400/80 mt-1">
                * Buat gambar di Google Flow, unduh hasilnya, lalu unggah melalui kolom "Ganti Gambar Unggulan" di bawah.
            </p>
        </div>
    </div>

    {{-- Main Editor & Live Preview Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {{-- Left: Form Input --}}
        <div class="lg:col-span-7 space-y-5">
            <form id="article-form" method="POST" action="{{ route('admin.articles.update', $article->id) }}"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Hidden inputs --}}
                <input type="hidden" name="image_brief" id="hidden_image_brief"
                       value="{{ is_array(old('image_brief', $article->image_brief)) ? json_encode(old('image_brief', $article->image_brief)) : old('image_brief', $article->image_brief) }}">
                <input type="hidden" name="faqs" id="hidden_faqs"
                       value="{{ is_array(old('faqs', $article->faqs)) ? json_encode(old('faqs', $article->faqs)) : old('faqs', $article->faqs) }}">
                <input type="hidden" name="city_id" id="hidden_city_id" value="{{ old('city_id', $article->city_id) }}">
                <input type="hidden" name="kecamatan_id" id="hidden_kecamatan_id" value="{{ old('kecamatan_id', $article->kecamatan_id) }}">

                <div class="card-k3 space-y-4 p-6">
                    {{-- Judul --}}
                    <div>
                        <label for="title" class="block text-xs font-bold text-regulatory-200 mb-1 uppercase tracking-wider">
                            Judul Artikel *
                        </label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            required
                            value="{{ old('title', $article->title) }}"
                            class="w-full px-3.5 py-2.5 text-sm font-bold rounded-lg input-k3 border-grid focus:outline-none focus:ring-2 focus:ring-accent"
                        >
                    </div>

                    {{-- Slug URL --}}
                    <div>
                        <label for="slug" class="block text-xs font-bold text-regulatory-200 mb-1 uppercase tracking-wider">
                            Slug URL
                        </label>
                        <input
                            type="text"
                            id="slug"
                            name="slug"
                            value="{{ old('slug', $article->slug) }}"
                            class="w-full px-3.5 py-2 text-xs font-mono rounded-lg input-k3 border-grid bg-regulatory-800 focus:outline-none focus:ring-2 focus:ring-accent"
                        >
                    </div>

                    {{-- Target Wilayah Selector --}}
                    <div class="p-3.5 rounded-xl bg-regulatory-800 border border-grid space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label for="target_city_slug" class="block text-xs font-bold text-regulatory-200 uppercase tracking-wider">
                                Tautkan ke Wilayah (Kota / Kabupaten)
                            </label>
                            <span class="text-[10px] text-muted">Kosongkan jika skala nasional</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <label for="filter_target_province" class="block text-[11px] text-muted mb-1">Filter Provinsi</label>
                                <select id="filter_target_province"
                                        class="w-full px-2.5 py-1.5 text-xs rounded-lg input-k3 border-grid bg-regulatory-700 focus:outline-none focus:ring-2 focus:ring-accent">
                                    <option value="">Semua Provinsi ({{ $provinces->count() }})</option>
                                    @foreach($provinces as $prov)
                                        <option value="{{ $prov }}">{{ $prov }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="search_target_city" class="block text-[11px] text-muted mb-1">Cari Nama Kota / Kabupaten</label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        id="search_target_city"
                                        placeholder="Ketik nama kota, misal: Surabaya..."
                                        class="w-full pl-2.5 pr-6 py-1.5 text-xs rounded-lg input-k3 border-grid bg-regulatory-700 focus:outline-none focus:ring-2 focus:ring-accent"
                                    >
                                    <button type="button" id="btn_clear_target_search"
                                            class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-muted hover:text-regulatory-200 text-xs cursor-pointer">&times;</button>
                                </div>
                            </div>
                        </div>
                        <div>
                            @php
                                $selectedCitySlug = old('target_city_slug', $article->city?->slug);
                            @endphp
                            <select id="target_city_slug" name="target_city_slug"
                                    class="w-full px-3 py-2 text-xs rounded-lg input-k3 border-grid bg-regulatory-700 focus:outline-none focus:ring-2 focus:ring-accent">
                                <option value="">-- Nasional / Tanpa Wilayah Tertentu --</option>
                                @foreach($cities as $city)
                                    <option
                                        value="{{ $city->slug }}"
                                        data-id="{{ $city->id }}"
                                        data-province="{{ $city->province }}"
                                        data-name="{{ $city->name }}"
                                        {{ $selectedCitySlug === $city->slug ? 'selected' : '' }}
                                    >
                                        {{ $city->label() }} ({{ $city->province }})
                                    </option>
                                @endforeach
                            </select>
                            <p id="target_city_feedback" class="text-[10px] text-muted mt-1"></p>
                        </div>

                        {{-- Kecamatan (form) --}}
                        <div>
                            <label for="form_kecamatan_id" class="block text-[11px] text-muted mb-1">
                                Kecamatan (Opsional)
                            </label>
                            <select id="form_kecamatan_id"
                                    class="w-full px-3 py-2 text-xs rounded-lg input-k3 border-grid bg-regulatory-700 focus:outline-none focus:ring-2 focus:ring-accent"
                                    {{ empty($article->city_id) ? 'disabled' : '' }}>
                                <option value="">-- Semua Kecamatan (Cakup Penuh Kota) --</option>
                                @if(!empty($article->kecamatan_id) && $article->kecamatan)
                                    <option value="{{ $article->kecamatan_id }}" selected>
                                        {{ $article->kecamatan->name }}
                                    </option>
                                @endif
                            </select>
                            <p id="form_kecamatan_feedback" class="text-[10px] text-muted mt-1"></p>
                        </div>
                    </div>

                    {{-- Katalog Terkait --}}
                    <div>
                        <label for="catalog_item_id" class="block text-xs font-bold text-regulatory-200 mb-1 uppercase tracking-wider">
                            Katalog Terkait (Pelatihan / Jasa)
                        </label>
                        <select id="catalog_item_id" name="catalog_item_id"
                                class="w-full px-3 py-2 text-xs rounded-lg input-k3 border-grid bg-regulatory-800 focus:outline-none focus:ring-2 focus:ring-accent">
                            <option value="">-- Tidak Ditautkan ke Katalog Tertentu --</option>
                            @foreach($catalogItems as $item)
                                <option value="{{ $item->id }}"
                                        {{ old('catalog_item_id', $article->catalog_item_id) == $item->id ? 'selected' : '' }}>
                                    {{ $item->name }} ({{ ucfirst($item->category) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Ringkasan / Excerpt --}}
                    <div>
                        <label for="excerpt" class="block text-xs font-bold text-regulatory-200 mb-1 uppercase tracking-wider">
                            Ringkasan / Excerpt
                        </label>
                        <textarea
                            id="excerpt"
                            name="excerpt"
                            rows="2"
                            placeholder="Ringkasan eksekutif 2-3 kalimat mengenai pokok bahasan artikel..."
                            class="w-full px-3.5 py-2 text-xs rounded-lg input-k3 border-grid focus:outline-none focus:ring-2 focus:ring-accent"
                        >{{ old('excerpt', $article->excerpt) }}</textarea>
                    </div>

                    {{-- Featured Image Upload --}}
                    <div>
                        <label for="featured_image" class="block text-xs font-bold text-regulatory-200 mb-1 uppercase tracking-wider">
                            Gambar Unggulan (Featured Image)
                        </label>

                        @if(!empty($article->featured_image))
                            <div class="mb-3 p-3 bg-regulatory-800 rounded-xl border border-grid flex items-center gap-3">
                                <img src="{{ asset($article->featured_image) }}" alt="{{ $article->title }}"
                                     class="w-20 h-14 object-cover rounded-lg border border-grid shrink-0">
                                <div class="text-xs">
                                    <span class="font-bold text-regulatory-100 block">Gambar saat ini</span>
                                    <span class="text-muted text-[11px] break-all">{{ $article->featured_image }}</span>
                                    <p class="text-[10px] text-muted mt-0.5">Unggah file baru di bawah jika ingin mengganti gambar.</p>
                                </div>
                            </div>
                        @endif

                        <input
                            type="file"
                            id="featured_image"
                            name="featured_image"
                            accept="image/jpeg,image/png,image/webp"
                            class="w-full text-xs text-muted file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-regulatory-800 file:text-accent hover:file:bg-regulatory-700 file:transition cursor-pointer"
                        >
                        <p class="text-[11px] text-muted mt-1">
                            Format diizinkan: JPG, PNG, WebP (Maks. 5MB). SVG ditolak demi keamanan.
                        </p>
                    </div>

                    {{-- Jodit Editor --}}
                    <div>
                        <label for="content_editor" class="block text-xs font-bold text-regulatory-200 mb-1 uppercase tracking-wider">
                            Konten Artikel (HTML) *
                        </label>
                        <textarea id="content_editor" name="content">{{ old('content', $article->content) }}</textarea>
                    </div>

                    {{-- FAQ Editor --}}
                    <div class="border-t border-grid pt-4 space-y-3">
                        <span class="text-xs font-bold text-regulatory-200 block uppercase tracking-wider">
                            FAQ (Pertanyaan &amp; Jawaban)
                        </span>
                        <p class="text-[11px] text-muted -mt-1">Satu pertanyaan/jawaban per baris, urutan harus sama.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="faq_questions" class="block text-[11px] text-muted mb-1">Pertanyaan</label>
                                <textarea
                                    id="faq_questions"
                                    name="faq_questions"
                                    rows="5"
                                    placeholder="Apa itu K3?&#10;Siapa yang wajib ikut pelatihan?&#10;..."
                                    class="w-full px-3 py-2 text-xs font-mono rounded-lg input-k3 border-grid focus:outline-none focus:ring-2 focus:ring-accent"
                                >{{ old('faq_questions', collect(old('faqs', $article->faqs ?? []))->pluck('question')->implode("\n")) }}</textarea>
                            </div>
                            <div>
                                <label for="faq_answers" class="block text-[11px] text-muted mb-1">Jawaban (urut sama)</label>
                                <textarea
                                    id="faq_answers"
                                    name="faq_answers"
                                    rows="5"
                                    placeholder="K3 adalah singkatan dari...&#10;Semua pekerja di sektor...&#10;..."
                                    class="w-full px-3 py-2 text-xs font-mono rounded-lg input-k3 border-grid focus:outline-none focus:ring-2 focus:ring-accent"
                                >{{ old('faq_answers', collect(old('faqs', $article->faqs ?? []))->pluck('answer')->implode("\n")) }}</textarea>
                            </div>
                        </div>
                        <p class="text-[10px] text-muted">Kosongkan untuk tidak mengubah FAQ yang sudah ada (jika ada).</p>
                    </div>

                    {{-- SEO Fields --}}
                    <div class="border-t border-grid pt-4 space-y-3">
                        <span class="text-xs font-bold text-regulatory-200 block uppercase tracking-wider">
                            Optimasi Mesin Pencari (SEO)
                        </span>
                        <div>
                            <label for="seo_title" class="block text-[11px] text-muted mb-0.5">SEO Title (Max 60 Karakter)</label>
                            <input
                                type="text"
                                id="seo_title"
                                name="seo_title"
                                value="{{ old('seo_title', $article->seo_title) }}"
                                class="w-full px-3 py-1.5 text-xs rounded-lg input-k3 border-grid focus:outline-none focus:ring-2 focus:ring-accent"
                            >
                        </div>
                        <div>
                            <label for="meta_description" class="block text-[11px] text-muted mb-0.5">Meta Description (Max 155 Karakter)</label>
                            <textarea
                                id="meta_description"
                                name="meta_description"
                                rows="2"
                                class="w-full px-3 py-1.5 text-xs rounded-lg input-k3 border-grid focus:outline-none focus:ring-2 focus:ring-accent"
                            >{{ old('meta_description', $article->meta_description) }}</textarea>
                        </div>
                    </div>

                    {{-- Status & Submit --}}
                    <div class="border-t border-grid pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <label for="status" class="block text-xs font-bold text-regulatory-200 mb-1 uppercase tracking-wider">
                                Status Publikasi
                            </label>
                            <select id="status" name="status" class="px-3 py-1.5 text-xs rounded-lg input-k3 border-grid bg-regulatory-800">
                                <option value="draft" {{ old('status', $article->status) === 'draft' ? 'selected' : '' }}>Draft (Belum Publik)</option>
                                <option value="published" {{ old('status', $article->status) === 'published' ? 'selected' : '' }}>Published (Tayang)</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="submit"
                                name="action"
                                value="draft"
                                onclick="document.getElementById('status').value='draft'"
                                class="px-4 py-2 text-xs font-bold text-regulatory-200 bg-regulatory-800 border border-grid hover:bg-regulatory-700 rounded-lg transition cursor-pointer"
                            >
                                Simpan Draf
                            </button>
                            <button
                                type="submit"
                                name="action"
                                value="published"
                                onclick="document.getElementById('status').value='published'"
                                class="btn-primary text-xs shadow-sm transition cursor-pointer"
                            >
                                Perbarui &amp; Publikasikan
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Delete Article --}}
            <div class="card-k3 p-4 border-red-900/40 bg-red-950/10">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-red-400 block uppercase tracking-wider">Zona Berbahaya</span>
                        <p class="text-[11px] text-muted mt-0.5">Tindakan ini tidak dapat dibatalkan. Artikel akan dihapus secara permanen.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.articles.destroy', $article->id) }}"
                          onsubmit="return confirm('Yakin ingin menghapus artikel ini secara permanen? Tindakan ini tidak dapat dibatalkan.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 text-xs font-bold text-red-300 bg-red-950/40 border border-red-700/50 hover:bg-red-900/50 rounded-lg transition cursor-pointer">
                            Hapus Artikel
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: Real-time Live Preview --}}
        <div class="lg:col-span-5 sticky top-24 space-y-4">
            <div class="card-k3 p-5 rounded-2xl border border-grid shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-grid pb-2">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-accent">
                        PREVIEW LANGSUNG (REAL-TIME)
                    </span>
                    <span class="text-[10px] text-muted bg-regulatory-800 px-2 py-0.5 rounded-full border border-grid">
                        Live Preview
                    </span>
                </div>

                {{-- Featured Image Preview --}}
                <div id="preview-image-box"
                     class="{{ !empty($article->featured_image) ? '' : 'hidden' }} rounded-xl overflow-hidden border border-grid max-h-48 bg-regulatory-950">
                    <img id="preview-image-tag"
                         src="{{ !empty($article->featured_image) ? asset($article->featured_image) : '' }}"
                         alt="Featured preview"
                         class="w-full h-44 object-cover">
                </div>

                {{-- Title & Excerpt Preview --}}
                <div class="space-y-2">
                    <h2 id="preview-title" class="font-bold text-lg text-regulatory-100 leading-snug">
                        {{ $article->title ?: '(Judul artikel akan muncul di sini)' }}
                    </h2>
                    <p id="preview-excerpt"
                       class="text-xs text-regulatory-300 leading-relaxed italic bg-regulatory-800 p-3 rounded-lg border-l-4 border-accent">
                        {{ $article->excerpt ?: '(Ringkasan excerpt akan tampil di sini)' }}
                    </p>
                </div>

                <div class="h-px bg-grid"></div>

                {{-- Article Meta --}}
                <div class="flex items-center gap-3 text-[11px] text-muted">
                    <span>
                        📅 {{ $article->published_at?->translatedFormat('d M Y') ?? 'Belum dipublikasikan' }}
                    </span>
                    @if($article->reading_time)
                        <span>⏱ {{ $article->reading_time }} mnt baca</span>
                    @endif
                    @if($article->word_count)
                        <span>📝 {{ number_format($article->word_count) }} kata</span>
                    @endif
                </div>

                <div class="h-px bg-grid"></div>

                {{-- Content Body Preview --}}
                <div class="space-y-2">
                    <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">
                        Isi Konten Artikel
                    </span>
                    <div id="preview-content"
                         class="prose prose-invert max-w-none text-xs text-regulatory-200 leading-relaxed max-h-[500px] overflow-y-auto p-4 rounded-xl bg-regulatory-800 border border-grid">
                        {!! $article->content !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/jodit@4.1.16/es2021/jodit.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Jodit Editor
    const editor = Jodit.make('#content_editor', {
        placeholder: 'Tulis konten artikel edukasi atau panduan teknis di sini...',
        height: 480,
        toolbarAdaptive: false,
        theme: 'dark',
        uploader: {
            url: '{{ route('admin.articles.upload-image') }}',
            format: 'json',
            fieldName: 'image',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            isSuccess: function (resp) {
                return resp && resp.success === true;
            },
            process: function (resp) {
                return {
                    files: resp.files || [resp.url],
                    path: resp.url,
                    baseurl: '',
                    error: resp.message || '',
                    msg: resp.message || ''
                };
            },
            error: function (e) {
                alert('Gagal mengunggah gambar: ' + (e.message || JSON.stringify(e)));
            }
        }
    });

    // 2. Real-time Live Preview
    const titleInput        = document.getElementById('title');
    const excerptInput      = document.getElementById('excerpt');
    const featuredImageInput = document.getElementById('featured_image');

    const previewTitle    = document.getElementById('preview-title');
    const previewExcerpt  = document.getElementById('preview-excerpt');
    const previewContent  = document.getElementById('preview-content');
    const previewImageBox = document.getElementById('preview-image-box');
    const previewImageTag = document.getElementById('preview-image-tag');

    function syncLivePreview() {
        previewTitle.textContent   = titleInput.value.trim() || '(Judul artikel akan muncul di sini)';
        previewExcerpt.textContent = excerptInput.value.trim() || '(Ringkasan excerpt akan tampil di sini)';

        const html = editor.value;
        previewContent.innerHTML = (html && html.trim() !== '')
            ? html
            : '<p class="text-muted italic">Mulai mengetik untuk melihat pratinjau konten.</p>';
    }

    titleInput.addEventListener('input', syncLivePreview);
    excerptInput.addEventListener('input', syncLivePreview);
    editor.events.on('change', syncLivePreview);

    // Featured image preview via FileReader
    if (featuredImageInput) {
        featuredImageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImageTag.src = e.target.result;
                    previewImageBox.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 3. City Selector Helper
    function setupCitySelector(config) {
        const provinceSelect = document.getElementById(config.provinceSelectId);
        const searchInput    = document.getElementById(config.searchInputId);
        const citySelect     = document.getElementById(config.citySelectId);
        const clearBtn       = config.clearBtnId ? document.getElementById(config.clearBtnId) : null;
        const feedbackEl     = config.feedbackElId ? document.getElementById(config.feedbackElId) : null;

        if (!citySelect) { return { setSelected: function () {} }; }

        const defaultOption = citySelect.options[0]
            ? { value: citySelect.options[0].value, text: citySelect.options[0].textContent }
            : null;

        const allOptions = [];
        for (let i = (defaultOption ? 1 : 0); i < citySelect.options.length; i++) {
            const opt = citySelect.options[i];
            allOptions.push({
                value:       opt.value,
                text:        opt.textContent,
                id:          opt.getAttribute('data-id'),
                province:    (opt.getAttribute('data-province') || '').toLowerCase().trim(),
                name:        (opt.getAttribute('data-name') || opt.textContent).toLowerCase().trim(),
                rawProvince: opt.getAttribute('data-province') || '',
                rawName:     opt.getAttribute('data-name') || opt.textContent
            });
        }

        let selectedValue = citySelect.value;

        function applyFilter() {
            const selectedProv = provinceSelect ? provinceSelect.value.toLowerCase().trim() : '';
            const keyword      = searchInput ? searchInput.value.toLowerCase().trim() : '';

            if (clearBtn) { clearBtn.classList.toggle('hidden', !keyword); }

            const filtered = allOptions.filter(function (item) {
                const matchProv   = !selectedProv || item.province === selectedProv;
                const matchSearch = !keyword || item.name.includes(keyword) || item.text.toLowerCase().includes(keyword);
                return matchProv && matchSearch;
            });

            citySelect.innerHTML = '';
            if (defaultOption) {
                const defOpt = document.createElement('option');
                defOpt.value = defaultOption.value;
                defOpt.textContent = defaultOption.text;
                citySelect.appendChild(defOpt);
            }

            let hasSelectedInFiltered = false;
            filtered.forEach(function (item) {
                const opt = document.createElement('option');
                opt.value = item.value;
                opt.textContent = item.text;
                opt.setAttribute('data-id', item.id);
                opt.setAttribute('data-province', item.rawProvince);
                opt.setAttribute('data-name', item.rawName);
                if (item.value === selectedValue) {
                    opt.selected = true;
                    hasSelectedInFiltered = true;
                }
                citySelect.appendChild(opt);
            });

            if (selectedValue && !hasSelectedInFiltered) {
                const currentItem = allOptions.find(function (item) { return item.value === selectedValue; });
                if (currentItem) {
                    const preservedOpt = document.createElement('option');
                    preservedOpt.value = currentItem.value;
                    preservedOpt.textContent = currentItem.text + ' (Sedang Dipilih)';
                    preservedOpt.setAttribute('data-id', currentItem.id);
                    preservedOpt.setAttribute('data-province', currentItem.rawProvince);
                    preservedOpt.setAttribute('data-name', currentItem.rawName);
                    preservedOpt.selected = true;
                    citySelect.appendChild(preservedOpt);
                }
            }

            if (feedbackEl) {
                feedbackEl.textContent = (keyword || selectedProv)
                    ? 'Menampilkan ' + filtered.length + ' dari ' + allOptions.length + ' kota/kabupaten'
                    : 'Total ' + allOptions.length + ' kota/kabupaten terdaftar';
            }
        }

        citySelect.addEventListener('change', function () { selectedValue = this.value; });
        if (provinceSelect) { provinceSelect.addEventListener('change', applyFilter); }
        if (searchInput)    { searchInput.addEventListener('input', applyFilter); }
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                searchInput.value = '';
                applyFilter();
                searchInput.focus();
            });
        }

        applyFilter();

        return {
            setSelected: function (val) {
                selectedValue = val;
                applyFilter();
                citySelect.value = val;
            }
        };
    }

    // 4. Setup selectors
    const aiCitySelector = setupCitySelector({
        provinceSelectId: 'filter_ai_province',
        searchInputId:    'search_ai_city',
        citySelectId:     'ai_city_slug',
        clearBtnId:       'btn_clear_ai_search'
    });

    const targetCitySelector = setupCitySelector({
        provinceSelectId: 'filter_target_province',
        searchInputId:    'search_target_city',
        citySelectId:     'target_city_slug',
        clearBtnId:       'btn_clear_target_search',
        feedbackElId:     'target_city_feedback'
    });

    // 5. Kecamatan Loader (AI panel)
    const aiCitySelect         = document.getElementById('ai_city_slug');
    const aiDistrictSelect     = document.getElementById('ai_district_slug');
    const aiKecamatanFeedback  = document.getElementById('ai_kecamatan_feedback');

    async function loadKecamatanForAi(citySlug) {
        if (!citySlug) {
            aiDistrictSelect.innerHTML = '<option value="">-- Pilih Kota terlebih dahulu --</option>';
            aiDistrictSelect.disabled = true;
            aiKecamatanFeedback.textContent = '';
            return;
        }

        aiDistrictSelect.innerHTML = '<option value="">Memuat kecamatan...</option>';
        aiDistrictSelect.disabled = true;

        try {
            const url = '{{ route('admin.cities.kecamatans', ['city' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', citySlug);
            const list = await fetch(url, { headers: { 'Accept': 'application/json' } }).then(r => r.json());

            aiDistrictSelect.innerHTML = '<option value="">-- Semua Kecamatan (Cakup Penuh Kota) --</option>';
            list.forEach(function (k) {
                const opt = document.createElement('option');
                opt.value = k.slug || k.name.toLowerCase().replace(/[^a-z0-9]+/g, '-');
                opt.setAttribute('data-id', k.id);
                opt.textContent = k.name;
                aiDistrictSelect.appendChild(opt);
            });
            aiDistrictSelect.disabled = false;
            aiKecamatanFeedback.textContent = list.length + ' kecamatan terdaftar';
        } catch (e) {
            aiDistrictSelect.innerHTML = '<option value="">Gagal memuat data kecamatan</option>';
            aiDistrictSelect.disabled = true;
        }
    }

    aiCitySelect.addEventListener('change', function () { loadKecamatanForAi(this.value); });

    // Initialize kecamatan if city already selected in AI panel
    if (aiCitySelect.value) {
        loadKecamatanForAi(aiCitySelect.value);
    }

    // 6. Kecamatan Loader (Form / Wilayah panel)
    const targetCitySelect        = document.getElementById('target_city_slug');
    const formKecamatanSelect     = document.getElementById('form_kecamatan_id');
    const formKecamatanFeedback   = document.getElementById('form_kecamatan_feedback');
    const hiddenKecamatanId       = document.getElementById('hidden_kecamatan_id');
    const currentKecamatanId      = '{{ $article->kecamatan_id }}';

    async function loadKecamatanForForm(citySlug) {
        if (!citySlug) {
            formKecamatanSelect.innerHTML = '<option value="">-- Semua Kecamatan (Cakup Penuh Kota) --</option>';
            formKecamatanSelect.disabled = true;
            formKecamatanFeedback.textContent = '';
            hiddenKecamatanId.value = '';
            return;
        }

        formKecamatanSelect.innerHTML = '<option value="">Memuat kecamatan...</option>';
        formKecamatanSelect.disabled = true;

        try {
            const url = '{{ route('admin.cities.kecamatans', ['city' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', citySlug);
            const list = await fetch(url, { headers: { 'Accept': 'application/json' } }).then(r => r.json());

            formKecamatanSelect.innerHTML = '<option value="">-- Semua Kecamatan (Cakup Penuh Kota) --</option>';
            list.forEach(function (k) {
                const opt = document.createElement('option');
                opt.value = k.id;
                opt.textContent = k.name;
                if (String(k.id) === String(currentKecamatanId)) {
                    opt.selected = true;
                }
                formKecamatanSelect.appendChild(opt);
            });
            formKecamatanSelect.disabled = false;
            formKecamatanFeedback.textContent = list.length + ' kecamatan terdaftar';
        } catch (e) {
            formKecamatanSelect.innerHTML = '<option value="">Gagal memuat kecamatan</option>';
            formKecamatanSelect.disabled = true;
        }
    }

    targetCitySelect.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const cityId = selectedOption ? selectedOption.getAttribute('data-id') : '';
        document.getElementById('hidden_city_id').value = cityId || '';
        hiddenKecamatanId.value = '';

        // Use slug for kecamatan API
        loadKecamatanForForm(this.value);
    });

    formKecamatanSelect.addEventListener('change', function () {
        hiddenKecamatanId.value = this.value;
    });

    // Initialize kecamatan if city already selected in form
    @if($article->city?->slug)
        loadKecamatanForForm('{{ $article->city->slug }}');
    @endif

    // 7. Form submit sync
    const form = document.getElementById('article-form');
    if (form) {
        form.addEventListener('submit', function () {
            if (editor) {
                document.getElementById('content_editor').value = editor.value;
            }
            // Sync city_id from target_city_slug selected option
            const selCity = document.getElementById('target_city_slug');
            const cityOpt = selCity.options[selCity.selectedIndex];
            if (cityOpt && cityOpt.dataset.id) {
                document.getElementById('hidden_city_id').value = cityOpt.dataset.id;
            }
            // Sync kecamatan_id
            const selKec = document.getElementById('form_kecamatan_id');
            if (selKec) {
                hiddenKecamatanId.value = selKec.value;
            }
        });
    }

    // 8. Ollama AI Regenerator
    const btnGenerate        = document.getElementById('btn-generate-ai');
    const spinner            = document.getElementById('ai-spinner');
    const btnText            = document.getElementById('ai-btn-text');
    const errorBox           = document.getElementById('ai-error-box');

    const imageBriefContainer = document.getElementById('image-brief-container');
    const briefConcept        = document.getElementById('brief-concept');
    const briefAlt            = document.getElementById('brief-alt');
    const briefEnglishPrompt  = document.getElementById('brief-english-prompt');
    const hiddenImageBrief    = document.getElementById('hidden_image_brief');
    const hiddenFaqs          = document.getElementById('hidden_faqs');

    let currentCitySlug = aiCitySelect.value || null;

    function populateForm(data) {
        titleInput.value   = data.title || '';
        excerptInput.value = data.excerpt || '';

        if (data.seo_title) {
            document.getElementById('seo_title').value = data.seo_title;
        }
        if (data.meta_description) {
            document.getElementById('meta_description').value = data.meta_description;
        }

        editor.value = data.content || '';
        document.getElementById('content_editor').value = data.content || '';

        if (data.image_brief) {
            briefConcept.textContent = data.image_brief.concept || '-';
            briefAlt.textContent     = data.image_brief.recommended_alt || '-';
            briefEnglishPrompt.value = data.image_brief.english_prompt || '';
            imageBriefContainer.classList.remove('hidden');
            hiddenImageBrief.value = JSON.stringify(data.image_brief);
        }

        if (data.suggested_faqs || data.faqs) {
            const faqs = data.suggested_faqs || data.faqs;
            hiddenFaqs.value = JSON.stringify(faqs);
            // Also populate FAQ textareas
            document.getElementById('faq_questions').value = faqs.map(f => f.question).join('\n');
            document.getElementById('faq_answers').value   = faqs.map(f => f.answer).join('\n');
        }

        if (currentCitySlug) {
            targetCitySelector.setSelected(currentCitySlug);
        }

        syncLivePreview();
    }

    btnGenerate.addEventListener('click', async function () {
        const citySlug    = aiCitySelect.value;
        const distSlug    = aiDistrictSelect.value;
        const serviceSlug = document.getElementById('ai_service_slug').value;
        const wordCount   = parseInt(document.getElementById('ai_word_count').value, 10);
        const customTopic = document.getElementById('ai_topic').value.trim();

        if (!citySlug && !serviceSlug && !customTopic) {
            alert('Silakan pilih Wilayah Target, Katalog, atau masukkan Topik Khusus terlebih dahulu.');
            aiCitySelect.focus();
            return;
        }

        if (isNaN(wordCount) || wordCount < 6000 || wordCount > 10000) {
            alert('Target jumlah kata harus antara 6.000 dan 10.000.');
            document.getElementById('ai_word_count').focus();
            return;
        }

        if (!confirm('Regenerasi akan menimpa judul, konten, dan excerpt di editor saat ini. Lanjutkan?')) {
            return;
        }

        currentCitySlug = citySlug;

        btnGenerate.disabled = true;
        spinner.classList.remove('hidden');
        btnText.textContent = 'Menghasilkan dengan Ollama...';
        errorBox.classList.add('hidden');

        try {
            const res = await fetch('{{ route('admin.articles.ai-generate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    city_slug:    citySlug || null,
                    district_slug: distSlug || null,
                    service_slug: serviceSlug || null,
                    word_count:   wordCount,
                    topic:        customTopic || null
                })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                errorBox.textContent = data.error || data.message || 'Gagal menghasilkan artikel dari Ollama.';
                errorBox.classList.remove('hidden');
                return;
            }

            populateForm(data);
            alert('Artikel berhasil diregenerasi oleh Ollama AI. Tinjau draf pada editor dan live preview, lalu klik Simpan.');

        } catch (err) {
            errorBox.textContent = 'Terjadi kesalahan koneksi atau jaringan: ' + err.message;
            errorBox.classList.remove('hidden');
        } finally {
            btnGenerate.disabled = false;
            spinner.classList.add('hidden');
            btnText.textContent = 'Regenerate dengan AI';
        }
    });

    // 9. Copy Prompt Button
    const btnCopyPrompt = document.getElementById('btn-copy-prompt');
    const copyText      = document.getElementById('copy-text');

    if (btnCopyPrompt && briefEnglishPrompt) {
        btnCopyPrompt.addEventListener('click', function () {
            if (!briefEnglishPrompt.value) { return; }

            navigator.clipboard.writeText(briefEnglishPrompt.value).then(function () {
                copyText.textContent = 'Berhasil Disalin!';
                setTimeout(function () { copyText.textContent = 'Salin Prompt Google Flow'; }, 2000);
            }).catch(function () {
                briefEnglishPrompt.select();
                document.execCommand('copy');
                copyText.textContent = 'Berhasil Disalin!';
                setTimeout(function () { copyText.textContent = 'Salin Prompt Google Flow'; }, 2000);
            });
        });
    }

    syncLivePreview();
});
</script>
@endpush
