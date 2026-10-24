@php($wordCountDefault = \App\Models\Setting::get('article_default_word_count', '8000'))

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <div class="card-k3">
            <div class="panel-k3">
                <h2 class="text-sm font-semibold text-fg">Generator AI (draf — TIDAK disimpan otomatis)</h2>
                @unless ($aiEnabled)
                    <p class="mt-1 text-xs text-caution">Nonaktif: <code>AI_API_KEY</code> belum diset.</p>
                @endunless
                <label class="mt-2 block text-xs font-medium" for="gen-topic">Tema</label>
                <input id="gen-topic" class="mt-1 w-full rounded input-k3 px-2 py-1.5 text-sm" value="{{ old('topic') }}">
                <label class="mt-2 block text-xs font-medium" for="gen-words">Target kata (8000–10000)</label>
                <input id="gen-words" type="number" min="8000" max="10000" value="{{ old('word_count', $wordCountDefault) }}"
                       class="mt-1 w-32 rounded input-k3 px-2 py-1.5 text-sm">
                <div class="mt-2 flex items-center gap-3">
                    <button type="button" id="gen-btn" class="rounded bg-amber-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-amber-600" {{ $aiEnabled ? '' : 'disabled' }}>Buat draf</button>
                    <button type="button" id="gen-preview-btn" class="hidden rounded border border-grid px-4 py-1.5 text-sm font-semibold text-fg hover:bg-grid-subtle" aria-expanded="false">Lihat Hasil</button>
                    <span id="gen-status" class="text-xs text-muted" role="status" aria-live="polite"></span>
                </div>

                {{-- HTML Preview Panel --}}
                <div id="gen-preview-panel" class="hidden mt-4 rounded-lg border border-grid bg-regulatory-900 p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted">Preview Hasil Generate</span>
                        <button type="button" id="gen-preview-close" class="text-xs text-muted hover:text-fg">✕ Tutup</button>
                    </div>
                    <div id="gen-preview-content"
                         class="prose prose-sm prose-invert max-w-none overflow-y-auto max-h-[60vh] text-fg [&_h2]:text-accent-bright [&_h3]:text-fg [&_a]:text-accent-bright [&_strong]:text-fg [&_ul]:list-disc [&_ol]:list-decimal [&_li]:ml-4">
                    </div>
                </div>
            </div>

            <label class="mt-4 block text-sm font-medium" for="title">Judul *</label>
            <input id="title" name="title" value="{{ old('title', $article->title ?? '') }}" required maxlength="255"
                   class="mt-1 w-full rounded-lg input-k3 px-3 py-2">

            <label class="mt-3 block text-sm font-medium" for="slug">Slug (kosongkan untuk otomatis)</label>
            <input id="slug" name="slug" value="{{ old('slug', $article->slug ?? '') }}"
                   pattern="[a-z0-9]+(-[a-z0-9]+)*" class="mt-1 w-full rounded-lg input-k3 px-3 py-2">

            <label class="mt-3 block text-sm font-medium" for="excerpt">Ringkasan *</label>
            <textarea id="excerpt" name="excerpt" rows="3" required maxlength="1000"
                      class="mt-1 w-full rounded-lg input-k3 px-3 py-2">{{ old('excerpt', $article->excerpt ?? '') }}</textarea>

            <label class="mt-3 block text-sm font-medium" for="content">Konten (HTML: p, h2–h6, ul/ol/li, strong, em, a, img, table…) *</label>
            <textarea id="content" name="content" rows="18" required
                      class="mt-1 w-full rounded-lg input-k3 px-3 py-2 font-mono text-sm">{{ old('content', $article->content ?? '') }}</textarea>
            <p class="mt-1 text-xs text-muted">Konten disanitasi server sebelum disimpan. Skrip/style/iframe/event handler dibuang otomatis.</p>

            <fieldset class="mt-4 rounded-lg border border-grid p-3">
                <legend class="px-1 text-sm font-medium">FAQ</legend>
                <label class="block text-xs font-medium" for="faq_questions">Pertanyaan (satu per baris)</label>
                <textarea id="faq_questions" name="faq_questions" rows="4"
                          class="mt-1 w-full rounded input-k3 px-2 py-1.5 font-mono text-xs">{{ old('faq_questions', collect(old('faqs', $article->faqs ?? []))->pluck('question')->implode("\n")) }}</textarea>
                <label class="mt-2 block text-xs font-medium" for="faq_answers">Jawaban (satu per baris, urut sama)</label>
                <textarea id="faq_answers" name="faq_answers" rows="4"
                          class="mt-1 w-full rounded input-k3 px-2 py-1.5 font-mono text-xs">{{ old('faq_answers', collect(old('faqs', $article->faqs ?? []))->pluck('answer')->implode("\n")) }}</textarea>
            </fieldset>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card-k3">
            <label class="block text-sm font-medium" for="status">Status</label>
            <select id="status" name="status" class="mt-1 w-full rounded-lg input-k3 px-3 py-2">
                <option value="draft" @selected(old('status', $article->status ?? 'draft') === 'draft')>draft</option>
                <option value="published" @selected(old('status', $article->status ?? '') === 'published')>published</option>
            </select>

            <label class="mt-3 block text-sm font-medium" for="seo_title">SEO title</label>
            <input id="seo_title" name="seo_title" value="{{ old('seo_title', $article->seo_title ?? '') }}" maxlength="255"
                   class="mt-1 w-full rounded-lg input-k3 px-3 py-2">

            <label class="mt-3 block text-sm font-medium" for="meta_description">Meta description</label>
            <input id="meta_description" name="meta_description" value="{{ old('meta_description', $article->meta_description ?? '') }}" maxlength="320"
                   class="mt-1 w-full rounded-lg input-k3 px-3 py-2">
        </div>

        <div class="card-k3">
            <label class="block text-sm font-medium" for="catalog_item_id">Katalog terkait</label>
            <select id="catalog_item_id" name="catalog_item_id" class="mt-1 w-full rounded-lg input-k3 px-3 py-2">
                <option value="">—</option>
                @foreach ($catalogItems as $catalogItem)
                    <option value="{{ $catalogItem->id }}" @selected(old('catalog_item_id', $article->catalog_item_id ?? '') == $catalogItem->id)>
                        {{ $catalogItem->name }} ({{ $catalogItem->category }})
                    </option>
                @endforeach
            </select>

            <label class="mt-3 block text-sm font-medium" for="city_id">Kota</label>
            <select id="city_id" name="city_id" class="mt-1 w-full rounded-lg input-k3 px-3 py-2">
                <option value="">—</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected(old('city_id', $article->city_id ?? '') == $city->id)>{{ $city->label() }} — {{ $city->province }}</option>
                @endforeach
            </select>

            <label class="mt-3 block text-sm font-medium" for="kecamatan_id">Kecamatan</label>
            <select id="kecamatan_id" name="kecamatan_id" class="mt-1 w-full rounded-lg input-k3 px-3 py-2">
                <option value="">—</option>
                @if (! empty($article->kecamatan_id))
                    <option value="{{ $article->kecamatan_id }}" selected>{{ optional($article->kecamatan)->name }}</option>
                @endif
            </select>
        </div>
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button class="rounded-lg bg-teal-600 px-6 py-2 font-semibold text-white hover:bg-teal-700">{{ $submitLabel }}</button>
    <a href="{{ route('admin.articles.index') }}" class="text-sm text-muted hover:underline">Batal</a>
