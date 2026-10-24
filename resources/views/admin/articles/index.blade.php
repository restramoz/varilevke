@extends('admin.layout')

@section('title', 'Manajemen Artikel — Admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-regulatory-100 tracking-tight">
                Manajemen Artikel
            </h1>
            <p class="text-xs text-muted mt-1">
                Kelola artikel edukasi pelatihan K3 dan layanan kepatuhan industri
            </p>
        </div>
        <a href="{{ route('admin.articles.create') }}" class="btn-primary text-xs shadow-sm inline-flex items-center gap-1.5 transition">
            <span>+ Buat Artikel Baru</span>
        </a>
    </div>

    <!-- Filters -->
    <div class="card-k3 p-4 rounded-xl border border-grid shadow-sm">
        <form method="GET" action="{{ route('admin.articles.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-6">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari judul atau slug..."
                    class="w-full px-3.5 py-2 text-xs rounded-lg input-k3 border-grid bg-regulatory-800 focus:bg-regulatory-700 focus:outline-none focus:ring-2 focus:ring-accent"
                >
            </div>
            <div class="sm:col-span-4">
                <select name="status" class="w-full px-3.5 py-2 text-xs rounded-lg input-k3 border-grid bg-regulatory-800 focus:bg-regulatory-700 focus:outline-none focus:ring-2 focus:ring-accent">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                </select>
            </div>
            <div class="sm:col-span-2 flex gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-regulatory-700 hover:bg-regulatory-600 text-accent text-xs font-bold rounded-lg border border-grid transition cursor-pointer">
                    Filter
                </button>
                @if(request()->hasAny(['q', 'status']))
                    <a href="{{ route('admin.articles.index') }}" class="px-3 py-2 bg-regulatory-800 text-muted hover:text-regulatory-200 text-xs font-semibold rounded-lg border border-grid transition flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card-k3 rounded-xl border border-grid shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-regulatory-200">
                <thead class="bg-regulatory-800 border-b border-grid text-[11px] font-bold text-muted uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Artikel</th>
                        <th class="py-3 px-4">Wilayah Target</th>
                        <th class="py-3 px-4">Katalog Terkait</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Jumlah Kata</th>
                        <th class="py-3 px-4">Tanggal Buat</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-grid">
                    @forelse($articles as $article)
                        <tr class="hover:bg-regulatory-800/60 transition">
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="font-bold text-sm text-regulatory-100 mb-0.5">
                                    <a href="{{ route('admin.articles.edit', $article->id) }}" class="hover:text-accent transition">
                                        {{ $article->title }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-muted font-mono truncate">
                                    /artikel/{{ $article->slug }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($article->city)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-teal-950/60 text-teal-300 border border-teal-800/50">
                                        {{ $article->city->label() }}
                                    </span>
                                @else
                                    <span class="text-[11px] text-muted">Nasional</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-muted">
                                @if($article->catalogItem)
                                    <span class="text-xs text-regulatory-200 font-medium">
                                        {{ $article->catalogItem->name }}
                                    </span>
                                    <span class="text-[10px] text-muted block uppercase">
                                        ({{ $article->catalogItem->category }})
                                    </span>
                                @else
                                    <span class="text-[11px] text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($article->status === 'published')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950/60 text-emerald-300 border border-emerald-700/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        Published
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-950/60 text-amber-300 border border-amber-700/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs text-muted">
                                {{ number_format($article->word_count) }} kata
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-muted">
                                {{ $article->created_at?->format('d M Y, H:i') ?? '—' }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    @if($article->status === 'published')
                                        <a href="{{ route('articles.show', $article->slug) }}" target="_blank" class="px-2.5 py-1 text-[11px] font-semibold text-accent hover:bg-regulatory-700 rounded-lg transition" title="Lihat di web publik">
                                            Lihat
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.articles.edit', $article->id) }}" class="px-2.5 py-1 text-[11px] font-semibold text-regulatory-200 hover:text-accent hover:bg-regulatory-700 rounded-lg transition">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.articles.destroy', $article->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus artikel ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold text-red-400 hover:text-red-300 hover:bg-red-950/30 rounded-lg transition cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-muted">
                                <div class="max-w-xs mx-auto space-y-3">
                                    <p class="text-sm">Belum ada artikel yang tersimpan.</p>
                                    <a href="{{ route('admin.articles.create') }}" class="btn-primary text-xs inline-block">
                                        Buat Artikel Pertama
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($articles->hasPages())
            <div class="p-4 border-t border-grid">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
