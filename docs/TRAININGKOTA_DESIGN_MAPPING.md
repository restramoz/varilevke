# TRAININGKOTA DESIGN MAPPING

Mapping between TrainingKota (reference, read-only) and Varilevke (target) design system.

---

## 1. COLOR TOKENS

### TrainingKota (tailwind.config.js + app.css)
```css
--background: #070D18        /* Main Background */
--panel: #0F2038             /* Primary Command Slate */
--workspace: #0B1526         /* Regulatory Slate 800 */
--accent: #0D7A5F            /* Primary Emerald */
--accent-bright: #10B981     /* Bright Emerald */
--caution: #D97706           /* Amber */
--caution-bright: #F59E0B    /* Bright Amber */
--link: #38BDF8              /* Sky Blue */
--whatsapp: #25D366          /* WhatsApp Green */
--text-primary: #F1F5F9      /* White */
--text-secondary: #94A3B8    /* Muted */
--text-tertiary: #64748B     /* Dim */
--border-grid: #1E324E       /* Border */
--border-subtle: #142338     /* Subtle Border */
```

### Varilevke (app.css @theme)
```css
--color-regulatory-900: #070d18
--color-regulatory-800: #0b1526
--color-regulatory-700: #0f2038
--color-grid: #1e324e
--color-grid-subtle: #142338
--color-accent: #0d7a5f
--color-accent-bright: #10b981
--color-caution: #d97706
--color-caution-bright: #f59e0b
--color-link: #38bdf8
--color-wa: #25d366
--color-fg: #f1f5f9
--color-muted: #94a3b8
--color-dim: #64748b
```
**Status**: ✅ Already aligned (same hex values, different naming)

---

## 2. TYPOGRAPHY

### TrainingKota
```css
--font-display: 'Space Grotesk', sans-serif
--font-body: 'IBM Plex Sans', sans-serif
```

### Varilevke
```css
--font-display: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif
--font-body: 'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif
```
**Status**: ✅ Already aligned

---

## 3. BORDER RADIUS — KEY DELIBERATE CHANGE

### TrainingKota (strict zero-radius industrial)
```css
/* tailwind.config.js */
borderRadius: {
    none: '0px', sm: '0px', DEFAULT: '0px', md: '0px', lg: '0px', xl: '0px', '2xl': '0px', '3xl': '0px', full: '0px',
}
/* app.css */
*, *::before, *::after { border-radius: 2px !important; }
```

### Varilevke Target (rounded treatment)
```css
--varilevke-radius: 18px  /* NEW centralized token */
```

**Apply to**: cards, panels, buttons, form controls, search fields, CTA surfaces, modals, dropdowns
**Exceptions**: badges (keep small/sharp), tables, code blocks, progress bars

---

## 4. COMPONENT MAPPING

| TrainingKota Component | Varilevke Current | Target (TrainingKota + 18px radius) |
|------------------------|-------------------|-------------------------------------|
| **Layout** | `app-layout.blade.php` | `layouts/app.blade.php` (TK structure) |
| **Header/Nav** | Top bar + sticky nav | TK: regulatory bar + sticky header with brand SVG |
| **Footer** | Simple 4-col | TK: 4-col with badges, city links, contact |
| **Buttons** | `.btn-primary`, `.btn-secondary`, `.btn-whatsapp` | TK styles + `rounded-[18px]` |
| **Badges** | `.badge-accent`, `.badge-caution`, etc. | TK styles (keep small, no radius change) |
| **Inputs** | `.input-k3` | TK styles + `rounded-[18px]` |
| **Panels** | `.panel-k3` | TK `.panel-k3` + `rounded-[18px]` |
| **Cards** | `.card-k3` | TK `.card-k3` + `rounded-[18px]` |
| **Catalog Grid** | Numbered `<ol>` list | **TK category.blade.php card grid** |
| **Service Detail** | `catalog/show.blade.php` | **TK service-detail.blade.php** structure |
| **City Landing** | `regional/city.blade.php` (basic) | **TK city-landing.blade.php** (full sections) |
| **Kecamatan Landing** | `regional/kecamatan.blade.php` (basic) | **TK kecamatan-landing.blade.php** (full sections) |
| **Homepage** | `home.blade.php` (2 pillars) | **TK home.blade.php** (hero, 3 pillars, city widget, FAQ) |
| **Category Page** | `catalog/index.blade.php` | **TK category.blade.php** (search, filter, cards, FAQ) |
| **Pagination** | Laravel default | TK `vendor/pagination/tailwind.blade.php` |

