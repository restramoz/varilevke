@extends('admin.layout')

@section('title', $item->exists ? 'Edit katalog' : 'Tambah katalog')

@section('content')
    <h1 class="mb-4 font-display text-3xl font-bold text-fg">{{ $item->exists ? 'Edit item katalog' : 'Tambah item katalog' }}</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($item->exists)
        <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
            Baris ini berasal dari dokumen sumber (urutan {{ $item->source_order }}). Nama berbeda dengan manifest akan dilaporkan
            konflik oleh <code>catalog:import</code>, bukan ditimpa.
        </p>
    @endif

    <form method="POST" action="{{ $item->exists ? route('admin.catalog.update', $item) : route('admin.catalog.store') }}"
          class="max-w-xl card-k3 p-4">
        @csrf
        @if ($item->exists)
            @method('PUT')
        @endif

        <label class="block text-sm font-medium" for="name">Nama *</label>
        <input id="name" name="name" value="{{ old('name', $item->name) }}" required maxlength="255"
               class="mt-1 w-full input-k3 px-3 py-2">

        <label class="mt-3 block text-sm font-medium" for="category">Kategori *</label>
        <select id="category" name="category" class="mt-1 w-full input-k3 px-3 py-2" {{ $item->exists ? 'disabled' : '' }}>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(old('category', $item->category) === $category)>{{ $category }}</option>
            @endforeach
        </select>
        @if ($item->exists)
            <input type="hidden" name="category" value="{{ old('category', $item->category) }}">
        @endif

        <label class="mt-3 flex items-center gap-2 text-sm">
            <input type="hidden" name="review_required" value="0">
            <input type="checkbox" name="review_required" value="1" class="rounded"
                   {{ old('review_required', $item->review_required) ? 'checked' : '' }}>
            Perlu konfirmasi klasifikasi
        </label>

        <label class="mt-3 block text-sm font-medium" for="review_note">Catatan review</label>
        <textarea id="review_note" name="review_note" rows="3" maxlength="1000"
                  class="mt-1 w-full input-k3">{{ old('review_note', $item->review_note) }}</textarea>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-teal-600 px-5 py-2 font-semibold text-white hover:bg-teal-700">Simpan</button>
            <a href="{{ route('admin.catalog.index') }}" class="text-sm text-muted hover:underline">Batal</a>
        </div>
    </form>
@endsection
