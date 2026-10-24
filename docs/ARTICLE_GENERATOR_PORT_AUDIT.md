# Article Generator and Editor Port Audit
**Source Project (Read-Only):** `/root/trainingahlik3umum`  
**Target Project:** `/root/varilevke`  
**Audit Date:** 2026-10-24  

---

## 1. Overview and Primary Objective

The objective of this port is to faithfully reproduce the article generation and rich-text editing subsystem from the reference project `Training Ahli K3 Umum` (`/root/trainingahlik3umum`) into `Varilevke` (`/root/varilevke`), adapting it to Varilevke's architecture, regulatory dark design system, 272-item catalog (227 training + 45 service items), relational BPS regional dataset, strict factual constraints, and word-count requirements (6,000–10,000 words, default 8,000 words).

---

## 2. Source-to-Target File Mapping

| Subsystem Component | Source File (`/root/trainingahlik3umum`) | Existing Target File (`/root/varilevke`) | Port Action / Strategy |
| :--- | :--- | :--- | :--- |
| **Ollama Service** | `app/Services/OllamaService.php` | `app/Services/Ai/AiClient.php`<br>`app/Services/Ai/ArticleGeneratorService.php` | Port `OllamaService` into `app/Services/OllamaService.php`. Adapt to support configured Ollama endpoint, model, timeout, bounded retries, chunking/continuation for 6,000–10,000 words, strict JSON output, and factual guardrails. |
| **Article Prompting & Profiles** | Embedded in `app/Services/OllamaService.php` | `app/Services/Catalog/Content/TrainingContentBuilder.php`<br>`app/Services/Catalog/Content/ServiceContentBuilder.php` | Extract prompt construction into a dedicated structured system separating Training ("pelatihan"), Service ("jasa"), and Regional articles. Hook into Varilevke's official 272 catalog items and BPS hierarchy. |
| **Admin Controller** | `app/Http/Controllers/Admin/ArticleController.php` | `app/Http/Controllers/Admin/ArticleAdminController.php` | Adapt `ArticleAdminController.php` to handle synchronous draft generation (`ai-generate`/`generate-preview`), image upload via `ImageUploadService`, and explicit create/edit draft/publish persistence with server-side validation and sanitization. |
| **Image Upload Service** | `app/Services/ImageUploadService.php` | Direct `Storage::storeAs` in `ArticleAdminController` | Port `ImageUploadService.php` to `app/Services/ImageUploadService.php` with 5MB cap, MIME check (JPG, PNG, WebP), SVG rejection, `getimagesize` verification, `.htaccess` execution blocking. |
| **HTML Sanitizer** | `app/Services/HtmlSanitizerService.php` | `app/Services/HtmlSanitizerService.php` | Varilevke's sanitizer is already present with an allowlist and `.article-table-scroll` wrapper. Preserve and ensure it handles all rich-text tags produced by Jodit. |
| **WhatsApp Contacts** | `app/Support/WhatsApp.php`<br>`config/services.php` (`whatsapp.callout_contacts`) | `app/Support/WhatsApp.php`<br>`config/contacts.php` | Varilevke already centralizes the four business numbers in `config/contacts.php`: `08118500177`, `081399810272`, `087894580806`, `087894580649`. Callouts will use `WhatsApp::numbers()`. |
| **Editor Views (Create)** | `resources/views/admin/articles/create.blade.php` | `resources/views/admin/articles/create.blade.php` | Align with source workflow: Jodit rich text editor (v4.1.16), real-time live preview, Google Flow image brief container with copy button, province filter, city search, kecamatan selector, and catalog selector. |
| **Editor Views (Edit)** | `resources/views/admin/articles/edit.blade.php` | `resources/views/admin/articles/edit.blade.php`<br>`resources/views/admin/articles/_form.blade.php` | Port full edit workflow: replace bare textarea with Jodit editor, real-time live preview, Google Flow brief display, featured image thumbnail/preview, and status switcher. |
| **Editor Views (Index)** | `resources/views/admin/articles/index.blade.php` | `resources/views/admin/articles/index.blade.php` | Ensure search, status filter, catalog display, word count display, view/edit/delete actions are consistent. |
| **Article Model & Schema** | `app/Models/Article.php`<br>`database/migrations/2026_10_15_000002_create_articles_table.php` | `app/Models/Article.php`<br>`database/migrations/2026_10_23_150448_create_articles_table.php` | Varilevke uses `catalog_item_id`, `city_id`, `kecamatan_id`, `word_count`, `reading_time`. Add `image_brief` JSON column via non-destructive migration so Google Flow prompts persist. |
| **Configuration** | `config/services.php` | `config/services.php`<br>`.env.example` | Add `services.ollama` config (`base_url`, `model`, `key`, `timeout`, `max_attempts`) with safe placeholders in `.env.example`. |
| **Unit & Feature Tests** | `tests/Unit/OllamaServiceTest.php`<br>`tests/Unit/OllamaPromptTest.php`<br>`tests/Feature/AiGenerateArticleTest.php` | `tests/Feature/ArticleGeneratePreviewTest.php`<br>`tests/Feature/ArticleAdminCrudTest.php` | Add comprehensive unit and feature tests covering Ollama service mocked responses, prompt categorization, word-count constraints, sanitization, image uploads, and draft persistence. |