---

## 5. PAGE STRUCTURE MAPPING

### Homepage
| Section | TrainingKota | Varilevke Current | Action |
|---------|--------------|-------------------|--------|
| Regulatory Top Bar | ✅ | ❌ | Add |
| Hero + Inquiry Card | ✅ | Basic | Replace with TK hero |
| 3 Pilar Layanan | ✅ | 2 Pilar | Replace with 3 pillars |
| 514 Kota Widget | ✅ | ❌ | Add (adapt for all regions) |
| Advantage/About | ✅ | ❌ | Add |
| Articles | ✅ | ✅ | Keep, style to TK |

### Category (Catalog) Page
| Element | TrainingKota | Varilevke Current | Action |
|---------|--------------|-------------------|--------|
| Search/Filter Bar | ✅ Top | ✅ Bottom form | Move to top, TK style |
| Category Header | ✅ Badge + title + pills | Basic | Replace with TK header |
| Service Cards | ✅ Grid cards | Numbered list | **Replace with TK card grid** |
| "Show All" Toggle | ✅ | ❌ | Add |
| City Selector | ✅ | ❌ | Add |
| FAQ Accordion | ✅ 8 items | ❌ | Add |
| Q&A Accordion | ✅ 8 items | ❌ | Add |

### Service Detail Page
| Section | TrainingKota | Varilevke Current | Action |
|---------|--------------|-------------------|--------|
| Breadcrumbs | TK style | Basic | Use TK |
| Hero + Spec Box | ✅ | ✅ similar | Align to TK |
| Service Visual | ✅ Archetype component | ❌ | Add `service-visual.blade.php` |
| Category Content | Syllabus/Scope/Workflow | Content sections | Use TK archetype logic |
| Dynamic Schedules | ✅ (pelatihan) | ❌ | Add if data exists |
| FAQ (DB) | ✅ Alpine.js | ❌ | Add |
| Article Content | ✅ Full prose + TOC | ❌ | Add |
| Sidebar: WA Form | ✅ | ✅ similar | Align to TK |
| Sidebar: Related | ✅ | ✅ similar | Align to TK |

### City Landing Page
| Section | TrainingKota | Varilevke Current | Action |
|---------|--------------|-------------------|--------|
| Hero + Dispatch Card | ✅ | Basic | Replace with TK |
| Google Maps | ✅ | ❌ | Add (if city has coords) |
| Schedule Table | ✅ (pelatihan) | ❌ | Add |
| Service Grid | ✅ 4-col + show all | ❌ | Add TK grid |
| Related Articles | ✅ Image cards | List | Upgrade to TK cards |
| FAQ Accordion | ✅ Alpine.js 2-col | ❌ | Add |
| Other Cities | ✅ Island group | ❌ | Add |
| Kecamatan Cards | ✅ Grid with links | Basic list | Upgrade to TK cards |

### Kecamatan Landing Page
| Section | TrainingKota | Varilevke Current | Action |
|---------|--------------|-------------------|--------|
| Hero | ✅ | Basic | Replace |
| Service Visual | ✅ | ❌ | Add |
| Service Grid | ✅ 3-col + show all | ❌ | Add |
| Locations + Maps | ✅ | ❌ | Add (if data) |
| Related Articles | ✅ Image cards | ❌ | Add |
| Empty State | ✅ Illustrated | ❌ | Add |
| FAQ (DB) | ✅ Alpine.js | ❌ | Add |
| CTA Section | ✅ Gradient | ❌ | Add |
| JSON-LD Schema | ✅ | ❌ | Add |

