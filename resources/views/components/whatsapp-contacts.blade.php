@props(['callout', 'title' => 'Hubungi tim kami'])
@if (! empty($callout))
    <section class="card-k3 p-6" aria-labelledby="wa-contacts-heading">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-grid pb-3">
            <h2 id="wa-contacts-heading" class="label-caps flex items-center gap-2 text-xs uppercase text-fg">
                <span class="inline-block h-2.5 w-2.5 bg-accent-bright" aria-hidden="true"></span>
                {{ $title }}
            </h2>
            <span class="label-caps text-[11px] text-accent-bright">KIRIM PESAN LANGSUNG</span>
        </div>
        <ul class="flex flex-wrap gap-3">
            @foreach ($callout as $index => $contact)
                <li>
                    <a href="{{ $contact['href'] }}" rel="noopener"
                       class="btn-whatsapp py-2 !px-4 text-xs">
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5 14.2c-.2.6-1.2 1.2-1.7 1.2-.4.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.5a10.6 10.6 0 0 1-4-3.6c-.3-.5-.9-1.4-.7-2.3.1-.5.5-.9.8-1.1.2-.2.4-.2.5-.2h.5c.2 0 .3 0 .5.4l.7 1.6c.1.2.1.4 0 .5l-.4.6c-.1.2-.3.4-.1.6.5.9 1 1.3 2 1.8.3.2.5.1.7-.1l.6-.7c.2-.2.3-.2.6-.1l1.6.8c.3.1.4.3.3.5 0 0 0 .6-.2 1Z"/></svg>
                        {{ $contact['raw'] }}
                    </a>
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-[11px] text-dim">Empat kanal resmi pertanyaan, penawaran, dan konsultasi. Pesan dibuka dengan sapaan otomatis — silakan ubah sesuai kebutuhan Anda.</p>
    </section>
@endif
