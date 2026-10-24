<x-app-layout :title="'Masuk Admin — '.config('app.name')">
    <div class="mx-auto max-w-sm">
        <h1 class="font-display text-3xl font-bold text-fg">Masuk Admin</h1>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-muted" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                       class="mt-1 w-full input-k3 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-muted" for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="mt-1 w-full input-k3 px-3 py-2">
            </div>
            <label class="flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="remember" class="rounded border-slate-300"> Ingat saya
            </label>
            <button class="w-full rounded-lg bg-teal-600 px-4 py-2 font-semibold text-white hover:bg-teal-700">Masuk</button>
        </form>
    </div>
</x-app-layout>
