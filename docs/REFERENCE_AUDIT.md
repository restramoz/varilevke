# Reference Project Audit

Read-only audit of two sibling Laravel projects. Both were inspected; **no files were
modified in either project**. Reused patterns are listed with their source.

## trainingahlik3umum

- Laravel ^13.17 / PHP ^8.3, custom Blade admin, no auth/admin/purifier/SEO packages.
- admin auth: `users.is_admin` bool + `app/Http/Middleware/EnsureUserIsAdmin.php`; login
  via `app/Http/Controllers/Auth/LoginController.php`; CLI admin creator
  `app/Console/Commands/CreateAdminCommand.php`; `throttle:5,1` on POST /login.
- article validation incl. `word_count min:6000|max:9000`.
- LLM: `app/Services/OllamaService.php` — Laravel `Http` client, `{base_url}/api/chat`
  (env `OLLAMA_*`), JSON parse with markdown-fence cleanup, bounded continuation loop.
- Generation = admin form Ajax fill (`resources/views/admin/articles/create.blade.php`),
  hidden fields; DB write only after manual save → draft-then-explicit-save workflow.
- sanitizer: hand-rolled `app/Services/HtmlSanitizerService.php` (DOMDocument, tag +
  attribute allowlist, strips event handlers/styles), applied at **input** before DB
  write; wraps `<table>` in a scroll container (`.article-table-scroll`).
- tests: `tests/Unit/HtmlSanitizerServiceTest.php`, `WhatsAppTest`, `OllamaServiceTest`,
  `ArticleWordCountTest`; sqlite in-memory in `phpunit.xml`.
- deployment: `docs/php-deployment-checklist.md` + storage symlink notes.

**Reused into Varilevke** (with the catalog/DB model differences noted):

| Pattern | Source file | Where used here |
| --- | --- | --- |
| `users.is_admin` + EnsureUserIsAdmin + throttled login | migrations 2026_10_15_000001, app/Http/Middleware/EnsureUserIsAdmin.php | `app/Http/Middleware/EnsureUserIsAdmin.php` |
| DOMDocument input-time sanitizer + table scroll wrap | app/Services/HtmlSanitizerService.php | `app/Services/HtmlSanitizerService.php` |
| WhatsApp 4-contact group as string config + `normalizePhone` 08→62 | app/Support/WhatsApp.php, config/services.php | `app/Support/WhatsApp.php`, `config/contacts.php` |
| Ollama-style env-configured provider (Http::timeout, JSON fence cleanup) | app/Services/OllamaService.php | `app/Services/ArticleGeneration/*` (chunked, strict-JSON, bounded retries) |
| Sitemap/robots as routes | SitemapController, routes/web.php | `app/Http/Controllers/SeoController.php` |

## trainingkota

- Laravel ^13.0 / PHP ^8.3, phpunit 11, Tailwind 3.4 + Alpine via CDN.
- **Not reused — insecure patterns, deliberately avoided:**
  - plain-text admin compare against env (config auth `ADMIN_PASSWORD` default
    `SuksesJaya2026` committed), custom session flags instead of the framework guard;
  - no server-side sanitization at all: AI output echoed `{!! $article->content !!}`
    (`resources/views/articles/show.blade.php`) → stored-XSS exposure;
  - `.env.example` ships a real-looking OLLAMA_API_KEY → never copy env values.
- Reused only as a caution list plus these ideas:
  - per-city/kecamatan landing URL structure (categories × city × service / kecamatan
    landings) — **simplified**: regional pages keyed off cities × kecamatan only, to
    avoid combinatorial doorway pages;
  - `reading_time = ceil(str_word_count(strip_tags(content)) / 200)` convention;
  - leading-zero WhatsApp handling (raw `08118500177` + display format + `wa.me/62…`).

## Data-sourcing decisions

- The four WhatsApp numbers, with leading zeros preserved as **strings**:
  `0811 8500 177 / 0813 9981 0272 / 0878 945 808 06 / 0878 945 806 49` come from
  trainingahlik3umum `app/Support/WhatsApp.php` (`callout_contacts`) and match the task
  spec verbatim → stored in `config/contacts.php`, displayed via `app/Support/WhatsApp.php`.
- Regional hierarchy source: BPS master wilayah JSON from the reference projects
  (public statistical data, not credentials). Varilevke seeds a bounded, curated subset
  per province — city pages stay useful/distinct, not a thin-page farm (`database/data/`).
