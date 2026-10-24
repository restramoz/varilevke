@extends('admin.layout')

@section('title', 'Pengaturan')

@section('content')
    <h1 class="mb-4 font-display text-3xl font-bold text-fg">Pengaturan aplikasi</h1>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-2xl space-y-4 card-k3">
        @csrf

        @foreach ($settings as $key => $field)
            <div>
                <label class="block text-sm font-medium" for="{{ $key }}">{{ $field['label'] }}</label>

                @if ($key === 'article_default_word_count')
                    <input id="{{ $key }}" name="{{ $key }}" type="number" min="8000" max="10000"
                           value="{{ old($key, $field['value']) }}"
                           class="mt-1 w-40 input-k3 px-3 py-2">
                @elseif ($key === 'whatsapp_numbers')
                    <input id="{{ $key }}" name="{{ $key }}" value="{{ old($key, $field['value']) }}"
                           class="mt-1 w-full input-k3 px-3 py-2 font-mono text-sm">
                    <p class="mt-1 text-xs text-muted">Hanya nomor yang terdaftar di <code>config/contacts.php</code> yang diizinkan; nomor baru tidak bisa ditambah dari sini.</p>
                @else
                    <input id="{{ $key }}" name="{{ $key }}" value="{{ old($key, $field['value']) }}"
                           class="mt-1 w-full input-k3 px-3 py-2">
                @endif
            </div>
        @endforeach

        <div class="pt-2">
            <button class="rounded-lg bg-teal-600 px-5 py-2 font-semibold text-white hover:bg-teal-700">Simpan pengaturan</button>
        </div>
    </form>
@endsection