---

## 6. REGIONAL DATA (master_wilayah_bps.json)

### TrainingKota
- Uses `master_wilayah_bps.json` (same file)
- Has `City`, `Kecamatan`, `Location` models
- `City` has: `slug`, `name`, `province`, `island`, `classification`, `lat`, `lng`, `address`, `mapsEmbedUrl()`

### Varilevke
- Same JSON file imported
- Models: `City`, `Kecamatan` (similar fields)
- Need to verify: all provinces imported, not just East Java

**Action**: Verify import covers all provinces in JSON. Fix queries if filtering to East Java only.

---

## 7. CATALOG DATA INTEGRITY

| Metric | Source | TrainingKota | Varilevke |
|--------|--------|--------------|-----------|
| Pelatihan | 227 | Service model | CatalogItem (227) |
| Jasa | 45 | Service model | CatalogItem (45) |
| Total | 272 | 62 (TK has different count) | 272 |

**Note**: TrainingKota has different catalog (62 services vs 272). Varilevke must preserve ALL 272 items with exact names, categories, source_order.

---

## 8. WHATSAPP NUMBERS (Preserve Exact)
```
08118500177
081399810272
087894580806
087894580649
```

---

## 9. ROUTE MAPPING

| TrainingKota Route | Varilevke Route | Notes |
|--------------------|-----------------|-------|
| `/` | `/` | Home |
| `/{category}` (pelatihan/kajian/jasa) | `/{category}` (pelatihan/jasa) | TK has 3 categories |
| `/{category}/{serviceSlug}` | `/{category}/{item:slug}` | Detail |
| `/{category}/kota-{citySlug}` | `/wilayah/{city:slug}` | City landing |
| `/{category}/kecamatan-{kecamatanSlug}` | `/wilayah/{city:slug}/{kecamatan:slug}` | Kec landing |
| `/{category}/{serviceSlug}/kota-{citySlug}` | ❌ | City-service landing (optional) |
| `/blog` | `/artikel` | Articles |
| `/artikel/{slug}` | `/artikel/{article:slug}` | Article show |

**Action**: Keep Varilevke routes, adapt views to TK design.

---

## 10. IMPLEMENTATION PRIORITY

### Phase 1: Design Tokens & Base Layout
1. Add `--varilevke-radius: 18px` to CSS
2. Update `app.css` components with radius
3. Replace `app-layout.blade.php` with TK `layouts/app.blade.php` structure
4. Add regulatory top bar, update footer

### Phase 2: Catalog (Critical - "plain numbered list" → cards)
1. Create `components/catalog-card.blade.php` (from TK category.blade.php card)
2. Update `catalog/index.blade.php` to use card grid
3. Add search/filter at top (TK style)
4. Add category header with pills
5. Add "Show All" toggle
6. Add city selector section
7. Add FAQ + Q&A accordions

### Phase 3: Service Detail
1. Create `components/service-visual.blade.php` (from TK)
2. Update `catalog/show.blade.php` to TK structure
3. Add archetype-based visual sections
4. Add sidebar WA form (TK style)
5. Add related services/articles

### Phase 4: Regional Pages (City & Kecamatan)
1. Update `regional/city.blade.php` → TK `city-landing.blade.php` structure
2. Update `regional/kecamatan.blade.php` → TK `kecamatan-landing.blade.php` structure
3. Add Google Maps (if coordinates available)
4. Add service grids with "Show All"
5. Add FAQ accordions
6. Add related articles as image cards
7. Add JSON-LD structured data

### Phase 5: Homepage
1. Replace with TK `home.blade.php` structure
2. Adapt 3 pillars to Varilevke catalog (pelatihan/jasa)
3. Add city widget (all provinces)
4. Add advantage/about sections

### Phase 6: Verification & Polish
1. Build assets (`npm run build`)
2. Run tests
3. Verify all pages render correctly
4. Create `docs/REGIONAL_CATALOG_REPORT.md`
5. Update `docs/IMPLEMENTATION_STATUS.md`