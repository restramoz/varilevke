<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-regulatory-900 text-fg antialiased">
<nav class="border-b border-grid bg-regulatory-800 text-fg">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3">
        <div class="flex flex-wrap items-center gap-4 text-sm">
            <a class="font-bold flex items-center gap-2" href="{{ route('admin.dashboard') }}">
                <span class="w-6 h-6 rounded bg-teal-600 text-white flex items-center justify-center text-xs font-bold">V</span>
                <span>Admin</span>
            </a>
            <a class="hover:text-accent-bright {{ request()->routeIs('admin.catalog.*') ? 'text-accent font-semibold' : '' }}" href="{{ route('admin.catalog.index') }}">Katalog</a>
            <a class="hover:text-accent-bright {{ request()->routeIs('admin.articles.*') ? 'text-accent font-semibold' : '' }}" href="{{ route('admin.articles.index') }}">Artikel</a>
            <a class="hover:text-accent-bright {{ request()->routeIs('admin.settings.*') ? 'text-accent font-semibold' : '' }}" href="{{ route('admin.settings.index') }}">Pengaturan</a>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <a class="hover:text-accent-bright flex items-center gap-1 text-xs" href="{{ route('home') }}" target="_blank">
                <span>Lihat situs</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
            </a>
            <span class="text-muted text-xs">{{ auth()->user()?->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-red-400 hover:text-red-300 text-xs cursor-pointer">Keluar</button>
            </form>
        </div>
    </div>
</nav>

<main class="mx-auto max-w-7xl px-4 py-8">
    @if (session('status'))
        <div class="mb-6 rounded-xl border border-teal-500/40 bg-teal-950/40 p-4 text-xs font-medium text-teal-300 flex items-center gap-2 shadow-xs">
            <svg class="w-4 h-4 text-teal-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-teal-500/40 bg-teal-950/40 p-4 text-xs font-medium text-teal-300 flex items-center gap-2 shadow-xs">
            <svg class="w-4 h-4 text-teal-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-700/50 bg-red-950/40 p-4 text-xs font-medium text-red-300 flex items-center gap-2 shadow-xs">
            <svg class="w-4 h-4 text-red-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-700/50 bg-red-950/40 p-4 text-xs text-red-300 space-y-2 shadow-xs">
            <div class="flex items-center gap-2 font-bold text-red-300">
                <svg class="w-4 h-4 text-red-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                <span>Terdapat kesalahan pengisian form:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 pl-6 text-regulatory-200">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer class="border-t border-grid py-4 text-center text-xs text-muted">
    {{ config('app.name') }} &mdash; Administrator Panel &copy; {{ date('Y') }}
</footer>

@stack('scripts')
</body>
</html>