</div>

<script>
(function () {
    const btn           = document.getElementById('gen-btn');
    const previewBtn    = document.getElementById('gen-preview-btn');
    const previewPanel  = document.getElementById('gen-preview-panel');
    const previewClose  = document.getElementById('gen-preview-close');
    const previewContent= document.getElementById('gen-preview-content');
    const status        = document.getElementById('gen-status');

    // Toggle preview panel
    previewBtn?.addEventListener('click', () => {
        const isHidden = previewPanel.classList.toggle('hidden');
        previewBtn.setAttribute('aria-expanded', String(!isHidden));
        previewBtn.textContent = isHidden ? 'Lihat Hasil' : 'Sembunyikan';
    });

    previewClose?.addEventListener('click', () => {
        previewPanel.classList.add('hidden');
        previewBtn.setAttribute('aria-expanded', 'false');
        previewBtn.textContent = 'Lihat Hasil';
    });

    btn?.addEventListener('click', async () => {
        const topic      = document.getElementById('gen-topic').value.trim();
        const words      = document.getElementById('gen-words').value;
        const catalog    = document.getElementById('catalog_item_id').value;
        const city       = document.getElementById('city_id').value;
        const kecamatan  = document.getElementById('kecamatan_id').value;

        if (!topic || topic.length < 5) {
            status.textContent = 'Tema minimal 5 karakter.';
            return;
        }

        status.textContent = 'Membuat draf (dapat memakan waktu beberapa menit)…';
        btn.disabled = true;
        previewBtn?.classList.add('hidden');

        try {
            const response = await fetch('{{ route('admin.articles.generate-preview') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    topic: topic,
                    word_count: words ? Number(words) : null,
                    catalog_item_id: catalog || null,
                    city_id: city || null,
                    kecamatan_id: kecamatan || null,
                }),
            });

            const payload = await response.json();

            if (!response.ok) {
                status.textContent = payload.error || 'Draf gagal dibuat.';
                return;
            }

            document.getElementById('title').value         = payload.draft.title;
            document.getElementById('excerpt').value       = payload.draft.excerpt;
            document.getElementById('content').value       = payload.draft.content;
            document.getElementById('faq_questions').value = payload.draft.faqs.map((f) => f.question).join('\n');
            document.getElementById('faq_answers').value   = payload.draft.faqs.map((f) => f.answer).join('\n');
            status.textContent = 'Draf terisi: ' + payload.draft.word_count + ' kata. Periksa, lalu klik simpan.';

            // Render HTML preview
            previewContent.innerHTML = payload.draft.content;
            previewPanel.classList.remove('hidden');
            previewBtn.classList.remove('hidden');
            previewBtn.textContent = 'Sembunyikan';
            previewBtn.setAttribute('aria-expanded', 'true');
        } catch (error) {
            status.textContent = 'Draf gagal: jaringan/kesalahan server.';
        } finally {
            btn.disabled = false;
        }
    });

    // Kecamatan dropdown follows the chosen city.
    document.getElementById('city_id')?.addEventListener('change', async (event) => {
        const cityId = event.target.value;
        const kecamatanSelect = document.getElementById('kecamatan_id');

        kecamatanSelect.innerHTML = '<option value="">—</option>';

        if (!cityId) {
            return;
        }

        const response = await fetch(
            '{{ route('admin.cities.kecamatans', ['city' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', cityId),
            { headers: { 'Accept': 'application/json' } }
        );
        const options = await response.json();

        for (const kecamatan of options) {
            kecamatanSelect.insertAdjacentHTML('beforeend',
                '<option value="' + kecamatan.id + '">' + kecamatan.name + '</option>');
        }
    });
})();
</script>