---

## 3. Detailed Component Audit

### 3.1 OllamaService & Support Classes
* **Source (`trainingahlik3umum`):**
  - Uses direct HTTP requests to Ollama's `/api/chat` endpoint with `messages: [system, user]`, `stream: false`.
  - Continuation loop (`requestContinuation`) bounded by 8 attempts when word count is below 6,000 words.
  - JSON repair helpers: `repairJsonEscapes()` handles LaTeX backslashes and LaTeX math environments, `extractJsonObject()` finds balanced braces, and trailing comma regex repair.
  - WhatsApp CTA injection: Injects 5 callouts evenly spaced across `<p>` tags.
  - **Identified Weaknesses to Fix in Varilevke:**
    1. If JSON decode completely failed, the source returned fallback text with NL2BR as content, marking `success => true`. The prompt requires: **"Invalid structured output must fail explicitly; never return success with raw malformed model text."**
    2. Hardcoded word limits were 6,000–9,000 in source. Varilevke requires **6,000–10,000 words (default 8,000)**.
    3. Topic list was specific to "Training K3 Listrik" (22 hardcoded topics). Varilevke has **227 training items and 45 service items** across diverse technical, environmental, civil, and management domains.
    4. HTTP timeout was fixed to 60s which can time out on long generations; needs configurable timeout and bounded retries on transient errors (5xx, timeouts), without retrying 4xx client errors.

### 3.2 Prompting System & Factual Constraints
* **Source:** Focused entirely on "Training K3 Listrik".
* **Target Requirement:**
  - Must strictly differentiate:
    1. **Training articles (`pelatihan`):** Target competencies, curriculum framework, practical field safety, participant roles, no fabricated duration/price/certificates.
    2. **Service articles (`jasa`):** Scope, deliverables, client prerequisites, dependencies, regulatory coordination, no guarantee of official approval.
    3. **Regional articles:** Contextualized to verified BPS province, city/regency, and kecamatan relationships without inventing local addresses, phone numbers, or local branch offices.
  - Prohibits fabricated certification bodies (e.g. Kemnaker, BNSP, ISO registrars unless verifiable), legal regulation numbers, schedules, or fake client testimonials.
  - Explicitly states when data is unavailable and directs questions to official WhatsApp consultation.

### 3.3 Rich-Text Editor & Preview Workflow
* **Source:**
  - Jodit v4.1.16 loaded via CDN.
  - Synchronous AI generate button makes `POST /admin/articles/ai-generate`.
  - JSON response populates title, slug, excerpt, content in Jodit, SEO fields, and image brief.
  - Real-time preview listens to input and Jodit change events (`editor.events.on('change')`).
  - Google Flow image brief displayed in a dedicated card with "Salin Prompt Google Flow" clipboard copy button.
  - Featured image preview via `FileReader`.
* **Target Adaptation:**
  - Preserve identical interactive behaviors and layout adapted to Varilevke's dark regulatory palette (`bg-regulatory-900`, `card-k3`, `input-k3`, `btn-primary`, etc.).
  - Ensure both `create.blade.php` and `edit.blade.php` share the full faithful Jodit editor and live preview capabilities.
  - Image upload route hooked to secure `ImageUploadService`.

### 3.4 Data Integrity & Database Schema
* **Source:** `target_city_slug`, `target_district_slug`, `image_brief` JSON column.
* **Target:** `catalog_item_id`, `city_id`, `kecamatan_id`, `word_count`, `reading_time`.
  - Add `image_brief` JSON column to `articles` table via migration.
  - Model `$fillable` and `$casts` updated accordingly.
  - Draft preview does not write to the database. Persistence occurs exclusively via authorized admin form submission (`store`/`update`).

---

## 4. Audit Conclusion & Implementation Plan

All components have been verified against the physical code in `/root/trainingahlik3umum` and `/root/varilevke`.
The port will proceed in structured, testable steps:
1. Database migration: Add nullable `image_brief` column to `articles` table and update `Article` model.
2. Port and adapt `ImageUploadService.php` with complete security constraints.
3. Configure `services.ollama` and `.env.example`.
4. Implement `OllamaService` with multi-category prompting (Training, Service, Regional), bounded chunking/continuation for 6,000–10,000 words, strict JSON validation, escape repairing, and WhatsApp CTA callouts.
5. Update `ArticleAdminController` and routes to support both `ai-generate` and `generate-preview` endpoints seamlessly with server validation.
6. Refactor and faithfully port `create.blade.php` and `edit.blade.php` with Jodit 4.1.16, real-time live preview, Google Flow prompt box, and regional/catalog selectors.
7. Add automated tests covering Ollama service, prompts, word-count validation, controller draft generation, and explicit persistence.
8. Run test suite, Pint formatting, asset builds, and document final report.
