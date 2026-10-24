@extends('admin.layout')

@section('title', 'Katalog')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="font-display text-3xl font-bold text-fg">Katalog ({{ $items->total() }})</h1>
        <a href="{{ route('admin.catalog.create') }}" class="btn-primary">+ Item baru</a>
    </div>

    <div class="mt-4 card-k3 rounded-xl border border-grid shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-regulatory-200">
                <thead class="bg-regulatory-800 border-b border-grid text-[11px] font-bold text-muted uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">Nama</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4">Slug</th>
                        <th class="py-3 px-4">Artikel</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-grid">
                    @forelse ($items as $item)
                        <tr class="hover:bg-regulatory-800/60 transition">
                            <td class="py-3.5 px-4 font-mono text-xs text-dim whitespace-nowrap">{{ $item->category }}/{{ $item->source_order }}</td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="font-bold text-sm text-regulatory-100 mb-0.5">
                                    {{ $item->name }}
                                </div>
                                @if ($item->review_required)
                                    <span class="rounded bg-amber-950/60 border border-amber-700/50 px-1.5 text-[10px] font-semibold text-amber-300">review</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-muted">{{ $item->category }}</td>
                            <td class="py-3.5 px-4 text-xs text-dim font-mono truncate max-w-[160px]">{{ $item->slug }}</td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-muted">{{ $item->articles_count }}</td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a class="px-2.5 py-1 text-[11px] font-semibold text-accent hover:bg-regulatory-700 rounded-lg transition" href="{{ route('admin.catalog.edit', $item) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.catalog.destroy', $item) }}" class="inline" onsubmit="return confirm('Hapus item katalog?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-2.5 py-1 text-[11px] font-semibold text-red-400 hover:text-red-300 hover:bg-red-950/30 rounded-lg transition cursor-pointer">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-muted">
                                <p class="text-sm">Katalog kosong — jalankan <code class="text-accent-bright">php artisan catalog:import</code>.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="p-4 border-t border-grid">
                {{ $items->links() }}
            </div>
        @endif
    </div>
@endsection
